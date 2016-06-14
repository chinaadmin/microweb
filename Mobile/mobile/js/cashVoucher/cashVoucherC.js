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
.controller("cashVoucherC",function ($scope,cashVoucherS,storage,ngMessage) {	
	storage.init();
	var token = storage.get("token");
    
    $scope.mytop = true;
    $scope.mylick = false;
	$scope.cashVoucher={
		"delivery":{},
		"data":{}
	};
	if(!token){
		storage.set('onceReferer',window.location.href);
		console.log('1112==='+storage.get('onceReferer'));
		storage.toPage('login');
	}
	var receiveCouponData = {
		couponCode:storage.queryField('couponCode'),
		token:token
	};
    cashVoucherS.getCouponDetail(receiveCouponData,function(rtn){
    	if(rtn.resCode !== 'SUCCESS')
    		ngMessage.showTip(rtn.resMsg);
    	$scope.cashVoucher = rtn;
    });

	$scope.recieveCode = function (){
        cashVoucherS.receiveCoupon(receiveCouponData,function(rtn){

    	if(rtn.resCode !== 'SUCCESS') {
    		ngMessage.showTip(rtn.resMsg);
    	} else {
	    	$scope.mytop = false;
	    	$scope.mylick = true;
	    	$scope.gid = rtn.goodsIds;
	    }
    });
	}








})
