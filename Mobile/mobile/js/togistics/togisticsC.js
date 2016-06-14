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
.controller("togisticsC",function ($scope,togisticsS,storage,ngMessage) {	
	storage.init();
	var token = storage.get("token");
	
	var orderItem = storage.get("orderItem");
	$scope.togistics={
		"delivery":{},
		"goods":{}
	};
     
	orderItem&&togisticsS.togistics({
		// "orderSn":orderItem.orderSn,
		"orderSn":orderItem.orderSn,
		"token":token
	},function (res) {
		$scope.togistics = res;
		if(res.mailNo == "") {
			ngMessage.showTip("此商品没有物流信息！",2000);
		}
		
	});



})














