<?php
/**
 * 微信开发配置.
 */
$ini = z::config('ini.wechat');

return [
    'appid' => z::arrayGet($ini, 'appid', ''),
    'appsecret' => z::arrayGet($ini, 'appsecret', ''),
    'token' => z::arrayGet($ini, 'token', ''),
    'encodingAesKey' => z::arrayGet($ini, 'encodingAesKey', ''),
    'componentAppid' => z::arrayGet($ini, 'componentAppid', ''),
    'componentAppsecret' => z::arrayGet($ini, 'componentAppsecret', ''),
    'certPath' => z::arrayGet($ini, 'certPath', ''),
    'corpid' => z::arrayGet($ini, 'corpid', ''),
    'keyPath' => z::arrayGet($ini, 'keyPath', ''),
    'agentid' => z::arrayGet($ini, 'agentid', ''),
    'debug' => z::arrayGet($ini, 'debug', false),
];
