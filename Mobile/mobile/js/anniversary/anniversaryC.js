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
				/*
				if (!reg.test(referer)&&!loginReg.test(referer)) {
					storage.toPage(referer);
					return false;
				};
				*/
				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else {
					window.history.go(-1);
				};
			});
		}
	}
})

.directive('ngEnter', function () {
    return function (scope, element, attrs) {
    	element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.ngEnter);
                }); 
                event.preventDefault();
            }
        });
    };
})

.controller("indexC",function ($rootScope,$element,$scope,anniversaryS,storage,ngMessage,ngWechat) {
		storage.init();
		function isInWeiXin() {
			var ua = window.navigator.userAgent.toLowerCase();
			if (ua.match(/MicroMessenger/i) == 'micromessenger') {
				return true;
			} else {
				return false;
			}
		}
		//微信分享接口
		(function(){
		var authKey = storage.queryField("info");
		if(isInWeiXin() && !sessionStorage.getItem('online')){
			authKey && addmiS.mauth({ // 根据key从后台获取用户缓存
				key: authKey
			}, function(res) {		
				storage.set("wx", res.info);
				//获取用户信息并缓存end
				 storage.init();
				 var token = storage.get("token");
				 var wechat = storage.get("wx");
				 var bind = storage.get("bind");	 	
				if (!wechat) {
					storage.toPage("wxAuth");
				} else {
					!bind&&addmiS.oauth({
						openId: wechat.openid,
						headPic: wechat.head,
						nick: wechat.nick,
						type: "wechat",
						unionid:wechat.unionid
					}, function(res) {
						// if (wechat.openid==res.openId) {
						// 	storage.clear()
							// storage.toPage("login")
						
						if (res) {
							storage.clear();
							if (res.data) {
								storage.set("bind", res.data.part);
							} else if (res.token) {
								var user = {
											"aliasname":res.aliasname
										};
								storage.set("token", res.token);
								storage.set("uid", res.uid);
								//登入成功测标志
								sessionStorage.setItem('online',1);
							};
						};
					});
				}
			}, function() {
					//storage.toPage(page)
			})
		}
	})();
		var token = storage.get("token");
		if(!token){
			  storage.toPage('login');
		}
	 
		$scope.toMylist = function(){
	   	    storage.toPage('AnniversaryMyList')
	   }
		$scope.toNotice = function(){
	   	    storage.toPage('AnniversaryNotice')
	   }
		
		$rootScope.getJsonLength =function(jsonData){
			var jsonLength = 0;
				for(var item in jsonData){
						jsonLength++;
				}
				return jsonLength;
	}
		
		
})

.controller("myListC",function($scope,anniversaryS,storage,ngWechat){  
	   storage.init();
	   var token = storage.get('token');
	   
	   if(!token){
	   	storage.toPage('login')
	   }
	   
	   $scope.totalMoney = 0;
		 anniversaryS.getMyList({
		 	 token:token
		 },function(res){
		 	  $scope.totalMoney = res.totalCash;
		 	  $scope.ListData = res;
		 });
		 
	
		$scope.couponDetail = function(){
	   	    storage.toPage('coupon')
	   }
		$scope.toBalance = function(){
			location.href="../order/balance.html?token="+token;
		}
		
})

.controller('noticeC',function($scope,$sce,anniversaryS,storage,ngWechat){
	  storage.init();
	   var token = storage.get('token');
	
	 anniversaryS.getContent({
	 	 token:token
	 },function(res){
	 	   $scope.Content = res.content.content;
	 	   $scope.trustHtml = $sce.trustAsHtml($scope.Content);
	 });
	
})

