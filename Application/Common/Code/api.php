<?php
return [

      'GOODS_REQUIRE'=>'商品不能为空',

      //首页
      "FEAT_REQUIRE" => "推荐展位不能为空",
      //退款退货
      "REFUND_REC_REQUIRE" => "退货商品不能为空",
      'REFUND_ALREADY'=>'商品已申请退货,请不要重复提交！',
      'REFUND_NUM_REQUIRE'=>'退款数量至少大于1',
      'REFUND_NUM_SPILL' =>'退款数量不能大于最大退款数',
      'REFUND_REASON_REQUIRE'=>'退款说明不能为空',
      'REFUND_7_DAY' => '收货7日后不能退款退货',
      'REFUNDID_REQUIRE'=>'退款id不能为空',
      'ORDER_REFUND_ALREADY' => '订单已申请退货，请不要重复提交！',
      //帐号
	"MOBILE_NOT_EXIST" => "旧手机号不正确",
	"MOBILE_REQUIRE" => "手机号不能为空",
	"MOBILE_NOT_MATCH" => "手机号不匹配",
	"MOBILE_EXISTS" =>'手机号码已存在',
	"CODE_REQUIRE" => "验证码不能为空",
	"MOBILE_CHANGE_FAIL" => "手机号修改失败",
	"USER_NICKNAME_REQUIRE" => "用户昵称不为空",
	"USER_SEX_REQUIRE" => "用户姓别不为空",
	"USER_SEX_FORMAT_ERROR" => "用户姓别格式有误",
	"USER_REALNAME_REQUIRE" => "用户真实姓名不为空",
	"OPENID_NOT_CONNECT" => "微信未绑定",
	//图片
	"PHOTO_REQUIRE" =>"请上传图片！",
	"PHOTO_EXT_REQUIRE" =>"请指定图片格式！",
	//优惠券
	"ORDER_AMOUNT_REQUIRE" =>"订单总金额不为空",
	'ORDER_GOODSIDS_REQUIRE' => '商品id不能为空',	
	'COUPON_CODE_REQUIRE' => '优惠码不能为空',	
	'COUPON_GET_ALREADY' => '优惠券已领取',	
	'COUPON_GET_ERROR' => '优惠券已领取或过期',	

	'COUPON_ID_REQUIRE' => '优惠券id不能为空',
	
	//快递查询
	'LOGISTICS_COMPANY_REQUIRE' => '物流公司名称不能为空',
	'LOGISTICS_NUMBER_REQUIRE' => '订单号不能为空',
	'LOGISTICS_NUMBER_ERROR' => '订单号有误',
	'LOGISTICS_SUBSCRIBE_FAIL' => '快递跟踪订阅失败',
	'LOGISTICS_MAILNO_REQUIRE' => '快递单号不能为空',
	//位置
	'LOCATION_LNG_REQUIRE' => '当前所在经度不能为空',	
	'LOCATION_LAT_REQUIRE' => '当前所在纬度不能为空',
	//众筹
	'CROWDFUNDING_INVALID' => '众筹项目无效',	
	'CROWDFUNDING_MAIL_NO_REQUIRE' => '众筹物流单号不能为空',	
	'DRAW_LESS_ZERO' => '抽奖机会已用完了',	
	'DRAW_ADDRESS_EMPTY' => '请先增加收货地址',	
	'DRAW_REVEIVE_ALREADY' => '奖品已领取',	
];