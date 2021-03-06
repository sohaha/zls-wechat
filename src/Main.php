<?php

namespace Zls\WeChat;

use Cassandra\Function_;
use Z;

/**
 * WeChat.
 * @author        影浅 seekwe@gmail.com
 * @updatetime    2018-1-11 17:44:38
 * @method Qr   getQr()   二维码
 * @method Qy   getQy()   企业号
 * @method Pay  getPay(?array $initConfig)  支付
 * @method Basi getBasi() 公众号
 * @method App  getApp()  小程序
 */
class Main
{
    const APIURL = 'https://api.weixin.qq.com';
    const OPENURL = 'https://open.weixin.qq.com';
    const FILEURL = 'https://file.api.weixin.qq.com';
    const QYAPIURL = 'https://qyapi.weixin.qq.com';
    const QYOPENURL = 'https://open.weixin.qq.com';
    const OUTTIME_JSAPI_TICKET = '_jsapi_ticket_outTime';
    const OUTTIME_ACCESS_TOKEN = '_access_token_outTime';

    public static $errCode;
    private $debug = true;
    private $errorCode = -1;
    private $errorMsg = '参数错误';
    private $encodingAesKey;
    private $appid;
    private $componentAppid;
    private $componentAppsecret;
    private $componentAccessToken;
    private $componentAuthorizerAccessToken;
    private $uniqueKey;
    private $appsecret;
    private $token;
    private $_receive;
    private $_encrypt;
    private $_signature;
    private $_timestamp;
    private $_nonce;
    private $_msg_signature;
    private $_receiveXml;
    private $accessToken;
    private $jsapiTicket;
    private $openid;
    private $userid;
    private $authScope = 'snsapi_userinfo'; //snsapi_base|snsapi_userinfo 全权限授权
    private $authState = 'zls_wechat';
    private $authAccessToken;
    private $refreshComponentCallback;
    private $agentid;
    private $reAccessToken;
    private $reply = true;
    private $triggerResetToken;
    private $componentRefreshTokenFn;

    public function __construct()
    {
        if (Z::config()->find('wechat')) {
            $this->init(Z::config('wechat'));
        }
        if (!self::$errCode) {
            /** @var Util $util */
            $util          = $this->getUtil();
            self::$errCode = $this->getUtil()->errCode;
        }
    }

    /**
     * @param array $data [appid,appsecret,encoding_aes_key,component_appid,component_appsecret]
     */
    public function init(array $data = [])
    {
        if (!$data) {
            $data = Z::config('wechat');
        }
        $this->appid              = Z::arrayGet($data, 'appid') ?: Z::arrayGet($data, 'corpid');
        $this->appsecret          = Z::arrayGet($data, 'appsecret');
        $this->token              = Z::arrayGet($data, 'token');
        $this->encodingAesKey     = Z::arrayGet($data, 'encodingAesKey');
        $this->componentAppid     = Z::arrayGet($data, 'componentAppid');
        $this->componentAppsecret = Z::arrayGet($data, 'componentAppsecret');
        $this->debug              = Z::arrayGet($data, 'debug');
        $this->agentid            = Z::arrayGet($data, 'agentid');
        $this->reAccessToken      = Z::arrayGet($data, 'reAccessToken', 1);
        $this->triggerResetToken  = Z::arrayGet($data, 'triggerResetToken', 100);
        if ($this->triggerResetToken < 0) {
            $this->triggerResetToken = 0;
        }
        $this->setUniqueKey();

        return $this;
    }

    /**
     * WeChatUtil.
     * @return Util
     */
    public function getUtil()
    {
        /** @var Util $class */
        $class = Z::extension('WeChat_Util');

        return $class;
    }

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * 清除赋值/模拟销毁
     */
    public function clear()
    {
        $this->_receive                       = null;
        $this->_receiveXml                    = null;
        $this->componentAccessToken           = null;
        $this->accessToken                    = null;
        $this->componentAuthorizerAccessToken = null;
        $this->openid                         = null;
    }

    public function __call($name, $value)
    {
        $className = 'Zls_WeChat_' . str_replace('get', '', $name);
        $class     = Z::factory($className, true, null, [$this]);
        if (method_exists($class, 'init')) {
            $cfg = Z::arrayGet($value, 0, Z::config('wechat'));
            $class->init($cfg);
        }

        return $class;
    }

    /**
     * 设置是否开启记录日志.
     *
     * @param bool $debug
     *
     * @return Main
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * 开放平台授权
     *
     * @param string $redirectUri 回调地址 默认当前页面
     *
     * @return array|string
     */
    public function runComponentAuth($redirectUri = '')
    {
        if (!$redirectUri) {
            $redirectUri = Z::host(true, true, true);
        }
        if (!$this->getComponentTicket()) {
            $this->setError($this->errorCode, '微信还没下发Ticket，请稍后再试');

            return false;
        }
        if ($auth_code = Z::get('auth_code')) {
            return Z::tap($this->getComponentApiQueryAuth($auth_code), function ($authInfo) use ($redirectUri) {
                if ($this->errorCode > 0) {
                    if (61010 === (int)$this->errorCode) {
                        $get = Z::get();
                        unset($get['auth_code']);
                        Z::setGlobalData(ZLS_PREFIX . 'get', $get);
                        $this->runComponentAuth($redirectUri);
                    }

                    return;
                }
                $appid        = $authInfo['authorization_info']['authorizer_appid'];
                $accessToken  = $authInfo['authorization_info']['authorizer_access_token'];
                $refreshToken = $authInfo['authorization_info']['authorizer_refresh_token'];
                $expiresIn    = $authInfo['authorization_info']['expires_in'] - 1800;
                $this->setComponentAuthorizerAccessToken($accessToken, $appid, $expiresIn);
                //刷新密钥缓存一个礼拜
                $this->log('刷新密钥缓存一个礼拜', $appid, $refreshToken);
                $this->setComponentRefreshToken($refreshToken, $appid, 602000);
            });
        } else {
            $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=' . $this->getComponentAppid() . '&pre_auth_code=' . $this->getComponentPreAuthCode() . '&redirect_uri=' . $redirectUri;
            Z::redirect($url);

            return false;
        }
    }

    /**
     * 获取开放平台接口调用凭据和授权信息.
     *
     * @param $authorization_code
     *
     * @return array|bool
     */
    public function getComponentApiQueryAuth($authorization_code)
    {
        $data = [
            'component_appid'    => $this->getComponentAppid(),
            'authorization_code' => $authorization_code,
        ];
        if ($result = $this->post(
            self::APIURL . '/cgi-bin/component/api_query_auth?component_access_token=' . $this->getComponentAccessToken(),
            $data
        )
        ) {
            $this->log('获取开放平台接口调用凭据和授权信息成功', $result);
        } else {
            $this->errorLog('获取开放平台接口调用凭据和授权信息失败', $this->getError(), $data);
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getComponentAppid()
    {
        return $this->componentAppid;
    }

    /**
     * @param mixed $componentAppid
     */
    public function setComponentAppid($componentAppid)
    {
        $this->componentAppid = $componentAppid;
    }

    /**
     * POST请求
     *
     * @param      $url
     * @param null $data
     * @param bool $atUpload
     *
     * @return bool|array
     */
    public function post($url, $data = null, $atUpload = false)
    {
        return $this->request($url, $data, 'post', 'json', 'json', $atUpload);
    }

    public function request($url, $data = null, $type = 'get', $dataType = 'json', $responseType = 'json', $atUpload = false)
    {
        if ('json' === $dataType && is_array($data)) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $result = ('get' == $type) ? $this->getHttp()->get($url, $data) : $this->getHttp()->post($url, $data, null, 0, $atUpload);
        $this->log('接口请求', $url, $data, $result);
        if ('json' === $responseType) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $result  = @json_decode($result, true);
            $errcode = Z::arrayGet($result, 'errcode', '');
            if (!$result || !empty($errcode)) {
                return $this->setError($errcode, $this->getErrText($result));
            }
        }

        return $result;
    }

    /**
     * @return object \Zls\Action\Http
     */
    public function getHttp()
    {
        return Z::extension('Action_Http');
    }

    /**
     * 打印日志.
     *
     * @param $_
     *
     * @return Main
     */
    public function log($_)
    {
        if ($this->debug) {
            $this->output();
        }

        return $this;
    }

    protected function output()
    {
        $trace = Z::arrayGet(debug_backtrace(), 1);
        $arg   = $trace['args'];
        $data  = [
            'file' => Z::safePath($trace['file']),
            'line' => $trace['line'],
        ];
        if (is_array($arg)) {
            foreach ($arg as $key => $value) {
                $data['log[' . $key . ']'] = $value;
            }
        } else {
            $data['log'] = $arg;
        }
        Z::log($data, 'wx');

        return $data;
    }

    public function setError($errorCode, $errorMsg, $force = false)
    {
        $result = false;
        switch ($errorCode) {
            case 42001: //令牌过期
                break;
            case 40001: //accessToken无效
                //case 40125:
                $this->errorLog('缓存没过期，但是accessToken失效了:' . $this->reAccessToken);
                if ((bool)$this->reAccessToken) {
                    $this->reAccessToken = ((int)$this->reAccessToken) - 1;
                    $this->accessToken   = '';
                    $this->getAccessToken(false);
                    //$this->getError(true);
                    //$result = $this->reRequest();
                }
                break;
        }
        if ($this->errorCode <= 0 || true === $force) {
            $this->errorCode = $errorCode;
            $this->errorMsg  = $errorMsg;
        }

        return $result;
    }

    /**
     * @param $_
     *
     * @return Main
     */
    public function errorLog($_)
    {
        $this->output();

        return $this;
    }

    /**
     * 获取AccessToken.
     *
     * @param bool $cache
     *
     * @return bool|string
     */
    public function getAccessToken($cache = true)
    {
        if (!$this->getComponentAppid()) {
            if (!$this->accessToken) {
                $agentid          = $this->getAgentid() ?: '';
                $cacheKey         = $this->getUniqueKey() . $agentid . '_access_token';
                $accessToken      = Z::cache()->get($cacheKey);
                $detectionOuttime = function ($accessToken) {
                    if ($accessToken) {
                        $rand = mt_rand(0, $this->triggerResetToken);
                        if ($rand === 0) {
                            $timeout = $this->getAccessTokenOutTime();
                            // 提前5分钟判断 token 是否将要过期
                            if ($timeout <= (time() + 300)) {
                                return false;
                            }
                        }
                    }

                    return $accessToken;
                };
                if (true != $cache || !$detectionOuttime($accessToken)) {
                    $res = $this->instance()->getAccessToken();
                    if (!$res) {
                        return false;
                    }
                    $accessToken = $res['access_token'];
                    $expire      = Z::arrayGet($res, 'expires_in', 2000);
                    $expire      = intval($expire) > 1800 ? $expire - 1800 : 3600;
                    Z::cache()->set($cacheKey, $accessToken, $expire);
                    //Z::cache()->set($cacheKey . '_outTime', time() + $expire, $expire + 200);
                    $this->outTime(self::OUTTIME_ACCESS_TOKEN, $expire);
                }
                $this->setAccessToken($accessToken);
            }
        } else {
            //存在开放平台APPID则强行把AccessToken返回ComponentAuthorizerAccessToken
            return $this->getComponentAuthorizerAccessToken();
        }

        return $this->accessToken;
    }

    /**
     * 设置AccessToken.
     *
     * @param string $access_token
     *
     * @return Main
     */
    public function setAccessToken($access_token)
    {
        $this->accessToken = $access_token;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAgentid()
    {
        return $this->agentid;
    }

    /**
     * @param mixed $agentid
     */
    public function setAgentid($agentid)
    {
        $this->accessToken = '';
        $this->agentid     = $agentid;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getUniqueKey($key = '')
    {
        return $this->uniqueKey . $key;
    }

    /**
     * 设置唯一Key.
     *
     * @param mixed $uniqueKey
     *
     * @return Main
     */
    public function setUniqueKey($uniqueKey = null)
    {
        if (!$uniqueKey) {
            $uniqueKey = 'Zls_WeChat_' . ($this->getComponentAppid() ? $this->getComponentAppid() . md5($this->getComponentAppsecret()) : $this->getAppid() . md5($this->getAppsecret()));
        }
        $this->uniqueKey = $uniqueKey;

        return $this;
    }

    /**
     * @return Basi|Qy
     */
    private function instance()
    {
        if ($this->getAgentid()) {
            $instance = $this->getQy();
        } else {
            $instance = $this->getBasi();
        }

        return $instance;
    }

    /**
     * @param      $type
     * @param null $expiresIn
     *
     * @return int|null
     */
    public function outTime($type, $expiresIn = null)
    {
        $cacheKey = 'Zls_WeChat_' . ($this->getComponentAppid() ? $this->getComponentAppid() : $this->getAppid()) . $type;
        if (is_null($expiresIn)) {
            $expiresIn = Z::cache()->get($cacheKey) ?: 0;
        } else {
            $expiresIn = time() + $expiresIn;
            Z::cache()->set($cacheKey, $expiresIn);
        }

        return $expiresIn;
    }

    /**
     * @return mixed
     */
    public function getAppid()
    {
        return $this->appid;
    }

    /**
     * 设置Appid.
     *
     * @param mixed $appid
     *
     * @return Main
     */
    public function setAppid($appid)
    {
        $this->appid = $appid;
        if (!$this->getComponentAppid()) {
            $this->getCrypt()->setAppId($appid);
        }
        $this->componentAuthorizerAccessToken = null;
        $this->accessToken                    = null;

        return $this;
    }

    /**
     * 获取开放平台AuthorizerAccessToken.
     *
     * @param string $appid
     *
     * @return mixed
     */
    public function getComponentAuthorizerAccessToken($appid = '')
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }
        if (!$this->componentAuthorizerAccessToken && (!$this->componentAuthorizerAccessToken = Z::cache()->get($this->getUniqueKey('_ComponentAuthorizerAccessToken_' . $appid)))) {
            $this->refreshComponentAuthorizerAccessToken();
        }

        return $this->componentAuthorizerAccessToken;
    }

    /**
     * 设置开放平台授权公众号AccessToken.
     *
     * @param mixed  $componentAuthorizerAccessToken
     * @param string $appid
     * @param int    $expiresIn
     */
    public function setComponentAuthorizerAccessToken($componentAuthorizerAccessToken, $appid = '', $expiresIn = 3600)
    {
        if (!$appid) {
            $appid = $this->getAppid();
        } else {
            $this->setAppid($appid);
        }
        if ($componentAuthorizerAccessToken) {
            Z::cache()->set(
                $this->getUniqueKey('_ComponentAuthorizerAccessToken_' . $appid),
                $componentAuthorizerAccessToken,
                $expiresIn
            );
        }
        $this->componentAuthorizerAccessToken = $componentAuthorizerAccessToken;
    }

    /**
     * 刷新AuthorizerAccessToken.
     *
     * @param string $appid                  appid
     * @param string $authorizerRefreshToken 刷新令牌
     *
     * @return mixed
     */
    public function refreshComponentAuthorizerAccessToken($appid = '', $authorizerRefreshToken = null)
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }
        if (!$authorizerRefreshToken && !($authorizerRefreshToken = $this->getComponentRefreshToken($appid))) {
            $this->errorLog('ComponentRefreshToken不存在');

            return false;
        }
        $data = [
            'component_appid'          => $this->getComponentAppid(),
            'authorizer_appid'         => $appid,
            'authorizer_refresh_token' => $authorizerRefreshToken,
        ];

        return Z::tap($this->post(
            self::APIURL . '/cgi-bin/component/api_authorizer_token?component_access_token=' . $this->getComponentAccessToken(),
            $data
        ), function (&$resule) use ($appid, $data) {
            if ($resule) {
                $this->getCallbackRefreshComponent($appid, $resule);
                $expiresIn = $resule['expires_in'] - 1800;
                $this->outTime(self::OUTTIME_ACCESS_TOKEN, $expiresIn);
                $this->setComponentAuthorizerAccessToken($resule['authorizer_access_token'], $appid, $expiresIn);
                $this->setComponentRefreshToken($resule['authorizer_refresh_token'], $appid, 602000);
            } else {
                $this->errorLog('刷新AuthorizerAccessToken失败', $data, $this->getComponentAccessToken(), $this->getError());
                $resule = false;
            }
        });
    }

    /**
     * 获取刷新令牌.
     *
     * @param string $appid
     *
     * @return mixed
     */
    public function getComponentRefreshToken($appid = '')
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }
        $token = Z::cache()->get($this->getUniqueKey('_ComponentRefreshToken_' . $appid));
        if (!$token) {
            $token = $this->getComponentRefreshTokenFn($appid);
        }

        return $token;
    }

    public function getComponentRefreshTokenFn($appid)
    {
        if (is_callable($this->componentRefreshTokenFn)) {
            return call_user_func($this->componentRefreshTokenFn, $appid);
        }

        return '';
    }

    public function setComponentRefreshTokenFn(callable $fn)
    {
        $this->componentRefreshTokenFn = $fn;
    }

    /**
     * 获取开放平台AccessToken.
     *
     * @param bool $cache
     *
     * @return mixed
     */
    public function getComponentAccessToken($cache = true)
    {
        $cacheKey                   = $this->getUniqueKey('_ComponentAccessToken');
        $this->componentAccessToken = $this->componentAccessToken ?: Z::cache()->get($cacheKey);
        if (false === $cache || !$this->componentAccessToken) {
            $ticket = $this->getComponentTicket();
            if ($ticket) {
                $data = [
                    'component_appid'         => $this->getComponentAppid(),
                    'component_appsecret'     => $this->getComponentAppsecret(),
                    'component_verify_ticket' => $this->getComponentTicket(),
                ];
                if ($result = $this->post(self::APIURL . '/cgi-bin/component/api_component_token', $data)) {
                    $this->log('获取开放平台AccessToken成功', $result);
                    $this->componentAccessToken = $result['component_access_token'];
                    $expiresIn                  = $result['expires_in'] - 1800;
                    Z::cache()->set($cacheKey, $this->componentAccessToken, $expiresIn);
                    Z::cache()->set($cacheKey . '_expiresIn', time() + $expiresIn, $expiresIn);
                } else {
                    $this->errorLog('获取开放平台AccessToken失败', $this->getError(), $data);
                }
                $this->log(
                    '获取开放平台AccessToken:',
                    $this->componentAccessToken,
                    '过期时间' . date('Y-m-d H:i:s', Z::cache()->get($cacheKey . '_expiresIn'))
                );
            } else {
                $this->errorLog('Ticket不存在');
            }
        }

        return $this->componentAccessToken;
    }

    /**
     * 设置开放平台AccessToken.
     *
     * @param mixed $componentAccessToken
     */
    public function setComponentAccessToken($componentAccessToken)
    {
        if ($componentAccessToken) {
            Z::cache()->set($this->getUniqueKey('_ComponentAccessToken'), $componentAccessToken, 3600);
        }
        $this->componentAccessToken = $componentAccessToken;
    }

    /**
     * 获取component_verify_ticket.
     * @return mixed
     */
    public function getComponentTicket()
    {
        return Z::cache()->get($this->getUniqueKey('_ComponentTicket'));
    }

    /**
     * @return mixed
     */
    public function getComponentAppsecret()
    {
        return $this->componentAppsecret;
    }

    /**
     * @param mixed $componentAppsecret
     */
    public function setComponentAppsecret($componentAppsecret)
    {
        $this->componentAppsecret = $componentAppsecret;
    }

    public function getError($clean = false)
    {
        $error = ['code' => $this->errorCode, 'msg' => $this->errorMsg];
        if (true === $clean) {
            $this->setError(-1, '', true);
        }

        return $error;
    }

    public function getCallbackRefreshComponent($appid, $resule)
    {
        $closure = $this->refreshComponentCallback;
        if ($closure instanceof \Closure) {
            $closure($appid, $resule);
        }
    }

    /**
     * 设置刷新令牌.
     *
     * @param mixed  $componentRefreshToken
     * @param string $appid     指定公众号
     * @param int    $expiresIn 过期时间
     */
    public function setComponentRefreshToken($componentRefreshToken, $appid = '', $expiresIn = 3600)
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }
        if ($componentRefreshToken) {
            Z::cache()->set(
                $this->getUniqueKey('_ComponentRefreshToken_' . $appid),
                $componentRefreshToken,
                $expiresIn
            );
        }
    }

    private function getErrText($err)
    {
        $code = Z::arrayGet($err, 'errcode', -1);
        if (isset(self::$errCode[$code])) {
            return self::$errCode[$code];
        } else {
            return $code . ':' . $err['errmsg'];
        }
    }

    /**
     * 获取开放平台预授权码
     */
    public function getComponentPreAuthCode()
    {
        $data        = ['component_appid' => $this->getComponentAppid()];
        $PreAuthCode = '';
        $result      = $this->post(
            self::APIURL . '/cgi-bin/component/api_create_preauthcode?component_access_token=' . $this->getComponentAccessToken(),
            $data
        );
        if ($result) {
            $this->log('获取开放平台预授权码成功', $result);
            $PreAuthCode = $result['pre_auth_code'];
        } else {
            $this->errorLog('获取开放平台预授权码失败', $this->getError(), $data, $this->getComponentAccessToken());
        }

        return $PreAuthCode;
    }

    /**
     * @return Crypt
     */
    public function getCrypt()
    {
        /** @var Crypt $class */
        $class = Z::tap(
            Z::extension('WeChat_Crypt'),
            function ($class) {
                /*  @var Crypt $class */
                if ($this->getComponentAppid()) {
                    $class->msgCrypt($this->getComponentAppid(), $this->getEncodingAesKey(), $this->getToken());
                } else {
                    $class->msgCrypt($this->getAppid(), $this->getEncodingAesKey(), $this->getToken());
                }
            }
        );

        return $class;
    }

    /**
     * @return mixed
     */
    public function getEncodingAesKey()
    {
        return $this->encodingAesKey;
    }

    /**
     * @param mixed $encodingAesKey
     *
     * @return Main
     */
    public function setEncodingAesKey($encodingAesKey)
    {
        $this->encodingAesKey = $encodingAesKey;
        $this->getCrypt()->setEncodingAesKey($encodingAesKey);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * 设置token.
     *
     * @param mixed $token
     *
     * @return Main
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->getCrypt()->setToken($token);

        return $this;
    }

    public function reRequest()
    {
        $result    = false;
        $backtrace = debug_backtrace();
        foreach ($backtrace as $k) {
            if (__CLASS__ === Z::arrayGet($k, 'class') && 'request' === Z::arrayGet($k, 'function')) {
                $args = $k['args'];
                $url  = Z::arrayGet($args, 0);
                $urls = parse_url($url);
                if ($query = Z::arrayGet($urls, 'query', '')) {
                    parse_str($query, $query);
                    $argsKey = ['appid' => 'getAppid', 'secret' => 'getAppsecret', 'access_token' => 'getAccessToken'];
                    foreach (array_keys($argsKey) as $v) {
                        if (isset($query[$v])) {
                            $getMethod = $argsKey[$v];
                            $query[$v] = $this->$getMethod();
                        }
                    }
                    $query = '?' . http_build_query($query);
                }
                $port     = Z::arrayGet($urls, 'port');
                $host     = Z::arrayGet($urls, 'host') . ($port ? ':' . $port : '');
                $fragment = Z::arrayGet($urls, 'fragment');
                $url      = Z::arrayGet($urls, 'scheme') . '://' . $host . Z::arrayGet($urls, 'path') . $query . ($fragment ? '#' . $fragment : '');
                $args[0]  = $url;
                $result   = call_user_func_array([$this, 'request'], $args);
                break;
            }
        }

        return $result;
    }

    /**
     * 请求上传文件.
     *
     * @param      $url
     * @param null $data
     * @param null $filepath 留空表示
     *
     * @return bool|array
     */
    public function upload($url, $data = null, $filepath = null)
    {
        $files = Z::filesGet();
        if ($filepath === null && !empty($files)) {
            $key = Z::arrayGet(array_keys($files), 0);
            if ($file = Z::arrayGet($files, $key)) {
                $tempPath = Z::tempPath();
                $filename = $tempPath . '/' . $file['name'];
                move_uploaded_file($file['tmp_name'], $filename);
                $data[$key] = '@' . $filename;
            }
        } elseif ($filepath !== null) {
            $data['media'] = '@' . $filepath;
        }

        return $this->request($url, $data, 'post', null, 'json', true);
    }

    /**
     * @param     $key
     * @param     $data
     * @param int $time
     *
     * @return mixed
     */
    public function cache($key, $data, $time = 30)
    {
        $key = $this->getUniqueKey() . $key;
        if (!$cacheData = Z::cache()->get($key)) {
            $cacheData = $data();
            Z::cache()->set($key, $cacheData, $time);
        }

        return $cacheData;
    }

    /**
     * 微信js签名.
     *
     * @param string $url
     * @param int    $timestamp
     * @param string $noncestr
     *
     * @return array|bool
     */
    public function getJsSign($url = '', $timestamp = 0, $noncestr = '')
    {
        if (!$this->getJsapiTicket()) {
            return false;
        }
        (!$url) && $url = Z::host(true, true, true);
        (!$timestamp) && $timestamp = time();
        (!$noncestr) && $noncestr = $this->generateNonceStr();
        $ret = strpos($url, '#');
        if ($ret) {
            $url = substr($url, 0, $ret);
        }
        $url = trim($url);
        if (empty($url)) {
            return false;
        }
        $arrdata  = ['timestamp' => $timestamp, 'noncestr' => $noncestr, 'url' => $url, 'jsapi_ticket' => $this->getJsapiTicket(),];
        $cacheKey = $this->getUniqueKey() . '_js_sign_' . md5($url . $this->getJsapiTicket());
        if (!$signPackage = Z::cache()->get($cacheKey)) {
            if (!function_exists('sha1')) {
                return false;
            }
            ksort($arrdata);
            $paramstring = '';
            foreach ($arrdata as $key => $value) {
                if (0 == strlen($paramstring)) {
                    $paramstring .= $key . '=' . $value;
                } else {
                    $paramstring .= '&' . $key . '=' . $value;
                }
            }
            $Sign        = sha1($paramstring);
            $signPackage = ['appid' => $this->getAppid(), 'nonceStr' => $noncestr, 'timestamp' => $timestamp, 'url' => $url, 'signature' => $Sign,];
            Z::cache()->set($cacheKey, $signPackage, 3600);
        }

        return $signPackage;
    }

    /**
     * 获取Ticket.
     * @return bool|string
     */
    public function getJsapiTicket()
    {
        if (!$jsapiTicket = Z::arrayGet($this->jsapiTicket, $this->getAppid())) {
            $cacheKey = $this->getUniqueKey() . '_jsapi_ticket' . $this->getAppid();
            if (!$jsapiTicket = Z::cache()->get($cacheKey)) {
                $res = $this->instance()->getJsapiTicket();
                if (!$res) {
                    return false;
                }
                $jsapiTicket = $res['ticket'];
                $expire      = $res['expires_in'] ? intval($res['expires_in']) - 1800 : 3600;
                $this->outTime(self::OUTTIME_JSAPI_TICKET, $expire);
                Z::cache()->set($cacheKey, $jsapiTicket, $expire);
            }
            $this->setJsapiTicket($jsapiTicket);
        }

        return $jsapiTicket;
    }

    /**
     * 设置Ticket.
     *
     * @param mixed $jsapiTicket
     *
     * @return Main
     */
    public function setJsapiTicket($jsapiTicket)
    {
        $this->jsapiTicket[$this->getAppid()] = $jsapiTicket;

        return $this;
    }

    /**
     * 生成随机字符串.
     *
     * @param int $length
     *
     * @return string
     */
    public function generateNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str   = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }

    /**
     * 设置刷新AuthorizerAccessToken回调.
     *
     * @param \Closure $closure
     */
    public function setRefreshComponentCallback(\Closure $closure)
    {
        $this->refreshComponentCallback = $closure;
    }

    /**
     * 获取AccessToken过期时间戳.
     */
    public function getAccessTokenOutTime()
    {
        return Z::cache()->get($this->getUniqueKey() . '_access_token_outTime');
    }

    /**
     * 获取用户详情.
     *
     * @param string $id openid|userid
     *
     * @return mixed
     */
    public function getUserInfo($id)
    {
        return $this->instance()->getUserInfo($id);
    }

    /**
     * 获取授权用户OPENID.
     *
     * @param string $type
     *
     * @return array
     */
    public function getAuthUserOpenid($type = 'openid')
    {
        if (in_array($type, ['openid', 'userid'], true)) {
            if (!$this->$type) {
                $this->getAuthAccessToken();
            }
            $result = $this->$type;
        } else {
            if (!$this->openid) {
                $this->getAuthAccessToken();
            }
            $result = ['openid' => $this->openid, 'userid' => $this->userid];
        }

        return $result;
    }

    /**
     * @return array|bool
     */
    public function getAuthAccessToken()
    {
        if (!$this->authAccessToken) {
            $authCode = $this->authCode(null, '');
            $result   = $this->instance()->getAuthAccessToken($authCode);
            if (!$result) {
                if ('41008' == $this->errorCode || '40029' == $this->errorCode || '40163' == $this->errorCode) {
                    $this->authCode(null, $authCode);
                }

                return false;
            }
            $this->authAccessToken = $result;
            $this->openid          = Z::arrayGet($result, 'openid', Z::arrayGet($result, 'OpenId'));
            $this->userid          = Z::arrayGet($result, 'UserId', Z::arrayGet($result, 'userid'));
        }

        return $this->authAccessToken;
    }

    /**
     * 获取授权 oauth_code.
     *
     * @param string $url
     * @param string $oldCode
     *
     * @return string|mixed
     */
    public function authCode($url = null, $oldCode = '')
    {
        if (($code = Z::get('code')) && ($oldCode !== $code)) {
            return $code;
        }
        if (!$url) {
            $url = Z::host(true, true, true);
        }
        $urls = parse_url($url);
        if ($query = Z::arrayGet($urls, 'query', '')) {
            parse_str($query, $query);
            unset($query['code'], $query['state'], $query['scope']);
            $query = '?' . http_build_query($query);
        }
        $port     = Z::arrayGet($urls, 'port');
        $host     = Z::arrayGet($urls, 'host') . ($port ? ':' . $port : '');
        $fragment = Z::arrayGet($urls, 'fragment');
        $url      = Z::arrayGet($urls, 'scheme') . '://' . $host . Z::arrayGet($urls, 'path') . $query . ($fragment ? '#' . $fragment : '');
        Z::redirect($this->getOauthRedirect($url, $this->getAuthState(), $this->getAuthScope()));

        return false;
    }

    /**
     * 获取授权URl.
     *
     * @param        $callback
     * @param string $state
     * @param string $scope
     *
     * @return string
     */
    public function getOauthRedirect($callback = null, $state = '', $scope = 'snsapi_userinfo')
    {
        (!$callback) && $callback = Z::host(true, true, true);

        return $this->instance()->getOauthRedirect($callback, $state, $scope);
    }

    /**
     * @return string
     */
    public function getAuthState()
    {
        return $this->authState;
    }

    /**
     * @param string $authState
     *
     * @return Main
     */
    public function setAuthState($authState)
    {
        $this->authState = $authState;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuthScope()
    {
        return $this->authScope;
    }

    /**
     * 设置页面授权方式.
     *
     * @param string $authScope snsapi_base|snsapi_userinfo|snsapi_privateinfo
     *
     * @return Main
     */
    public function setAuthScope($authScope)
    {
        $this->authScope = $authScope;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAppsecret()
    {
        return $this->appsecret;
    }

    /**
     * @param mixed $appsecret
     *
     * @return Main
     */
    public function setAppsecret($appsecret)
    {
        $this->appsecret = $appsecret;

        return $this;
    }

    /**
     * 获取授权用户详情.
     *
     * @param null $openid
     *
     * @return bool|mixed|string
     */
    public function getAuthUserInfo($openid = null)
    {
        return $this->instance()->getAuthUserInfo($openid);
    }

    /**
     * 下载多媒体.
     *
     * @param string $media_id 媒体ID
     * @param string $saveFile 文件保存路径
     *
     * @return string
     */
    public function mediaDownload($media_id = '', $saveFile)
    {
        $this->get(self::FILEURL . '/cgi-bin/media/get?access_token=' . $this->getAccessToken() . '&media_id=' . $media_id);
        if (preg_match('/filename="(.*)"/i', $this->getHttp()->header(), $filename)) {
            $filepath = $saveFile . '/' . $filename[1];
            file_put_contents($filepath, $this->getHttp()->data());

            return Z::safePath($filepath, '');
        }
        $result    = @json_decode($this->getHttp()->data(), true);
        $errorCode = Z::arrayGet($result, 'errcode', 404);
        $errorMsg  = Z::arrayGet($result, 'errmsg', '文件名获取失败');
        $this->setError($errorCode, $errorMsg);
        $this->errorLog('多媒体下载失败', $result);

        return false;
    }

    public function get($url, $data = null)
    {
        return $this->request($url, $data, 'get');
    }

    public function valid()
    {
        if ($echoStr = Z::get('echostr')) {
            $signature     = Z::get('signature', '');
            $timestamp     = Z::get('timestamp', '');
            $nonce         = Z::get('nonce', '');
            $msg_signature = Z::get('msg_signature', '');
            $this->errorLog(Z::get(), $msg_signature);
            if ($msg_signature) {
                $error = $this->getCrypt()->verifyUrl($this->getToken(), $timestamp, $nonce, $echoStr, $msg_signature, $echoStr);
            } else {
                $error = $this->getUtil()->checkSignature($this->getToken(), $signature, $timestamp, $nonce);
            }
            if (0 == $error) {
                Z::end($echoStr);
            } else {
                $this->errorLog('解密valid失败' . $error);
                Z::end('error');
            }
        }
    }

    /**
     * @param $type
     * @param $fn
     *
     * @return $this
     */
    public function on($type, \Closure $fn)
    {
        if (!is_array($type)) {
            $type = [$type];
        }
        foreach ($type as $item) {
            Z::di()->bind($this->getUniqueKey() . '_event_' . $item, $fn);
        }

        return $this;
    }

    public function run()
    {
        [$getRev, $getRevXml] = $this->getRev();
        if ($getRev) {
            //是否回复
            if (!$this->isReply()) {
                $this->push();
            }
            $this->log('收到消息', $getRev, $getRevXml);
            $type     = $getRev['MsgType'];
            $typeAll  = $this->getUniqueKey() . '_event_all';
            $taskName = $this->getUniqueKey() . '_event_' . $type;
            if (Z::di()->has($taskName)) {
                Z::di()->makeShared($taskName, [$this, $getRev, $type, $getRevXml]);
            } elseif (Z::di()->has($typeAll)) {
                Z::di()->makeShared($typeAll, [$this, $getRev, $type, $getRevXml]);
            }
        } else {
            $this->errorLog('消息获取失败');
            Z::end();
        }
    }

    private function getRev()
    {
        if (empty($this->_receive)) {
            $postStr = Z::postRaw();
            if (!empty($postStr)) {
                $this->_receive = (is_array($postStr)) ?
                    $postStr : $this->FromXml($postStr);
                if (isset($this->_receive['Encrypt']) && (!isset($this->_receive['MsgType']))) {
                    $this->_encrypt       = true;
                    $msg                  = '';
                    $this->_signature     = Z::get('signature');
                    $this->_timestamp     = Z::get('timestamp');
                    $this->_nonce         = Z::get('nonce');
                    $this->_msg_signature = Z::get('msg_signature');
                    $errCode              = $this->getCrypt()->decryptMsg($this->_msg_signature, $this->_timestamp, $this->_nonce, $postStr, $msg);
                    if (0 === $errCode) {
                        $this->_receiveXml = $msg;
                        $this->_receive    = $this->FromXml($msg);
                    } else {
                        $this->errorLog('消息解密失败:' . $errCode, $this->_msg_signature, $this->_timestamp, $this->_nonce, $postStr);
                        $this->_receive = [];
                    }
                }
            }
        }

        return [$this->_receive, $this->_receiveXml];
    }

    protected function FromXml($msg)
    {
        $xml = @simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml) {
            $error = error_get_last() ?: ['message' => 'simplexmlLoad解析失败'];
            $this->errorLog('simplexmlLoad解析失败', $error, $msg);
            $msg = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $msg);
            $xml = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        }

        return (array)$xml;
    }

    /**
     * @return bool
     */
    public function isReply()
    {
        return $this->reply;
    }

    /**
     * @param bool $reply
     */
    public function setReply($reply)
    {
        $this->reply = $reply;
    }

    /**
     * 被动回复消息.
     *
     * @param string $_ 为空则不返回任何消息
     */
    public function push($_ = null)
    {
        $result = 'success';
        if (!$_ || !$this->isReply()) {
            $this->log('直接返回:success');
            @ob_clean();
        } else {
            $data = func_get_args();
            if (count($data) > 1) {
                $type = array_shift($data);
            } else {
                $type = 'text';
            }
            if (!!$data && !!Z::arrayGet($data, 0)) {
                list($getRev) = $this->getRev();
                $data   = array_merge([$getRev['FromUserName'], $getRev['ToUserName']], $data);
                $result = $this->getUtil()->getXml($type, $data);
                if (true === $this->_encrypt) {
                    $this->getCrypt()->encryptMsg($result, $this->_timestamp, $this->_nonce, $result);
                }
                $this->log('最终返回微信', $result);
            } else {
                $this->log('没有给微信返回任何东西');
            }
        }
        Z::end($result);
    }

    /**
     * 处理开放平台推送事件.
     *
     * @param \Closure $closure
     *
     * @return bool
     */
    public function runComponentPush($closure = null)
    {
        $ticket = $this->getCrypt()->extractDecrypt(Z::postRaw());
        $this->log('收到开放平台推送事件', $ticket);
        if ($infoType = Z::arrayGet($ticket, 'InfoType')) {
            switch ($infoType) {
                case 'component_verify_ticket':
                    $this->setComponentTicket($ticket);
                    break;
            }
            if ($closure instanceof \Closure) {
                $closure($ticket, $infoType);
            }

            return $ticket;
        } else {
            $this->errorLog('解密开放平台推送事件失败', $ticket);

            return false;
        }
    }

    /**
     * 设置component_verify_ticket.
     *
     * @param $ticket
     */
    public function setComponentTicket($ticket)
    {
        $this->log('缓存component_verify_ticket', $ticket);
        //缓存一个礼拜
        Z::cache()->set($this->getUniqueKey('_ComponentTicket'), $ticket['ComponentVerifyTicket'], 604800);
    }

    /**
     * 获取错误信息详情.
     *
     * @param null $code
     *
     * @return array|string
     */
    public function errorCode($code = null)
    {
        if (is_null($code)) {
            return self::$errCode;
        }

        return Z::arrayGet(self::$errCode, $code, '未知错误');
    }
}
