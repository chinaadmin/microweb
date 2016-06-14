<?php
// 定义回调URL通用的URL
//define ( 'URL_CALLBACK', 'http://www.bihaohuo.cn/Oauth/callback/type/' );
define ( 'URL_CALLBACK', 'http://www.bihaohuo.cn/Oauth/callback/type/' );
return array (
		// 腾讯QQ登录配置
		 /* 'THINK_SDK_QQ' => array (
				'APP_KEY' => '101221326', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '2e95bd64fdfbe9948e563e0fec979301', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'qq' 
		),  */
		//腾讯QQ测试环境
		'THINK_SDK_QQ' => array (
				'APP_KEY' => '101236856', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => 'e162a68e518c1633f93c66fb92c7c957', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'qq'
		),
		
		'THINK_SDK_WECHAT' => array(
				'APP_KEY'    => 'wxa75f096bcb5531cd', //应用注册成功后分配的 APP ID
				'APP_SECRET' => '18863a1d22899b46bf1b060e357b83f4', //应用注册成功后分配的KEY
				'CALLBACK'   => URL_CALLBACK . 'wechat',
		), 
		// 腾讯微博配置
		'THINK_SDK_TENCENT' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'tencent' 
		),
		// 新浪微博配置
		'THINK_SDK_SINA' => array (
				'APP_KEY' => '1808972095', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => 'e1e6485c30128a09f38538ca08a0a2ee', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'sina' 
		),
		// 网易微博配置
		'THINK_SDK_T163' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 't163' 
		),
		// 人人网配置
		'THINK_SDK_RENREN' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'renren' 
		),
		// 360配置
		'THINK_SDK_X360' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'x360' 
		),
		// 豆瓣配置
		'THINK_SDK_DOUBAN' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'douban' 
		),
		// Github配置
		'THINK_SDK_GITHUB' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'github' 
		),
		// Google配置
		'THINK_SDK_GOOGLE' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'google' 
		),
		// MSN配置
		'THINK_SDK_MSN' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'msn' 
		),
		// 点点配置
		'THINK_SDK_DIANDIAN' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'diandian' 
		),
		// 淘宝网配置
		'THINK_SDK_TAOBAO' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'taobao' 
		),
		// 百度配置
		'THINK_SDK_BAIDU' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'baidu' 
		),
		// 开心网配置
		'THINK_SDK_KAIXIN' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'kaixin' 
		),
		// 搜狐微博配置
		'THINK_SDK_SOHU' => array (
				'APP_KEY' => '', // 应用注册成功后分配的 APP ID
				'APP_SECRET' => '', // 应用注册成功后分配的KEY
				'CALLBACK' => URL_CALLBACK . 'sohu' 
		) 
);