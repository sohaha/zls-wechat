<?php

namespace Zls\WeChat;

/*
 * WeChat
 * @author      影浅-Seekwe
 * @email       seekwe@gmail.com
 *              Date:        17/1/28
 *              Time:        20:27
 */

use Closure;
use Z;
use Zls_Exception_Exit;

class Pay implements WxInterface
{
    const API_UNIFIEDORDER = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const API_UNIFIEDORDER_SANDBOX = 'https://api.mch.weixin.qq.com/sandboxnew/pay/unifiedorder';
    const API_ORDERQUERY = 'https://api.mch.weixin.qq.com/pay/orderquery';
    const API_ORDERQUERY_SANDBOX = 'https://api.mch.weixin.qq.com/sandboxnew/pay/orderquery';
    const API_ORDERQUERY_QY = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/queryworkwxredpack';
    const API_ORDERQUERY_QY_SANDBOX = 'https://api.mch.weixin.qq.com/sandboxnew/mmpaymkttransfers/queryworkwxredpack';
    const API_CLOSEORDER = 'https://api.mch.weixin.qq.com/pay/closeorder';
    const API_CLOSEORDER_SANDBOX = 'https://api.mch.weixin.qq.com/sandboxnew/pay/closeorder';
    const API_REFUND = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    const API_REFUND_SANDBOX = 'https://api.mch.weixin.qq.com/sandboxnew/pay/refund';
    const API_REFUNDQUERY = 'https://api.mch.weixin.qq.com/pay/refundquery';
    const API_REFUNDQUERY_SANDBOX = 'https://api.mch.weixin.qq.com/sandboxnew/pay/refundquery';

    const API_SEND = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
    const API_SEND_QY = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendworkwxredpack';

    /** @var Main $WX */
    public $WX;

    private $CONFIG = [
        'mch_id'    => '',
        'key'       => '',
        'cert_path' => '',
        'key_path'  => '',
        'sandbox'   => false,
    ];

    /**
     * Zls_WeChat_Pay constructor.
     *
     * @param $wx
     */
    public function __construct(Main $wx)
    {
        $this->WX = $wx;
    }

    /**
     * 商户配置.
     *
     * @param array $config
     */
    public function init(array $config = [])
    {
        if (!$config) {
            $config = Z::config('wechat', true, []);
            foreach ($config as $k => $v) {
                if (!Z::strBeginsWith($k, 'pay')) {
                    continue;
                }
                $k                = Z::strCamel2Snake(substr($k, 3));
                $this->CONFIG[$k] = $v;
            }
        } else {
            foreach ($config as $k => $v) {
                $k                = Z::strCamel2Snake($k);
                $this->CONFIG[$k] = $v;
            }
        }
        if ($this->isSandbox()) {
            $this->CONFIG ['key'] = $this->getSandboxSignkey();
        }
    }

    /**
     * 是否沙盒
     * @desc 测试用例 https://pay.weixin.qq.com/wiki/doc/api/download/mczyscsyl.pdf
     *
     * @param bool|string $yes
     * @param bool|string $no
     *
     * @return bool
     */
    public function isSandbox($yes = true, $no = false)
    {
        return !!$this->CONFIG['sandbox'] ? $yes : $no;
    }

    public function getSandboxSignkey()
    {
        $mchId = $this->CONFIG['mch_id'];

        return Z::cacheDate($this->WX->getUniqueKey('_sandbox_signkey_' . $mchId), function () use ($mchId) {
            $data         = [
                'mch_id'    => $mchId,
                'key'       => $this->CONFIG['key'],
                'sign_type' => 'MD5',
                'nonce_str' => $this->WX->generateNonceStr(16),
            ];
            $data['sign'] = $this->sign($data, $data['sign_type']);
            $res          = $this->post('https://api.mch.weixin.qq.com/sandboxnew/pay/getsignkey', $data, true);

            return $res ? Z::arrayGet($res, 'sandbox_signkey') : '';
        });
    }

    /**
     * 统一下单.
     * @desc 拿到prepay_id, 沙盒只支持指定金额, 如: 552,101, https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=23_13
     *
     * @param string|array $notify_url 通知URL地址|参数组
     * @param string       $outTradeNo 商户订单号
     * @param string       $openid     openid
     * @param string       $totalFee   标价金额
     * @param string       $body       商品描述
     * @param array        $parameter  更多参数
     *
     * @return array|bool
     */
    public function unifiedOrder($notify_url, $openid, $totalFee, $outTradeNo, $body, $parameter = [])
    {
        $data = [
            'appid'            => $this->WX->getAppid(),
            'device_info'      => 'WEB',
            'mch_id'           => $this->CONFIG['mch_id'],
            'nonce_str'        => $this->WX->generateNonceStr(16),
            'sign_type'        => 'MD5',
            'body'             => $body,
            'out_trade_no'     => $outTradeNo,
            'fee_type'         => 'CNY',
            'total_fee'        => $totalFee,
            'spbill_create_ip' => Z::clientIp(),
            'trade_type'       => 'JSAPI',
            'openid'           => $openid,
        ];
        if (!is_array($notify_url)) {
            $data['notify_url'] = $notify_url;
            $data               = array_merge($data, $parameter);
        } else {
            $data = array_merge($data, $notify_url);
        }
        $data['sign'] = $this->sign($data, Z::arrayGet($data, 'sign_type', 'MD5'));

        return Z::tap($this->post($this->isSandbox(self::API_UNIFIEDORDER_SANDBOX, self::API_UNIFIEDORDER), $data), function (&$res) {
            if (!$res) {
                $err = $this->WX->getError()['msg'];
                if (Z::strEndsWith($err, '无效，请检查需要验收的case')) {
                    $this->WX->setError(211, '沙盒只支持指定金额, 如: 101 https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=23_13', true);
                }
            }
        });
    }

    /**
     * 签名.
     *
     * @param array  $arrdata
     * @param string $signType 签名类型
     *
     * @return string
     * @todo 后续要支持md5以外的加密方式
     */
    public function sign(&$arrdata = [], $signType = 'MD5')
    {
        ksort($arrdata);
        $paramstring = '';
        foreach ($arrdata as $key => $value) {
            if (0 == strlen($paramstring)) {
                $paramstring .= $key . '=' . $value;
            } else {
                $paramstring .= '&' . $key . '=' . $value;
            }
        }
        $paramstring = $paramstring . '&key=' . $this->CONFIG['key'];
        $sign        = ('MD5' === $signType) ? strtoupper(md5($paramstring)) : $paramstring;

        return $sign;
    }

    /**
     * 发起请求
     *
     * @param $url
     * @param $data
     * @param $xml
     *
     * @return array|bool|mixed|string
     */
    private function post($url, $data, $xml = true)
    {
        if ($xml) {
            $data = $this->toXml($data);
        }
        $result = $this->WX->request($url, $data, 'post', 'xml', 'xml');
        if ($xml) {
            $result = $this->FromXml($result);
        }
        $errorCode = Z::arrayGet($result, 'result_code', Z::arrayGet($result, 'return_code', 'failed'));
        if ('SUCCESS' === $errorCode) {
            return $result;
        } else {
            $des      = Z::arrayGet($result, 'err_code_des');
            $errorMsg = $des ? Util::payErrorCode($des) : Z::arrayGet($result, 'return_msg', '请求接口失败');
            $this->WX->setError(211, $errorMsg);

            return false;
        }
    }

    /**
     * 输出xml.
     *
     * @param $array
     *
     * @return string
     */
    private function toXml($array)
    {
        $xml = '<xml>';
        foreach ($array as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * 将xml转为array.
     *
     * @param string $xml
     *
     * @return array|bool
     */
    private function FromXml($xml)
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        return $data ? (array)$data : false;
    }

    /**
     * 查询订单.
     *
     * @param string $transactionId 微信订单号
     * @param string $outTradeNo    商户订单号
     * @param string $signType      签名类型
     *
     * @return array|bool
     */
    public function orderquery($transactionId = '', $outTradeNo = '', $signType = 'MD5')
    {
        if (!$outTradeNo && !$transactionId) {
            $this->WX->setError(-1, 'out_trade_no、transaction_id至少填一个！');

            return false;
        }
        $data = [
            'mch_id'    => $this->CONFIG['mch_id'],
            'appid'     => $this->WX->getAppid(),
            'nonce_str' => $this->WX->generateNonceStr(32),
            'sign_type' => $signType,
        ];
        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }
        if ($outTradeNo) {
            $data['out_trade_no'] = $outTradeNo;
        }
        $data['sign'] = $this->sign($data, $signType);
        $api          = !$this->WX->getAgentid() ? $this->isSandbox(self::API_ORDERQUERY_SANDBOX, self::API_ORDERQUERY) : $this->isSandbox(self::API_ORDERQUERY_QY_SANDBOX, self::API_ORDERQUERY_QY);

        return $this->post($api, $data);
    }

    /**
     * 关闭订单.
     *
     * @param string $outTradeNo 商户订单号
     * @param string $signType   签名类型
     *
     * @return array|bool
     */
    public function closeorder($outTradeNo = '', $signType = 'MD5')
    {
        $data = [
            'out_trade_no' => $outTradeNo,
            'mch_id'       => $this->CONFIG['mch_id'],
            'appid'        => $this->WX->getAppid(),
            'nonce_str'    => $this->WX->generateNonceStr(32),
            'sign_type'    => $signType,
        ];
        if ($outTradeNo) {
            $data['out_trade_no'] = $outTradeNo;
        }
        $data['sign'] = $this->sign($data, $signType);

        return $this->post($this->isSandbox(self::API_CLOSEORDER_SANDBOX, self::API_CLOSEORDER), $data);
    }

    /**
     * 申请退款.
     *
     * @param int        $totalFee      订单总金额，单位为分，只能为整数
     * @param int        $refundFee     退款金额
     * @param int|string $outRefundNo   商户退款单号
     * @param string     $transactionId 微信订单号
     * @param string     $outTradeNo    商户订单号
     * @param array      $parameter     更多字段
     *
     * @return array|bool
     */
    public function refund($totalFee, $refundFee = 0, $outRefundNo = '', $transactionId = '', $outTradeNo = '', array $parameter = [])
    {
        $mchId = $this->CONFIG['mch_id'];
        $data  = [
            'mch_id'          => $mchId,
            'device_info'     => 'WEB',
            'nonce_str'       => $this->WX->generateNonceStr(),
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
            $this->WX->setError(-1, 'out_trade_no、transaction_id至少填一个！');

            return false;
        }
        if ($outTradeNo) {
            $data['out_trade_no'] = $outTradeNo;
        }
        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }
        $data['appid'] = $this->WX->getAppid();
        $this->WX->getHttp()->setSsl($this->CONFIG['cert_path'], $this->CONFIG['key_path']);
        $data['sign'] = $this->sign($data, $data['sign_type']);

        return $this->post($this->isSandbox(self::API_REFUND_SANDBOX, self::API_REFUND), $data);
    }

    /**
     * 查询退款.
     *
     * @param string|array $transactionId 微信订单号
     * @param string       $outTradeNo    商户订单号
     * @param string       $outRefundNo   商户退款单号
     * @param string       $refundId      微信退款单号
     * @param array        $parameter     更多参数
     *
     * @return array|bool
     */
    public function refundquery($transactionId = '', $outTradeNo = '', $outRefundNo = '', $refundId = '', array $parameter = [])
    {
        $data = [
            'appid'       => $this->WX->getAppid(),
            'device_info' => 'WEB',
            'nonce_str'   => $this->WX->generateNonceStr(),
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
        $data['mch_id'] = $this->CONFIG['mch_id'];
        $data['sign']   = $this->sign($data, $data['sign_type']);

        return $this->post($this->isSandbox(self::API_REFUNDQUERY_SANDBOX, self::API_REFUNDQUERY), $data);
    }

    /**
     * 支付结果通用通知.
     *
     * @param null|Closure $callback
     * @param bool         $die
     *
     * @throws Zls_Exception_Exit
     */
    public function notify($callback = null, $die = true)
    {
        $data      = Z::postRaw() ?: Z::post();
        $response  = $this->FromXml($data);
        $result    = ['return_code' => 'FAIL', 'return_msg' => '非法支付结果通用通知'];
        $errorCode = Z::arrayGet($response, 'return_code', '211');
        if ($response) {
            if ('SUCCESS' == $errorCode) {
                //开始签名验证
                $data = $response;
                unset($data['sign']);
                $sign = $this->sign($data, Z::arrayGet($data, 'sign_type', 'MD5'));
                if ($sign == $response['sign']) {
                    $result = ['return_code' => 'SUCCESS', 'return_msg' => 'ok'];
                } else {
                    $result['return_msg'] = '签名不一致,本地:' . $sign . ',微信:' . $response['sign'];
                }
            } else {
                $result['return_msg'] = $response['return_msg'];
            }
        }
        if ($callback instanceof Closure) {
            $callback($response, $result);
        }
        $result = $this->toXml($result);
        if (true === $die) {
            Z::end($result);
        }
    }

    /**
     * 微信页面支付签名.
     *
     * @param string|array $prepay_id 预付ID|参数组
     *
     * @return array
     */
    public function jsSign($prepay_id)
    {
        if (!is_array($prepay_id)) {
            $data = [
                'signType'  => 'MD5',
                'timeStamp' => time(),
                'nonceStr'  => $this->WX->generateNonceStr(),
                'package'   => 'prepay_id=' . $prepay_id,
            ];
        } else {
            $data = $prepay_id;
        }
        $data['appId']   = $this->WX->getAppid();
        $sign            = $this->sign($data, Z::arrayGet($data, 'signType', 'MD5'));
        $data['paySign'] = $sign;

        return $data;
    }

    /**
     * 发送企业红包
     * //todo 开发中.
     *
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
     *
     * @return array|bool|mixed|string
     */
    public function sendQyHb($total_amount, $openid, $mch_billno, $send_name, $act_name, $wishing, $remark, $scene_id = null, $total_num = 1, $more = [])
    {
        $data = [
            'nonce_str'    => $this->WX->generateNonceStr(),
            'mch_billno'   => $mch_billno, //商户订单号
            'send_name'    => $send_name, //商户名称
            're_openid'    => $openid, //用户openid
            'total_amount' => $total_amount, //付款金额
            'total_num'    => $total_num, //红包发放总人数
            'wishing'      => $wishing, //红包祝福语
            'client_ip'    => Z::clientIp(), //Ip地址
            'act_name'     => $act_name, //活动名称
            'remark'       => $remark, //备注
        ];
        if ($scene_id) {
            $data['scene_id'] = $scene_id; //场景id PRODUCT_2
        }
        if (is_array($total_amount)) {
            $data = array_merge($data, $total_amount);
        }
        $data['mch_id']  = $this->CONFIG['mch_id'];
        $data['wxappid'] = $this->WX->getAppid();
        $data['sign']    = $this->sign($data);
        if ($more) {
            $data = array_merge($data, $more);
        }
        $this->WX->getHttp()->setSsl($this->CONFIG['cert_path'], $this->CONFIG['key_path']);

        return $this->post(self::API_SEND_QY, $data);
    }

    /**
     * 发送红包.
     *
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
     *
     * @return array|bool|mixed|string
     */
    public function sendHb($total_amount, $openid, $mch_billno, $send_name, $act_name, $wishing, $remark, $scene_id = null, $total_num = 1, $more = [])
    {
        $data = [
            'nonce_str'    => $this->WX->generateNonceStr(),
            'mch_billno'   => $mch_billno, //商户订单号
            'send_name'    => $send_name, //商户名称
            're_openid'    => $openid, //用户openid
            'total_amount' => $total_amount, //付款金额
            'total_num'    => $total_num, //红包发放总人数
            'wishing'      => $wishing, //红包祝福语
            'client_ip'    => Z::clientIp(), //Ip地址
            'act_name'     => $act_name, //活动名称
            'remark'       => $remark, //备注
        ];
        if ($scene_id) {
            $data['scene_id'] = $scene_id; //场景id PRODUCT_2
        }
        if (is_array($total_amount)) {
            $data = array_merge($data, $total_amount);
        }
        $data['mch_id']  = $this->CONFIG['mch_id'];
        $data['wxappid'] = $this->WX->getAppid();
        $data['sign']    = $this->sign($data);
        if ($more) {
            $data = array_merge($data, $more);
        }
        $this->WX->getHttp()->setSsl($this->CONFIG['cert_path'], $this->CONFIG['key_path']);

        return $this->post(self::API_SEND, $data);
    }

    /**
     * 发送裂变红包.
     *
     * @param string $total_amount 付款金额
     * @param string $openid       用户openid
     * @param string $mch_billno   商户订单号
     * @param string $send_name    商户名称
     * @param string $act_name     活动名称
     * @param string $wishing      红包祝福语
     * @param string $scene_id     场景id
     * @param string $remark       备注
     * @param int    $total_num    红包发放总人数
     *
     * @return array|bool|mixed|string
     */
    public function sendGroupHb($total_amount, $openid, $mch_billno, $send_name, $act_name, $wishing, $remark, $scene_id = 'PRODUCT_2', $total_num = 1)
    {
        $data = [
            'nonce_str'    => $this->WX->generateNonceStr(),
            'mch_billno'   => $mch_billno, //商户订单号
            'send_name'    => $send_name, //商户名称
            're_openid'    => $openid, //用户openid
            'total_amount' => $total_amount, //付款金额
            'total_num'    => $total_num, //红包发放总人数
            'wishing'      => $wishing, //红包祝福语
            'client_ip'    => Z::clientIp(), //Ip地址
            'act_name'     => $act_name, //活动名称
            'remark'       => $remark, //备注
            'scene_id'     => $scene_id, //场景id
        ];
        if (is_array($total_amount)) {
            $data = array_merge($data, $total_amount);
        }
        $data['mch_id']  = $this->CONFIG['mch_id'];
        $data['wxappid'] = $this->WX->getAppid();
        $data['sign']    = $this->sign($data);
        $this->WX->getHttp()->setSsl($this->CONFIG['cert_path'], $this->CONFIG['key_path']);

        return $this->post('https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack', $data);
    }

    /**
     * 获取红包详情.
     *
     * @param string $mch_billno 商户订单号
     *
     * @return array|bool|mixed|string
     */
    public function getHbInfo($mch_billno)
    {
        $data = [
            'nonce_str'  => $this->WX->generateNonceStr(),
            'mch_billno' => $mch_billno,
            'bill_type'  => 'MCHT',
        ];
        if (is_array($mch_billno)) {
            $data = array_merge($data, $mch_billno);
        }
        $data['mch_id'] = $this->CONFIG['mch_id'];
        $data['appid']  = $this->WX->getAppid();
        $data['sign']   = $this->sign($data);
        $this->WX->getHttp()->setSsl($this->CONFIG['cert_path'], $this->CONFIG['key_path']);

        return $this->post('https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo', $data);
    }
}
