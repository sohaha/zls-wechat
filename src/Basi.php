<?php

namespace Zls\WeChat;

/**
 * Zls
 * @author        影浅
 * @email         seekwe@gmail.com
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 * @link          ---
 * @since         v0.0.1
 * @updatetime    2017-06-06 11:01
 */
use Z;

class Basi implements WxInterface
{
    private $wx;

    public function __construct(Main $wx)
    {
        $this->wx = &$wx;
    }


    public function getAccessToken()
    {
        $wx = $this->wx;
        return $wx->post($wx::APIURL . '/cgi-bin/token?grant_type=client_credential&appid=' . $wx->getAppid() . '&secret=' . $this->wx->getAppsecret());
    }

    /**
     * @return bool|mixed
     */
    public function getJsapiTicket()
    {
        $wx = $this->wx;
        return $wx->get($wx::APIURL . '/cgi-bin/ticket/getticket?&type=jsapi&access_token=' . $wx->getAccessToken());
    }

    /**
     * 拼接授权页
     * @param $callback
     * @param $state
     * @param $scope
     * @return string
     */
    public function getOauthRedirect($callback, $state, $scope)
    {
        $wx = $this->wx;
        $component = $wx->getComponentAppid() ? '&component_appid=' . $wx->getComponentAppid() : '';

        return $wx::OPENURL . '/connect/oauth2/authorize?' . 'appid=' . $wx->getAppid() . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . $component . '#wechat_redirect';
    }

    /**
     * 获取用户详情
     * @param null $openid
     * @return bool|mixed|String
     */
    public function getAuthUserInfo($openid = null)
    {
        $wx = $this->wx;
        $authData = $wx->getAuthAccessToken();
        if (!$openid) {
            $openid = z::arrayGet($authData, 'openid');
        }

        return $wx->get($wx::APIURL . '/sns/userinfo?access_token=' . z::arrayGet($authData,
                'access_token') . '&openid=' . $openid . '&lang=zh_CN');
    }

    /**
     * @param $authCode
     * @return array|bool
     */
    public function getAuthAccessToken($authCode)
    {
        $wx = $this->wx;
        $url = $wx->getComponentAppid() ? 'component/access_token?appid=' . $wx->getAppid() . '&code=' . $authCode . '&grant_type=authorization_code&component_appid=' . $wx->getComponentAppid() . '&component_access_token=' . $wx->getComponentAccessToken() : 'access_token?appid=' . $wx->getAppid() . '&secret=' . $wx->getAppsecret() . '&code=' . $authCode . '&grant_type=authorization_code';

        return $wx->post($wx::APIURL . '/sns/oauth2/' . $url);
    }
}
