<?php

namespace Zls\WeChat;

use Z;

/**
 * 小程序.
 *
 * @author        影浅
 * @email         seekwe@gmail.com
 *
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 *
 * @see          ---
 * @since         v0.0.2
 * @updatetime    2018-3-4 20:39:45
 */
class App implements WxInterface
{
    private $wx;
    private $sessionKey;

    public function __construct(Main $wx)
    {
        $this->wx = &$wx;
    }

    public function setSessionKey($sessionKey)
    {
        $this->sessionKey = $sessionKey;

        return $this;
    }

    /**
     * 获取SessionKey.
     *
     * @param        $code
     * @param        $appid
     * @param        $secret
     * @param string $grantType
     *
     * @return bool|mixed
     */
    public function getSessionKey($code, $appid = '', $secret = '', $grantType = 'authorization_code')
    {
        $wx = $this->wx;
        if (!$appid) {
            $appid = $wx->getAppid();
        }
        if (!$secret) {
            $secret = $wx->getAppsecret();
        }

        return z::tap($wx->get($wx::APIURL.'/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type='.$grantType), function ($res) {
            if ((bool) $res) {
                $this->sessionKey = z::arrayGet($res, 'session_key');
            }
        });
    }

    /**
     * 验证数据合法.
     *
     * @param $rawData
     * @param $signature
     *
     * @return bool
     */
    public function verify($rawData, $signature)
    {
        $_signature = sha1($rawData.$this->sessionKey);

        return $_signature === $signature;
    }

    /**
     * 解密用户信息.
     *
     * @param        $iv
     * @param        $encryptedData
     * @param string $appid
     *
     * @return string
     */
    public function decrypt($iv, $encryptedData, $appid = '')
    {
        if (!$appid) {
            $appid = $this->wx->getAppid();
        }
        if (24 != strlen($this->sessionKey)) {
            $this->wx->setError(-41001, 'sessionKey无效');

            return false;
        }
        $aesKey = base64_decode($this->sessionKey);
        if (24 != strlen($iv)) {
            $this->wx->setError(-41002, 'iv数据无效');

            return false;
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, 1, $aesIV);
        if (!$data = json_decode($result, true)) {
            $this->wx->setError(-41004, '数据解密失败');

            return false;
        }
        if (z::arrayGet($data, 'watermark.appid') !== $appid) {
            $this->wx->setError(-41003, 'AppId不匹配');

            return false;
        }

        return $data;
    }
}
