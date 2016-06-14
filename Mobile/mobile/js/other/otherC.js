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
.controller("feedbackC",function ($scope,storage,feedbackS,ngMessage) {	
	storage.init()
	$scope.options=[];
	feedbackS.option({},function (res) {
		if (res.resCode=="SUCCESS") {
			$scope.options = res.opinionType;
			if ($scope.options.length) {
				$scope.action($scope.options[0])
			};
		};
	})
	$scope.option={
		"contact":"",
		"content":""
	}
	$scope.action = function (value) {
		for (var i = 0; i < $scope.options.length; i++) {
			$scope.options[i].active = false;
		};
		value.active = !value.active ;
	}
	$scope.submit = function () {
		var ids=[];
		for (var i = 0; i < $scope.options.length; i++) {
			if ($scope.options[i].active) {
				ids.push($scope.options[i].id)
			};
		};
		feedbackS.submit({
			"type":ids.join(","),
			"content":$scope.option.content,
			"contact":$scope.option.contact
		}, function(res) {
			if (res.resCode == "SUCCESS") {
				$scope.option = {
					"contact": "",
					"content": ""
				}
				ngMessage.show(res.resMsg,function(){
					storage.toPage("memcenter")
				},function(){
					storage.toPage("memcenter")
				})
			}else{
				ngMessage.showTip(res.resMsg);
			}
		})
	}
})

