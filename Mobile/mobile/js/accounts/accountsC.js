var jump ='';
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
				// if (!reg.test(referer)&&!loginReg.test(referer)) {
				// 	storage.toPage(referer);
				// 	return false;
				// };
				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else {
					window.history.go(-1);
				};
			});
		}
	}
})
.controller("balanceC",function ($scope,balanceS,storage,ngWechat,ngMessage) {	
	storage.init();
	var ids = storage.get("cartId");
	var token = storage.get("token");
	var wx = storage.get("wx")//||{openid:"oo54huGZkeH8ECoVAB2IftXOS5EI"};
	var Urltoken= storage.getUrlParam('token');
	var index = 1;
	if (!token) {
		storage.toPage("login")

	};
	
	$scope.layout = {
		"index": {
			"show": false ,
			"title": "充值",
			"history":true
		},
		"success": {
			"show": false,
			"title": "充值成功"
		},
		"fail": {
			"show": false,
			"title": "充值失败"
		},
		"history": {
			"show": false,
			"title": "充值查询"
		}
	}

	$scope.page={
		title:"充值",
		title1:"红包",
		currentLayout:"index",
		prevLayout:"",
		ppLayout:"",
		submit:function() {
			console.log('submit');
		}
	}


	$scope.switchLayout = function(layout) {
		if ($scope.layout.hasOwnProperty(layout)) {
			for (var i in $scope.layout) {
				if ($scope.layout.hasOwnProperty(i)) {
					$scope.layout[i].show = false;
				}
			}

			$scope.layout[layout].show = true;
			$scope.page.title = $scope.layout[layout].title+"";
			$scope.page.showBackbtn = $scope.layout[layout].back;
			$scope.page.ppLayout = $scope.page.prevLayout ;
			$scope.page.prevLayout = $scope.page.currentLayout;
			$scope.page.currentLayout = layout;
			$scope.page.history = $scope.layout[layout].history;
		};
	};
	$scope.switchLayout("index");

	$scope.back = function() {
		var layout = $scope.page.currentLayout;
		if ($scope.layout[layout].history) {
			storage.toPage(-1);
		} else {
			if ($scope.page.ppLayout != "index") {
				$scope.switchLayout('index');
			} else {
				$scope.switchLayout($scope.page.prevLayout);
			}
		}
	}

	$scope.toPage = function(){
		storage.toPage("memcenter");
	};
	$scope.history=[];
	balanceS.history({
		token:token,
		currentPage:index,
		count:32
	},function(res){
		if (res&&res.resCode=="SUCCESS") {
			if(Urltoken){
				$scope.switchLayout("history");
			  $scope.packerTitle = $scope.page.title1
				$scope.history = [];
				for (var i=0;i<res.rechargeList.length;i++) {
						if(res.rechargeList[i].type=='6'){
							res.rechargeList[i].title = '红包';
							$scope.history.push(res.rechargeList[i]);
						}
				}
			}else{
				$scope.history = [];
				for (var i=0;i<res.rechargeList.length;i++) {
					if(res.rechargeList[i].type=='6'){
							res.rechargeList[i].title = '红包';
							$scope.history.push(res.rechargeList[i]);
						}else{
							res.rechargeList[i].title = '充值';
							$scope.history.push(res.rechargeList[i]);
						}
						
				}
			}
		};
	})
	$scope.pay = {
		//"number":storage.queryField('number'),    传参数
		"pay":function () {
			var number = parseFloat($scope.pay.number)||0;
			if (!number||number<=0) {
				ngMessage.showTip("无效金额！");
				return false
			};
			if ($scope.pay.pay) {
				$scope.pay.pay = false;
			}
			if (!wx||!wx.openid) {
				ngMessage.showTip("请使用微信客户端进行充值！");
				setTimeout(function() {
					storage.set("wxReferer","balance");
					storage.toPage("wxAuth");
				}, 1000);
				return
			};
			balanceS.recharge({
				amount:number,
				openid:wx.openid,
				token:token
			},function(res){				
				if (res&&res.resCode=="SUCCESS") {					
					ngWechat.pay(res.data, function(res) {
						jump = "success";
						storage.toPage("memcenter");
					}, function(res) {
						jump = "fail";
						// document.getElementById('suny').style.display = 'none';					
						// document.getElementById('teest').style.display = 'block';

						storage.toPage("recharge");
					})					
				}else{
					ngMessage.showTip(res.resMsg, function(res) {
						$scope.switchLayout("fail");      
					})
				}
			});
			$scope.switchLayout(jump);
		}
	}
	$scope.$watch('pay.number', function(newValue, oldValue, scope) {
		
		if (oldValue!=newValue&&newValue) {
			if (/\d+\.$/.test(newValue)) {
				newValue = newValue+"";
			};
			newValue = newValue+"";
			//newValue = newValue.replace(/\./," ");
			newValue = newValue.replace(/\.+/,".");

			var t = newValue.match(/^\d{0,6}\.*(\d{1,2}|\d{0,1})/)[0]||" ";
			//var m = /(^\.)/;
			if (t&&t.length) {
				$scope.pay.number = t;
			};
			for (var i=0; i<t.length; i++) {
				if (t[0] == '.') {
					//alert(111)
					return false;
				}
			}
			
		};		
		if (parseInt(newValue) && newValue[0] == 0) {
			 $scope.pay.number = newValue.replace(/^0+/," ");
		};
	});
	
	
})

.controller("chargeDetaC",function ($scope,balanceS,storage,ngWechat,ngMessage){
	storage.init();	

	var token = storage.get("token");

	$scope.money = '';
	$scope.record = [];
	balanceS.i({
		"token":token
	},function(res) {
		if (res.resCode=="SUCCESS") {
			$scope.money = res.money;			
		}else{
			ngMessage.showTip(res.resMsg);
		}
	});

	$scope.toPageData = function(){
		storage.toPage("balance");
	};

	balanceS.allRecord({
		"token":token
	},function(res) {
		$scope.record = res.result_data;
	})
})

.controller("rechargeC",function ($scope,balanceS,storage,ngWechat,ngMessage) {	
	storage.init();
	var ids = storage.get("cartId");
	var token = storage.get("token");
	var wx = storage.get("wx")//||{openid:"oo54huGZkeH8ECoVAB2IftXOS5EI"};
	var index = 1;
	if (!token) {
		storage.toPage("login")

	};
    $scope.show = function(){
    	document.getElementById('suny').style.display = 'none';
    	window.location.href='/html/order/balance.html?number='+storage.queryField('number');
    }
})

.controller("currencyC",function ($scope,balanceS,storage) {	
	storage.init();
	var ids = storage.get("cartId");
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login")
	};
	var index = 1;
	$scope.page={
		index:1,
		currentLayout:"index",
		prevLayout:"",
		ppLayout:"",
		coin:{
			current:0,
			pass:0,
			data:[]
		},
		tab:function (i) {
			$scope.page.index =parseInt(i);
			$scope.page.list(i-1);
		},
		list:function (type) {
			balanceS.coin({
				token:token,
				type:type,
				currentPage:index
			},function (res) {
				if (res&&res.resCode=="SUCCESS") {
					$scope.page.coin.current = res.credits||"0.00";
					$scope.page.coin.pass = res.credits_ending||"0.00";					
					if (!type&& res.data) {
						for (var i = 0; i < res.data.length; i++) {
							if (/-/.test(res.data[i].credits)) {
								res.data[i].sub = "sub";
							};
						};
					};
					$scope.page.coin.data[type] = res.data;
				};
			})
		}
	}
	$scope.page.list(0);

})












