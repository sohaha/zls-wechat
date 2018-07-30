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

class Pay implements WxInterface
{
    const API_ORDERQUERY = 'https://api.mch.weixin.qq.com/pay/orderquery';
    const API_ORDERQUERY_QY = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/queryworkwxredpack';

    const API_SEND = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
    const API_SEND_QY = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendworkwxredpack';

    /** @var Main $WX */
    private static $WX;

    private static $CONFIG = [
        'mch_id'   => '',
        'key'      => '',
        'certPath' => '',
        'keyPath'  => '',
    ];

    /**
     * Zls_WeChat_Pay constructor.
     * @param $wx
     */
    public function __construct(Main $wx)
    {
        self::$WX = $wx;
    }

    /**
     * 商户配置
     * @param array $config
     */
    public function init(array $config = [])
    {
        self::$CONFIG = array_merge(self::$CONFIG, $config);
    }

    /**
     * 统一下单
     * @desc 拿到prepay_id
     * @param string|array $notify_url 通知URL地址|参数组
     * @param string       $outTradeNo 商户订单号
     * @param string       $openid     openid
     * @param string       $totalFee   标价金额
     * @param string       $body       商品描述
     * @param array        $parameter  更多参数
     * @return array|bool
     */
    public function unifiedOrder($notify_url, $openid, $totalFee, $outTradeNo, $body, $parameter = [])
    {
        $data = [
            'appid'            => self::$WX->getAppid(),
            'device_info'      => 'WEB',
            'mch_id'           => self::$CONFIG['mch_id'],
            'nonce_str'        => self::$WX->generateNonceStr(16),
            'sign_type'        => 'MD5',
            'body'             => $body,
            'out_trade_no'     => $outTradeNo,
            'fee_type'         => 'CNY',
            'total_fee'        => $totalFee,
            'spbill_create_ip' => z::clientIp(),
            'trade_type'       => 'JSAPI',
            'openid'           => $openid,
        ];
        if (!is_array($notify_url)) {
            $data['notify_url'] = $notify_url;
            $data = array_merge($data, $parameter);
        } else {
            $data = array_merge($data, $notify_url);
        }
        $data['sign'] = $this->sign($data, z::arrayGet($data, 'sign_type', 'MD5'));

        return $this->post('https://api.mch.weixin.qq.com/pay/unifiedorder', $data);
    }

    /**
     * 签名
     * @param array  $arrdata
     * @param string $signType 签名类型
     * @todo 后续要支持md5以外的加密方式
     * @return string
     */
    public function sign(&$arrdata = [], $signType = 'MD5')
    {
        ksort($arrdata);
        $paramstring = "";
        foreach ($arrdata as $key => $value) {
            if (strlen($paramstring) == 0) {
                $paramstring .= $key . "=" . $value;
            } else {
                $paramstring .= "&" . $key . "=" . $value;
            }
        }
        $paramstring = $paramstring . '&key=' . self::$CONFIG['key'];
        $sign = ($signType === 'MD5') ? strtoupper(md5($paramstring)) : $paramstring;

        return $sign;
    }

    /**
     * 发起请求
     * @param $url
     * @param $data
     * @param $xml
     * @return array|bool|mixed|String
     */
    private function post($url, $data, $xml = true)
    {
        if ($xml) {
            $data = $this->toXml($data);
        }
        $result = self::$WX->request($url, $data, 'post', 'xml');
        if ($xml) {
            $result = $this->FromXml($result);
        }
        $errorCode = z::arrayGet($result, 'result_code', 'failed');
        if ($errorCode === 'SUCCESS') {
            return $result;
        } else {
            $errorMsg = z::arrayGet($result, 'err_code_des') ?: z::arrayGet($result, 'return_msg', '请求接口失败');
            self::$WX->setError(201, $errorMsg);

            return false;
        }
    }

    /**
     * 输出xml
     * @param $array
     * @return string
     */
    private function toXml($array)
    {
        $xml = "<xml>";
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @return array|bool
     */
    private function FromXml($xml)
    {
        $data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        return $data ? (array)$data : false;
    }

    /**
     * 查询订单
     * @param string $transactionId 微信订单号
     * @param string $outTradeNo    商户订单号
     * @param string $signType      签名类型
     * @return array|bool
     */
    public function orderquery($transactionId = '', $outTradeNo = '', $signType = 'MD5')
    {
        if (!$outTradeNo && !$transactionId) {
            self::$WX->setError(-1, 'out_trade_no、transaction_id至少填一个！');

            return false;
        }
        $data = [
            'mch_id'    => self::$CONFIG['mch_id'],
            'appid'     => self::$WX->getAppid(),
            'nonce_str' => self::$WX->generateNonceStr(32),
            'sign_type' => $signType,
        ];
        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }
        if ($outTradeNo) {
            $data['out_trade_no'] = $outTradeNo;
        }
        $data['sign'] = $this->sign($data, $signType);
        $api = !self::$WX->getAgentid() ? self::API_ORDERQUERY : self::API_ORDERQUERY_QY;

        return $this->post($api, $data);
    }

    /**
     * 关闭订单
     * @param string $outTradeNo 商户订单号
     * @param string $signType   签名类型
     * @return array|bool
     */
    public function closeorder($outTradeNo = '', $signType = 'MD5')
    {
        $data = [
            'out_trade_no' => $outTradeNo,
            'mch_id'       => self::$CONFIG['mch_id'],
            'appid'        => self::$WX->getAppid(),
            'nonce_str'    => self::$WX->generateNonceStr(32),
            'sign_type'    => $signType,
        ];
        if ($outTradeNo) {
            $data['out_trade_no'] = $outTradeNo;
        }
        $data['sign'] = $this->sign($data, $signType);

        return $this->post('https://api.mch.weixin.qq.com/pay/closeorder', $data);
    }

    /**
     * 申请退款
     * @param int        $totalFee      订单总金额，单位为分，只能为整数
     * @param int        $refundFee     退款金额
     * @param int|string $outRefundNo   商户退款单号
     * @param string     $transactionId 微信订单号
     * @param string     $outTradeNo    商户订单号
     * @param array      $parameter     更多字段
     * @return array|bool
     */
    public function refund($totalFee, $refundFee = 0, $outRefundNo = '', $transactionId = '', $outTradeNo = '', array $parameter = [])
    {
        $mchId = self::$CONFIG['mch_id'];
        $data = [
            'mch_id'          => $mchId,
            'device_info'     => 'WEB',
            'nonce_str'       => self::$WX->generateNonceStr(),
            'sign_type'       => 'MD5',
            'out_refund_no'   => $outRefundNo,
            'total_fee'       => $totalFee,
            'refund_fee'      => $refundFee,
            'refund_fee_type' => 'CNY',
            'op_user_id'      => $mchId,
        ];
        if (!is_array($totalFee)) {
            $data = array_merge($data, $parameter);
        } else {
            $data = array_merge($data, $totalFee);
        }
        if (!$outTradeNo && !$transactionId) {
            self::$WX->setError(-1, 'out_trade_no、transaction_id至少填一个！');

            return false;
        }
        if ($outTradeNo) {
            $data['out_trade_no'] = $outTradeNo;
        }
        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }
        $data['appid'] = self::$WX->getAppid();
        self::$WX->getHttp()->setSsl(self::$CONFIG['certPath'], self::$CONFIG['keyPath']);
        $data['sign'] = $this->sign($data, $data['sign_type']);

        return $this->post('https://api.mch.weixin.qq.com/secapi/pay/refund', $data);
    }

    /**
     * 查询退款
     * @param string|array $transactionId 微信订单号
     * @param string       $outTradeNo    商户订单号
     * @param string       $outRefundNo   商户退款单号
     * @param string       $refundId      微信退款单号
     * @param array        $parameter     更多参数
     * @return array|bool
     */
    public function refundquery($transactionId, $outTradeNo = '', $outRefundNo = '', $refundId = '', array $parameter = [])
    {
        $data = [
            'appid'       => self::$WX->getAppid(),
            'device_info' => 'WEB',
            'nonce_str'   => self::$WX->generateNonceStr(),
            'sign_type'   => 'MD5',
        ];
        if (!is_array($transactionId)) {
            if ($outTradeNo) {
                $data['out_trade_no'] = $outTradeNo;
            }
            if ($transactionId) {
                $data['transaction_id'] = $transactionId;
            }
            if ($outRefundNo) {
                $data['out_refund_no'] = $outRefundNo;
            }
            if ($refundId) {
                $data['refund_id'] = $refundId;
            }
            $data = array_merge($data, $parameter);
        } else {
            $data = array_merge($data, $transactionId);
        }
        $data['mch_id'] = self::$CONFIG['mch_id'];
        $data['sign'] = $this->sign($data, $data['sign_type']);
        z::dump($data);

        return $this->post('https://api.mch.weixin.qq.com/pay/refundquery', $data);
    }

    /**
     * 支付结果通用通知
     * @param null|\Closure $callback
     * @param bool          $die
     * @return void
     * @throws \Exception
     */
    public function notify($callback = null, $die = true)
    {
        $data = z::postRaw() ?: z::post();
        $response = $this->FromXml($data);
        $result = ['return_code' => 'FAIL', 'return_msg' => '非法支付结果通用通知'];
        $errorCode = z::arrayGet($response, 'return_code', '201');
        if ($response) {
            if ($errorCode == 'SUCCESS') {
                //开始签名验证
                $data = $response;
                unset($data['sign']);
                $sign = $this->sign($data, z::arrayGet($data, 'sign_type', 'MD5'));
                if ($sign == $response['sign']) {
                    $result = ['return_code' => 'SUCCESS', 'return_msg' => 'ok'];
                } else {
                    $result['return_msg'] = '签名不一致,本地:' . $sign . ',微信:' . $response['sign'];
                }
            } else {
                $result['return_msg'] = $response['return_msg'];
            }
        }
        if ($callback instanceof \Closure) {
            $callback($response, $result);
        }
        $result = $this->toXml($result);
        if ($die === true) {
            z::finish($result);
        }
    }


    /**
     * 微信页面支付签名
     * @param string|array $prepay_id 预付ID|参数组
     * @return array
     */
    public function jsSign($prepay_id)
    {
        if (!is_array($prepay_id)) {
            $data = [
                'signType'  => 'MD5',
                'timeStamp' => '1492053232',//time(),
                'nonceStr'  => self::$WX->generateNonceStr(),
                'package'   => 'prepay_id=' . $prepay_id,
            ];
        } else {
            $data = $prepay_id;
        }
        $data['appId'] = self::$WX->getAppid();
        $sign = $this->sign($data, z::arrayGet($data, 'signType', 'MD5'));
        $data['paySign'] = $sign;

        return $data;
    }

    /**
     * 发送企业红包
     * //todo 开发中
     * @param string $total_amount 付款金额
     * @param string $openid       用户openid
     * @param string $mch_billno   商户订单号
     * @param string $send_name    商户名称
     * @param string $act_name     活动名称
     * @param string $wishing      红包祝福语
     * @param string $remark       备注
     * @param string $scene_id     场景id
     * @param int    $total_num    红包发放总人数
     * @param array  $more
     * @return array|bool|mixed|String
     */
    public function sendQyHb($total_amount, $openid, $mch_billno, $send_name, $act_name, $wishing, $remark, $scene_id = null, $total_num = 1, $more = [])
    {
        $data = [
            'nonce_str'    => self::$WX->generateNonceStr(),
            'mch_billno'   => $mch_billno,//商户订单号
            'send_name'    => $send_name,//商户名称
            're_openid'    => $openid,//用户openid
            'total_amount' => $total_amount,//付款金额
            'total_num'    => $total_num,//红包发放总人数
            'wishing'      => $wishing,//红包祝福语
            'client_ip'    => z::clientIp(),//Ip地址
            'act_name'     => $act_name,//活动名称
            'remark'       => $remark,//备注
        ];
        if ($scene_id) {
            $data['scene_id'] = $scene_id;//场景id PRODUCT_2
        }
        if (is_array($total_amount)) {
            $data = array_merge($data, $total_amount);
        }
        $data['mch_id'] = self::$CONFIG['mch_id'];
        $data['wxappid'] = self::$WX->getAppid();
        $data['sign'] = $this->sign($data);
        if ($more) {
            $data = array_merge($data, $more);
        }
        self::$WX->getHttp()->setSsl(self::$CONFIG['certPath'], self::$CONFIG['keyPath']);

        return $this->post(self::API_SEND_QY, $data);
    }

    /**
     * 发送红包
     * @param string $total_amount 付款金额
     * @param string $openid       用户openid
     * @param string $mch_billno   商户订单号
     * @param string $send_name    商户名称
     * @param string $act_name     活动名称
     * @param string $wishing      红包祝福语
     * @param string $remark       备注
     * @param string $scene_id     场景id
     * @param int    $total_num    红包发放总人数
     * @param array  $more
     * @return array|bool|mixed|String
     */
    public function sendHb($total_amount, $openid, $mch_billno, $send_name, $act_name, $wishing, $remark, $scene_id = null, $total_num = 1, $more = [])
    {
        $data = [
            'nonce_str'    => self::$WX->generateNonceStr(),
            'mch_billno'   => $mch_billno,//商户订单号
            'send_name'    => $send_name,//商户名称
            're_openid'    => $openid,//用户openid
            'total_amount' => $total_amount,//付款金额
            'total_num'    => $total_num,//红包发放总人数
            'wishing'      => $wishing,//红包祝福语
            'client_ip'    => z::clientIp(),//Ip地址
            'act_name'     => $act_name,//活动名称
            'remark'       => $remark,//备注
        ];
        if ($scene_id) {
            $data['scene_id'] = $scene_id;//场景id PRODUCT_2
        }
        if (is_array($total_amount)) {
            $data = array_merge($data, $total_amount);
        }
        $data['mch_id'] = self::$CONFIG['mch_id'];
        $data['wxappid'] = self::$WX->getAppid();
        $data['sign'] = $this->sign($data);
        if ($more) {
            $data = array_merge($data, $more);
        }
        self::$WX->getHttp()->setSsl(self::$CONFIG['certPath'], self::$CONFIG['keyPath']);

        return $this->post(self::API_SEND, $data);
    }

    /**
     * 发送裂变红包
     * @param string $total_amount 付款金额
     * @param string $openid       用户openid
     * @param string $mch_billno   商户订单号
     * @param string $send_name    商户名称
     * @param string $act_name     活动名称
     * @param string $wishing      红包祝福语
     * @param string $scene_id     场景id
     * @param string $remark       备注
     * @param int    $total_num    红包发放总人数
     * @return array|bool|mixed|String
     */
    public function sendGroupHb($total_amount, $openid, $mch_billno, $send_name, $act_name, $wishing, $remark, $scene_id = 'PRODUCT_2', $total_num = 1)
    {
        $data = [
            'nonce_str'    => self::$WX->generateNonceStr(),
            'mch_billno'   => $mch_billno,//商户订单号
            'send_name'    => $send_name,//商户名称
            're_openid'    => $openid,//用户openid
            'total_amount' => $total_amount,//付款金额
            'total_num'    => $total_num,//红包发放总人数
            'wishing'      => $wishing,//红包祝福语
            'client_ip'    => z::clientIp(),//Ip地址
            'act_name'     => $act_name,//活动名称
            'remark'       => $remark,//备注
            'scene_id'     => $scene_id//场景id
        ];
        if (is_array($total_amount)) {
            $data = array_merge($data, $total_amount);
        }
        $data['mch_id'] = self::$CONFIG['mch_id'];
        $data['wxappid'] = self::$WX->getAppid();
        $data['sign'] = $this->sign($data);
        self::$WX->getHttp()->setSsl(self::$CONFIG['certPath'], self::$CONFIG['keyPath']);

        return $this->post('https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack', $data);
    }

    /**
     * 获取红包详情
     * @param string $mch_billno 商户订单号
     * @return array|bool|mixed|String
     */
    public function getHbInfo($mch_billno)
    {
        $data = [
            'nonce_str'  => self::$WX->generateNonceStr(),
            'mch_billno' => $mch_billno,
            'bill_type'  => 'MCHT',
        ];
        if (is_array($mch_billno)) {
            $data = array_merge($data, $mch_billno);
        }
        $data['mch_id'] = self::$CONFIG['mch_id'];
        $data['appid'] = self::$WX->getAppid();
        $data['sign'] = $this->sign($data);
        self::$WX->getHttp()->setSsl(self::$CONFIG['certPath'], self::$CONFIG['keyPath']);

        return $this->post('https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo', $data);
    }
}
