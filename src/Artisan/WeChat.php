<?php

namespace Zls\Wechat\Artisan;
use Z;

class WeChat extends \Zls_Artisan {

    public function execute(\Zls_CliArgs $args) {
        $this->init($args);
    }

    public function init(\Zls_CliArgs $args) {
        $this->copy(z::realPath(__DIR__ . '/../Config/wechat.php', false, false));
    }
    private function copy($origin) {
        $path = z::realPath(ZLS_APP_PATH . 'config/default/wechat.php', false, false);
        echo "copy config: {$origin} -> {$path}".PHP_EOL;
        echo "Status: ".@copy($origin,$path);
    }
}
