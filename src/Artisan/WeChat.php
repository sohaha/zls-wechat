<?php

namespace Zls\Wechat\Artisan;

use Z;

class WeChat extends \Zls\Artisan\Artisan
{

    public function execute(\Zls_CliArgs $args)
    {
        $this->init($args);
    }

    public function init(\Zls_CliArgs $args)
    {
        $force = $args->get('force', $args->get('f'));
        $this->copy(z::realPath(__DIR__ . '/../Config/wechat.php', false, false), $force);
    }

    private function copy($origin, $force)
    {
        $path = z::realPath(ZLS_APP_PATH . 'config/default/wechat.php', false, false);
        if (!file_exists($path) || $force) {
            echo parent::success("copy config: {$origin} -> {$path}") . PHP_EOL;
            echo parent::getColoredString('Status: ' . @copy($origin, $path)) . PHP_EOL;
        } else {
            echo parent::error('wechat config already exists') . PHP_EOL;
            echo parent::getColoredString('you can use -force to force the config file') . PHP_EOL;
        }
    }

    public function title()
    {
        return 'WeChat Packages';
    }

    public function options()
    {
        return ['-force Overwrite old config file'];
    }

    public function example()
    {

    }
}
