<?php

namespace Zls\WeChat;

/**
 * WeChat
 * @author      影浅-Seekwe
 * @email       seekwe@gmail.com
 *              Date:        17/1/28
 *              Time:        20:27
 */
use Z;

class Card implements WxInterface
{
    /** @var  Main $WX */
    private static $WX;

    /**
     * Zls_WeChat_Pay constructor.
     * @param $wx
     */
    public function __construct(Main $wx)
    {
        self::$WX = $wx;
    }

    /**
     * 卡券签名
     * @param        $cardId
     * @param string $openid
     * @param string $code
     * @param string $outerStr
     * @param string $fixedBegintimestamp
     * @return mixed
     * @throws \Zls_Exception_500
     */
    public function cardExt($cardId, $openid = '', $code = '', $outerStr = 'web', $fixedBegintimestamp = '')
    {
        $data['card_id'] = $cardId;
        $data['api_ticket'] = $this->getAccessToken();
        $data['timestamp'] = time();
        $data['nonce_str'] = self::$WX->generateNonceStr();
        $data['code'] = $code;
        $data['openid'] = $openid;
        //$data['outer_str'] = $outerStr;
        //$data['fixed_begintimestamp'] = $fixedBegintimestamp;
        $signature = $this->getCardSign($data, 'sha1');
        $data['signature'] = $signature;

        return $data;
    }

    /**
     * @return mixed
     * @throws \Zls_Exception_500
     */
    public function getAccessToken()
    {
        $appid = self::$WX->getAppid();
        $cacheKey = 'WeChat_Card_AccessToken' . $appid;
        if (!$access_token = z::cache()->get($cacheKey)) {
            $request = self::$WX->get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . self::$WX->getAccessToken() . '&type=wx_card');
            if ($access_token = z::arrayGet($request, 'ticket')) {
                z::cache()->set($cacheKey, $access_token, $request['expires_in'] - 100);
            } else {
                self::$WX->log('获取api_ticket失败', self::$WX->getError());
            }
        }

        return $access_token;
    }

    /**
     * 卡券签名cardSign
     * @param        $card
     * @param string $signType
     * @return bool
     */
    protected function getCardSign($card, $signType = 'sha1')
    {
        sort($card, SORT_STRING);
        $sign = $signType(implode($card));
        if (!$sign) {
            return false;
        }

        return $sign;
    }
}
