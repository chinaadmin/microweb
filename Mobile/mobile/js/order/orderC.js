angular.module('app.controllers', [])
.directive('errSrc', function() {
  return {
    link: function(scope, element, attrs) {    	
      element.bind('error', function() {      	
        if (attrs.src != attrs.errSrc) {
          attrs.$set('src', attrs.errSrc);          
        }
      });
      if (attrs.$attr['ngSrc']&&!attrs.ngSrc) {
      	 attrs.$set('src', attrs.errSrc);  
      };

    }
  }
})

.directive('ngImagescale', function() {
  return {
    link: function(scope, element, attrs) {  
    	if (element[0].tagName=="IMG") {
			element.bind('load', function() {
				if (attrs.ngImagescale&&(attrs.ngImagescale.split(":")).length==2) {
					var size = attrs.ngImagescale.split(":");
					var w= parseInt(size[0])||0,
						h = parseInt(size[1])||0;
						if (w&&h) {
							element[0].height = element[0].width*h/w;							
						}else{
							element[0].height = element[0].width;
						}					
				} else {
					element[0].height = element[0].width;
				}
			});
    	};
    }
  }
})
.directive('ngBack', function(storage) {
	return {
		link: function(scope, element, attrs) {
			storage.init();
			var referer = storage.get("referer")||"";
			var url = location.href.replace(location.search,'');
			var reg = new RegExp(url,"img");
			var loginReg = /http[^?#=]+login.html/img
			element.bind('click', function() {

				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else if (!reg.test(referer) && !loginReg.test(referer)) {
					storage.toPage(referer);
					return false;
				} else {
					window.history.go(-1);
				};

			});
		}
	}
})
.controller("orderC",function ($scope,orderS,storage,ngMessage) {	
	storage.init();
	
	$scope.isDisplay = false;
	var index = 1;	
	var type= storage.queryField("type")||storage.getOnce("orderType")||0;      //getOnce

	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
	};
	var resText=[
		"暂无订单",
		"暂无待支付订单",
		"暂无待收货订单",
		"暂无完成订单",
		"暂无取消订单",
		"暂无待发货订单"
	]
	$scope.hasMore = false;
	$scope.order=[];
	$scope.act=type;
	$scope.hasRes = true;
	$scope.noRes="";
	function getList() {	
		$scope.hasRes = true;	
		orderS.order({
			"token": storage.get("token") || "",
			"keyword": "",
			"type": type,
			"currentPage": index,
			"count": 10
		}, function(res) {
			if (res && res.resCode == "SUCCESS") {				
				if (res.lastPage == "1") {
					$scope.hasMore=true;
				}else{
					$scope.hasMore=false;
				};
				if (type == "2" || type =="3" ) {  
					$scope.isDisplay = true;
				} else {
					$scope.isDisplay = false;
				}
				
				if (index>1) {
					var tmp = angular.copy($scope.order);
					$scope.order = tmp.concat(res.orderList);
				}else{
					$scope.order = res.orderList;
				};
				if (!$scope.order.length) {
					$scope.hasRes = false;
					$scope.noRes = resText[type];
				};
			} else if (res) {
				ngMessage.showTip(res.Msg)
			};
		})
	};
	getList();

	$scope.switch = function (ty) {
		if (ty!=type) {
			type = ty;
			index=1;
			getList();
		};
		$scope.act = ty;
	};
	$scope.more=function () {
		if ($scope.hasMore) {
			index++;
			getList();
		};
	}

    $scope.toMin = function () {
    	storage.toPage("home")
    }
	$scope.detail = function (v) {
		 storage.set("orderItem",v);
		 storage.toPage("orderDetail");
	}
	//取消订单
	$scope.cancel = function (v) {

		ngMessage.show("取消该订单？", function() {
			orderS.cancel({
				"orderId": v.id,
				"token": token
			}, function(res) {
				if (res) {
					ngMessage.showTip(res.resMsg)
					$scope.reload();
				};
			})
		});
	};

	$scope.pay = function(v) {
		storage.set("orderItem", v);
		storage.toPage("payOrder");
	};
	//删除订单
	$scope.del = function(v) {
		ngMessage.show("删除该订单？", function() {
			orderS.del({
				"orderId": v.id,
				"token": token
			}, function(res) {
				if (res) {
					ngMessage.showTip(res.resMsg);
					$scope.reload();
				};
			})
		});
	};
	//确认订单
	$scope.sure = function (v) {

		ngMessage.show("确认收货？", function() {
			orderS.sure({
				"orderId": v.id,
				"token": token
			}, function(res) {
				if (res) {
					ngMessage.showTip(res.resMsg);
					setTimeout(function(){
						$scope.switch(3);
					}, 1500)
					$scope.reload();
				};
			})
		});		
	};
	$scope.reload = function () {
		index=1;
		getList();
	}
	//查看物流
	$scope.togtics = function (v) {
	    window.location.href=host+"html/messagePush/check.html?rec_id="+v;
	};
	//退款退货
	$scope.refund = function (v) {
		storage.set("orderId",v.recId);
		storage.toPage("request");
	}

	//  待评论
	$scope.comments = function (v) {
		storage.set("orderId",v.recId);
		storage.toPage("comments", "?type=6");
	};
	// 评论
	$scope.commentsChild = function (orderId,src,goodsId) {
	    storage.set("goods_order",{'orderId':orderId,'goods_src':src,'goodsId':goodsId});
		storage.toPage("commentsChild");
	};

})

.controller("commentsC",function ($scope,orderS,storage,ngMessage) {	
	storage.init();

	var token = storage.get("token");

	var goods_order = storage.get("goods_order");
	$scope.goods_order = goods_order;

	// goods_order && orderS.commitComment({
	// 	"orderId":goods_order.orderId,
	// 	"goodsId":goods_order.goodsId,
	// 	"token":token
	// },function (res) {
	// 	console.log(res)
	// });

	matchStart = '';
	sellerStart = '';
	logisticsStart = '';
	$scope.content = '';

	$scope.descr = {
		'index0':"/images/wuxin.png",
		'index1':"/images/wuxin.png",
		'index2':"/images/wuxin.png",
		'index3':"/images/wuxin.png",
		'index4':"/images/wuxin.png"
	};
	$scope.sellerStart = {
		'index0':"/images/wuxin.png",
		'index1':"/images/wuxin.png",
		'index2':"/images/wuxin.png",
		'index3':"/images/wuxin.png",
		'index4':"/images/wuxin.png"
	};
	$scope.logisticsStart = {
		'index0':"/images/wuxin.png",
		'index1':"/images/wuxin.png",
		'index2':"/images/wuxin.png",
		'index3':"/images/wuxin.png",
		'index4':"/images/wuxin.png"
	};
	$scope.option = function(type,num) {
		if(type == 1){
			matchStart = num;
			for (var i = 0; i <= num; i++) {
				if($scope.descr['index'+num] == "/images/wuxin-color.png" && num <= 3 && $scope.descr['index'+(num+1)] == "/images/wuxin-color.png"){
					continue;
				}	
				if($scope.descr['index'+num] == "/images/wuxin-color.png"){
					$scope.descr['index'+i] = "/images/wuxin.png";
				}else{
					$scope.descr['index'+i] = "/images/wuxin-color.png"
				}
			}
			for (var i = num + 1; i <= 4; i++) {
				$scope.descr['index'+i]  = "/images/wuxin.png";
			}
		}
		if(type == 2){
			sellerStart = num;
			for (var i = 0; i <= num; i++) {
				if($scope.sellerStart['index'+num] == "/images/wuxin-color.png" && num <= 3 && $scope.sellerStart['index'+(num+1)] == "/images/wuxin-color.png"){
					continue;
				}	
				if($scope.sellerStart['index'+num] == "/images/wuxin-color.png"){
					$scope.sellerStart['index'+i] = "/images/wuxin.png";
				}else{
					$scope.sellerStart['index'+i] = "/images/wuxin-color.png"
				}
			}
			for (var i = num + 1; i <= 4; i++) {
				$scope.sellerStart['index'+i]  = "/images/wuxin.png";
			}
		}
		if(type == 3){
			logisticsStart = num;
			for (var i = 0; i <= num; i++) {
				if($scope.logisticsStart['index'+num] == "/images/wuxin-color.png" && num <= 3 && $scope.logisticsStart['index'+(num+1)] == "/images/wuxin-color.png"){
					continue;
				}	
				if($scope.logisticsStart['index'+num] == "/images/wuxin-color.png"){
					$scope.logisticsStart['index'+i] = "/images/wuxin.png";
				}else{
					$scope.logisticsStart['index'+i] = "/images/wuxin-color.png"
				}
			}
			for (var i = num + 1; i <= 4; i++) {
				$scope.logisticsStart['index'+i]  = "/images/wuxin.png";
			}
		}
	}
	

	$scope.submit = function() {
		var data = {
			matchStart:matchStart+1,
			sellerStart:sellerStart+1,
			logisticsStart:logisticsStart+1,
			content:$scope.content,
			"orderId":goods_order.orderId,
			"goodsId":goods_order.goodsId,
			"token":token
		};
		if (!matchStart&&matchStart!==0) {
		    ngMessage.showTip("请给商品描述评论一番吧！")
		    return
	    };
	    if ($scope.content=="") {
		    ngMessage.showTip("请对该商品进行评论！")
		    return
	    };
	    if (!sellerStart&&sellerStart!==0) {
		    ngMessage.showTip("请给卖家服务点个赞吧！")
		    return
	    };
	    if (!logisticsStart&&logisticsStart!==0) {
		    ngMessage.showTip("请给物流服务点个赞吧！")
		    return
	    };
	   
		goods_order && orderS.commitComment(data,function (res) {
			if(res.resCode == "SUCCESS"){
				ngMessage.showTip(res.resMsg,2000);
				setTimeout(function(){
					storage.toPage("memcenter");
				}, 2000)
			}else{
				ngMessage.showTip(res.resMsg);
			}
		});
	}
	
		

})


.controller("checkC",function ($rootScope,$scope,orderS, invoiceS ,storage,ngMessage,ngWechat) {	
	storage.init();
	var ids = storage.get("cartId");
	var token = storage.get("token");
	storage.set("is_order","1");
	var wx = storage.get("wx");
	var user_id = storage.get("uid");
	// console.log(user_id);return false;
	if (!token) {
		storage.toPage("login")
	};
	var payPrice = $scope.payPrice= 0;
	var payPostData=null;
	var submitedData = null;
	$scope.cartBox = {
		"cacheShipment":0,//缓存普通快递运费
		"shipment":0,
		"goodsPrice":0,
		"payPrice":0,
		"money":0,
		"goodsList":[],
		"totalPrice":0,
		'queryShipment':false
	};
	$scope.bestCoin={		
		"maxBcoin":0,
		"bcoin":0,
		"coin":0,
		"usecoin":false,
		"use":function () {
			$scope.bestCoin.usecoin = !$scope.bestCoin.usecoin;
			if (!$scope.bestCoin.usecoin) {
				$scope.bestCoin.bcoin = 0;
			};
		},
		"init":function () {
			orderS.coin({
				token:token,
			},function (res) {
				if (res&&res.resCode=="SUCCESS") {
					$scope.bestCoin.maxBcoin = parseFloat(res.integral)
				};
			})
		}
	}

	//$scope.bestCoin.init();
	// init 初始化
	orderS.goShopping({
		"cartId": ids,
		"token": token
	}, function(res) {
		// console.log(res);return false;
		if (res.resCode == "SUCCESS") {
			$scope.cartBox = res;
			$scope.cartBox.totalPrice = res.payPrice;
			$scope.cartBox.tPrice = res.payPrice;
			payPrice = $scope.cartBox.payPrice;
			integral = $scope.cartBox.integral;
			//goodsList = $scope.cartBox.goodsList;
			//console.log(goodsList.express);
		 res.shipment = '0';
		 $scope.cartBox.shipment = parseInt(res.shipment);
			
			$scope.coupon.list(payPrice,res.goodsList);
			if (res.selPayWay) {
				var i = res.selPayWay == "ACCOUNT" ? 1 : 0;
				$scope.payby.get(i,1);
			};
			$scope.psstyle.ways.length=255;
			for (var i = 0; i < res.deliveryWay.length; i++) {
				if (res.deliveryWay[i].id=="0") {
					$scope.psstyle.ways[0]=res.deliveryWay[i];
				};
				if (res.deliveryWay[i].id=="1") {
					$scope.psstyle.ways[1]=res.deliveryWay[i];					
				};
				if (res.deliveryWay[i].id=="2") {
					$scope.psstyle.ways[2]=res.deliveryWay[i];					
				};
			};

			var sid = res.selDeliveryStores;
			
			if (!res.selDeliveryWay || true) { //不设置默认配送方式
				return false
			};
			var selectIndex = parseInt(res.selDeliveryWay)||0;
			if ($scope.psstyle.list.length) {
				var list = $scope.psstyle.list;
				for (var i = 0; i < list.length; i++) {
					list[i].active = "";
					if (list[i].storesId==sid) {
						$scope.psstyle.use(list[i])			
						break
					};
				};
				
			}else{
				$scope.psstyle.store.sid = sid;
			}
			$scope.psstyle.select(selectIndex)
		} else {
			// storage.toPage("cart");
			res && ngMessage.showTip(res.resMsg);
		}
		if (res.resCode == "UNKNOWN_ERROR") {
			setTimeout(function(){
				storage.toPage("cart");
			}, 1500)
		}
	});
	var payedMsg="订单已提交，请重新登录微信后再进行支付";
	$scope.page={
		title:"订单详情",
		showBackbtn:true,
		userfeedback:"",
		currentLayout:"index",
		prevLayout:"",
		ppLayout:"",
		pay:function () {
			
		},
		
		submited:false,
		submit:function() {
				if ($scope.page.submited) {
					window.setTimeout(function(){
						$scope.page.submited = false;
					}, 2400)
					return
				};
				// $scope.page.submited = true;
				if (!$scope.recAddress.default.id && $scope.psstyle.page != 0) {
					ngMessage.showTip("请填写收货地址");
					return false;
				};
				if (!$scope.psstyle.msg) {
					ngMessage.showTip("请选择配送方式");
					return false;
				};
				if (!$scope.payby.msg) {
					ngMessage.showTip("请选择支付方式");
					return false;
				};
				if(($scope.psstyle.page == 1) && !$scope.recAddress.default.id){
					
				}
				if($scope.page.submited == false) {
					$scope.page.submited = true;
					var post={
						shippingType:$scope.psstyle.page,
						addressId:$scope.recAddress.default.id, //
						storesId:$scope.psstyle.store.storesId, //
						storesTime:$scope.psstyle.uTime.TIME,
						invoiceId:$scope.invoice.default.uid, //
						invoicePayee:$scope.invoice.content,
						couponId:$scope.coupon.default.id, //券
						integral: $scope.bestCoin.bcoin,
						postscript:$scope.page.userfeedback,
						payType:($scope.payby.currentItem?'ACCOUNT':'WEIXIN'),
						cartIds:ids,
						token:token,
						uid:user_id
					};
					if (post.shippingType!=0) {
						post.storesId = $scope.psstyle.sdid;
					};
					
					if (payPostData) {
						ngWechat.pay(payPostData, function(rs) {
							if (rs.success) {
								storage.toPage("paySuccess")
							} else {
								storage.toPage("payFailure")
							}
						})
						return
					};
					
					// ngMessage.showTip("正在提交订单，请稍等片刻...",3000);
					ngMessage.loading("正在提交订单，请稍等片刻...");
					if (submitedData) {						
						ngMessage.showTip(submitedData.resMsg);
						if (post.payType == "WEIXIN" && submitedData.isPay == "1") {
							if (!wx || !wx.openid) {
								ngMessage.showTip(payedMsg);
								setTimeout(function() {
									storage.set("wxReferer","payOrder");
									storage.toPage("wxAuth");

								}, 1000);						
								return false;
							};
							orderS.pay({
								token: token,
								orderId: submitedData.orderId,
								openid: wx.openid
							}, function(dat) {
								if (dat.resCode == "SUCCESS") {
									payPostData = dat.data;
									ngWechat.pay(payPostData, function(rs) {
										if (rs.success) {
											storage.toPage("paySuccess")
										} else {
											storage.toPage("payFailure")
										}
									})
								};
							});
						} else { //使用余额支付
							storage.toPage("paySuccess")
						}
						return false
					};
					// var wx = storage.get("wx");
					orderS.submit(post,function (res) {
						// console.log(res);return false;
						$scope.page.submited = false;
						if(res.toBind=="toBind"){
							storage.set("toBind","toBind");
						}else{
							storage.set("toBind","");
						}
						
						var tobind=storage.get("toBind");
						if (res&&res.resCode=="SUCCESS") {
							res.id = res.orderId;
							storage.set("orderItem",res);
							if (post.payType == "WEIXIN" && res.isPay == "1") {
								if (!wx || !wx.openid) {   //备注
									ngMessage.showTip(payedMsg);
									setTimeout(function() {
										storage.set("wxReferer","payOrder");
										storage.toPage("wxAuth");
									}, 1000);
									return false;
								};
								orderS.pay({
									token: token,//orderItem.id,
									orderId: res.orderId,
									openid: wx.openid
								}, function(dat) {
									if (dat.resCode == "SUCCESS") {
										submitedData = res;
										ngMessage.hide();
										ngWechat.pay(dat.data, function(rs) {
											if(tobind=="toBind"){
												storage.set("payRes","SUCCESS");
																			
												storage.toPage("oldUserBindPhone");
												return false;
											}
											
											storage.toPage("paySuccess");
										}, function() {
											if(tobind=="toBind"){
												storage.set("payRes","ERROR");
												storage.toPage("oldUserBindPhone");
												return false;
											}
											
											storage.toPage("payFailure");
										})
									};
								});
							} else { //使用余额支付
								if(tobind == "toBind"){
									storage.set("payRes","SUCCESS");
									
									storage.toPage("oldUserBindPhone");
									return false;
								}
								storage.toPage("paySuccess");
							}
						}else if (res) {
							ngMessage.showTip(res.resMsg);
						};
					})
				}else{
					return false;
				}
			
		}
	}


	$scope.layout={
		"invoice":{
			"show":false,
			"title":"填写发票信息"
		},
		"index":{
			"show":true,
			"title":"订单确认",
			"back":true,
			"history":true
		},
		"psstyle":{
			"show":false,
			"title":"配送方式"
		},
		"invoiceInfo":{
			"show":false,
			"title":"发票说明",
			"back":true
		},
		"payby":{
			"show":false,
			"title":"选择支付方式"
		},
		"cartList":{
			"show":false,
			"title":"商品清单",
			"back":true
		},
		"coupon":{
			"show":false,
			"title":"使用优惠券",
			"back":true
		},
		"address":{
			"show":false,
			"title":"收货地址",
			"back":true
		},
		"recAdd":{
			"show":false,
			"title":"新增收货地址",
			"subTitle":"修改地址",
			"back":true
		}
	}

	$scope.switchLayout = function(layout,b) {
		if ($scope.layout.hasOwnProperty(layout)) {
			for (var i in $scope.layout) {
				if ($scope.layout.hasOwnProperty(i)) {
					$scope.layout[i].show = false;
				}
			}
			
			$scope.layout[layout].show = true;
			$scope.page.title = $scope.layout[layout].title+"";
			if (b) {
				$scope.page.title = $scope.layout[layout].subTitle+"";
			};	
			$scope.page.showBackbtn = $scope.layout[layout].back;
			$scope.page.ppLayout = $scope.page.prevLayout ;
			$scope.page.prevLayout = $scope.page.currentLayout;
			$scope.page.currentLayout = layout;
		};
		
	}
	$scope.switchLayout("index");
   /*
   $scope.support=function(){
    orderS.goShopping({
		"cartId": ids,
		"token": token
	}, function(res) {
     console.log(res.goodsList);
      for (var i = 0; i < res.goodsList.length; i++) {
			if (res.goodsList[i].express == 0) {
				//$scope.psstyle.use(res.stores[i]);
				alert('kkk');
				break
			};
			};
	        });
          }

    $scope.support();
    */
	//配送方式
	var yyname="";
	var pssmsg="送货上门不满足条件提示：\n门店附近500米内满30元免费配送！因您的地址或订单额不满足条件，恕不支持送货上门，请选择其他配送方式 ";
    //var pasmsgss=$scope.psstyle.goodsname;
	var pssmsgss="不支持普通快递";
	$scope.psstyle={
		"msg":"", //选择的配送标题
		"page":2, //配送方式选项
		"store":{},//门店自提的门店
		"list":[], //自提门店列表
		"uTime":{},//自提时间
		"time":[],//自提时间列表【往后一周】		
		"sdid":"",//送货上门的门店的id
		"storesDistance":null,//送货上门的门店
		"webStoresDistance":null,//缓存web送货上门的门店请求数据
		"goodsname":null,//商品名称
		"ways":[],//配送方式
		"select":function (index,init) {
			if (index==2&&!$scope.psstyle.storesDistance) {	
				!init&&$scope.psstyle.storesDistanceMethod();		
				return  false
			};
			if(index==1&&!$scope.psstyle.goodsname){
				//alert('pppp');
				!init&&$scope.psstyle.support();
				return  false;
			}
			$scope.psstyle.page = index;
			if (index==0&&!$scope.psstyle.list.length) {				
				orderS.address({
					token:token
				},function (res) {
					if (res&&res.resCode=="SUCCESS"&&res.stores&&res.stores.length) {
						$scope.psstyle.list = res.stores;//配送的门店列表
						var sid = $scope.psstyle.store.sid; //历史配送的门店id
						//$scope.psstyle.use(res.stores[0]); //默认第一个门店为自提门店
						if (sid) {
							for (var i = 0; i < res.stores.length; i++) {
								if (res.stores[i].storesId == sid) {
									$scope.psstyle.use(res.stores[i]);
									break
								};
							};
						}					
					};
				})
			};

			if (init) {
				return false
			};
			switch(index){
				case 1:
					$scope.psstyle.msg="普通快递";
					break;
				case 2:
					$scope.psstyle.msg="送货上门";

					break;
				default:
					$scope.psstyle.msg="门店自提";
					break;
			}
		},
		"onSwitch":function () {
			if($scope.psstyle.page == 0 && angular.isUndefined($scope.psstyle.store.storesId)){ //门店自提未选择门店
				ngMessage.showTip("请选择自提门店");
				return;
			}else if(($scope.psstyle.page == 1 && !$scope.cartBox.queryShipment) || ($scope.psstyle.page == 2)){ //普通快递
				$scope.cartBox.queryShipment = true;
				orderS.shipmentPrice({
					token:token,
					addressId:$scope.recAddress.default.id,
					orderMoney:$scope.cartBox.payPrice,
					shippingType:$scope.psstyle.page,//普通快递
					orderWeight:$scope.cartBox.weight,
				},function(res){
   				  $scope.cartBox.shipment = res.shipmentPrice || 0;
						cwatch = $scope.$watch("coupon.default",function (n,o,s) {
							     if(n.money){
							     	    if(($scope.cartBox.tPrice-n.money)<= 0){
													$scope.cartBox.totalPrice= $scope.cartBox.shipment?$scope.cartBox.shipment:0;
														}else{
															$scope.cartBox.totalPrice = ($scope.cartBox.tPrice-n.money+$scope.cartBox.shipment*1).toFixed(2);
												}
							     }else{
							     	  $scope.cartBox.totalPrice = ($scope.cartBox.tPrice*1+$scope.cartBox.shipment*1).toFixed(2);
							     }
						})
				});
			}else if($scope.psstyle.page == 1){//读缓存
				orderS.shipmentPrice({
					token:token,
					addressId:$scope.recAddress.default.id,
					orderMoney:$scope.cartBox.payPrice,
					shippingType:1,//普通快递
					orderWeight:$scope.cartBox.weight,
				},function(res){
   				  $scope.cartBox.shipment = res.shipmentPrice || 0;
						cwatch = $scope.$watch("coupon.default",function (n,o,s) {
							     if(n.money){
							     	    if(($scope.cartBox.tPrice-n.money)<= 0){
													$scope.cartBox.totalPrice= $scope.cartBox.shipment?$scope.cartBox.shipment:0;
														}else{
															$scope.cartBox.totalPrice = ($scope.cartBox.tPrice-n.money+$scope.cartBox.shipment*1).toFixed(2);
												}
							     }else{
							     	  $scope.cartBox.totalPrice = ($scope.cartBox.tPrice*1+$scope.cartBox.shipment*1).toFixed(2);
							     }
						})
				});
			}else if($scope.psstyle.page == 0){ //门店自提
				$scope.cartBox.shipment = 0;
				cwatch = $scope.$watch("coupon.default",function (n,o,s) {
							     if(n.money){
							     	    if(($scope.cartBox.tPrice-n.money)<= 0){
													$scope.cartBox.totalPrice= $scope.cartBox.shipment?$scope.cartBox.shipment:0;
														}else{
															$scope.cartBox.totalPrice = ($scope.cartBox.tPrice-n.money-$scope.cartBox.shipment).toFixed(2);
												}
							     }else{
							     	  $scope.cartBox.totalPrice = $scope.cartBox.tPrice-$scope.cartBox.shipment;
							     }
						})
			}else if($scope.psstyle.page==2){  //送货上门
				$scope.cartBox.shipment = 0;
				cwatch = $scope.$watch("coupon.default",function (n,o,s) {
							     if(n.money){
							     	    if(($scope.cartBox.tPrice-n.money)<= 0){
													$scope.cartBox.totalPrice= $scope.cartBox.shipment?$scope.cartBox.shipment:0;
														}else{
															$scope.cartBox.totalPrice = ($scope.cartBox.tPrice-n.money-$scope.cartBox.shipment).toFixed(2);
												}
							     }else{
							     	  $scope.cartBox.totalPrice = $scope.cartBox.tPrice-$scope.cartBox.shipment;
							     }
						})
			}
			
			var tmp = parseFloat($scope.cartBox.payPrice) + parseFloat($scope.cartBox.shipment);  //计算总价
			if($scope.cartBox.totalPrice != tmp){
//				$scope.cartBox.totalPrice = tmp;
			}
			$scope.switchLayout('index'); 
			$scope.psstyle.select($scope.psstyle.page)
		},
		"use":function (v) {
			for (var i = 0; i < $scope.psstyle.list.length; i++) {
				$scope.psstyle.list[i].active="";
				if ($scope.psstyle.list[i].storesId==v.storesId) {
					$scope.psstyle.list[i].active="active";
				};
			};
			$scope.psstyle.store = v;

		},

        "support":function(){
          orderS.goShopping({
		   "cartId": ids,
		   "token": token
	    }, function(res) {
        //console.log(res.goodsList);
        $scope.goodslists=res.goodsList;
        var goodslistst=$scope.goodslists;
        //console.log(goodslistst);
        for (var i = 0; i <goodslistst.length; i++) {
        	if (goodslistst[i].express == 0) {
         for (var y = 0; y <goodslistst.length; y++) {
			if (goodslistst[y].express == 0) {
				
				yyname+=goodslistst[y].goods.name;
				
				//pssmsgss="不支持普通快递";
				//pssmsgss=yyname+pssmsgss;
				//ngMessage.showTip(pssmsgss, 2400);
				//return false;
			}
            /*
			else{
               $scope.psstyle.goodsname=goodslistst[i].goods.name;
				 break;
                 return true;
			}
			*/
			//return true;
			};
            pssmsgss="不支持普通快递";
			pssmsgss=yyname+pssmsgss;
			ngMessage.showTip(pssmsgss, 2400);
			yyname="";
			return false;
		}else{
			     $scope.psstyle.goodsname=goodslistst[i].goods.name;
				 //break;
                 return true;
		}
		}
	        });
          },

		"storesDistanceMethod":function (select) {
			if ($scope.recAddress.default) {
				if ($scope.psstyle.webStoresDistance) {
					if ($scope.psstyle.webStoresDistance.stores_id == "0") {
						ngMessage.showTip(pssmsg, 2400)
					};
					return
				};
				orderS.storesDistance({
					addressId: $scope.recAddress.default.id,
					token: token,
					orderMoney: payPrice,
					orderWeight: $scope.cartBox.weight || 0
				}, function(res) {
					if (res && res.resCode == "SUCCESS") {
						$scope.psstyle.webStoresDistance = res;
						$scope.cartBox.shipment = res.shipment_price;
						$scope.psstyle.sdid = parseInt(res.stores_id) || "";
						var list = $scope.psstyle.list;
						//console.log(list);
						for (var i = 0; i < list.length; i++) {
							if (list[i].storesId == res.stores_id) {
								$scope.psstyle.storesDistance = list[i];
								$scope.psstyle.select(2,true); 
								break;
							};
						};		
						$scope.$applyAsync();
					};

				});
				return true;
			} else {
				ngMessage.showTip(pssmsg,2400);
				return false;
			}
		},
		showZore:function(){
			if ($scope.psstyle.msg&&!$scope.psstyle.page) {
				return true
			};
			return false
		},
		reInit:function(pindex){
			this.msg="";	
			this.page = isNaN(pindex)?0:pindex;		
		}
	};
	$scope.psstyle.select(0,true);

	function ctime() {
		var dtime = 24 * 60 * 60 * 1000;
		var DAY = "日一二三四五六"
		var Time = new Date().getTime();
		var Things = [];
		for (var i = 0; i < DAY.length; i++) {
			var d = (new Date(Time + i * dtime));
			var o = {
				"day": "周" + DAY.charAt(d.getDay()),
				"date": (d.getMonth() + 1) + "月" + d.getDate() + "日",
				"TIME":d.getFullYear()+"-"+(d.getMonth() + 1)+"-"+d.getDate()
			};
			o.time = o.date + "[" + o.day + "]";
			Things.push(o)
		};
		$scope.psstyle.uTime = Things[0];
		$scope.psstyle.time = Things;
	};
	ctime()
	//发票
	$scope.invoice={
		"msg":"填写发票信息",
		"content":"",
		"default":{},
		"res":[],
		"get":function(type) {
			if (type) { //确定
				if (!$scope.invoice.content) {
					ngMessage.showTip("请填写抬头！");
					return false;
				};
				$scope.invoice.msg = $scope.invoice.content+'/'+"商品详情"
			}else{
				$scope.invoice.msg = "不开发票"
			};
			if ($scope.invoice.content!=$scope.invoice.default.invoicePayee) {
				$scope.invoice.default.uid=""
			}else{
				$scope.invoice.default.uid = $scope.invoice.default.id;
			}
			$scope.switchLayout("index");
		},
		"list":function () {
			invoiceS.list({
				"token":token
			},function (res) {
				$scope.invoice.res = res.invoiceList||[];
				if (res.invoiceList&&res.invoiceList.length) {
					$scope.invoice.content = res.invoiceList[0].invoicePayee;
					$scope.invoice.default = res.invoiceList[0];
					$scope.invoice.default.uid = $scope.invoice.default.id;
					if ($scope.invoice.content) {
						$scope.invoice.get(1);
					};
				};
			})
		}
		
	};
	$scope.invoice.list();
	//支付方式选择
	$scope.payby={
		"msg":"",
		"currentItem":0,
		"get":function(index,noUserOpera) {		
			if (index) {
				var money = parseFloat($scope.cartBox.money)||0;
				var payPrice = parseFloat($scope.cartBox.payPrice)||0;
				if (money<payPrice&&!noUserOpera) {
					ngMessage.showTip("余额不足以支付该订单，请选择其他方式支付！")
					return  false
				};
				$scope.payby.msg="账户余额支付"
			}else{
				$scope.payby.msg="微信安全支付"
			}
			$scope.payby.currentItem = index;
		},
		"onSwitch":function () {
			$scope.payby.get($scope.payby.currentItem)
		}
	}

	//收货地址列表管理
	$scope.recAddress = {
		"list": [],
		"default": {},
		"edit":null,
		"setDefault": function(v) {
			orderS.setDefault({
				"addressId": v.id,
				"token": token
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					v.isDefault = "1";
					$scope.recAddress.get();
				};
			});
		},
		"get": function(id) {
			orderS.addressRec({
				"token": token
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					$scope.recAddress.list = res.recaddressList;
					var b = false;
					for (var i = 0; i < res.recaddressList.length; i++) {
						if ((res.recaddressList[i].isDefault == "1"&&!id)||(id && id == res.recaddressList[i].id)) {
							$scope.recAddress['default'] = res.recaddressList[i];
							b = true;
							break;
						};
					};
					if (!b) {
						$scope.recAddress.default = res.recaddressList[0] || {};
					};
					$scope.recAddress.default.use = true;
				};
			})
		},
		"setAddress":function (v) {		
			$scope.recAddress.edit = v;
			$scope.addressArea.username = v.name;
			$scope.addressArea.mobile = v.mobile;
			$scope.addressArea.address = v.address;
			for (var i = 0; i < $scope.addressArea.provice.length; i++) {
				if ($scope.addressArea.provice[i].provice_id==v.provice) {
					$scope.addressArea.useProvice = $scope.addressArea.provice[i];
					break;
				};
			};
		},
		"set":function (v) {
			for (var i = 0; i < $scope.recAddress.list.length; i++) {
				$scope.recAddress.list[i].use = false;
			};
			v.use = true;
			if(!angular.equals($scope.recAddress.default,v)){ //修改收货地址为另一个地址
				$scope.recAddress.resetAddress();	
			}
			$scope.recAddress.default  = v;
			//getStoresDistance(v.id)
		},
		'resetAddress':function(){
			$scope.psstyle.sdid = '';
			$scope.psstyle.storesDistance = null;
			$scope.psstyle.webStoresDistance = null;
			$scope.psstyle.msg = null;
			$scope.cartBox.queryShipment = false;//
			$scope.cartBox.cacheShipment = 0;//
		}
	}
	$scope.recAddress.get();

	//新增/编辑地址管理
	$scope.addressArea={
		"username":"",
		"mobile":"",
		"address":"",
		"provice":[],
		"useProvice":null,
		"city":[],
		"useCity":null,
		"county":[],
		"useCounty":null,
		"isDefault":0,
		"getPr":function () {
			orderS.area({},function(res) {
				if (res) {
					$scope.addressArea.provice = res.data||[];
				};				
			});
			$scope.addressArea.onChange()
		},
		"getCi":function (pid) {
			orderS.area({
				"pid":pid
			}, function(res) {
				if (res) {
					$scope.addressArea.city = res.data || [];
					if ($scope.recAddress.edit) {
						for (var i = 0; i < res.data.length; i++) {
							if (res.data[i].city_id == $scope.recAddress.edit.city) {
								$scope.addressArea.useCity = res.data[i];
								break;
							};
						};
					};
				};
			})
		},
		"getCo":function (pid,cid) {
			orderS.area({
				"pid":pid,
				"cid": cid
			}, function(res) {
				if (res) {
					$scope.addressArea.county = res.data || [];
					if ($scope.recAddress.edit) {
						for (var n = 0; n < res.data.length; n++) {
							if (res.data[n].county_id == $scope.recAddress.edit.county) {
								$scope.addressArea.useCounty = res.data[n];
								break;
							};
						};
					};
				};
			})
		},
		"onChange":function () {
			var p = $scope.$watch("addressArea.useProvice", function(n, o) {
				$scope.addressArea.useCity=null;
				$scope.addressArea.useCounty=null;
				if (n) {
					$scope.addressArea.getCi(n.provice_id);
				};

			});
			var ci = $scope.$watch("addressArea.useCity", function(n, o) {
				$scope.addressArea.useCounty=null;
				if (n) {
					$scope.addressArea.getCo(n.province_id,n.city_id)
				};
			});
		},
		"add":function () {
			var data={
				"token":token,
				"mobile":$scope.addressArea.mobile,
				"provice":$scope.addressArea.useProvice.provice_id||"",
				"city":$scope.addressArea.useCity.city_id||"",
				"county":$scope.addressArea.useCounty.county_id,
				"address":$scope.addressArea.address,
				"name":$scope.addressArea.username,
				"isDefault":$scope.addressArea.isDefault?1:0
			};

			if (!data.address) {
				ngMessage.showTip("请填写详细地址！");
				return false
			};
			if (!data.mobile||!/^[1][35867][0-9]{9}$/.test(data.mobile)) {
				ngMessage.showTip("请有效的手机号！");
				return false
			};
			if ($scope.recAddress.edit&&$scope.recAddress.edit.id) {
				data.addressId = $scope.recAddress.edit.id;
				orderS.updateRecAddress(data,function(res) {
					if (res.resCode=="SUCCESS") {
						ngMessage.showTip(res.resMsg);
						$scope.switchLayout("index");
						$scope.recAddress.get(data.addressId);
					}else{
						res&&ngMessage.showTip(res.resMsg);
					}
				});
			}else{
				orderS.addRecAddress(data,function(res) {
					if (res.resCode=="SUCCESS") {
						ngMessage.showTip(res.resMsg);
						$scope.switchLayout("index");
						$scope.recAddress.get(res.addressId);
					}else{
						res&&ngMessage.showTip(res.resMsg);
					}
				});
			}
		},
		"clear":function () {
			$scope.recAddress.edit = null;
			$scope.addressArea.username = "";
			$scope.addressArea.mobile = "";
			$scope.addressArea.address = "";
			$scope.addressArea.isDefault = 0;
			$scope.addressArea.useProvice = null;
			$scope.addressArea.useCity = null;
			$scope.addressArea.useCounty = null;
		},
		"del":function () {
			ngMessage.show("确定删除该收货地址！", function() {
				orderS.delRecAddress({
					"addressId": $scope.recAddress.edit.id,
					"token": token
				}, function(res) {
					res && ngMessage.showTip(res.resMsg);
					$scope.recAddress.get();
					$scope.switchLayout("index");
				})
			})
		},
		"setDefault":function () {
			if ($scope.recAddress.edit && $scope.recAddress.edit.id) {
				orderS.setDefault({
					"addressId": $scope.recAddress.edit.id,
					"token": token
				}, function(res) {
					if (res.resCode == "SUCCESS") {
						$scope.recAddress.get();
					};
				});
			};
		}
	}
	$scope.addressArea.getPr();

	$scope.coupon={
		"msg":"无优惠券使用",
		"tPrice":"",
		"hasMsg":"",
		"default":{},
		"res":[],
		"list":function (payPrice,goodsList) {
			var goodsIds = [];			
			goodsList = goodsList || [];
			for(var i = 0;i < goodsList.length ; i++){
				goodsIds.push(goodsList[i]['goods']['id']);	
			}
			goodsIds = goodsIds.join(',');
			orderS.coupon({
				"token":token,
				"currentPage":1,
				"order_amount":payPrice||0,
  			"goodsIds":goodsIds
			},function (res) {
				if (res&&res.resCode=="SUCCESS") {
					var leng = 0;
					$scope.coupon.res = res.data;
					 angular.forEach(res.data, function(value, key) {
						 leng++;
					     });
					if (leng) {
						$scope.coupon.hasMsg = "可使用"+leng;
						$scope.coupon.msg = "选择优惠券";
					};
				};
			})
		},
		"select":function (v) {
			for (var i = 0; i < $scope.coupon.res.length; i++) {
				if ($scope.coupon.res[i].id!=v.id) {
					$scope.coupon.res[i].active = false;
					$scope.coupon.msg = "选择优惠券";
					$scope.cartBox.totalPrice = $scope.cartBox.tPrice*1+$scope.cartBox.shipment*1;
				};
				
			};
			v.active = !v.active;
			 if(!v.active){
			 	  v='';
			 }
			$scope.coupon.default = v;
		}
	}
	

	$scope.back=function () {
		var layout = $scope.page.currentLayout;
		if ($scope.layout[layout].history) {
			storage.toPage(-1);
		}else{	
			if ($scope.page.ppLayout!="index") {
				$scope.switchLayout('index');
			}else{
				$scope.switchLayout($scope.page.prevLayout);
			}
			
		}		
	}

	orderS.wxsdk({
		url: window.location.href
	}, function(res) {
		if (res && res.resCode == "SUCCESS") {
			ngWechat && ngWechat.init({
				"appId": res.jssdk.appId,
				"timestamp": res.jssdk.timestamp,
				"nonceStr": res.jssdk.nonceStr,
				"signature": res.jssdk.signature
			})
		};
	});



//	var watch = $scope.$watch('bestCoin.bcoin', function(newValue, oldValue, scope) {
//		var nv = parseFloat(newValue)||0;
//		$scope.bestCoin.bcoin = nv>$scope.bestCoin.maxBcoin?$scope.bestCoin.maxBcoin:nv;
//		$scope.bestCoin.coin = $scope.bestCoin.bcoin/100;
//		$scope.cartBox.payPrice = (parseFloat(payPrice) - $scope.bestCoin.coin).toFixed(2);
//
//	});

	function getStoresDistance(id) {
		id&&orderS.storesDistance({
			addressId: id,
			
			token: token,
			orderMoney: payPrice
		}, function(res) {
			if (res && res.resCode == "SUCCESS") {
				$scope.psstyle.sdid = parseInt(res.stores_id) || "";
				if (!$scope.psstyle.sdid) {
					$scope.psstyle.storesDistance = null;
					$scope.psstyle.reInit(0)
					return false
				};
				var list = $scope.psstyle.list
				for (var i = 0; i < list.length; i++) {
					if (list[i].storesId == res.stores_id) {
						$scope.psstyle.storesDistance = list[i];
						$scope.psstyle.page = 2;
						$scope.psstyle.select(2, true)
						break;
					};
				};

			};

		})
	}
	var awatch = $scope.$watch("recAddress.default", function(n, o, s) {
		if (n.id&&payPrice) {						
			getStoresDistance(n.id)
		};
	});
	var pwatch = $scope.$watch("payPrice", function(n, o, s) {
		if (payPrice&&recAddress.default&&recAddress.default.id) {
			getStoresDistance(recAddress.default.id);
		};

	});
	var cwatch = $scope.$watch("coupon.default",function (n,o,s) {
		if (n.money) {
			$scope.coupon.msg = "已抵扣"+n.money;
			if(($scope.cartBox.tPrice-n.money)<= 0){
				$scope.cartBox.totalPrice= $scope.cartBox.shipment?$scope.cartBox.shipment:0;
			}else{
//				$scope.cartBox.totalPrice = ($scope.cartBox.tPrice-n.money).toFixed(2);
				$scope.cartBox.totalPrice = ($scope.cartBox.tPrice-n.money+$scope.cartBox.shipment*1).toFixed(2);
			}
//			var tprice = (parseFloat(payPrice) - parseFloat(n.money)+$scope.cartBox.shipment*1).toFixed(2);
//			$scope.cartBox.payPrice = tprice<0?0:tprice;
		}
		
	})
})

.controller('detailC', function ($scope,orderS ,storage,ngMessage){
	storage.init();
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
	};
	var orderItem = storage.get("orderItem");
	//var rec_id = storage.getUrlParam('rec_id');
	//console.log(rec_id);
	var rec_id=null;
	$scope.detail={
		"delivery":{},
		"goods":{}
	};
	$scope.checkRefundBtn = function(v){
		return v&&$scope.detail.btnReturns=="1"&&v.isRefund=="0";
	}
	$scope.statusName="退款/退款";
	$scope.btnReturns = false;
  $scope.refundSta = true;
	$scope.checkFun = true;
	//var rec_id ="";
	var refundMsg=["审核未通过","待审核","待退款","售后中","已退款","退款失败","待退货","取消退款"]

	orderItem&&orderS.detail({
		"orderId":orderItem.id,
		"token":token
	},function (res) {
		 //console.log(res);//return false;
		if(res.btnReturns==0){
			$scope.refundSta = false;
		}
		$scope.detail = res;
		//console.log($scope.detail.goods);
		rec_id=$scope.detail.goods[0].recId;
		//console.log(rec_id);
		$scope.getrecid();
		if (res.stores&&res.stores.name&&res.shippingType=='2') {
			res.stores.nameExt = "由"+res.stores.name+"配送"
		};
		$scope.statusM = false;
		if(res.goods.length>1){
			$scope.statusM = true;
		}
		
		 if(res.refundStatus || res.statusName=="待付款"){
		 	   $scope.refundSta = false;
		 	   $scope.refundStatusText = "退款/退款: "+refundMsg[parseInt(res.refundStatus)+1];
		 }
	});
 $scope.getrecid=function(){   
    orderS.getkuaidiinfo({
        //"rec_id":orderItem.id,
        


        "rec_id":rec_id,
		"token":token,
        count:1,
        //type:type
      },function (res) {
        console.log(res);
       //res && ($scope.tab.res[type].data = res);
       $scope.istrues=res.logistics_info.length;
       res && ($scope.datas=res);
       
       $scope.istrue=true;
       if($scope.istrues==0){
       	$scope.istrue=false;
       }
       
       console.log($scope.istrues);
      });
}
	$scope.storesTips = function(){
		var res = $scope.detail;
		if (res.stores&&res.stores.name) {
			var msg =res.stores.name+"<br>"
				+ res.stores.mobile+"<br>"
				+res.stores.localtion +" \t "+ res.stores.address;
			ngMessage.showTip(msg,2400);
		};
	}
	$scope.refund = function (v) {
		storage.set("orderId",v.recId);
		storage.toPage("request");
	};
	$scope.refundAll = function (v) {
		storage.set("orderId",v.id);
		storage.toPage("requestAll");
	};
	
	
	
	
	$scope.refundItem = function(v){
		storage.set("refundItem",{"refundId":v.refundId});
		storage.toPage('refundDetail');
	}

		//取消订单
	$scope.cancel = function () {
		ngMessage.show("取消该订单？", function() {
			orderS.cancel({
				"orderId": orderItem.id,
				"token": token
			}, function(res) {
				if (res) {
					ngMessage.showTip(res.resMsg)
					$scope.reload();
				};
			})
		});
	};

	$scope.pay = function() {
		storage.toPage("payOrder");
	};
	$scope.reload = function(){
		orderItem && orderS.detail({
			"orderId": orderItem.id,
			"token": token
		}, function(res) {
			$scope.detail = res;
		});
	}
	
	$scope.checkRefundBtn = function(v){
		return v&&$scope.detail.btnReturns=="1"&&v.isRefund=="0";
	}

})
.controller('payC', function ($scope,orderS ,storage,ngMessage,ngWechat){
	storage.init();
	var wx = storage.get("wx");
	console.log(wx)
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
	};
	var orderItem = storage.get("orderItem");
	$scope.coin = {
		num:0,
		text:"( ￥0 )",
		check:function(price){
			var price = parseFloat(price)||0;
			return this.num>=price
		}
	};
	$scope.detail={
		"delivery":{},
		"goods":{}
	}
	orderS.detail({
		"orderId":orderItem.id,
		"token":token
	},function (res) {
		if (res.resCode=="SUCCESS") {
			$scope.detail = res;
		}else {
			ngMessage.showTip(res.resMsg);
			storage.toPage("login");
		};

	});
	orderS.coin({
				token:token,
			},function (res) {
				if (res&&res.resCode=="SUCCESS") {
					$scope.coin.num = parseFloat(res.money);
					$scope.coin.text = "( ￥"+res.money+" )";
				};
			})


	$scope.refund = function () {
		storage.set("orderId",$scope.detail.id);
		storage.toPage("request");
	}
		//支付方式选择
	$scope.payby={
		"msg":"",
		"currentItem":0,
		"get":function(index) {			
			if (index) {
				if ($scope.coin.check($scope.detail.price)) {
					$scope.payby.msg="账户余额支付"
				}else{
					ngMessage.showTip("余额不足以支付该订单，请选择其他方式支付！");
					return false;
				}
				
			}else{
				$scope.payby.msg="微信安全支付"
			}
			$scope.payby.currentItem = index;
		},
		"onSwitch":function () {
			$scope.payby.get($scope.payby.currentItem)
		},
		pay:function() {
			if ($scope.detail.btnPay=="0") {
				ngMessage.showTip($scope.detail.statusName);
				return
			};
			if ($scope.payby.pay) {
				$scope.payby.pay = false;
			}
			if ($scope.payby.currentItem) {
				//使用余额支付
				orderS.apay({
					token: token,
					orderId: orderItem.id
				}, function(dat) {
					if (dat.resCode == "SUCCESS") {
						storage.toPage("paySuccess");
					}else{
						storage.toPage("payFailure")
					}
				});
			} else {
				if (!wx) {
					ngMessage.showTip("请使用微信登陆");
					return false
				};
				orderS.pay({
					token: token,
					orderId: orderItem.id,
					openid: wx.openid //"oo54huGZkeH8ECoVAB2IftXOS5EI"//
				}, function(dat) {
					  
					if (dat.resCode == "SUCCESS") {
						ngWechat.pay(dat.data, function(rs) {
							storage.toPage("paySuccess")
						}, function() {
							storage.toPage("payFailure")
						})
					};
				});
			}


		}
	}
})













