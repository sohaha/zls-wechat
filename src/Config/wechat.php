<?php
/**
 * 微信开发配置.
 */
$ini = z::config('ini.wechat');

return [
    // 输出日志
    'debug'              => z::arrayGet($ini, 'debug', false),
    'appid'              => z::arrayGet($ini, 'appid', ''),
    'appsecret'          => z::arrayGet($ini, 'appsecret', ''),
    'token'              => z::arrayGet($ini, 'token', ''),
    'encodingAesKey'     => z::arrayGet($ini, 'encodingAesKey', ''),
    // 开放平台
    'componentAppid'     => z::arrayGet($ini, 'componentAppid', ''),
    'componentAppsecret' => z::arrayGet($ini, 'componentAppsecret', ''),
    // 企业微信
    'corpid'             => z::arrayGet($ini, 'corpid', ''),
    'agentid'            => z::arrayGet($ini, 'agentid', ''),
    // 商户平台id
    'payKey'             => z::arrayGet($ini, 'payKey', ''),
    'payMchId'           => z::arrayGet($ini, 'payMchId', ''),
    'payCertPath'        => z::arrayGet($ini, 'payCertPath', ''),
    'payKeyPath'         => z::arrayGet($ini, 'payKeyPath', ''),
    // 开启沙盒测试
    'paySandbox'         => z::arrayGet($ini, 'paySandbox', false),
];
