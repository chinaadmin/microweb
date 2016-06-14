<?php
return [
    'SUCCESS' => '操作成功',
	'UNKNOWN_ERROR' => '未知错误',
    'RETURNED_FORMAT_ERROR' => '返回格式错误',

    'DATA_INSERTION_FAILS' => '数据插入失败',
    'DATA_MODIFICATIONS_FAIL' => '数据修改失败',
    'DATA_DELETE_FAILED' => '数据删除失败',
    'SAVE_SORT_FAILURE' => '保存排序失败',
    'DATA_ERROR' => '数据错误',
    'PARAM_EMPTY' => '请求参数不能为空！',

    //token
    'TOKEN_REQUIRE'=>'token不能为空',
    'TOKEN_SET_FAILED'=>'获取token失败',
    'TOKEN_AUTH_FAILED'=>'token验证失败',
    'TOKEN_HAS_EXPIRED'=>'token已过期',


    //格式不正确
    'EMAIL_FORMAT_ERROR' =>'邮箱格式不正确',
    'MOBILE_FORMAT_ERROR'=>'手机格式不正确',
    'VERIFICATION_CODE_ERROR' => '验证码不正确',

    //验证码
    'CODE_NOT_EXIST'=>'验证码不存在',
    'CODE_HAS_EXPIRED'=>'验证码已过期',
    'CODE_USED'=>'验证码已使用',

    //用户
    'USER_IS_LOCKED'=>'用户被锁定',
    'USER_NOT_EXIST'=>'用户不存在',
    'GET_USER_FAILED'=>'获取用户信息失败',
    'ACCOUNT_REQUIRE'=>'账号不能为空',
    'PASSWORD_REQUIRE'=>'密码不能为空',
    'PASSWORD_ERROR'=>'密码有误',
    'USERNAME_REQUIRE'=>'用户名不能为空',
    'ROLE_REQUIRE'=>'角色不能为空',
	'ROLE_MODI_FAILED'	=> '修改角色失败',
    'ACCOUNT_EXISTS'=>'账号已经存在',
    'USERNAME_EXISTS'=>'用户名已经存在',
    'EMAIL_EXISTS'=>'邮箱已经存在',
    'MOBILE_EXISTS'=>'手机已经存在',
    'ACCOUNT_IS_MODIFY'=>'该账号已经修改',
    'ACCOUNT_IS_OCCUPIED'=>'该账号已被占用',
    'ACCOUNT_NOT_EXISTS'=>'账号不存在',
    'USER_ID_REQUIRE'=>'用户ID不能为空',
    'ADD_ACCOUNTS_FAILED'=>'添加用户账号失败',
    'UPDATE_ACCOUNTS_FAILED'=>'修改用户账号失败',
    'DELETE_ACCOUNTS_FAILED'=>'删除用户账号失败',
    'MOBILE_CODE_ERROR' => '手机验证码错误',
    'NICKNAME_REQUIRE'=>'昵称不能为空',
    'REALNAME_REQUIRE'=>'真实姓名不能为空',
    'NO_LOGIN_ERROR' =>'未登入',

    'USERNAME_NOT_MODIFY'=>'用户名不能修改',
    'OlD_PASSWORD_REQUIRE'=>'旧密码不能为空',
    'NEW_PASSWORD_REQUIRE'=>'新密码不能为空',
    'PASSWORD_INCONSISTENCY'=>'两次密码不一致',
    'OlD_PASSWORD_ERROR'=>'旧密码错误',

     'CONNECT_REQUIRE'=>'第三方数据不能为空',
     'OPENID_REQUIRE'=>'openid不能为空',
     'PART_TYPE_REQUIRE'=>'登陆类型不能为空',
     'CONNECT_EXISTS'=>'该账号已绑定，请使用其他账号！',
    //积分
    'CREDITS_INADEQUATE'=>'积分不足',
    'SET_CREDITS_FAIL'=>'修改积分失败',

    //发送消息
    'SEND_MESSAGE_FAIL' => '发送短信失败',
    'MESSAGE_CODE_EXPIRE' => '短信验证码过期',
    'MESSAGE_CODE_MATCHERROR' => '短信验证码不匹配',
    'MESSAGE_CODE_SEND' => '短信验证码已发送',
    'TEL_MATCH_SUCCESS' => '手机绑定成功',
    //文件上传
    'UPLOAD_ERROR'=>'上传文件失败！',

    //百度api
    'BAIDU_MAP_ERROR'=>'百度地图返回失败',

	//邀请码
	'INVITE_CODE_NOT_EXIST' => '邀请码不存在!',	
	//评论
	'MATCH_START_REQUIRE' => 	'描述相符不能为空',
	'COMMENT_CONTENT_REQUIRE' => 	'评论不能为空',
	'SELLER_START_REQUIRE' => 	'卖家服务不能为空',
	'LOGISTICS_START_REQUIRE' => 	'物流服务不能为空',
	'COMMENT_GOODSID_REQUIRE' => 	'被评论商品id不能为空',
	'COMMENT_ORDERSN_REQUIRE' => 	'订单号不能为空',
	'COMMENT_ORDERID_REQUIRE' => 	'订单id不能为空',
    
    //好友助力
    'NOT_HELP_SELF' => '无法给自己助力',
    'HELP_FAILURE'  => '助力失败,请稍后再试',
    'CODE_NOT_FOUND'  => '请输入助力码',
    'CODE_NOT_EXISTS' => '您输入的助力码有误',
    'HAS_HELP_FRIEND'  => '您已助力过好友，不能再助力了哦',
    'MUST_REGISTER_ACT_START' => '需3月25日起注册的用户才能助力',
    'HELP_FULL' => '您来晚一步，好友蜜罐已经加满',
    'FRIEND_NOT_BUY'=>'您的好友还没有获得抽奖资格，提醒好友看清活动说明',
    'SYSTEM_BUSY' => '系统繁忙，请稍后再试',
];