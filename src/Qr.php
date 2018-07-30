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

class Qr implements WxInterface
{
    /** @var Main $WX */
    private static $WX;
    private $ticket;
    private $qrPath;

    /**
     * Zls_WeChat_Pay constructor.
     * @param $wx
     */
    public function __construct(Main $wx)
    {
        self::$WX = $wx;
    }

    /**
     * 创建临时二维码
     * @param int   $sceneId       32位非0整型
     * @param int   $expireSeconds 该二维码有效时间，以秒为单位
     * @param array $actionInfo
     * @return array|bool
     */
    public function createTemp($sceneId, $expireSeconds = 2592000, $actionInfo = [])
    {

        if ($generateActionInfo = $this->generateActionInfo($sceneId, true)) {
            $data = ['action_name' => $generateActionInfo['actionName'], 'expire_seconds' => $expireSeconds, 'action_info' => array_merge($actionInfo, $generateActionInfo['actionInfo'])];

            return $this->post($data);
        }

        return false;
    }

    /**
     * 生成二维码参数
     * @param $sceneId
     * @param $temporary
     * @return bool|array ['actionInfo' => xxx, 'actionName' => xxx]
     */
    private function generateActionInfo($sceneId, $temporary)
    {
        $isStr = is_string($sceneId);
        if ($isStr) {
            if (mb_strlen($sceneId) > 64) {
                self::$WX->setError(201, '二维码场景值ID长度不能超过64位字符串');

                return false;
            }
            $actionInfo = ['scene' => ['scene_str' => $sceneId]];
            $actionName = $temporary ? 'QR_SCENE' : 'QR_LIMIT_SCENE';
        } else {
            if (strlen($sceneId) > 32) {
                self::$WX->setError(201, '二维码场景值ID长度不能超过32位非0整型');

                return false;
            }
            $actionInfo = ['scene' => ['scene_id' => $sceneId]];
            $actionName = $temporary ? 'QR_STR_SCENE' : 'QR_LIMIT_STR_SCENE';
        }

        return ['actionInfo' => $actionInfo, 'actionName' => $actionName];
    }

    private function post($data)
    {
        $result = self::$WX->post('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . self::$WX->getAccessToken(), $data);
        $this->setQrInof($result);

        return $result;
    }

    private function setQrInof($result)
    {
        $this->ticket = z::arrayGet($result, 'ticket');
        $this->qrPath = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $this->ticket;
    }

    /**
     * 二维码插入logo
     * @param        $logoPath
     * @param        $savePath
     * @param bool   $save
     * @param string $filename
     * @param string $qrPath 二维码访问路径
     * @return string|bool
     */
    public function compound($logoPath, $savePath, $save = true, $text = '', $fontPath = '', $filename = '', $qrPath = '')
    {
        $qrPath = $qrPath ?: $this->qrPath;
        if (preg_match('/^http[s]?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"])*$/', $qrPath)) {
            $qrObj = self::$WX->request($qrPath, [], 'get', 'file');
        } else {
            $qrObj = file_get_contents(z::realPath($qrPath));
        }
        $logoObj = file_get_contents(z::realPath($logoPath));
        $QR = imagecreatefromstring($qrObj);
        $logo = imagecreatefromstring($logoObj);
        $qrWidth = imagesx($QR);
        $qrHeight = imagesy($QR);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        $logoQrWidth = $qrWidth / 5;
        $scale = $logoWidth / $logoQrWidth;
        $logoQrHeight = $logoHeight / $scale;
        $fromWidth = ($qrWidth - $logoQrWidth) / 2;
        $fontColor = imagecolorallocate($QR, 0, 0, 0);
        imagecopyresampled(
            $QR,
            $logo,
            $fromWidth,
            $fromWidth,
            0,
            0,
            $logoQrWidth,
            $logoQrHeight,
            $logoWidth,
            $logoHeight
        );
        if ($text) {
            $fontSize = $qrWidth / 25;
            if ($fontPath) {
                $fontPath = z::realPath($fontPath);
                //todo 字体定位要优化
                $box = imagettfbbox($fontSize, 0, $fontPath, $text);
                $height = $box[3] - $box[5];
                $width = $box[4] - $box[6];
                imagettftext($QR, $fontSize, 0, ($qrWidth - $width) / 2, $qrHeight - $height / 3, $fontColor, $fontPath, $text);
            } else {
                $fontWidth = imagefontwidth($fontSize);
                $fontHeight = imagefontheight($fontSize);
                $textWidth = $fontWidth * strlen($text);
                imagestring($QR, 5, ($qrWidth - $textWidth) / 2, $qrHeight - $fontHeight, $text, $fontColor);
            }
        }
        $fileName = $filename ?: md5($this->ticket) . '_logo.png';
        $filePath = z::realPathMkdir($savePath, true) . $fileName;
        if ($save) {
            imagepng($QR, $filePath);
            imagedestroy($QR);

            return z::safePath($filePath, '');
        } else {
            header("Content-type: image/png");
            imagepng($QR);
            imagedestroy($QR);
            z::finish();

            return true;
        }
    }

    /**
     * 创建永久二维码
     * @param int|string $sceneId 最大值为100000（目前参数只支持1--100000）,字符串类型，长度限制为1到64，
     * @param array      $actionInfo
     * @return array|bool
     */
    public function create($sceneId, $actionInfo = [])
    {
        if ($generateActionInfo = $this->generateActionInfo($sceneId, true)) {
            $data = ['action_name' => $generateActionInfo['actionName'], 'expire_seconds' => $expireSeconds, 'action_info' => array_merge($actionInfo, $generateActionInfo['actionInfo'])];

            return $this->post($data);
        }

        return false;
    }

    /**
     * 获取二维码路径
     * @param string $saveLocal 保存到本地的目录
     * @return bool|string
     */
    public function download($saveLocal = '')
    {
        if (!$this->qrPath) {
            self::$WX->setError(404, '还没生成二维码,无法获取二维码链接');

            return false;
        }
        $qrUrl = $this->qrPath;
        if ($saveLocal) {
            $fileData = self::$WX->request($qrUrl, [], 'get', 'file');
            $filePath = z::realPathMkdir($saveLocal, true) . md5($this->ticket) . '.png';
            if (!!$fileData && file_put_contents($filePath, $fileData)) {
                $qrUrl = z::safePath($filePath, '');
            } else {
                self::$WX->setError(404, '二维码保存失败');

                return false;
            }
        }
        $this->qrPath = $qrUrl;

        return $qrUrl;
    }
}
