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
        //   storage.toPage(referer);
        //   return false;
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
.directive('ngDisableSelect', function() {
  return {
    link: function(scope, element, attrs) {    
      element.bind('selectstart', function() {
      	console.log('/*/*/*');
        return false;
      });
    }
  }
})

.controller('couponC', function ($scope,storage,couponS){
	storage.init();
	var token = storage.get("token");
  var index = 1 ; 
	if (!token) {
		storage.toPage("login");
		return false
	};
	$scope.tab={
    "res":[
      {
        "tag":true,
        "data":[]
      },
      {
        "tag":false,
        "data":[]
      },
      {
        "tag":false,
        "data":[]
      }
    ],
		switch:function (type) {
			$scope.tab.res[0].tag = false;
			$scope.tab.res[1].tag = false;
			$scope.tab.res[2].tag = false;
			$scope.tab.res[type].tag = true;
      $scope.tab.list(type);
		},
    list:function (type) {
      couponS.list({
        token:token,
        currentPage:index,
        count:20,
        type:type+1
      },function (res) {
       res && ($scope.tab.res[type].data = res.data);
      })
    }
	}
  $scope.tab.list(0);

  //点击查看优惠券详情
  $scope.toDetail = function(v){
    storage.toPage("couponDetail", "?couponId="+v);
  }

})

//获取优惠券备注说明
.controller('couponRemarkC', function($scope,storage,couponS){
    storage.init();
    var token = storage.get("token");
    var couponId = storage.queryField("couponId"); //获取优惠券id
    var index = 1 ; 
    if (!token) {
      storage.toPage("login");
      return false
    };
    $scope.data = [];
    $scope.list = function(v){
      couponS.getCouponRemark({
        token : token,
        currentPage : index,
        count : 1,
        couponId : v
      }, function(res){
        res && ($scope.data = res.data);
      });
    }
    $scope.list(couponId);
    
})
