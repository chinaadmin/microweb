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
					window.history.go(0);
					window.history.go(-1);
				};
			});
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

.controller("myListC",function ($scope,$rootScope,activS,storage,ngWechat) {	
	  storage.init();
	  var token = storage.get("token");
		 if (!token) {
	      storage.toPage("login");
	      return;
	    };
	   activS.getMyList({
	   	  token:token
	   },function(res){
	   	   $scope.List = res.price.slice(0,20);
				
	   })
	   
	   $scope.toSys = function(e){
	   				e.preventDefault();
	   				window.location.replace('systemList.html');
	   }
	    //微信分享接口
	  activS.wxsdk({ //从后台获取注入信息 并提供跳转参数 
			url: window.location.href
		}, function(res) {
			$scope.tt = JSON.stringify(res);
			if (res && res.resCode == "SUCCESS") {
				var data = JSON.stringify(res.jssdk);				
				ngWechat && ngWechat.init({//从后台的信息初始化微信端
					"debug":false,
					"appId": res.jssdk.appId,
					"timestamp": res.jssdk.timestamp,
					"nonceStr": res.jssdk.nonceStr,
					"signature": res.jssdk.signature
				},function(e){
//					ngMessage.loading(JSON.stringify(e));
				});
			};
		});
	   
})
.controller('systemListC',function($scope,$rootScope,activS,storage,ngWechat){
		storage.init();
	  var token = storage.get("token");
	   activS.getSysList({
	   	  token:token,
	   	  pageNum:20
	   },function(res){
	   	   $scope.List = res.price;
	   })
	   $scope.toMy = function(e){
	   	e.preventDefault();
			window.location.replace('premiumList.html');
	   }
	   //微信分享接口
	  activS.wxsdk({ //从后台获取注入信息 并提供跳转参数 
			url: window.location.href
		}, function(res) {
			$scope.tt = JSON.stringify(res);
			if (res && res.resCode == "SUCCESS") {
				var data = JSON.stringify(res.jssdk);				
				ngWechat && ngWechat.init({//从后台的信息初始化微信端
					"debug":false,
					"appId": res.jssdk.appId,
					"timestamp": res.jssdk.timestamp,
					"nonceStr": res.jssdk.nonceStr,
					"signature": res.jssdk.signature
				},function(e){
//					ngMessage.loading(JSON.stringify(e));
				});
			};
		});
})
.controller('noticeC',function($scope,$rootScope,$sce,activS,storage,ngWechat){
	 activS.getContent({},function(res){
	 	   $scope.Content = res.content.content;
	 	   $scope.trustHtml = $sce.trustAsHtml($scope.Content);
	 });
	  //微信分享接口
	  activS.wxsdk({ //从后台获取注入信息 并提供跳转参数 
			url: window.location.href
		}, function(res) {
			$scope.tt = JSON.stringify(res);
			if (res && res.resCode == "SUCCESS") {
				var data = JSON.stringify(res.jssdk);				
				ngWechat && ngWechat.init({//从后台的信息初始化微信端
					"debug":false,
					"appId": res.jssdk.appId,
					"timestamp": res.jssdk.timestamp,
					"nonceStr": res.jssdk.nonceStr,
					"signature": res.jssdk.signature
				},function(e){
//					ngMessage.loading(JSON.stringify(e));
				});
			};
		});
})
