<?php
return [
    //用户操作Model
    'USER_MODEL' => 'Admin/Admin',
    //超级管理员会员id
    'SUPPER_USER_ID' => '9d1f346e7b05ee9bf35bcf9fe19521af',
    'USER_AUTH_ON'=> true,
    'USER_AUTH_GATEWAY'	=>'/Public/login',	// 默认认证网关
    'NOT_AUTH_MODULE'		=>'Public',		// 默认无需认证模块

    /* 分页设置 */
    'VAR_PAGE'              => 'p',   // 分页跳转变量
    'PAGE_ROLLPAGE'         => 10,     // 分页显示页数
    'PAGE_LISTROWS'         => 10,     // 分页每页显示记录数
    //登录验证码
    'VERIFY_CONFIG' => [
        'imageH' => 35,
        'imageW'=>140,
        'fontSize' => 18,
        'type' => 'gif',
        'length' => 4,
        'useNoise'=>false,
        'useCurve'=>false
    ]
];