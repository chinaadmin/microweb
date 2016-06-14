angular.module('app.controllers', [])
	.directive('errSrc', function() {
		return {
			link: function(scope, element, attrs) {
				element.bind('error', function() {
					if (attrs.src != attrs.errSrc) {
						attrs.$set('src', attrs.errSrc);
					}
				});
				if (attrs.$attr['ngSrc'] && !attrs.ngSrc) {
					attrs.$set('src', attrs.errSrc);
				};

			}
		}
	})


	
.controller("detailC", function ($scope, $window, $filter, detailS, storage, ngMessage, ngWechat) {
	storage.init();
	var token = storage.get("token") || "";
	var vio = document.getElementById("project");
    var travel = document.getElementById("travel");
	var oElem = angular.element(vio);
	var oTave = angular.element(travel);
	var fkId = storage.queryField('fkId');

	$scope.left_count = -1;
	$scope.isShow = false;
	$scope.showHide = true;
	$scope.xieyi = true;

	$scope.iShow = function (){
		if($scope.showHide == false){
			$scope.showHide = true;
			$scope.xieyi = true;
			// window.location.reload();
		}else{
			$scope.showHide = false;
			$scope.xieyi = false;
			// window.location.reload();
		}
	}
	$scope.isXieyi = function (){
		if($scope.xieyi ==true){
			$scope.xieyi = false;
		}else{
			$scope.xieyi = true;
		}
	}

	$scope.addToCart = function (){
		if(!token){
			ngMessage.showTip("请先登录再报名！",2000,function(){
				storage.toPage("login");
			});	
		}else if($scope.xieyi == true){
			ngMessage.showTip("请阅读并接受蜂蜜认筹协议！",1000);
			return
		}else if($scope.left_count == 0){
			ngMessage.showTip('众筹名额已满');
		}else{
			storage.toPage('honey');
		}
	}

	$scope.page = {
		image: "/images/fav.png",
		count: 0
	}
	$scope.detail = {
		"number": 1,
		"photo": [],
		"norms": {
			"norms_value": [],
			"norms_attr": []
		}
	};

	$scope.crowd = [];
	$scope.iNow = [];
	$scope.detail.faved = [];

	detailS.getProject({
		"token":token,
		"fkId":fkId
	},function(res) {
		storage.set("fk_id",res.data.fk_id);
		$scope.crowd = res.data;
		$scope.left_count = res.data.left_count;
		$scope.iNow = res.data.percentage;
		oElem.html(res.data.project_content);
		oTave.html(res.data.travel_content);

		$scope.crowd.faved = res.data.isCollect == "1" ? true : false;
		
		if ($scope.crowd.faved) {
			$scope.page.image = "/images/fav-active.png";
		}else{
			$scope.page.image = "/images/fav.png";
		};
		
		pot = parseFloat($scope.iNow)/100;
		// $(function(){
		//     $('#dowebok').waterbubble({
		//         data: pot,
		//         radius: 30,
		//         waterColor: 'rgba(243,91,67,0.8)',
		//         textColor: 'rgba(41,36,22,0.8)',
		//         wave: true,
		//         animation: true
		//     });
		// });
	})

    $scope.travel = function() {
    	detailS.travelReg({
			"token": token,
			"fkId": fkId
		},function(res) {
			if(res.resCode == "SUCCESS") {
				ngMessage.showTip("报名成功！",1000)
			}else{
				ngMessage.showTip("请先登录再报名！",2000,function(){
					storage.toPage("login");
				});
			}
		})
    }
	$scope.share = function() {
		if ($scope.isShow == true){
			$scope.isShow = false;
		}else{
			$scope.isShow = true;
		}
		return false;
	};
	$scope.tt = function (){
		$scope.isShow = false;
	}
	$scope.fav = function() {
		if (!$scope.crowd.faved) {
			detailS.doCollec({
				"token": token,
				"fkId": fkId
			}, function(res) {
				//激活图标
				if (res) {
					if (res.resCode == "SUCCESS") {
						$scope.crowd.faved = true;
						$scope.page.image = "/images/fav-active.png";
						ngMessage.showTip(res.resMsg);
					} else if (/token/img.test(res.resCode)) {
						ngMessage.showTip("请先登录再收藏！",2000,function(){
							storage.toPage("login");
						});
					} else {
						ngMessage.showTip(res.resMsg);
					}
				};
			});
			// console.log($scope.detail.goods_id)
		} else {
			detailS.delCollect({
				"token": token,
				"fkId": fkId
			}, function(res) {
				//激活图标
				if (res && res.resCode == "SUCCESS") {
					$scope.crowd.faved = false;
					$scope.page.image = "/images/fav.png";
					ngMessage.showTip(res.resMsg);
				};
			});
		}
		// console.log($scope.detail.goods_id+"11")
	};
	$scope.back = function() {
		if ($window.history.length) {
			$window.history.go(-1);
		} else {
			storage.toPage("home");
		}

	}
})

.controller("balanceC", function ($scope, $window, $filter, detailS, storage, ngMessage, ngWechat) {
	storage.init();
	var fkId = storage.get("fk_id");
	storage.set("cgId","");
	function countPrice() {
		var list = $scope.goods;
		var n = 0;
		// for (var i = 0; i < list.length; i++) {
		// 	// if (list[i].check) {
		// 		n += (parseInt(list[i].number) * parseFloat(list[i].goods.price));
		// 	// };			
		// };
		n = Math.round(n*100)/100;
		$scope.priceSum = n;
	};

	var catId,index = 2,stopEnd = false;
	var catId = storage.get("catId");

	$scope.bnlan = false;
	if($scope.bnlan == true){
		$scope.bnlan = false;
	}else{
		$scope.bnlan = true;
	}
	$scope.scheme = [];
	$scope.goods = [];
	$scope.cart=[];
	$scope.priceSum = 0;

	detailS.chips({
		"fkId":fkId
	},function(res) {
		$scope.scheme = res.scheme;
		if(res.scheme.length) {
			$scope.switch($scope.scheme[0]);
		}
	})

	$scope.submit = function() {
		var tip = false;
		for (var i = 0; i < $scope.goods.length; i++) {
			if($scope.goods[i].check) {
				tip = true;
				continue;
				break;
			}
		};
		if(tip){
			storage.toPage('details');
		}else{
			ngMessage.showTip("请选择商品！")			
		}

	}

	$scope.switch = function(v){
		index = 2;
		stopEnd = false;
		cdId = v.cdId;
		storage.set("catId",v.cdId);
		storage.set("cgId","");
		if (v) {
			for (var i = 0; i < $scope.scheme.length; i++) {
				$scope.scheme[i].active = false;
			};
			v.active = true;
			$scope.list = v.child;
			$scope.goods_lis = v.goods_list;
		};
		$scope.isLastPage = true;
		detailS.chipsGoods({
			"cdId":cdId
		},function(res) {
			$scope.goods = res.goods;
			countPrice();
		})
	}

	$scope.check=function (v) {
		v.check = !v.check;
		var get = storage.get("cgId");
		if(get){
			var re = new RegExp(","+v.cgId+"|"+v.cgId+""); 
			get  = get.replace(re,'');//剔除以前的纪录
		}
		if(v.check) {
			if(get){				
				get += ','+v.cgId;
			}else{
				get = v.cgId;
			}
		}
		storage.set("cgId",get);
		if (!v.check) {
			$scope.checks = v.check;
			countPrice();
			return false;
		};
		$scope.checks = true;
		for (var i = 0; i < $scope.cart.length; i++) {
			if (!$scope.cart[i].check) {
				$scope.checks = $scope.cart[i].check;
				break;
			};
		};
		countPrice();
	}

	$scope.back = function() {
		if ($window.history.length) {
			$window.history.go(-1);
		} else {
			storage.toPage("home");
		}

	}

})

.controller("checkC",function ($scope,orderS, invoiceS ,storage,ngMessage,ngWechat) {	
	storage.init();
	function countPrice() {
		var list = $scope.order;
		var n = 0;
		for (var i = 0; i < list.goods.length; i++) {
				n += (parseInt(list.goods[i].number) * parseFloat(list.scheme.term[0].money));
		};
		n = Math.round(n*100)/100;
		$scope.priceSum = n;
	};
	//psstyle.uTime
	var ids = storage.get("cartId");
	var catId = storage.get("catId");
	var cgId = storage.get("cgId");
	var token = storage.get("token");
	var wx = storage.get("wx");
	var fkId = storage.get("fk_id");
	if (!token) {
		storage.toPage("login")
	};
	var payPrice = $scope.payPrice= 0;
	var payPostData=null;
	var submitedData = null;

	$scope.priceSum = 0;
	$scope.order = [];
	$scope.scheme_term = [];
	$scope.orderid = storage.get("orderId");
	$scope.orderSn = storage.get("orderSn");

	$scope.$watch('detail.number', function(newValue, oldValue, scope) {
		
		if (oldValue!=newValue&&newValue) {
			newValue = newValue+"";

			var t = newValue.match(/^[1-9]\d{0,2}$/)||" ";
			if (t&&t.length) {
				$scope.detail.number = t;
			};
			
		};	
	});
    orderS.orderShow({
    	"token":token,
    	"cdId":catId,
    	"cgId":cgId
    },function(res) {
    	var length = res.order.goods.length;
    	for(var i=0;i<length;i++){
     	   res.order.goods[i].number=1;
     	   res.order.goods[i].money = res.order.scheme.term[0].money;
     	   res.order.goods[i].discountMoney = res.order.scheme.discountMoney;
     	}
    	$scope.order = res.order;
    	$scope.order.number = 1
    	$scope.scheme_term = res.order.scheme.term[0];
    	$scope.scheme = res.order.scheme.term[1];
    	$scope.order.goodsLength = length;  //产品数
    	$scope.order.allTotal = res.order.scheme.oneGoodsTotal*$scope.order.goodsLength;
    	countPrice()
    })

	$scope.cartBox = {
		"shipment":0,
		"goodsPrice":0,
		"payPrice":0,
		"money":0,
		"goodsList":[]
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
	$scope.add = function(v) {
		v.number++;
		v.money = parseInt(v.number) * parseFloat($scope.scheme_term.money);
		v.discountMoney = parseInt(v.number) * parseFloat($scope.order.scheme.discountMoney);
		$scope.priceSum = parseFloat($scope.priceSum) + parseFloat($scope.scheme_term.money); 
		$scope.order.goodsLength++;
		$scope.order.allTotal = $scope.order.scheme.oneGoodsTotal*$scope.order.goodsLength;
	}
	$scope.sub = function(v) {
		if (v.number > 1) {
			v.number--;
			v.money = parseInt(v.number) * parseFloat($scope.scheme_term.money);
			v.discountMoney =  parseInt(v.number) * parseFloat($scope.order.scheme.discountMoney);
			$scope.priceSum = parseFloat($scope.priceSum) - parseFloat($scope.scheme_term.money); 
			$scope.order.goodsLength--;
			$scope.order.allTotal = $scope.order.scheme.oneGoodsTotal*$scope.order.goodsLength;
		};
	};


    orderS.i({
    	"token": token
    },function(res) {
    	$scope.cartBox = res;
    })

	
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
		},
		pay:function() {
			
			// if ($scope.detail.btnPay=="0") {
			// 	ngMessage.showTip($scope.detail.statusName);
			// 	return
			// };
			// if ($scope.payby.pay) {
			// 	$scope.payby.pay = false;
			// }
			if ($scope.payby.currentItem) {
				//使用余额支付
				orderS.accountPay({
					"token": token,
					"orderId": $scope.orderid
				}, function(dat) {
					if (dat.resCode == "SUCCESS") {
						// ngMessage.showTip("支付成功！")
						storage.toPage("successPay");
					}else{
						// ngMessage.showTip("支付失败！")
						storage.toPage("failurePay")
					}
				});
			} else {
				
				if (!wx) {
					ngMessage.showTip("请使用微信登陆");
					return false
				};
				// alert(wx.openid)
				orderS.wechatPay({
					"token": token,
					"orderId": $scope.orderid,
					"openid": wx.openid //"oo54huGZkeH8ECoVAB2IftXOS5EI"//
				}, function(dat) {
					// alert(wx.openid)
					if (dat.resCode == "SUCCESS") {
						ngWechat.pay(dat.data, function(rs) {
							// ngMessage.showTip("支付成功！")
							storage.toPage("successPay")
						}, function(rs) {
							// ngMessage.showTip("支付失败！")
							storage.toPage("failurePay")
						})
					};
				});
			}
	    }
    }

	var payedMsg="订单已提交，请重新登录微信后再进行支付";
	$scope.page={
		title:"订单详情",
		showBackbtn:true,
		userfeedback:"",
		mobile:"",
		currentLayout:"index",
		prevLayout:"",
		ppLayout:"",
		pay:function () {
			var post={
				shippingType:$scope.psstyle.page,
				addressId:$scope.recAddress.default.id, //
				storesId:$scope.psstyle.store.storesId, //
				storesTime:$scope.psstyle.uTime.TIME,
				// invoiceId:$scope.invoice.default.uid, //
				// invoicePayee:$scope.invoice.content,
				couponId:$scope.coupon.default.id, //券
				integral: $scope.bestCoin.bcoin,
				postscript:$scope.page.userfeedback,
				payType:($scope.payby.currentItem?'ACCOUNT':'WEIXIN'),
				cartIds:ids,
				mobile:$scope.page.mobile,
				token:token
			};
			ngMessage.showTip("正在提交订单，请稍等片刻...",3000);
			
		},
		getType:function(index){ //获取配送信息
			var typeObj = {'1':1,'2':2,'0':0};
			return typeObj[index];
		},
		submited:false,

		submit:function() { 
			$scope.page.submited = true;
			if($scope.psstyle.page != 0){
				if (!$scope.recAddress.default.id) {
					ngMessage.showTip("请填写收货地址");
					return false;
				};
			};
			if (!$scope.psstyle.msg) {
				ngMessage.showTip("请选择配送方式");
				return false;
			};
			
			// if (!$scope.payby.msg) {
			// 	ngMessage.showTip("请选择支付方式");
			// 	return false;
			// };

			var post={
				shippingType:$scope.psstyle.page,
				addressId:$scope.recAddress.default.id, //
				storesId:$scope.psstyle.store.storesId, //
				storesTime:$scope.psstyle.uTime.TIME,
				// invoiceId:$scope.invoice.default.uid, 
				// invoicePayee:$scope.invoice.content,
				couponId:$scope.coupon.default.id, //券
				integral: $scope.bestCoin.bcoin,
				postscript:$scope.page.userfeedback,
				payType:($scope.payby.currentItem?'ACCOUNT':'WEIXIN'),
				cartIds:ids,
				mobile:$scope.page.mobile,
				token:token
			};
			if(post.mobile) {
				if (!post.mobile||!/^[1][35867][0-9]{9}$/.test(post.mobile)) {
					ngMessage.showTip("请填写有效的手机号码！");
					return false
				};
			}
			
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
			var cgIds = [];
            var goods = $scope.order.goods;
            for(i in goods){
                cgIds[i] = {
                	'cgId':goods[i].cgId,
                	'num':goods[i].number
                };
            }
            storage.set('paySum',$scope.priceSum);
            var submitData = {
    				"token": token,
    				"addressId": $scope.recAddress.default.id,
    				"cdId": catId,
    				"cgIds": JSON.stringify(cgIds),
    				"storesId": $scope.psstyle.page == 2 ? $scope.psstyle.sdid : $scope.psstyle.store.storesId,
    			    'shippingType':$scope.page.getType($scope.psstyle.page),
    				'refereeMobile':$scope.page.mobile,
    				'postscript':$scope.page.userfeedback,
					'cor_shipping_time':$scope.psstyle.uTime.TIME
    			};
				
				
				
			orderS.submitOrder(submitData, function(res) {
				$scope.orderid = res.orderId
				storage.set("orderId",res.orderId)
				storage.set("orderSn",res.orderSn);
				if(res.resCode == "SUCCESS") {
					ngMessage.showTip("订单提交成功！",1000,function(){
						storage.toPage("check");
					});
				}else{
					ngMessage.showTip(res.resMsg);
				}
			})
		}
	}
    
    
	$scope.layout={
		"invoice":{
			"show":false,
			"title":"填写发票信息"
		},
		"index":{
			"show":true,
			"title":"我的认筹",
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
	//配送方式
	var pssmsg="送货上门不满足条件提示：\n门店附近500米内满30元免费配送！因您的地址或订单额不满足条件，恕不支持送货上门，请选择其他配送方式 "
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
		"ways":[],//配送方式
		"select":function (index,init) {
			if (index==2&&!$scope.psstyle.storesDistance) {	
				!init&&$scope.psstyle.storesDistanceMethod();		
				return  false
			};
			$scope.psstyle.page = index;
			if (index==0&&!$scope.psstyle.list.length) { //获取门店信息				
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
					$scope.psstyle.msg="快兔配送";
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
			}
			$scope.switchLayout('index');
			$scope.psstyle.select($scope.psstyle.page);
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
		"storesDistanceMethod":function (select) {
			if ($scope.recAddress.default) {
				orderS.storesDistance({
					addressId: $scope.recAddress.default.id,
					token: token,
					orderMoney: payPrice
				}, function(res) {
					if (res && res.resCode == "SUCCESS") {
						$scope.psstyle.webStoresDistance = res;
						$scope.psstyle.sdid = parseInt(res.stores_id) || "";
						var list = $scope.psstyle.list;
						for (var i = 0; i < list.length; i++) {
							if (list[i].storesId == res.stores_id) {
								$scope.psstyle.storesDistance = list[i];
								$scope.psstyle.select(2,true); 
								break;
							};
						};				
					};
					if ($scope.psstyle.webStoresDistance) {
						if ($scope.psstyle.webStoresDistance.stores_id == "0") {
							ngMessage.showTip(pssmsg, 2400)
						};
						return
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
			Things.push(o);
		};
		$scope.psstyle.uTime = Things[0];
		$scope.psstyle.time = Things;
	

	//发票
	//支付方式选择

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
				$scope.recAddress.default  = v;
				$scope.recAddress.resetAddress();	
			}
		},
		'resetAddress':function(){
			$scope.psstyle.sdid = '';
			$scope.psstyle.storesDistance = null;
			$scope.psstyle.webStoresDistance = null;
			$scope.psstyle.msg = null;
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
				};
				
			};
			v.active = !v.active;
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
			var tprice = (parseFloat(payPrice) - $scope.bestCoin.coin - parseFloat(n.money)).toFixed(2);
			$scope.cartBox.payPrice = tprice<0?0:tprice;
		};
		
	})
	$scope.paySum = $scope.priceSum || storage.get('paySum');
})

.controller('paySC', function ($scope,storage,orderS,ngMessage) {
	storage.init();
	$scope.toDetail = function() {
		storage.toPage("Myraise")
	}
	$scope.toIndex = function() {
		storage.toPage("home");
	}
	$scope.toPage = function() {
		storage.toPage("activityDefault")
	}
	var token = storage.get("token");
	$scope.hideDiv = false;
	$scope.hideDialog= function(){
		$scope.hideDiv = !$scope.hideDiv;
	}
	$scope.goToGetAward = true;
	
	orderS.awardTo({token:token},function(res){
		$scope.goToGetAward = res.isDraw;
		console.log(res.isDraw);
	})

})
.controller('payFC', function ($scope,storage,orderS) {
	storage.init();
	$scope.toPay = function() {
		storage.toPage("check")
	}
})

.controller('orderInfoC', function ($scope,storage,orderS,ngMessage) {
	storage.init();
	var token = storage.get("token");
	var orderId = storage.get('orderSn');
	function getOrders(){
		orderS.orderInfo({
			"token": token,
			"orderId": orderId,
		}, function(res) {
			$scope.detail = res;
		})
	}
	$scope.toUrl = function (v){
		storage.toPage(v);
	}
	getOrders();
	
	
})

.controller('myraiseC', function ($scope,storage,orderS,ngMessage) {
	storage.init();
	$scope.page = function(v){
		storage.set("orderSn",v);
		storage.toPage('orderInfo');
	}
	
	var token = storage.get("token");
	if(!token){
		ngMessage.showTip("请先登录再报名！",2000,function(){
			storage.toPage("login");
		});
		return;
	}
	$scope.payString = {'0':'待支付','1':'支付中','2':'已支付','3':'申请退款','4':'已退款'};
	$scope.deliveryString = {'0':'待发货','1':'发货中','2':'确认收货','3':'已收货','4':'退货'};
	$scope.allorders = [];
	function getOrders(index){
		orderS.allOrders({
			"token": token,
			"type": 3,
			"currentPage": index,
			"count": 10
		}, function(res) {
			if($scope.allorders){
				$scope.allorders = $scope.allorders.concat(res.data);
			}else{
				$scope.allorders = res.data;
			}
		})
	}
	var index = 1;
	getOrders(index);
	$scope.showTips=true;
	
	$scope.receivingConfirm = function(orderId, goodsId){ //待收货
		ngMessage.show("是否确认收货？",function(){
			orderS.confirmReceive({
				'orderId':orderId,
				'goodsId' : goodsId, // 增加单个商品id
				"token": token
			},function(res){
				if(res){
					$scope.showTips=false;
					window.location.reload();
				}
			});
		});		
	}
	$scope.outCrowd = function(totalOrderId,v){ //退出众筹
		if(v.ifCanExit == 1){
			ngMessage.show("请确认是否取消众筹！",function(){
				orderS.exitChips({
					"token": token,
					"countOrder": totalOrderId
				}, function(res) {
					if(res.resCode == "SUCCESS"){
						ngMessage.showTip("取消成功!",1000,function(){
							window.location.reload();
						});
					}else{
						ngMessage.showTip(res.resMsg);
					}
				})
			});
		}else{
			ngMessage.showTip("已受理认筹名额，无法取消！")
		}		
	}
	$scope.goPay = function(v){ //重新支付
        storage.set('paySum',v.shouldPay);
		storage.set("orderId",v.orderId);
		storage.set("orderSn",v.orderSn);
		storage.toPage("check");
	}
	$scope.toPage = function (totalOrderId, v){
		storage.set("mail_no", v.mail_no)
		if (!v.mail_no){
			ngMessage.showTip("暂无物流信息！")
		}else{
			storage.toPage("logistics");
		}
	}
})



.controller("travelC", function ($scope, $window, $filter, detailS, storage, ngMessage, ngWechat) {
	storage.init();
	var token = storage.get("token") || "";
	var vio = document.getElementById("project");
	var oElem = angular.element(vio);
	var fkId = storage.get("fk_id");

	detailS.travelReg({
		"token": token,
		"fkId": fkId
	},function(res) {
		
	})
})

.controller("logistcsC", function ($scope, detailS, storage, ngMessage) {
	storage.init();
	var token = storage.get("token");
	var mail_no = storage.get("mail_no");
	$scope.logistcs = [];
	
	detailS.getLogistic({
		"token":token,
		"mail_no":mail_no
	},function(res){
		$scope.logistcs = res;
	})
	
})
