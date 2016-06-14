<?php
return [
        "PAGE_LISTROWS"=>10,
        'THUMB_SIZE' => [  // 商品缩略图
				'200X200',
				'400X400',
				'700X700' 
		],
		'DEFAULT_IMAGE' => "/Public/Image/ListDefault/default.jpg",
		'VAR_PAGE' => 'currentPage',
        //翁翁翁活动配置
        'WONG_CONFIG'=>[
            // 'awardPlanID' => 2,//t,33,//tp,13,//活动ID
            // 'tenPlanId' => 3,//十周年活动ID
            'awardPlanID' => 33,//t,33,//tp,13,//活动ID
            'tenPlanId' => 14,//十周年活动ID
            'sanYuId' => 6,//三文鱼优惠券id
            // 'cd_id'=> 145,//t266,//tp277, //方案二
            'cd_id'=> 266,//t266,//tp277, //方案二
            'game_time' => 10,  //游戏时长
            'one_help_times' => 10,  //购买一次能好友助力10次
            // 'fkId'  => '46',//t:'37',  //tp137//众筹ID
            'fkId'  => '37',//t:'37',  //tp137//众筹ID
            'zc_start_time' => '2016-03-15', //众筹开始日期
            'zc_end_time' => '2016-03-30 23:59:59', //众筹结束日期
        ],
        //微信原始ID
        'WX_ORIGIN_ID' => 'gh_7870d90c0487', //测试环境
];