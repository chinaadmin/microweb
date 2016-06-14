


angular.module('tryEat.controllers', [])
.directive('errSrc', function() {
  return {
    link: function(scope, element, attrs) {
      element.bind('error', function() {
        if (attrs.src != attrs.errSrc) {
          attrs.$set('src', attrs.errSrc);
        }
      });
    }
  }
})
.directive('ngEnter', function() {
        return function(scope, element, attrs) {
            element.bind("keydown keypress", function(event) {
                if(event.which === 13) {
                    scope.$apply(function(){
                        scope.$eval(attrs.ngEnter, {'event': event});
                    });
                    event.preventDefault();
                }
            });
        };
    })
.controller("indexControllers",function ($scope,resSvr,msgPopup,cache) {
	msgPopup.init($scope);
	cache.init();
	var index = 1;
	var searchIndex = 1;
	var size = 12;
	var atSearch = false;
	$scope.query = cache.query();
	$scope.images=[];
	$scope.ishow = true;
	$scope.isLastPage = true;
	$scope.input={
		keyword:''
	}


	$scope.showInput= function(){
		$scope.ishow = !$scope.ishow;
		if ($scope.input.keyword) {
			$scope.search();
		};
	}

	$scope.vote=function (index) {
			var id = $scope.images[index].photo_id;
			resSvr.vote(id,function(data){
				if (data.resCode=="SUCCESS") {
					$scope.images[index].vote_num = parseInt($scope.images[index].vote_num )+1;
					window.alert("点赞成功！")
				};
				if (data.resCode=="ALREADY_VOTE") {
					window.alert(data.resMsg)
				};				
			});
	}
	$scope.vpdate=function () {
		if (cache.getUid()) {
			cache.toPage("upload");
		}else{
			cache.toPage("login");
		}		
	};

	$scope.search = function() {	
		atSearch = true;	
		getRes();
	}


	$scope.more = function() {
		getRes(true);
	}
	getRes();

	$scope.reload = function(){
		window.location.reload();
	}

	function getRes(k) {
		var idx = index;
		var searchText = "";			
		if (atSearch) {
			idx  = searchIndex;
			searchText = $scope.input.keyword;
			index = 1;
		}else{
			searchIndex = 1;
		}
		resSvr.getImages(idx, size, searchText, function(data) {
			if (k) {
				var tmp = angular.copy($scope.images);
				$scope.images = tmp.concat(data.data);
			}else{
				$scope.images = data.data;
			}			
			$scope.$applyAsync();
			if (data.lastPage != "0") {
				$scope.isLastPage = false;
				if (atSearch) {
					searchIndex++;
				}else{
					index++;
				}
				
			}else{
				$scope.isLastPage = true;
			}
		});
	}


})

.controller('loginControllers',function ($scope,$window,$timeout,httpRequest,cache,msgPopup) {
	var time = 60;	
	$scope.user={
		"mobile":"",
		"code":""
	};
	msgPopup.init($scope);
	cache.init();
	$scope.hide = true;
	$scope.isRun = false;
	$scope.btnText = "获取验证码";
	$window.previewCamera.mobileBoxCallback(function(sNumber) {
		httpRequest.post(appURLRES.sendsms, {
			"mobile": sNumber
		}).success(function(data) {
			if (data.resCode=="UID_REQUIRE") {
					cache.clearAndReload();
					return false
				};	
			if (data["resCode"] == "SUCCESS") {
			} else {
				data && msgPopup.show(data.resMsg);	
				$window.previewCamera.stopMobileBox();			
			}
			
		})
	})

	$scope.add=function () {
		var bx = $window.previewCamera.mobileBoxValue();

		$scope.user.mobile = bx.mobile;
		$scope.user.code = bx.code;
		if (!($scope.user.mobile&&$scope.user.code)) {
			window.alert("请填写手机号和验证码！")
			return false
		};
		httpRequest.post(appURLRES.adduser,{
			"mobile":$scope.user.mobile,
			"code":$scope.user.code
		}).success(function (data) {
			if (data.resCode=="UID_REQUIRE") {
					cache.clearAndReload();
					return false
				};	
			if (data["resCode"]=="SUCCESS") {
				cache.setPhoneNumber($scope.user.mobile)
				cache.setUid(data.user.uid);
				$timeout(function(){
					if (cache.getUrl()) {
						window.location.href = cache.getUrl()+"#"+cache.query();
					}else{
						cache.toPage("upload");
					}	
				},1200)			
			}else{								
				data&&msgPopup.show(data.resMsg);
			};
		})
	}
})

.controller('uploadControllers', function($scope, $window,$http, captureSvr, cache, msgPopup) {
	msgPopup.init($scope);
	cache.init();
	$scope.isInit = true;
	$scope.image = {
		"src": "",
		"title": "",
		"disabled": ""
	};
	$scope.title = "添加照片";
	$scope.upload = function() {
		msgPopup.show("正在上传...",true);
		$window.previewCamera.send($http,appURLRES.uploadImage, cache.getUid(), $scope.image.title, function(success, data) {
			if (success) {
				if (data.resCode == "SUCCESS") {
					$window.previewCamera.capture("SUCCESS");
					cache.toPage("detail");
					msgPopup.show("图片上传成功！");
				} else {
					data&&data.resMsg&&msgPopup.show(data.resMsg);
				}
			}else{
				msgPopup.show("网络问题，上传失败！");
				if (data) {
					msgPopup.show(JSON.stringify(data));
				};
			}
			
		});
	}

	$window.previewCamera.regBackButton(function () {
		cache.toPage("list");
	})

	captureSvr.get(function(data) {
		if (!data) {return};
		if (data.resCode == "SUCCESS") {
			$scope.image.src = data["return"].photo;
			$scope.image.title = data["return"].title;
			$scope.image.disabled = data["return"].disUpload;
			$scope.isInit = false;
			$scope.title = "更换照片";
			$scope.plusImage = $window.plusChangeImage;
			$window.previewCamera.preview(data["return"].photo)
		}else{
			data.resMsg&&msgPopup.show(data.resMsg);
		}

	})

	$scope.plusImage = $window.plusImage;

})

.controller('detailControllers', function ($scope,resSvr,detailSvr,msgPopup,cache,wxPreview) {
	cache.init();
	msgPopup.init($scope)
	$scope.detailInfo={
		"title":'',
		"photo_id":"0",
		"vote_num":"0",
		"uid":"",
		"nick":"",
		"number":"",
		"photo":defaultImage
	};
	$scope.isOwner = false;
	$scope.title = "照片详情";
	var pid = detailSvr.getPid();
	var canVote = true;
	if (pid) {
		detailSvr.getByPid(pid,function (data) {
			if (data.is_my == "1") {
				$scope.isOwner = true;
			};
			detail(data)
		})
	}else{	
		$scope.title = "我的照片";	
		detailSvr.getByUid(function (data) {
			$scope.isOwner = true;
			detail(data)
		})
	};

	function detail(data) {
		$scope.detailInfo = data;		
		var msg = ""
		if (data["status"] == "0") {
			msg = ("照片不可用")
		};
		if (data["user-status"] == "0") {
			msg = ("照片不可用")
		};
		if (msg) {
			canVote = false;
			window.alert(msg)
		};
		
	}

	$scope.vote=function () {
		if ($scope.detailInfo.photo_id&&parseInt($scope.detailInfo.photo_id)&&canVote) {
			resSvr.vote($scope.detailInfo.photo_id,function(data){
				if (data.resCode=="SUCCESS") {
					$scope.detailInfo.vote_num = parseInt($scope.detailInfo.vote_num )+1;
					window.alert("点赞成功！")
				};
				if (data.resCode=="ALREADY_VOTE") {
					window.alert(data.resMsg)
				};
				
			});
		}else{
			window.alert("当前用户或照片不可用，无法完成点赞！")
		}
		
	}

	$scope.home = function(){
		cache.toPage("list");
	}

	$scope.wxPreview=function(){
		wxPreview.show($scope.detailInfo.photo);
	};

	$scope.logout =function () {
		cache.clear();
		cache.toPage("login");
	}
})
