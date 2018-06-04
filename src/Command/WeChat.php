<?php

namespace Zls\Wechat\Command;

use Z;

class WeChat extends \Zls\Command\Command
{

    public function execute($args)
    {
        $this->init($args);
    }

    public function init($args)
    {
        $force = Z::arrayGet($args, ['force', 'F']);
        $this->copy(z::realPath(__DIR__ . '/../Config/wechat.php', false, false), $force);
    }

    private function copy($origin, $force)
    {
        $config = Z::config();
        $path = z::realPath(ZLS_APP_PATH . 'config/default/wechat.php', false, false);
        if (!file_exists($path) || $force) {
            $this->echoN("copy config: {$origin} -> {$path}");
            copy($origin, $path);
            //$this->echoN('Status: ' . @copy($origin, $path));
            if ($config->find('ini')) {
                $ini = z::config('ini');
                /**
                 * @var \Zls\Action\Ini $ActionIni
                 */
                $ActionIni = z::extension('Action\Ini');
                $ini = array_merge($ini, [
                    'wechat' => [
                        'token'              => '',
                        'appid'              => '',
                        'appsecret'          => '',
                        'corpid'             => '',
                        'agentid'            => '',
                        'encodingAesKey'     => '',
                        'componentAppid'     => '',
                        'componentAppsecret' => '',
                        'debug'              => 1,
                    ],
                ]);
                @file_put_contents(ZLS_PATH . '../zls.ini', $ActionIni->extended($ini));
                $this->success('Please amend the zls.ini');
            }
        } else {
            $this->error('wechat config already exists');
            $this->echoN('you can use -force to force the config file');
        }
    }

    public function title()
    {
        return 'WeChat Packages';
    }

    public function options()
    {
        return ['-force' => ' Overwrite old config file'];
    }

    public function example()
    {

    }

    /**
     * 命令介绍
     * @return string
     */
    public function description()
    {
        return 'Initialize WeChat configuration';
    }
}
