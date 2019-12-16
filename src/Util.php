<?php

namespace Zls\WeChat;

/*
 * 错误码
 * 暂时整理,以后用
 * @author      影浅-Seekwe
 * @email       seekwe@gmail.com
 *              Date:        17/1/28
 *              Time:        20:27
 */

use Z;

class Util
{
    public $errCode = [
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

    public function checkSignature($token, $signature, $timestamp, $nonce)
    {
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return 0;
        } else {
            return -1;
        }
    }

    public function getXml($type = 'text', $data)
    {
        switch ($type) {
            case 'text':
                $template = $this->textTemplate($data);
                break;
            case 'news':
                $template = $this->newsTemplate($data);
                break;
            case 'image':
                $template = $this->imageTemplate($data);
                break;
            case 'video':
                $template = $this->videoTemplate($data);
                break;
            case 'music':
                $template = $this->musicTemplate($data);
                break;
            case 'voice':
                $template = $this->voiceTemplate($data);
                break;
            case 'transfer_kf':
                $template = $this->transferKfTemplate($data);
                break;
            default:
                $template = $this->textTemplate($data);
        }

        return $template;// vsprintf($template, $data);
    }

    public function textTemplate($data)
    {
        return vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>', $data);
    }

    public function newsTemplate($data = [])
    {
        $list     = array_pop($data);
        $len      = count($list);
        $template = vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>' . $len . '</ArticleCount>
<Articles>', $data);
        foreach ($list as $value) {
            $template .= '<item><Title><![CDATA[' . $value['title'] . ']]></Title>
<Description><![CDATA[' . $value['description'] . ']]></Description>
<PicUrl><![CDATA[' . $value['picurl'] . ']]></PicUrl>
<Url><![CDATA[' . $value['url'] . ']]></Url>
</item>';
        }
        $template .= '</Articles>
</xml>';

        return $template;
    }

    public function imageTemplate($data)
    {
        return vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[%s]]></MediaId>
</Image>
</xml>', $data);
    }

    public function videoTemplate($data)
    {
        return vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
<Video>
<MediaId><![CDATA[%s]]></MediaId>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
</Video>
</xml>', $data);
    }

    public function musicTemplate($data)
    {
        return vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
<Music>
<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<MusicUrl><![CDATA[%s]]></MusicUrl>
<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
</Music>
</xml>', $data);
    }

    public function voiceTemplate($data)
    {
        return vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
<Voice>
<MediaId><![CDATA[%s]]></MediaId>
</Voice>
</xml>', $data);
    }

    public function transferKfTemplate($data)
    {
        if (!z::arrayKeyExists(2, $data)) {
            $data[2] = '';
        }

        return vsprintf('<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>' . time() . '</CreateTime>
<MsgType><![CDATA[transfer_customer_service]]></MsgType>
<TransInfo>
<KfAccount><![CDATA[%s]]></KfAccount>
</TransInfo>
</xml>', $data);
    }

    public function payTemplate($data)
    {
    }

    public static function payErrorCode($code)
    {
        $errList = [
            'NOAUTH'                => '商户未开通此接口权限',
            'NOTENOUGH'             => '用户帐号余额不足',
            'ORDERNOTEXIST'         => '订单号不存在',
            'ORDERPAID'             => '商户订单已支付，无需重复操作',
            'ORDERCLOSED'           => '当前订单已关闭，无法支付',
            'SYSTEMERROR'           => '系统错误!系统超时',
            'APPID_NOT_EXIST'       => '参数中缺少APPID',
            'MCHID_NOT_EXIST'       => '参数中缺少MCHID',
            'APPID_MCHID_NOT_MATCH' => 'appid和mch_id不匹配',
            'LACK_PARAMS'           => '缺少必要的请求参数',
            'OUT_TRADE_NO_USED'     => '同一笔交易不能多次提交',
            'SIGNERROR'             => '参数签名结果不正确',
            'XML_FORMAT_ERROR'      => 'XML格式错误',
            'REQUIRE_POST_METHOD'   => '未使用post传递参数 ',
            'POST_DATA_EMPTY'       => 'post数据不能为空',
            'NOT_UTF8'              => '未使用指定编码格式',
        ];

        return Z::arrayGet($errList, $code, $code);
    }
}
