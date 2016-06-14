var host = "http://"+location.host+"/";//jtshop  t-bihaohuo
var mockHost2 = "http://act.t-bihaohuo.cn/";
var mockHost = "http://api.t-bihaohuo.cn/";
var mockChips = "http://chips.t-bihaohuo.cn/";
var activityHost = "http://chips.t-bihaohuo.cn/";

// var host = "http://m.m.com/";//jtshop  t-bihaohuo
// var mockHost2 = "http://act.a.com/";
// var mockHost = "http://api.a.com/";
// var mockChips = "http://chips.a.com/";
// var activityHost = "http://chips.a.com/";



//接口url      //act 
var interfaceURL={
	allChips:{  // 众筹
		chips:mockChips+"chips/chips",               // 方案列表
		chipsGoods:mockChips+"chips/chipsGoods",     // 方案商品
		orderShow:mockChips+"Order/orderShow",       // 订单页面
		submitOrder:mockChips+"Order/submitOrder",   // 订单提交
		getProject:mockChips+"Crowdfunding/getProject",  // 获取众筹项目内容
		doCollec:mockChips+"Crowdfunding/doCollect",       // 添加收藏  
		delCollect:mockChips+"Crowdfunding/delCollect",    // 删除收藏
		accountPay:mockChips+"Pay/accountPay",           // 余额支付
		wechatPay:mockChips+"Pay/wechatPay",               // 微信支付
		mobilePay:mockChips+"Pay/mobilePay",               // 移动支付
		i:mockChips+"i/index",                             // 余额
		doCollect:mockChips+"Crowdfunding/showCollect",       //收藏
		allOrders:mockChips+"Order/orders",                       //订单列表
		travelReg:mockChips+"Crowdfunding/travelRegister",       // 旅游
		exitChips:mockChips+"Order/exitChips",                   // 退出众筹
		getLogistic:mockChips+"Crowdfunding/getLogisticTrace",    //  物流跟踪信息
		receiveConfirm:mockChips+"Crowdfunding/receiveConfirm",    //  确认收货
		orderInfo:mockChips +"Order/orderInfo"
	},
	luckyDraw:{   // 众筹抽奖
		getAwardContent:mockChips+"Award/getAwardContent",       //获取抽奖内容
		draw:mockChips+"Award/draw",                             // 参与抽奖
		myDrawChance:mockChips+"Award/myDrawChance",             //获取抽奖名额数
		myDrawLis:mockChips+"Award/myDrawList",					 //获取所中奖品列表						
		getMyDraw:mockChips+"Award/getMyDraw",                   //领取奖品
		showMyAddress:mockChips+"Award/showMyAddress",           //领奖地址查看
		addAddress:mockChips+"Award/addAddress"				     //新增或修改获奖收获地址
	},
	home:{ //首页
		banner:mockHost+"Home/banner",
		explosion:mockHost+"Home/recommend",//爆款
		recommend:mockHost+"Home/position", //推荐位
		brand: mockHost + "Home/brands", //品牌 
		category: mockHost +"Home/getCats", //类别
		feats:mockHost+"Home/feats" //推荐位 
	},
	user:{ //用户
		login:mockHost+"User/login",  
		register:mockHost+"User/register",
		logout:host+"User/loginOut", //--------weixin unused
		recPassword:mockHost+"User/forgotPass",//找回密码
		updPassword:mockHost+"User/updatePass",
		updName:mockHost+"User/updateUsername",
		infomation:mockHost+"User/getInfo",
		i:mockHost+"i/index",
		memberCenter:mockHost+"User/memberCenter",//用户中心
		updAvatar:mockHost+"user/uploadHeadPic",
		accountBind:mockHost+"User/accountBind",   // 第三方登录 
		
		setPassword:mockHost+"User/setPass",		//老用户设置密码
		getBindInfo:mockHost+"User/getBindInfo",     // 判断是否是老用户，是且用户名为空，则推送消息
	},
	sms:{ //短信
		send:mockHost+"Message/sendSMS",  //发送短信
		verify:mockHost+"Message/verifySMS", //验证短信
		checkTelSend:mockHost+"User/checkTelSend",//发送旧手机验证码
		checkTel:mockHost+"User/checkTel",//核对旧手机
		checkTelNewSend:mockHost+"user/recieveTelSendCode",//发送新手机验证码
		checkTelNew:mockHost+"user/recieveTel",//核对发送新手机
		
		sendOldUserCode:mockHost+"user/sendOldUserCode",	//老用户发送验证码
		checkUserCode:mockHost+"user/checkUserCode",		//老用户核对验证码
	},
	activitys:{//活动Ycj
		activityUrl:activityHost+"Activity/getWong",// 活动接口
		activityHelpCode:activityHost+"Activity/getCode",         // 活动助力码
		activityGetFriend:activityHost+"Activity/getFriendDetail",		 //加蜜友
		activityFriendCode:activityHost+"Activity/friendCode",		 //助力蜜友
        myList:activityHost+"Activity/MyAward",     				 //嗡嗡嗡我的奖单记录
		sysList:activityHost+"Activity/SystemAward",   //系统奖单记录
		notice:activityHost+"Activity/detail",    //     活动说明
		activityAward:activityHost+"Activity/award",		 //抽奖接口
		isDraw:activityHost+"Activity/isDraw",
		AnniversaryUrl:activityHost+"Activity/tenYear",  //十周年庆活动接口
		AnniversaryAward:activityHost+"Activity/tenYearAward",       //十周年庆抽奖接口                 
		AnniversaryList:activityHost+"Activity/tenMyAward",          //十年周庆我的奖品记录
		AnniversaryNotice:activityHost+'Activity/tenYearDetail'    //十周年庆活动说明
	},
	feedback:{ //反馈
		option:mockHost+"Feedback/opinionType",  //获取意见反馈类型
		submitOpt:mockHost+"Feedback/submitOpinion"	//提交意见反馈类型	
	},
	commodity:{  //商品
		list:mockHost+"List/goodsList",//商品列表
		detail: mockHost + "Detail/detail",  //商品详情
		getActivityList: mockHost + "List/getActivityList"
	},
	cart:{  //购物车
		list:mockHost+"Cart/lists",   //购物车列表
		add : mockHost+ "Cart/add",   //加入购物车
		update:mockHost+ "Cart/update",  //更新购物车
		del: mockHost +"Cart/del",       //删除购物车
		delAllGoods: mockHost +"Cart/delAll",  //购物车批量删除
		clearing:mockHost+"Cart/goShopping",  //结算购物车
		buynow:mockHost+"Cart/buynow",   //立即购买
		count:mockHost+"cart/getMyCartCount"
	},
	address:{ //地址
		list:mockHost+"Affiliated/getdelivery", //提货地址列表 
		add:host+"Affiliated/adddelivery",  //提货地址新增
		update: host +"Affiliated/updatedelivery", //提货地址
		del:host+"Affiliated/deldelivery", //提货地址
		option:mockHost+"Affiliated/getStores", //提货地址 门店列表
		listRec:mockHost+"Affiliated/getrecaddress",//收货地址列表
		addRec:mockHost+"Affiliated/addrecaddress",//增加收货地址
		updateRec:mockHost+"Affiliated/updaterecaddress",//更新收货地址
		delRec:mockHost+"Affiliated/delrecaddress",//删除收货地址
		setDefault:mockHost+"Affiliated/setDefaultRecaddress", //设置默认收货地址
		storesDistance : mockHost +"Affiliated/getStoresDistance",//获取送货上门
		commitComment : mockHost +"GoodsComment/commitComment"
		
	},
	invoice:{ //发票
		list:mockHost+"Affiliated/getinvoice", //获取发票列表
		add: host+"Affiliated/addinvoice",    //新增发票
		update:host+"Affiliated/updateinvoice", //修改发票
		del:host+"Affiliated/delinvoice"      //删除发票
	},
	order:{
		submit:mockHost+"Order/single", //先提交订单，获取订单好，再进行支付
		list:mockHost+"Order/lists",     //订单列表
		pay:mockHost+"Order/wechatPay",      //微信支付
		apay:mockHost+"Order/accountPay",    //账户余额支付
		recharge:mockHost+"Order/wechatRecharge",//充值接口
		cancal:mockHost+"Order/cancel",  //取消订单
		receipt:mockHost+"Order/receipt",//确认收货
		del: mockHost +"Order/del",      //删除订单
		detail: mockHost+ "Order/detail",// 订单详情
		shipmentPrice: mockHost+ "Order/shipmentPrice",// 订单运费
		getInfo:mockHost+"KuaiDi/get_info"   //查看物流详情
		
	},
	refund:{
		list  : mockHost + "Refund/refundList",   //退款退货列表
		apply : mockHost +"Refund/refundShow",    //申请退款退货页面
		submit: mockHost + "Refund/refundAction", //申请退款/退货
		detail: mockHost + "Refund/refundInfo",   //退款退货详情
		cancel: mockHost +"Refund/cancelRefund"   //取消退款退货
	},
	other:{
		history:mockHost+"History/history", //浏览记录
		area:mockHost+"Area/getArea",  //获取行政地区数据
		coin:mockHost+"i/creditsDetail",
		recHistory:mockHost+"Record/recharge",   // 充值历史记录
		payRecord:mockHost+"Record/payRecord",   // 消费记录
		allRecord:mockHost+"Record/allRecord",   // 全部记录
		getLocation:mockHost+"Area/getLocationByLngLat"   // 通过经纬获取地区信息
	},
	fav:{
		add:mockHost+"Collect/addCollect", //添加收藏
		del:mockHost+"Collect/del",   //取消收藏
		list:mockHost+"Collect/myCollect"  //我的收藏
	},
	coupon:{
		list:mockHost+"Coupon/couponList",  // 优惠券列表
		byOrder:mockHost+"Coupon/getCouponListByOrderMount",    // 订单可用优惠券列表
		receiveCoupon:mockHost+"Coupon/receiveCoupon",
		activity:mockHost+"Home/activity",       // 活动首页列表 (限时)
		getCouponRemark:mockHost + 'Coupon/getCouponRemark'    //获取优惠券备注说明

	},
	jifen:{
		list:mockHost+"User/memberIntegral",  // 积分列表
		//byOrder:mockHost+"Jifen/getJifenListByOrderMount",    // 订单可用积分列表
		//receivejifen:mockHost+"Jifen/receiveJifen",          //获取积分
		//activity:mockHost+"Home/activity",       // 活动首页列表 (限时)
		//getjifenRemark:mockHost + 'Jifen/getJifenRemark'    //获取积分备注说明

	},
	freeeat:{
		list:mockHost+"FreeEat/activityList",  // 免费品尝列表
		getFreeeatDetail:mockHost+"FreeEat/actDetail",   //获取品尝活动详情
		getverify:mockHost+"FreeEat/receiveActivity"    //获取品尝活动验证码
		
	},
	wechat:{
		pub:mockHost+"WechatPublic/getCode",
		oauth:mockHost+"User/oauth",
		mauth:mockHost+"WechatPublic/getInfo",
		sdk:mockHost+"JsSdk/getSignPackage",
		commentList:mockHost+"GoodsComment/commentList",
		isLook:mockHost+"jsSdk/validateMp",   //是否关注
		getHotCity:mockHost+"Area/getHotCity"
	},
	trytoeat:{
		list:mockHost2 + 'Tast/addTast'     // 试吃
	},
	togistics:{
		tost:mockHost + 'Logistics/getLogisticsTrace'  //查询快递跟踪
	},
	cashVoucher:{
		receiveCoupon:mockHost + 'Coupon/receiveCoupon',      //领取优惠券
		getCouponDetail:mockHost + 'Coupon/getCouponDetail'    //获取优惠券详情
	},
	push:{
		contactDetail:mockHost + 'MarketPushingRecommend/contactDetail'  //地推
	},
	msgPush:{
		msgPush:mockHost+'MessagePush/get_num',     //消息推送
		getInfo:mockHost+"KuaiDi/get_info",   //查看物流详情
		updateM:mockHost+'MessagePush/up_push'  //更新阅读状态
	}
	
}



//静态页面
var staticPages = {
	home:host+"index.html",
	detail:host+"html/commodity/detail.html",
	orderInfo:host+"/html/allchips/orderInfo.html",
	list:host+"html/list.html",
	classification:host+"html/classification.html",
	memcenter:host+"html/memcenter.html",
	login:host+"html/user/login.html",
	register:host+"html/user/register.html",
	ucenter:host+"html/user/ucenter.html",
	user:host+"html/user/user.html",
	bindPhone:host+'html/user/bindPhone.html',
	personalcenter:host+"html/personalcenter.html",
	oldUserBindPhone:host+'html/user/oldUserBindPhone.html',	//老用户绑定页面
	setPassword:host+'html/user/setPassword.html',				//老用户设置密码页面
	
	accountSecurity:host+"html/user/accountSecurity.html",//账户安全
	changePhone:host+"html/user/changePhone.html",
	changePassword:host+"html/user/changePassword.html",
	findPassword:host+"html/user/forPassword.html", //找回密码
	history:host+"html/commodity/history.html",
	fav:host+"html/commodity/favoriter.html",
	order:host+"html/order/index.html",
	orderDetail:host+"html/order/detail.html",	//订单详情
	checkOrder:host+"html/order/check.html",
	payOrder:host+"html/order/pay.html",
	feedback:host+"html/feedback.html",
	addressMan:host+"html/address/manage.html",
	addAddress:host+"html/address/add.html",
	editAddress:host+"html/address/edit.html",
	cart:host+"html/cart/cart.html",
	refund:host + "html/refund/index.html",
	refundDetail:host + "html/refund/detail.html",
	request:host + "html/refund/request.html",
	coupon:host+"html/coupon/index.html",

	couponDetail:host+"html/coupon/detail.html", // 优惠券详情页面

	balance:host+"html/order/balance.html",
	currency:host+"html/accounts/currency.html",
	paySuccess:host+"html/pay/paySuccess.html",
	payFailure:host+"html/pay/payFailure.html",
	wxAuth:host+"html/auth.html",
	collection:host+"html/user/collection.html",
	togistics:host+"html/togistics/togistics.html",  //查询快递跟踪
	recharge:host+"html/order/recharge.html",        //支付失败页面
	comments:host+"html/refund/comments.html",       //待评论
	commentsChild:host+"html/refund/commentsChild.html",       //评论
	push:host+"/html/push.html",
	Myraise:host+"/html/allchips/Myraise.html",
	details:host+"/html/allchips/details.html",
	honey:host+"/html/allchips/honey.html",
	chargeDetails:host+"html/order/chargeDetails.html",    // 充值详情
	check:host+"html/allchips/check.html",
	successPay:host+"html/allchips/pay.html",
	failurePay:host+"html/allchips/successPay.html",
	logistics:host+"html/allchips/logistics.html",
	luckyDraw:host+"html/allchips/luckyDraw.html",
	results:host+"html/friend/results.html",
	friendTes:host+"html/friend/friendTes.html",
	prizes:host+"html/allchips/prizes.html",          //领取奖品
	receive:host+"html/allchips/receive.html",         //添加地址
	
	premiumList:host+"html/wechatActivity/premiumList.html", //嗡嗡活动我的奖单
	systemList:host+"html/wechatActivity/systemList.html",    //嗡嗡活动系统奖单
	wengNotice:host+"html/wechatActivity/notice.html",       // 嗡嗡活动说明
	activity:host+"html/activitys/helpcode.html",      //助力码
	activityAddmi:host+"html/activitys/addmi.html",		//加蜜友
	activityDefault:host+"html/activitys/default.html",	//活动首页
	
	AnniversaryDefault:host+"html/anniversary/index.html", //十周年庆抽奖活动首页
	AnniversaryNotice:host+"html/anniversary/notice.html",  //十周年庆活动说明页
	AnniversaryMyList:host+"html/anniversary/proList.html",  //十周年庆我的奖品页
	

	deposit:host+"html/deposit/index.html",    //充值,挥金页面
	
	fullprivileges:host+"html/fullprivileges.html",						//全部特权
	IntegralDeduction:host+"html/privileges/integralDeduction.html",	//积分抵扣
	BirthdayGift:host+"html/privileges/BirthdayGift.html",				//生日有礼
	FreeTasting:host+"html/privileges/FreeTasting.html",				//免费品尝
	UpgradeGift:host+"html/privileges/UpgradeGift.html",				//升级有礼
	FreeTravel:host+"html/privileges/FreeTravel.html",                  //免费旅行
	PhysicalExamination:host+"html/privileges/PhysicalExamination.html",//健康体检
	about:host+"html/privileges/about.html", 							//关于必好货
	
	messageCheck:host+"html/messagePush/check.html"
}