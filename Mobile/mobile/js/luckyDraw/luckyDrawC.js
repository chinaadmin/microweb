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
.directive('ngBack', function(storage) {
	return {
		link: function(scope, element, attrs) {
			storage.init();
			var referer = storage.get("referer")||"";
			var url = location.href.replace(location.search,'');
			var reg = new RegExp(url,"img");
			var loginReg = /http[^?#=]+login.html/img
			element.bind('click', function() {
				if (!reg.test(referer)&&!loginReg.test(referer)) {
					storage.toPage(referer);
					return false;
				};
				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else {
					window.history.go(-1);
				};
			});
		}
	}
})
.controller('luckyDrawC', function ($scope,storage,ngMessage,allDrawS,$window){
	storage.init();
	var token = storage.get("token") || "";
	var vio = document.getElementById("Rules");
	var oElem = angular.element(vio);

	$scope.luckyDraw = [];
	$scope.detailData = [];
	$scope.lessChance = 0;
	$scope.alias = false;
	$scope.alias_name = false;
    var location_map = {};
	allDrawS.getAwardContent({
		"token":token
	},function(res){
		$scope.luckyDraw = res;
		oElem.html(res.plan.remark);
		$scope.lessChance = res.plan.lessChance;
		if(res.plan.lessChance == 0){
			$scope.ifStart = true;
		}
		  var award = res.award;
		  for(key in award){
			  if(award[key].alias_name == "一等奖"){
				  location_map[award[key].id] = [1,7,5];
				  //设置图片样式
				  var cssObj = {};
				  cssObj = {src:award[key].src};
				  angular.element(document.getElementById('lottery-unit-1')).attr(cssObj);
			 	  angular.element(document.getElementById('lottery-unit-7')).attr(cssObj);
				  angular.element(document.getElementById('lottery-unit-5')).attr(cssObj);
			  }else if(award[key].alias_name == "二等奖"){
				  location_map[award[key].id] = [2,3,6];
				  //设置图片样式
				  var cssObj = {};
				  cssObj = {src:award[key].src};
				  angular.element(document.getElementById('lottery-unit-2')).attr(cssObj);
				  angular.element(document.getElementById('lottery-unit-3')).attr(cssObj);
				  angular.element(document.getElementById('lottery-unit-6')).attr(cssObj);
			  }else if(award[key].alias_name == "三等奖"){
				  location_map[award[key].id] = [0,4];
				  //设置图片样式
				  var cssObj = {};
				  cssObj = {src:award[key].src};
				  angular.element(document.getElementById('lottery-unit-0')).attr(cssObj);
				  angular.element(document.getElementById('lottery-unit-4')).attr(cssObj);
			  }
		  }
	})

	$scope.tohide = function (){
		$scope.showAll = false;
	}
	$scope.toReceive = function () {
		storage.toPage("prizes");
	}

	$scope.inow = [];
	var lottery={
		index:0,	//当前转动到哪个位置
		count:99,	//总共有多少个位置
		timer:0,	//setTimeout的ID，用clearTimeout清除
		speed:200,	//初始转动速度
		times:0,	//转动次数
		cycle:50,	//转动基本次数：即至少需要转动多少次再进入抽奖环节
		prize:-1,	//中奖位置
		init:function(id){
			if ($("#"+id).find(".lottery-unit").length>0) {
				$lottery = $("#"+id);
				$units = $lottery.find(".lottery-unit");
				this.obj = $lottery;
				this.count = $units.length;
				$lottery.find(".lottery-unit-"+this.index).addClass("active");
			};
		},
		roll:function(){
			var index = this.index;
			var count = this.count;
			var lottery = this.obj;
			$(lottery).find(".lottery-unit-"+index).removeClass("active");
			index += 1;
			if (index>count-1) {
				index = 0;
			};
			$(lottery).find(".lottery-unit-"+index).addClass("active");
			this.index=index;
			return false;
		},
		stop:function(index){
			this.prize=index;
			return false;
		}
	};

	function roll(){
		lottery.times += 1;
		lottery.roll();		
		if (lottery.times > lottery.cycle+10 && lottery.prize==lottery.index) {
			clearTimeout(lottery.timer);
			//lottery.prize=-1;
			lottery.times=0;
			$scope.showDraw = true;
			showTip();
			click=false;//停止转盘
		}else{			
			if (lottery.times<lottery.cycle) {
				lottery.speed -= 10;
			}else if(lottery.times==lottery.cycle && tellPosition()) {
				lottery.prize = tellPosition();//停留位置
			}else{
				if (lottery.times > lottery.cycle+10 && ((lottery.prize==0 && lottery.index==7) || lottery.prize==lottery.index+1)) {
					lottery.speed += 110;
				}else{
					lottery.speed += 20;
				}
			}
			if (lottery.speed<40) {
				lottery.speed=40;
			};
			lottery.timer = setTimeout(roll,lottery.speed);
		}
		return false;
	}
	$scope.ifStart = false;//是否抽奖
	$scope.showDraw = false;
   $scope.showAll = false;//是否显示中奖结果
   
   function tellPosition(){
	   if($scope.ifStart && $scope.inow){ //开始抽奖 且 已经拿到抽奖结果
		    var arr = location_map[$scope.inow];//[2,3,6]
		   index =  Math.floor(Math.random()*(arr.length));
		   return arr[index];
	   }
	   return false;
   }
   function showTip(){
	   setTimeout(function(){
		   if($scope.showDraw && $scope.showDraw){
			   $scope.showAll = true;
		   }else{
			   $scope.showAll = false;
		   }
		   $scope.ifStart = false;
		   $scope.$applyAsync();	
	   },1000);
   }
	var click=false;
	$scope.zero = false;
	window.onload=function(){
		lottery.init('lottery');
		$("#lottery .startDraw").click(function(){
			$scope.$apply();
			if(click || typeof($scope.luckyDraw.plan) == 'undefined') {
				return false;
			}else{
				if(!storage.checkLogin()){
					return;
				}
				$scope.ifStart = false;
				 $scope.showDraw = false;
				 if($scope.luckyDraw.plan.lessChance <= 0 ){
					   $scope.zero = true;
						$scope.alias = true;
						$scope.alias_name = false;
						ngMessage.showTip("您的抽奖次数已用完！",1000);
						$scope.$apply();
						return false;
				 }
				$scope.$apply();
				allDrawS.draw({
					"token": token
				},function(res){
					$scope.ifStart = true;
					$scope.detailData = res.detailData;
					if (res.resCode == "SUCCESS") {
						$scope.inow = res.detailId;
						$scope.luckyDraw.plan.lessChance>0 && $scope.luckyDraw.plan.lessChance--;
						$scope.alias = true;
						$scope.alias_name = true;
					}else{
						$scope.alias = true;
						$scope.alias_name = false;
						ngMessage.showTip("您的抽奖次数已用完！",1000);
					}
				})
				lottery.speed=100;
				roll();
				click=true;
				return false;			
				// lottery.speed=100;
				// roll();
				// click=true;
				// return false;
			}
		});
	};

	$scope.back = function() {
		if ($window.history.length) {
			$window.history.go(-1);
		} else {
			storage.toPage("home");
		}

	}

})
.controller("checkC",function ($scope,orderS, invoiceS ,allDrawS,storage,ngMessage,ngWechat) {	
	storage.init();
	var ids = storage.get("cartId");
	var token = storage.get("token");
	var wx = storage.get("wx");
	if (!token) {
		storage.toPage("login")
	};
	var payPrice = $scope.payPrice= 0;
	var payPostData=null;
	var submitedData = null
	var addressId = storage.get("addressId");




	$scope.myDrawLis = [];
	$scope.isShow = false;
	$scope.listBox = false;

	allDrawS.myDrawLis({
		"token":token
	},function(res){
		$scope.myDrawLis = res.data;
		for (var i = 0; i < $scope.myDrawLis.length; i++) {
			$scope.myDrawLis[i].str = 1;
		};
	})

	$scope.Location = function(v) {
		ngMessage.show("请核对当前领奖地址！",function(){
			allDrawS.getMyDraw({
				"token": token,
				"id": v.id
			},function(res){
				if(res.resCode == "SUCCESS") {
					ngMessage.showTip("领取成功！",800,function(){
						v.is_reveive = 1;
					});
				}else if(res.resCode == 'DRAW_ADDRESS_EMPTY'){
					ngMessage.showTip("请填写收获地址！",1200,function(){
						$scope.layout.address.show = true;
					    $scope.layout.index.show = false;
					})
				}	
			})
		})
	}
	$scope.viewAddress = function () {
		allDrawS.showMyAddress({
			"token":token
		},function(res){
		})
	}


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

	//$scope.bestCoin.init();
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
		}
	}


	$scope.layout={
		"invoice":{
			"show":false,
			"title":"填写发票信息"
		},
		"index":{
			"show":true,
			"title":"我的赠品",
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
			"title":"收获地址",
			"back":true
		},
		"recAdd":{
			"show":false,
			"title":"添加收获地址",
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
			if (index==0&&!$scope.psstyle.list.length) {				
				orderS.address({
					token:token
				},function (res) {
					if (res&&res.resCode=="SUCCESS"&&res.stores&&res.stores.length) {
						$scope.psstyle.list = res.stores;//配送的门店列表
						var sid = $scope.psstyle.store.sid; //历史配送的门店id
						$scope.psstyle.use(res.stores[0]);
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
		"storesDistanceMethod":function (select) {
			if ($scope.recAddress.default) {
				storage.set("recAddress",$scope.recAddress.default.id);
				if ($scope.psstyle.webStoresDistance) {
					storage.set("recAddress",$scope.recAddress.default.id);
					if ($scope.psstyle.webStoresDistance.stores_id == "0") {
						ngMessage.showTip(pssmsg, 2400)
					};
					return
				};
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
	allDrawS.showMyAddress({
		"token": token
	},function(res){
		if(res.resCode != 'SUCCESS'){
			return;
		}
		var data = res.data;
		data.address = data.detail_address;
		$scope.recAddress['default'] = data;
	})
	//收货地址列表管理
	$scope.recAddress = {
		"list": [],
		"default": {},
		"edit":null,
		"setDefault": function(v) {
			alert(v.id)
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
//							$scope.recAddress['default'] = res.recaddressList[i];
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
			$scope.recAddress.default  = v;
			//getStoresDistance(v.id)
			addressId = v.id;
			addAwardAddress(token,addressId);
		}
	}
	function addAwardAddress(token,addressId){
		allDrawS.addAddress({
			'token':token,
			'addressId':addressId,
		},function(res){
				
		});
	};
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
				storage.set("addressId",$scope.recAddress.edit.id);
				addressId = $scope.recAddress.edit.id;//test123
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
						addressId = res.addressId;
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


	/*
	var watch = $scope.$watch('bestCoin.bcoin', function(newValue, oldValue, scope) {
		var nv = parseFloat(newValue)||0;
		$scope.bestCoin.bcoin = nv>$scope.bestCoin.maxBcoin?$scope.bestCoin.maxBcoin:nv;
		$scope.bestCoin.coin = $scope.bestCoin.bcoin/100;
		$scope.cartBox.payPrice = (parseFloat(payPrice) - $scope.bestCoin.coin).toFixed(2);

	});
	*/
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






