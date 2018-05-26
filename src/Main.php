<?php

namespace Zls\WeChat;

use Z;

/**
 * WeChat
 * @package       ZlsPHP-WeChat
 * @author        影浅
 * @email         seekwe@gmail.com
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 * @link          ---
 * @since         v2.0.16
 * @updatetime    2018-1-11 17:44:38
 * @method Qr getQr() 二维码
 * @method Qy getQy() 企业号
 * @method Pay getPay() 支付
 * @method Basi getBasi() 公众号
 * @method App getApp() 小程序
 */
class Main
{
    const APIURL = 'https://api.weixin.qq.com';
    const OPENURL = 'https://open.weixin.qq.com';
    const FILEURL = 'https://file.api.weixin.qq.com';
    const QYAPIURL = 'https://qyapi.weixin.qq.com';
    const QYOPENURL = 'https://open.weixin.qq.com';
    public static $errCode = [
        '-1'      => '系统繁忙',
        '0'       => '请求成功',
        '40001'   => '获取access_token时AppSecret错误，或者access_token无效',
        '40002'   => '不合法的凭证类型',
        '40003'   => '不合法的OpenID',
        '40004'   => '不合法的媒体文件类型',
        '40005'   => '不合法的文件类型',
        '40006'   => '不合法的文件大小',
        '40007'   => '不合法的媒体文件id',
        '40008'   => '不合法的消息类型',
        '40009'   => '不合法的图片文件大小',
        '40010'   => '不合法的语音文件大小',
        '40011'   => '不合法的视频文件大小',
        '40012'   => '不合法的缩略图文件大小',
        '40013'   => '不合法的APPID',
        '40014'   => '不合法的access_token',
        '40015'   => '不合法的菜单类型',
        '40016'   => '不合法的按钮个数',
        '40017'   => '不合法的按钮类型',
        '40018'   => '不合法的按钮名字长度',
        '40019'   => '不合法的按钮KEY长度',
        '40020'   => '不合法的按钮URL长度',
        '40021'   => '不合法的菜单版本号',
        '40022'   => '不合法的子菜单级数',
        '40023'   => '不合法的子菜单按钮个数',
        '40024'   => '不合法的子菜单按钮类型',
        '40025'   => '不合法的子菜单按钮名字长度',
        '40026'   => '不合法的子菜单按钮KEY长度',
        '40027'   => '不合法的子菜单按钮URL长度',
        '40028'   => '不合法的自定义菜单使用用户',
        '40029'   => '不合法的oauth_code',
        '40030'   => '不合法的refresh_token',
        '40031'   => '不合法的openid列表',
        '40032'   => '不合法的openid列表长度/每次传入的openid列表个数不能超过50个',
        '40033'   => '不合法的请求字符，不能包含\uxxxx格式的字符',
        '40035'   => '不合法的参数',
        '40038'   => '不合法的请求格式',
        '40039'   => '不合法的URL长度',
        '40050'   => '不合法的分组id',
        '40051'   => '分组名字不合法',
        '40066'   => '不合法的url',
        '40099'   => '该 code 已被核销',
        '41001'   => '缺少access_token参数',
        '40125'   => 'appsecret无效',
        '40130'   => '参数错误',
        '41002'   => '缺少appid参数',
        '41003'   => '缺少refresh_token参数',
        '41004'   => '缺少secret参数',
        '41005'   => '缺少多媒体文件数据',
        '41006'   => '缺少media_id参数',
        '41007'   => '缺少子菜单数据',
        '41008'   => '缺少oauth code',
        '41009'   => '缺少openid',
        '42001'   => 'access_token超时',
        '42002'   => 'refresh_token超时',
        '42003'   => 'oauth_code超时',
        '42005'   => '调用接口频率超过上限',
        '43001'   => '需要GET请求',
        '43002'   => '需要POST请求',
        '43003'   => '需要HTTPS请求',
        '43004'   => '需要接收者关注',
        '43005'   => '需要好友关系',
        '44001'   => '多媒体文件为空',
        '44002'   => 'POST的数据包为空',
        '44003'   => '图文消息内容为空',
        '44004'   => '文本消息内容为空',
        '45001'   => '多媒体文件大小超过限制',
        '45002'   => '消息内容超过限制',
        '45003'   => '标题字段超过限制',
        '45004'   => '描述字段超过限制',
        '45005'   => '链接字段超过限制',
        '45006'   => '图片链接字段超过限制',
        '45007'   => '语音播放时间超过限制',
        '45008'   => '图文消息超过限制',
        '45009'   => '接口调用超过限制',
        '45010'   => '创建菜单个数超过限制',
        '45015'   => '回复时间超过限制',
        '45016'   => '系统分组，不允许修改',
        '45017'   => '分组名字过长',
        '45018'   => '分组数量超过上限',
        '45024'   => '账号数量超过上限',
        '45157'   => '标签名非法，请注意不能和其他标签重名',
        '45158'   => '标签名长度超过30个字节',
        '45056'   => '创建的标签数过多，请注意不能超过100个',
        '45058'   => '不能修改0/1/2这三个系统默认保留的标签',
        '45057'   => '该标签下粉丝数超过10w，不允许直接删除',
        '45059'   => '有粉丝身上的标签数已经超过限制',
        '45159'   => '非法的tag_id',
        '46001'   => '不存在媒体数据',
        '46002'   => '不存在的菜单版本',
        '46003'   => '不存在的菜单数据',
        '46004'   => '不存在的用户',
        '47001'   => '解析JZLSN/XML内容错误',
        '48001'   => 'api功能未授权',
        '49003'   => '传入的openid不属于此AppID',
        '50001'   => '用户未授权该api',
        '61450'   => '系统错误',
        '61451'   => '参数错误',
        '61452'   => '无效客服账号',
        '61453'   => '账号已存在',
        '61454'   => '客服帐号名长度超过限制(仅允许10个英文字符，不包括@及@后的公众号的微信号)',
        '61455'   => '客服账号名包含非法字符(英文+数字)',
        '61456'   => '客服账号个数超过限制(10个客服账号)',
        '61457'   => '无效头像文件类型',
        '61458'   => '客户正在被其他客服接待',
        '61459'   => '客服不在线',
        '61500'   => '日期格式错误',
        '61501'   => '日期范围错误',
        '7000000' => '请求正常，无语义结果',
        '7000001' => '缺失请求参数',
        '7000002' => 'signature 参数无效',
        '7000003' => '地理位置相关配置 1 无效',
        '7000004' => '地理位置相关配置 2 无效',
        '7000005' => '请求地理位置信息失败',
        '7000006' => '地理位置结果解析失败',
        '7000007' => '内部初始化失败',
        '7000008' => '非法 appid（获取密钥失败）',
        '7000009' => '请求语义服务失败',
        '7000010' => '非法 post 请求',
        '7000011' => 'post 请求 json 字段无效',
        '7000030' => '查询 query 太短',
        '7000031' => '查询 query 太长',
        '7000032' => '城市、经纬度信息缺失',
        '7000033' => 'query 请求语义处理失败',
        '7000034' => '获取天气信息失败',
        '7000035' => '获取股票信息失败',
        '7000036' => 'utf8 编码转换失败',
    ];
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
    private $_receive, $_encrypt, $_signature, $_timestamp, $_nonce, $_msg_signature, $_receiveXml;
    private $accessToken;
    private $jsapiTicket;
    private $openid;
    private $userid;
    private $authScope = 'snsapi_userinfo'; //snsapi_base|snsapi_userinfo 全权限授权
    private $authState = 'zls_wechat';
    private $authAccessToken;
    private $refreshComponentCallback;
    private $agentid;
    private $reply = true;

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * 清除赋值/模拟销毁
     */
    public function clear()
    {
        $this->_receive = null;
        $this->_receiveXml = null;
        $this->componentAccessToken = null;
        $this->accessToken = null;
        $this->componentAuthorizerAccessToken = null;
        $this->openid = null;
    }

    /**
     * @param array $data [appid,appsecret,encoding_aes_key,component_appid,component_appsecret]
     */
    public function init(array $data)
    {
        z::log(date('Y-M-d H:i:s') . '实例化微信库类对象', 'wxInit');
        $this->appid = z::arrayGet($data, 'appid', z::arrayGet($data, 'corpid'));
        $this->appsecret = z::arrayGet($data, 'appsecret');
        $this->token = z::arrayGet($data, 'token');
        $this->encodingAesKey = z::arrayGet($data, 'encodingAesKey');
        $this->componentAppid = z::arrayGet($data, 'componentAppid');
        $this->componentAppsecret = z::arrayGet($data, 'componentAppsecret');
        $this->debug = z::arrayGet($data, 'debug');
        $this->agentid = z::arrayGet($data, 'agentid');
        $this->setUniqueKey();
    }

    public function __call($name, $value)
    {
        $className = 'Zls_WeChat_' . str_replace('get', '', $name);
        $class = z::factory($className, true, null, [$this]);

        return $class;
    }

    /**
     * 设置是否开启记录日志
     * @param boolean $debug
     * @return Main
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * 进行开放平台授权
     * @param string $redirect_uri 回调地址 默认当前页面
     * @return array|string
     */
    public function runComponentAuth($redirect_uri = '')
    {
        if (!$redirect_uri) {
            $redirect_uri = z::host(true, true, true);
        }
        if ($auth_code = z::get('auth_code')) {
            return z::tap($this->getComponentApiQueryAuth($auth_code), function ($authInfo) use ($redirect_uri) {
                if ($this->errorCode == '61010') {
                    unset($_GET['auth_code']);
                    $this->runComponentAuth($redirect_uri);
                }
                $appid = $authInfo['authorization_info']['authorizer_appid'];
                $accessToken = $authInfo['authorization_info']['authorizer_access_token'];
                $refreshToken = $authInfo['authorization_info']['authorizer_refresh_token'];
                $expiresIn = $authInfo['authorization_info']['expires_in'] - 300;
                $this->setComponentAuthorizerAccessToken($accessToken, $appid, $expiresIn);
                //刷新密钥缓存一个礼拜
                $this->log('刷新密钥缓存一个礼拜', $appid, $refreshToken);
                $this->setComponentRefreshToken($refreshToken, $appid, 602000);
            });
        } else {
            $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=' . $this->getComponentAppid() . '&pre_auth_code=' . $this->getComponentPreAuthCode() . '&redirect_uri=' . $redirect_uri;
            z::redirect($url);

            return false;
        }
    }

    /**
     * 获取开放平台接口调用凭据和授权信息
     * @param $authorization_code
     * @return array|bool
     */
    public function getComponentApiQueryAuth($authorization_code)
    {
        $data = [
            'component_appid'    => $this->getComponentAppid(),
            'authorization_code' => $authorization_code,
        ];
        if ($result = $this->post(self::APIURL . '/cgi-bin/component/api_query_auth?component_access_token=' . $this->getComponentAccessToken(),
            $data)
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
     * @param      $url
     * @param null $data
     * @param bool $atUpload
     * @return bool|array
     */
    public function post($url, $data = null, $atUpload = false)
    {
        return $this->request($url, $data, 'post', 'json', 'json', $atUpload);
    }

    public function request($url, $data = null, $type = 'get', $dataType = 'json', $responseType = 'json', $atUpload = false)
    {
        if ($dataType === 'json' && is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $result = ($type == 'get') ? $this->getHttp()->get($url, $data) : $this->getHttp()->post($url, $data, null, 0, $atUpload);
        $this->log('接口请求', $url, $data, $result);
        if ($responseType === 'json') {
            $result = @json_decode($result, true);
            $errcode = z::arrayGet($result, 'errcode', '');
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
        return z::extension('Action_Http');
    }

    /**
     * 打印日志
     * @param $_
     * @return void
     */
    public function log($_)
    {
        if ($this->debug) {
            $this->output();
        }
    }

    protected function output()
    {
        $trace = z::arrayGet(debug_backtrace(), 1);
        $arg = $trace['args'];
        $data = [
            'time' => date('Y-m-d H:i:s'),
            'file' => z::safePath($trace['file']),
            'line' => $trace['line'],
        ];
        if (is_array($arg)) {
            foreach ($arg as $key => $value) {
                $data['log[' . $key . ']'] = $value;
            }
        } else {
            $data['log'] = $arg;
        }
        z::log($data, 'wx');
    }

    public function setError($errorCode, $errorMsg, $force = false)
    {
        $result = false;
        static $reAccessToken = false;
        switch ($errorCode) {
            case 42001://令牌过期
                break;
            case 40001://accessToken无效
                $this->errorLog('缓存没过期，但是accessToken失效了');
                if (!$reAccessToken) {
                    $this->getAccessToken(false);
                    $reAccessToken = true;
                    $backtrace = debug_backtrace();
                    foreach ($backtrace as $k) {
                        if (z::arrayGet($k, 'class') === __CLASS__ && z::arrayGet($k, 'function') === 'request') {
                            $args = $k['args'];
                            $url = z::arrayGet($args, 0);
                            $urls = parse_url($url);
                            if ($query = z::arrayGet($urls, 'query', '')) {
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
                            $port = z::arrayGet($urls, 'port');
                            $host = z::arrayGet($urls, 'host') . ($port ? ':' . $port : '');
                            $fragment = z::arrayGet($urls, 'fragment');
                            $url = z::arrayGet($urls, 'scheme') . '://' . $host . z::arrayGet($urls, 'path') . $query . ($fragment ? '#' . $fragment : '');
                            $data = z::arrayGet($args, 1);
                            $type = z::arrayGet($args, 2, 'get');
                            $dataType = z::arrayGet($args, 3, 'json');
                            $responseType = z::arrayGet($args, 4, 'json');
                            $atUpload = z::arrayGet($args, 5, false);
                            $result = $this->request($url, $data, $type, $dataType, $responseType, $atUpload);
                            break;
                        }
                    }
                }
                break;
        }
        if ($this->errorCode <= 0 || $force === true) {
            $this->errorCode = $errorCode;
            $this->errorMsg = $errorMsg;
        }

        return $result;
    }

    /**
     * @param $_
     */
    public function errorLog($_)
    {
        $this->output();
    }

    /**
     * 获取AccessToken
     * @param bool $cache
     * @return bool|string
     */
    public function getAccessToken($cache = true)
    {
        if (!$this->getComponentAppid()) {
            if (!$this->accessToken) {
                $agentid = $this->getAgentid() ?: '';
                $cacheKey = $this->getUniqueKey() . $agentid . '_access_token';
                if ($cache != true || !$access_token = z::cache()->get($cacheKey)) {
                    $res = $this->instance()->getAccessToken();
                    if (!$res) {
                        return false;
                    }
                    $access_token = $res['access_token'];
                    $expire = $res['expires_in'] ? intval($res['expires_in']) - 300 : 3600;
                    z::cache()->set($cacheKey, $access_token, $expire);
                    z::cache()->set($cacheKey . '_outTime', time() + $expire, $expire + 200);
                    $this->accessTokenOutTime($expire);
                }
                $this->setAccessToken($access_token);
            }
        } else {
            //存在开放平台APPID则强行把AccessToken返回ComponentAuthorizerAccessToken
            return $this->getComponentAuthorizerAccessToken();
        }

        return $this->accessToken;
    }

    /**
     * 设置AccessToken
     * @param string $access_token
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
        $this->agentid = $agentid;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getUniqueKey($key = '')
    {
        return $this->uniqueKey . $key;
    }

    /**
     * 设置唯一Key
     * @param mixed $uniqueKey
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
     * @param null $expiresIn
     * @return int|null
     */
    public function accessTokenOutTime($expiresIn = null)
    {
        $cacheKey = 'Zls_WeChat_' . ($this->getComponentAppid() ? $this->getComponentAppid() : $this->getAppid()) . '_AccessTokenOutTime';
        if (is_null($expiresIn)) {
            $expiresIn = z::cache()->get($cacheKey) ?: 0;
        } else {
            $expiresIn = time() + $expiresIn;
            z::cache()->set($cacheKey, $expiresIn);
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
     * 设置Appid
     * @param mixed $appid
     * @return Main
     */
    public function setAppid($appid)
    {
        $this->appid = $appid;
        if (!$this->getComponentAppid()) {
            $this->getCrypt()->setAppId($appid);
        }
        $this->componentAuthorizerAccessToken = null;
        $this->accessToken = null;

        return $this;
    }

    /**
     * 获取开放平台AuthorizerAccessToken
     * @param string $appid
     * @return mixed
     */
    public function getComponentAuthorizerAccessToken($appid = '')
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }
        if (!$this->componentAuthorizerAccessToken && (!$this->componentAuthorizerAccessToken = z::cache()->get($this->getUniqueKey('_ComponentAuthorizerAccessToken_' . $appid)))) {
            $this->refreshComponentAuthorizerAccessToken();
        }

        return $this->componentAuthorizerAccessToken;
    }

    /**
     * 设置开放平台授权公众号AccessToken
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
            z::cache()->set($this->getUniqueKey('_ComponentAuthorizerAccessToken_' . $appid),
                $componentAuthorizerAccessToken, $expiresIn);
        }
        $this->componentAuthorizerAccessToken = $componentAuthorizerAccessToken;
    }

    /**
     * 刷新AuthorizerAccessToken
     * @param string $appid                  appid
     * @param string $authorizerRefreshToken 刷新令牌
     * @return mixed
     */
    public function refreshComponentAuthorizerAccessToken($appid = '', $authorizerRefreshToken = null)
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }
        $data = [
            'component_appid'          => $this->getComponentAppid(),
            'authorizer_appid'         => $appid,
            'authorizer_refresh_token' => $authorizerRefreshToken ?: $this->getComponentRefreshToken($appid),
        ];

        return z::tap($this->post(self::APIURL . '/cgi-bin/component/api_authorizer_token?component_access_token=' . $this->getComponentAccessToken(),
            $data), function ($resule) use ($appid, $data) {
            if ($resule) {
                $this->getCallbackRefreshComponent($appid, $resule);
                $expiresIn = $resule['expires_in'] - 300;
                $this->accessTokenOutTime($expiresIn);
                $this->setComponentAuthorizerAccessToken($resule['authorizer_access_token'], $appid, $expiresIn);
                $this->setComponentRefreshToken($resule['authorizer_refresh_token'], $appid, 602000);
            } else {
                $this->errorLog('刷新AuthorizerAccessToken失败', $data, $this->getComponentAccessToken(), $this->getError());
            }
        });
    }

    /**
     * 获取刷新令牌
     * @param string $appid
     * @return mixed
     */
    public function getComponentRefreshToken($appid = '')
    {
        if (!$appid) {
            $appid = $this->getAppid();
        }

        return z::cache()->get($this->getUniqueKey('_ComponentRefreshToken_' . $appid));
    }

    /**
     * 获取开放平台AccessToken
     * @param bool $cache
     * @return mixed
     */
    public function getComponentAccessToken($cache = true)
    {
        $cacheKey = $this->getUniqueKey('_ComponentAccessToken');
        $this->componentAccessToken = $this->componentAccessToken ?: z::cache()->get($cacheKey);
        if ($cache === false || !$this->componentAccessToken) {
            $data = [
                'component_appid'         => $this->getComponentAppid(),
                'component_appsecret'     => $this->getComponentAppsecret(),
                'component_verify_ticket' => $this->getComponentTicket(),
            ];
            if ($result = $this->post(
                self::APIURL . '/cgi-bin/component/api_component_token', $data)
            ) {
                $this->log('获取开放平台AccessToken成功', $result);
                $this->componentAccessToken = $result['component_access_token'];
                $expiresIn = $result['expires_in'] - 1800;
                z::cache()->set($cacheKey, $this->componentAccessToken, $expiresIn);
                z::cache()->set($cacheKey . '_expiresIn', time() + $expiresIn, $expiresIn);
            } else {
                $this->errorLog('获取开放平台AccessToken失败', $this->getError(), $data);
            }
        }
        $this->log('获取开放平台AccessToken:', $this->componentAccessToken,
            '过期时间' . date('Y-m-d H:i:s', z::cache()->get($cacheKey . '_expiresIn')));

        return $this->componentAccessToken;
    }

    /**
     * 设置开放平台AccessToken
     * @param mixed $componentAccessToken
     */
    public function setComponentAccessToken($componentAccessToken)
    {
        if ($componentAccessToken) {
            z::cache()->set($this->getUniqueKey('_ComponentAccessToken'), $componentAccessToken, 3600);
        }
        $this->componentAccessToken = $componentAccessToken;
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

    /**
     * 获取component_verify_ticket
     * @return mixed
     */
    public function getComponentTicket()
    {
        return z::cache()->get($this->getUniqueKey('_ComponentTicket'));
    }

    public function getError($clean = \false)
    {
        $error = ['code' => $this->errorCode, 'msg' => $this->errorMsg];
        if ($clean === true) {
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
     * 设置刷新令牌
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
            z::cache()->set($this->getUniqueKey('_ComponentRefreshToken_' . $appid), $componentRefreshToken,
                $expiresIn);
        }
    }

    private function getErrText($err)
    {
        $code = z::arrayGet($err, 'errcode', -1);
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
        $data = ['component_appid' => $this->getComponentAppid()];
        $PreAuthCode = '';
        $result = $this->post(self::APIURL . '/cgi-bin/component/api_create_preauthcode?component_access_token=' . $this->getComponentAccessToken(),
            $data);
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
        /**  @var Crypt $class */
        $class = z::tap(
            z::extension('WeChat_Crypt'), function ($class) {
            /**  @var Crypt $class */
            if ($this->getComponentAppid()) {
                $class->msgCrypt($this->getComponentAppid(), $this->getEncodingAesKey(), $this->getToken());
            } else {
                $class->msgCrypt($this->getAppid(), $this->getEncodingAesKey(), $this->getToken());
            }
        });

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
     * 设置token
     * @param mixed $token
     * @return Main
     */
    public function setToken($token)
    {
        $this->token = $token;
        $this->getCrypt()->setToken($token);

        return $this;
    }

    /**
     * 请求上传文件
     * @param      $url
     * @param null $data
     * @return bool|array
     */
    public function upload($url, $data = null)
    {
        if (!empty($_FILES)) {
            $key = z::arrayGet(array_keys($_FILES), 0);
            if ($file = z::arrayGet($_FILES, $key)) {
                $tempPath = z::tempPath();
                $filename = $tempPath . '/' . $file['name'];
                move_uploaded_file($file['tmp_name'], $filename);
                $data[$key] = '@' . $filename;
            }
        }

        return $this->post($url, $data, true);
    }

    /**
     * @param     $key
     * @param     $data
     * @param int $time
     * @return mixed
     */
    public function cache($key, $data, $time = 30)
    {
        $key = $this->getUniqueKey() . $key;
        if (!$cacheData = z::cache()->get($key)) {
            $cacheData = $data();
            z::cache()->set($key, $cacheData, $time);
        }

        return $cacheData;
    }

    /**
     * 微信js签名
     * @param string $url
     * @param int    $timestamp
     * @param string $noncestr
     * @return array|bool
     */
    public function getJsSign($url = '', $timestamp = 0, $noncestr = '')
    {
        if (!$this->getJsapiTicket()) {
            return false;
        }
        (!$url) && $url = z::host(true, true, true);
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
        $arrdata = [
            "timestamp"    => $timestamp,
            "noncestr"     => $noncestr,
            "url"          => $url,
            "jsapi_ticket" => $this->getJsapiTicket(),
        ];
        $cacheKey = $this->getUniqueKey() . '_js_sign_' . md5($url . $this->getJsapiTicket());
        if (!$signPackage = z::cache()->get($cacheKey)) {
            if (!function_exists('sha1')) {
                return false;
            }
            ksort($arrdata);
            $paramstring = "";
            foreach ($arrdata as $key => $value) {
                if (strlen($paramstring) == 0) {
                    $paramstring .= $key . "=" . $value;
                } else {
                    $paramstring .= "&" . $key . "=" . $value;
                }
            }
            $Sign = sha1($paramstring);
            $signPackage = [
                "appid"     => $this->getAppid(),
                "nonceStr"  => $noncestr,
                "timestamp" => $timestamp,
                "url"       => $url,
                "signature" => $Sign,
            ];
            z::cache()->set($cacheKey, $signPackage, 3600);
        }

        return $signPackage;
    }

    /**
     * 获取Ticket
     * @return bool|string
     */
    public function getJsapiTicket()
    {
        if (!$jsapiTicket = z::arrayGet($this->jsapiTicket, $this->getAppid())) {
            $cacheKey = $this->getUniqueKey() . '_jsapi_ticket' . $this->getAppid();
            if (!$jsapiTicket = z::cache()->get($cacheKey)) {
                $res = $this->instance()->getJsapiTicket();
                if (!$res) {
                    return false;
                }
                $jsapiTicket = $res['ticket'];
                $expire = $res['expires_in'] ? intval($res['expires_in']) - 100 : 3600;
                z::cache()->set($cacheKey, $jsapiTicket, $expire);
            }
            $this->setJsapiTicket($jsapiTicket);
        }

        return $jsapiTicket;
    }

    /**
     * 设置Ticket
     * @param mixed $jsapiTicket
     * @return Main
     */
    public function setJsapiTicket($jsapiTicket)
    {
        $this->jsapiTicket[$this->getAppid()] = $jsapiTicket;

        return $this;
    }

    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    public function generateNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }

    /**
     * 设置刷新AuthorizerAccessToken回调
     * @param \Closure $closure
     */
    public function setRefreshComponentCallback(\Closure $closure)
    {
        $this->refreshComponentCallback = $closure;
    }

    /**
     * 获取AccessToken过期时间戳
     */
    public function getAccessTokenOutTime()
    {
        return z::cache()->get($this->getUniqueKey() . '_access_token_outTime');
    }

    /**
     * 获取用户详情
     * @param $openid
     * @return mixed
     */
    public function getUserInfo($openid)
    {
        return $this->get(self::APIURL . '/cgi-bin/user/info?access_token=' . $this->getAccessToken() . '&openid=' . $openid . '&lang=zh_CN');
    }

    public function get($url, $data = null)
    {
        return $this->request($url, $data, 'get');
    }

    /**
     * 获取授权用户OPENID
     * @param string $type
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
            $result = $this->instance()->getAuthAccessToken($authCode);
            if (!$result) {
                if ($this->errorCode == '41008' || $this->errorCode == '40029' || $this->errorCode == '40163') {
                    $this->authCode(null, $authCode);
                }

                return false;
            }
            $this->authAccessToken = $result;
            $this->openid = z::arrayGet($result, 'openid', z::arrayGet($result, 'OpenId'));
            $this->userid = z::arrayGet($result, 'UserId', z::arrayGet($result, 'userid'));
        }

        return $this->authAccessToken;
    }

    /**
     * 获取授权 oauth_code
     * @param string $url
     * @param string $oldCode
     * @return string|mixed
     */
    public function authCode($url = null, $oldCode = '')
    {
        if (($code = z::get('code')) && ($oldCode !== $code)) {
            return $code;
        }
        if (!$url) {
            $url = z::host(true, true, true);
        }
        $urls = parse_url($url);
        if ($query = z::arrayGet($urls, 'query', '')) {
            parse_str($query, $query);
            unset($query['code'], $query['state'], $query['scope']);
            $query = '?' . http_build_query($query);
        }
        $port = z::arrayGet($urls, 'port');
        $host = z::arrayGet($urls, 'host') . ($port ? ':' . $port : '');
        $fragment = z::arrayGet($urls, 'fragment');
        $url = z::arrayGet($urls, 'scheme') . '://' . $host . z::arrayGet($urls, 'path') . $query . ($fragment ? '#' . $fragment : '');
        z::redirect($this->getOauthRedirect($url, $this->getAuthState(), $this->getAuthScope()));

        return false;
    }

    /**
     * 获取授权URl
     * @param        $callback
     * @param string $state
     * @param string $scope
     * @return string
     */
    public function getOauthRedirect($callback = null, $state = '', $scope = 'snsapi_userinfo')
    {
        (!$callback) && $callback = z::host(true, true, true);

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
     * 设置页面授权方式
     * @param string $authScope snsapi_base|snsapi_userinfo
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
     * @return Main
     */
    public function setAppsecret($appsecret)
    {
        $this->appsecret = $appsecret;

        return $this;
    }

    /**
     * 获取授权用户详情
     * @param null $openid
     * @return bool|mixed|String
     */
    public function getAuthUserInfo($openid = null)
    {
        return $this->instance()->getAuthUserInfo($openid);
    }

    /**
     * 下载多媒体
     * @param string $media_id 媒体ID
     * @param string $saveFile 文件保存路径
     * @return string
     */
    public function mediaDownload($media_id = '', $saveFile)
    {
        $this->get(self::FILEURL . '/cgi-bin/media/get?access_token=' . $this->getAccessToken() . '&media_id=' . $media_id);
        if (preg_match('/filename="(.*)"/i', $this->getHttp()->header(), $filename)) {
            $filepath = $saveFile . '/' . $filename[1];
            file_put_contents($filepath, $this->getHttp()->data());

            return z::safePath($filepath, '');
        }
        $result = @json_decode($this->getHttp()->data(), true);
        $errorCode = z::arrayGet($result, 'errcode', 404);
        $errorMsg = z::arrayGet($result, 'errmsg', '文件名获取失败');
        $this->setError($errorCode, $errorMsg);
        $this->errorLog('多媒体下载失败', $result);

        return false;
    }

    public function valid()
    {
        if ($echoStr = z::get('echostr')) {
            $signature = z::get('signature', '');
            $timestamp = z::get('timestamp', '');
            $nonce = z::get('nonce', '');
            $msg_signature = z::get('msg_signature', '');
            $this->errorLog(z::get(), $msg_signature);
            if ($msg_signature) {
                $error = $this->getCrypt()->verifyUrl($this->getToken(), $timestamp, $nonce, $echoStr, $msg_signature, $echoStr);
            } else {
                $error = $this->getUtil()->checkSignature($this->getToken(), $signature, $timestamp, $nonce);
            }
            if ($error == 0) {
                z::finish($echoStr);
            } else {
                $this->errorLog('解密valid失败' . $error);
                z::finish('error');
            }
        }
    }

    /**
     * WeChatUtil
     * @return Util
     */
    public function getUtil()
    {
        /** @var Util $class */
        $class = z::extension('WeChat_Util');

        return $class;
    }

    /**
     * @param $type
     * @param $fn
     * @return $this
     */
    public function on($type, $fn)
    {
        if (!is_array($type)) {
            $type = [$type];
        }
        foreach ($type as $item) {
            z::di()->bind($this->getUniqueKey() . '_event_' . $item, $fn);
        }

        return $this;
    }

    public function run()
    {
        list($getRev, $getRevXml) = $this->getRev();
        if ($getRev) {
            //是否回复
            if (!$this->isReply()) {
                $this->push();
            }
            $this->log('收到消息', $getRev, $getRevXml);
            $type = $getRev['MsgType'];
            $typeAll = $this->getUniqueKey() . '_event_all';
            $taskName = $this->getUniqueKey() . '_event_' . $type;
            if (z::di()->has($taskName)) {
                z::di()->makeShared($taskName, [$this, $getRev, $type, $getRevXml]);
            } elseif (z::di()->has($typeAll)) {
                z::di()->makeShared($typeAll, [$this, $getRev, $type, $getRevXml]);
            }
        } else {
            $this->errorLog('消息获取失败');
            z::finish();
        }
    }

    private function getRev()
    {
        if (empty($this->_receive)) {
            $postStr = z::postRaw();
            if (!empty($postStr)) {
                $this->_receive = (is_array($postStr)) ?
                    $postStr : (array)simplexml_load_string($postStr,
                        'SimpleXMLElement', LIBXML_NOCDATA);
                if (isset($this->_receive['Encrypt']) && (!isset($this->_receive['MsgType']))) {
                    $this->log('解密消息');
                    $this->_encrypt = true;
                    $msg = '';
                    $this->_signature = z::get('signature');
                    $this->_timestamp = z::get('timestamp');
                    $this->_nonce = z::get('nonce');
                    $this->_msg_signature = z::get('msg_signature');
                    $errCode = $this->getCrypt()->decryptMsg(
                        $this->_msg_signature,
                        $this->_timestamp,
                        $this->_nonce,
                        $postStr,
                        $msg
                    );
                    if ($errCode === 0) {
                        $this->_receiveXml = $msg;
                        $this->_receive = (array)simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
                    } else {
                        $this->errorLog('消息解密失败:' . $errCode,
                            $this->_msg_signature,
                            $this->_timestamp,
                            $this->_nonce,
                            $postStr
                        );
                        $this->_receive = [];
                    }
                }
            }
        }

        return [$this->_receive, $this->_receiveXml];
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
        $this->log('设置是否被动回复状态:', $reply);
        $this->reply = $reply;
    }

    /**
     * 被动回复消息
     * @param string $_ 为空则不返回任何消息
     */
    public function push($_ = null)
    {
        if (!$_ || !$this->isReply()) {
            $this->log('直接返回:success');
            @ob_clean();
            $result = 'success';
        } else {
            $data = func_get_args();
            $type = array_shift($data);
            list($getRev) = $this->getRev();
            $data = array_merge([$getRev['FromUserName'], $getRev['ToUserName']], $data);
            $result = $this->getUtil()->getXml($type, $data);
            if ($this->_encrypt === true) {
                $this->log('原始返回微信', $result);
                $this->getCrypt()->encryptMsg($result, $this->_timestamp, $this->_nonce, $result);
            }
            $this->log('最终返回微信', $result);
        }
        z::finish($result);
    }

    /**
     * 处理开放平台推送事件
     * @param \Closure $closure
     * @return bool
     */
    public function runComponentPush($closure = null)
    {
        $ticket = $this->getCrypt()->extractDecrypt(z::postRaw());
        $this->log('收到开放平台推送事件', $ticket);
        if ($infoType = z::arrayGet($ticket, 'InfoType')) {
            $this->log('解密ComponentPush成功', $ticket);
            switch ($infoType) {
                case 'component_verify_ticket':
                    $this->log(microtime(true) - IN_ZLS);
                    $this->log('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>component_verify_ticket');
                    $this->setComponentTicket($ticket);
                    break;
            }
            if ($closure instanceof \Closure) {
                $closure($ticket, $infoType);
            }

            return $ticket;
        } else {
            $this->errorLog('解密ComponentPush失败', $ticket);

            return false;
        }
    }

    /**
     * 设置component_verify_ticket
     * @param $ticket
     * @return void
     */
    public function setComponentTicket($ticket)
    {
        $this->log('缓存component_verify_ticket', $ticket);
        z::cache()->set($this->getUniqueKey('_ComponentTicket'), $ticket['ComponentVerifyTicket'], 24 * 3600);
    }

    /**
     * 获取错误信息详情
     * @param null $code
     * @return array|string
     */
    public function errorCode($code = null)
    {
        if (is_null($code)) {
            return self::$errCode;
        }

        return z::arrayGet(self::$errCode, $code, '未知错误');
    }
}
