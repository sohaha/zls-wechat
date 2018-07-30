<?php

namespace Zls\WeChat;

/**
 * 企业号
 * @author        影浅
 * @email         seekwe@gmail.com
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 * @link          ---
 * @since         v0.0.1
 * @updatetime    2017-6-22 14:07:22
 */
use Z;

class Qy implements WxInterface
{
    private $wx;

    public function __construct(Main $wx)
    {
        $this->wx = &$wx;
    }

    public function getAgentid()
    {
        return $this->wx->getAgentid();
    }

    public function setAgentid($agentid)
    {
        $this->wx->setAgentid($agentid);

        return $this->wx;
    }


    public function getAccessToken()
    {
        $wx = $this->wx;
        $corpid = $wx->getAppid();
        $corpsecret = $wx->getAppsecret();

        return $wx->get($wx::QYAPIURL . '/cgi-bin/gettoken?corpid=' . $corpid . '&corpsecret=' . $corpsecret);
    }

    public function getJsapiTicket()
    {
        $wx = $this->wx;

        return $wx->get($wx::QYAPIURL . '/cgi-bin/get_jsapi_ticket?access_token=' . $wx->getAccessToken());
    }

    /**
     * 获取应用概况列表
     * @param int $time
     * @return mixed
     */
    public function getAgentList($time = 60)
    {
        $wx = $this->wx;

        return $wx->cache(__METHOD__, function () use ($wx) {
            return $wx->get($wx::QYAPIURL . '/cgi-bin/agent/list?access_token=' . $wx->getAccessToken());
        }, $time);
    }


    /**
     * 根据code获取成员信息
     * @param $authCode
     * @return array|bool
     */
    public function getAuthAccessToken($authCode)
    {
        $wx = $this->wx;

        return z::tap($wx->post($wx::QYAPIURL . '/cgi-bin/user/getuserinfo?access_token=' . $wx->getAccessToken() . '&code=' . $authCode), function (&$result) {
            $openid = z::arrayGet($result, 'Openid');
            if (!!$openid) {
                $result['openid'] = $openid;
            }
        });
    }

    /**
     * 获取用户详情
     * @param null $userTicket
     * @return bool|mixed|String
     */
    public function getAuthUserInfo($userTicket = null)
    {
        $wx = $this->wx;
        $authData = $wx->getAuthAccessToken();
        if (!$userTicket) {
            $userTicket = z::arrayGet($authData, 'user_ticket');
        }

        return z::tap($this->wx->post($wx::QYAPIURL . '/cgi-bin/user/getuserdetail?access_token=' . $wx->getAccessToken(), ['user_ticket' => $userTicket]), function ($resule) use ($wx, $userTicket, $authData) {
            if (!$resule) {
                $wx->setError(z::arrayGet($wx->getError(true), 'code'), '该用户不在企业应用可见范围之内');
            }
        });
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

        return $wx::QYOPENURL . '/connect/oauth2/authorize?appid=' . $wx->getAppid() . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope . '&agentid=' . $wx->getAgentid() . '&state=' . $state . '#wechat_redirect';
    }
}
