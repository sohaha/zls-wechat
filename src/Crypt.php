<?php

namespace Zls\WeChat;

/**
 * 对公众平台发送给公众账号的消息加解密
 */
use Z;

class Crypt
{
    public static $OK = 0;
    public static $ValidateSignatureError = -40001;
    public static $ParseXmlError = -40002;
    public static $ComputeSignatureError = -40003;
    public static $IllegalAesKey = -40004;
    public static $ValidateAppidError = -40005;
    public static $EncryptAESError = -40006;
    public static $DecryptAESError = -40007;
    public static $IllegalBuffer = -40008;
    public static $EncodeBase64Error = -40009;
    public static $DecodeBase64Error = -40010;
    public static $GenReturnXmlError = -40011;
    public static $block_size = 32;
    public $key;
    private $token;
    private $encodingAesKey;
    private $appId;

    /**
     * @param $token          string 公众平台上，开发者设置的token
     * @param $encodingAesKey string 公众平台上，开发者设置的EncodingAESKey
     * @param $appId          string 公众平台的appId
     */
    public function msgCrypt($appId, $encodingAesKey, $token)
    {
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->appId = $appId;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @param mixed $encodingAesKey
     */
    public function setEncodingAesKey($encodingAesKey)
    {
        $this->encodingAesKey = $encodingAesKey;
    }

    /**
     * @param mixed $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * 将公众平台回复用户的消息加密打包.
     * <ol>
     *    <li>对要发送的消息进行AES-CBC加密</li>
     *    <li>生成安全签名</li>
     *    <li>将消息密文和安全签名打包成xml格式</li>
     * </ol>
     * @param $replyMsg     string 公众平台待回复用户的消息，xml格式的字符串
     * @param $timeStamp    string 时间戳，可以自己生成，也可以用URL参数的timestamp
     * @param $nonce        string 随机串，可以自己生成，也可以用URL参数的nonce
     * @param &$encryptMsg  string 加密后的可以直接回复用户的密文，包括msg_signature, timestamp, nonce, encrypt的xml格式的字符串,
     *                      当return返回0时有效
     * @return int 成功0，失败返回对应的错误码
     */
    public function encryptMsg($replyMsg, $timeStamp, $nonce, &$encryptMsg)
    {
        $this->Prpcrypt($this->encodingAesKey);
        //加密
        $array = $this->encrypt($replyMsg, $this->appId);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        if ($timeStamp == null) {
            $timeStamp = time();
        }
        $encrypt = $array[1];
        $array = $this->getSHA1($this->token, $timeStamp, $nonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        //生成发送的xml
        $encryptMsg = $this->generate($encrypt, $signature, $timeStamp, $nonce);

        return self::$OK;
    }

    public function Prpcrypt($k)
    {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 对明文进行加密
     * @param string $text 需要加密的明文
     * @param        $appid
     * @return array 加密后的密文
     */
    public function encrypt($text, $appid)
    {
        try {
            //获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            $iv = \substr($this->key, 0, 16);
            //使用自定义的填充方式对明文进行补位填充
            $text = $this->encode($text);
            if (\function_exists('openssl_encrypt')) {
                $encrypted = openssl_encrypt($text, 'AES-256-CBC', substr($this->key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
            } else {
                // 网络字节序
                $size = \mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
                $module = \mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
                \mcrypt_generic_init($module, $this->key, $iv);
                //加密
                $encrypted = mcrypt_generic($module, $text);
                \mcrypt_generic_deinit($module);
                \mcrypt_module_close($module);
                $encrypted = base64_encode($encrypted);
            }

            return [self::$OK, $encrypted];
        } catch (\Exception $e) {
            //print $e;
            return [self::$EncryptAESError, null];
        }
    }

    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    public function getRandomStr()
    {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }

        return $str;
    }

    /**
     * 对需要加密的明文进行填充补位
     * @param string $text 需要进行填充补位操作的明文
     * @return string 补齐明文字符串
     */
    public function encode($text)
    {
        $block_size = self::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = $block_size - ($text_length % $block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = $block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }

        return $text . $tmp;
    }

    public function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
    {
        //排序
        try {
            $array = [$encrypt_msg, $token, $timestamp, $nonce];
            sort($array, SORT_STRING);
            $str = implode($array);

            return [self::$OK, sha1($str)];
        } catch (\Exception $e) {
            //print $e . "\n";
            return [self::$ComputeSignatureError, null];
        }
    }

    /**
     * 生成xml消息
     * @param string $encrypt   加密后的消息密文
     * @param string $signature 安全签名
     * @param string $timestamp 时间戳
     * @param string $nonce     随机字符串
     * @return string
     */
    public function generate($encrypt, $signature, $timestamp, $nonce)
    {
        $format =
            '<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>';

        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }

    /**
     * 企业号验证URL
     * @param $token
     * @param $timestamp
     * @param $nonce
     * @param $echoStr
     * @param $msgSignature
     * @param $replyEchoStr
     * @return int|mixed
     */
    public function verifyUrl($token, $timestamp, $nonce, $echoStr, $msgSignature, &$replyEchoStr)
    {
        $array = $this->getSHA1($token, $timestamp, $nonce, $echoStr);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        if ($signature != $msgSignature) {
            return self::$ValidateSignatureError;
        }
        $this->Prpcrypt($this->encodingAesKey);
        $result = $this->decrypt($echoStr, $this->appId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $replyEchoStr = $result[1];

        return self::$OK;
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @param string $appid
     * @return array|string
     */
    public function decrypt($encrypted, $appid = null)
    {
        $iv = substr($this->key, 0, 16);
        try {
            if (function_exists('openssl_decrypt')) {
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', substr($this->key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
            } else {
                $ciphertext_dec = base64_decode($encrypted);
                $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
                mcrypt_generic_init($module, $this->key, $iv);
                //解密
                $decrypted = mdecrypt_generic($module, $ciphertext_dec);
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);
            }
        } catch (\Exception $e) {
            return [self::$DecryptAESError, null];
        }
        try {
            //去除补位字符
            $result = $this->decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16) {
                return "";
            }
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
        } catch (\Exception $e) {
            return [self::$IllegalBuffer, null];
        }
        if ($from_appid != $appid) {
            return [self::$ValidateAppidError, null];
        }

        return [0, $xml_content];
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string $text 解密后的明文
     * @return string 删除填充补位后的明文
     */
    public function decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }

        return substr($text, 0, (strlen($text) - $pad));
    }

    /**
     * 签名数据
     * @param $array
     * @param $signType
     * @return string
     */
    public function signaData($array, $signType = 'md5')
    {
        if (z::get('test')) {
            z::dump($array);
        }
        sort($array, SORT_STRING);
        if (z::get('test')) {
            z::dump($array);
        }
        $str = implode($array);
        if (z::get('test')) {
            z::dump($array, $str);
        }

        return $signType($str);
    }

    /**
     * 检验消息的真实性，并且获取解密后的明文.
     * @param $msgSignature string 签名串，对应URL参数的msg_signature
     * @param $timestamp    string 时间戳 对应URL参数的timestamp
     * @param $nonce        string 随机串，对应URL参数的nonce
     * @param $postData     string 密文，对应POST请求的数据
     * @param &$msg         string 解密后的原文，当return返回0时有效
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptMsg($msgSignature, $timestamp = null, $nonce, $postData, &$msg)
    {
        if (strlen($this->encodingAesKey) != 43) {
            return self::$IllegalAesKey;
        }
        $this->Prpcrypt($this->encodingAesKey);
        //提取密文
        $array = $this->extract($postData);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        if ($timestamp == null) {
            $timestamp = time();
        }
        $encrypt = $array[1];
        $touser_name = $array[2];
        //验证安全签名
        $array = $this->getSHA1($this->token, $timestamp, $nonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        if ($signature != $msgSignature) {
            return self::$ValidateSignatureError;
        }
        $result = $this->decrypt($encrypt, $this->appId);
        if ($result[0] != 0) {
            return $result[0];
        }
        $msg = $result[1];

        return self::$OK;
    }

    /**
     * 提取出xml数据包中的加密消息
     * @param string $xmltext 待提取的xml字符串
     * @return array 提取出的加密消息字符串
     */
    public function extract($xmltext)
    {
        try {
            $xml = new \DOMDocument();
            $xml->loadXML($xmltext);
            $array_e = $xml->getElementsByTagName('Encrypt');
            $array_a = $xml->getElementsByTagName('ToUserName');
            $encrypt = $array_e->item(0)->nodeValue;
            $tousername = $array_a->item(0)->nodeValue;

            return [0, $encrypt, $tousername];
        } catch (\Exception $e) {
            return [self::$ParseXmlError, null, null];
        }
    }

    /**
     * 解密xml包
     * @param $postStr
     * @return array|boolean
     */
    public function extractDecrypt($postStr)
    {
        try {
            if (!$postStr) {
                return [self::$GenReturnXmlError, null];
            }
            $this->Prpcrypt($this->encodingAesKey);
            $xml_tree = new \DOMDocument();
            $xml_tree->loadXML($postStr);
            $array_e = $xml_tree->getElementsByTagName('AppId');
            $array_s = $xml_tree->getElementsByTagName('Encrypt');
            $AppId = $array_e->item(0)->nodeValue;
            $Encrypt = $array_s->item(0)->nodeValue;
            $msg = $this->decrypt($Encrypt, $AppId);

            return (array)simplexml_load_string(
                array_pop($msg),
                'SimpleXMLElement',
                LIBXML_NOCDATA
            );
        } catch (\Exception $e) {
            return [self::$GenReturnXmlError, null];
        }
    }
}
