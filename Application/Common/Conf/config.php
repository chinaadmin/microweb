<?php
// 数据库配置
$configs = [];
$configs_file = [
    'db',//数据库配置
    'environment',//环境配置
    'pay', //支付配置
    'redis',//redis配置
];
array_map(function($file)use (&$configs){
    $configs[] = require (APP_PATH . 'Common/Conf/'.$file.'.php');
},$configs_file);
//动态设置系统配置信息
$sys_configs = F('SysConfigs');
if(!empty($sys_configs)){
    $configs[] = $sys_configs;
}
$configs[] = [
    'TMPL_PARSE_STRING' =>[
        '__IMG__' => __ROOT__.'/'.APP_NAME.(APP_NAME?'/':'').'Public/Css/Images',
        '__CSS__' => __ROOT__.'/'.APP_NAME.(APP_NAME?'/':'').'Public/Css',
        '__JS__' => __ROOT__.'/'.APP_NAME.(APP_NAME?'/':'').'Public/Js'
    ],
    //加载标签库
    'TAGLIB_PRE_LOAD' =>'Common\Lib\MyTag',
    // 用户认证密码加密方式
    'AUTH_PWD_ENCODER' => 'myMD5',
    // session生存时间
    'SESSION_OPTIONS' => [
        'expire' => 7200,
        //获取顶级域名
        'domain' => getTopHost()
    ],
    'URL_MODEL' => 2,
    // 伪静态
    'URL_HTML_SUFFIX' => '.html',
    // 是否进行字段类型检查
    'DB_FIELDTYPE_CHECK' => true,
    // 分组配置
    'MODULE_ALLOW_LIST' => [
        'Home',
        'Admin',
        'Upload',
        'User',
        'Stores',
        'Cron',
        'Api',
        'Activity'
    ],
    'DEFAULT_MODULE' => 'Home',
    'APP_SUB_DOMAIN_DEPLOY' => 1,
    'APP_SUB_DOMAIN_RULES' => [
        // admin子域名指向Admin模块
        'admin' => 'Admin',
        'upload'=>'Upload',
        'user' => 'User',
        'api' => 'Api',
        'act' => 'Activity',
        'logistics' => 'Logistics',
        'chips'=>'Chips'
    ],
    'URL_CASE_INSENSITIVE' =>true,
    'VAR_SESSION_ID' =>'session_hao'
];
$return_configs = [];
array_map(function($data) use(&$return_configs){
    if(is_array($data)) {
        $return_configs = array_merge($return_configs, $data);
    }
},$configs);
return $return_configs;