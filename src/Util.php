<?php

namespace Zls\WeChat;

/**
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

        return vsprintf($template, $data);
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
        $list = array_pop($data);
        $len = count($list);
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

}
