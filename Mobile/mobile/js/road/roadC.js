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
	$scope.fkids = storage.getUrlParam("fkId");
	var token = storage.get("token") || "";
	$scope.toLink =function(){
		 window.location.href= "http://"+window.location.host+"/html/allchips/index.html?fkId="+$scope.fkids;
	}
})







