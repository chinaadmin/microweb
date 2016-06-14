<?php
return [
	'THINK_PAY'=>[
        'common'=>[
            'sign_type'=>'MD5',
            'transport'=>'http',//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            'return_url'=>'http://www.tp-bihaohuo.cn/Pay/notify/method/return/type/',
            'notify_url'=>'http://www.tp-bihaohuo.cn/Pay/notify/method/notify/type/',
            'refund_notify_url'=>'http://www.tp-bihaohuo.cn/Pay/refund_notify/method/notify/type/'
        ],
        'alipay'=>[
            //收款支付宝账号
            'email' => 'cw@jitujituan.com',
            //安全检验码，以数字和字母组成的32位字符
            'key' => 'ip8vj0dhlj6zs6f5bohek5e1vz1rdy2n',
            //合作身份者id，以2088开头的16位纯数字
            'partner' => '2088911888143207',
            //商户的私钥（后缀是.pen）文件相对路径
            'private_key_path'	=> 'Application/Common/Org/ThinkPay/Driver/Alipay/key/rsa_private_key.pem',
            //支付宝公钥（后缀是.pen）文件相对路径
            'ali_public_key_path'=> 'Application/Common/Org/ThinkPay/Driver/Alipay/key/alipay_public_key.pem'
        ],
        'chips'=>[
	        'return_url'=>'http://chips.tp-bihaohuo.cn/Notice/notify/method/return/type/',
	        'notify_url'=>'http://chips.tp-bihaohuo.cn/Notice/notify/method/notify/type/'
        ]
    ]
];