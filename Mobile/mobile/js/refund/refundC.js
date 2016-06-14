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

.controller('refundC', function ($scope,storage,refundS,ngMessage) {
	storage.init();
	var index = 1;
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
		return false
	};	
	$scope.list=[];
	function getList() {
		refundS.list({
			"token": token,
			"currentPage": index,
			"count": 20
		}, function(res) {
			if (res&&res.resCode=="SUCCESS") {
				$scope.list = res.data;
			};
		})
	};
	getList();

	$scope.detail = function (v) {
		if (v) {
			storage.set("refundItem",v);
			storage.toPage('refundDetail')
		};
	}

	$scope.cancelRef = function (v) {
		refundS.cancel({
			refundId:v.refundId,
			token:token
		},function(res){
			ngMessage.showTip(res.resMsg);
			if (res&&res.resCode=="SUCCESS") {
				getList();
			};
		})
	}
	
})

.controller('detailC', function ($scope,storage,refundS){
	storage.init();
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
		return false
	};
	var refundItem = storage.get("refundItem");

	$scope.refund={};
	refundS.detail({
		"refundId": refundItem.refundId,
		"token": token
	}, function(res) {
		if (res) {
			$scope.refund = res.info;
			switch(res.info.refundStatus){
				  case "0":
							    res.info.refundStatus = '待审核';
							    break;
				  case "1":
							    res.info.refundStatus = '待退款';
							    break;
					case "2":
							    res.info.refundStatus = '退款中';
							    break;
				  case "3":
							    res.info.refundStatus = '已退款';
							    break;
					case "4":
							    res.info.refundStatus = '退款失败';
							    break;
				  case "5":
							    res.info.refundStatus = '待退货';
							    break;
					case "6":
							    res.info.refundStatus = '已取消';
							    break;
				  case "-1":
							    res.info.refundStatus = '审核未通过';
							    break;
			}
		};		
	});

})


.controller('requestC', function ($rootScope,$scope,storage,refundS,ngMessage,ngResizeImage){
	storage.init();
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
		return false
	};
	var orderId = storage.get("orderId");
	$scope.list=[];
	$scope.page={
		maxNum:1
	};

	$scope.refund={ 
		"number":1,
		"reason":{},
		"explain":"",
		"attrId":""
	};
	refundS.apply({
		"recId": orderId,
		"token": token
	}, function(res) {
		if (res) {
			if (res&&res.resCode=="SUCCESS") {
				$rootScope.maxMoney = res.data.maxRefundMoney;
				storage.set('maxmoney',res.data.maxRefundMoney)
				$scope.list = res.data.reason;
				$scope.page = res.data;
				$scope.refund.reason = $scope.list[0]||{};
			};
		};	
	});


$scope.$watch('hopeMoney',function(n,o,s){
	var maxmoney = storage.get('maxmoney')
    if(n){
    	  if(n>parseInt(maxmoney)){
    	  	$scope.hopeMoney= maxmoney
    	  }
    }
})
	
	$scope.submit = function () {
		if($scope.hopeMoney){
			  refundS.submit({
			 		"token":token,
					"recId":orderId,
					"number":$scope.refund.number,
					"reason":$scope.refund.reason.name,
					"explain":$scope.refund.explain,
					"attrId":$scope.refund.attrId,
					"hopeRefundMoney":$scope.hopeMoney
				},function (res) {
					if (res) {
						if (res.resCode=="SUCCESS") {
							storage.set("orderId","");
							storage.toPage("refund");
						}else{
							ngMessage.showTip(res.resMsg)
						}
					};
				})
		}else{
			ngMessage.showTip('退款金额不能为空')
		}
	}
})


.controller('requestAllC', function ($rootScope,$scope,storage,refundS,ngMessage){
	storage.init();
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
		return false
	};
	var orderId = storage.get("orderId");
	$scope.list=[];
	$scope.page={
		maxNum:1
	};
	$scope.refund={
		"number":1,
		"reason":{},
		"explain":"",
		"attrId":""
	};

	refundS.applyAll({
		"orderId": orderId,
		"token": token
	}, function(res) {
		if (res) {
			if (res&&res.resCode=="SUCCESS") {
				$scope.list = res.data[0].reason;
				$scope.page = res.data;
				$rootScope.maxMoney = res.maxRefundMoney;
				storage.set('maxmoney',res.maxRefundMoney)
				$scope.refund.reason = $scope.list[0]||{};
			};
		};		
	});
	
	

$scope.$watch('hopeMoney',function(n,o,s){
	   var maxmoney = storage.get('maxmoney')
    if(n){
    	  if(n>parseInt(maxmoney)){
    	  	$scope.hopeMoney= maxmoney
    	  }
    }
})


	$scope.submit = function () {
		  if($scope.hopeMoney){
		  	  refundS.submitAll({
							"token":token,
							"orderId":orderId,
							"reason":$scope.refund.reason.name,
							"explain":$scope.refund.explain,
							"attrId":$scope.refund.attrId,
							"hopeRefundMoney":$scope.hopeMoney
						},function (res) {
							if (res) {
								if (res.resCode=="SUCCESS") {
									storage.set("orderId","");
									storage.toPage("refund");
								}else{
									ngMessage.showTip(res.resMsg)
								}
							};
						})
		  }else{
		  	ngMessage.showTip('退款金额不能为空')
		  }
		
	}
})

.directive('imgup',function(storage,ngMessage,refundS,ngResizeImage){
	storage.init();
	return {
		restrict:'E',
		template:'<label><input type="file"   capture="camera" /></label>',
		link:function($scope, element, attrs){
			element.bind('change',function(e){
				var obj = e.target,file = obj.files[0];
				 $scope.photo = '';
		        //判断类型是不是图片  
		        if(!/image\/\w+/.test(file.type)){     
		                ngMessage.showTip("请确保文件为图像类型");
		                return false;   
		        }
		        ngMessage.hide();
		        ngMessage.loading("图像正在上传...");
		        //找出后辍
		        ext = file.type.split('/')[1];
		        var reader = new FileReader();   
		        reader.readAsDataURL(file);
		        if(!storage.isInWeiXin() || true){
		        	 readerOnload = function(event){  
							ngResizeImage.resize(file,obj, function(src) {
								refundS.updAvatar({
									"baseData":src,
									"ext":"jpeg",
									"type":2,
									"token":storage.get('token')
								},function(rtn){
									if(rtn.resCode == 'SUCCESS'){
										 $scope.photo = rtn.result.photo;
				    					if(rtn.result.photo){
				    						/*document.getElementById('image').setAttribute('src',rtn.src);
				    						$('#image').attr('src',rtn.src);*/
				    						angular.element(document.getElementById('image')).attr('src',rtn.result.photo);
				    						$scope.refund.attrId = rtn.result.attId;
				    						ngMessage.hide();
				    					}else{
				    						location.reload();
				    					}
				    				}else{
				    					ngMessage.showTip("图像上传失败!");
				    				}
								})
							})	
				        }
		        }else{
			        readerOnload = function(event){
			        	var _self = this;
			        	refundS.updAvatar({
							"base": _self.result,
							"ext":ext,
							"token":storage.get('token')
						},function(rtn){
							  
							if(rtn.resCode == 'SUCCESS'){
		    					if(rtn.result.photo){
		    						/*document.getElementById('image').setAttribute('src',rtn.src);
		    						$('#image').attr('src',rtn.src);*/
		    						angular.element(document.getElementById('image')).attr('src',rtn.result.photo);
		    						ngMessage.hide();	
		    					}else{
//		    						location.reload();
		    					}
		    				}else{
		    					ngMessage.showTip("图像上传失败!");
		    				}
						});
			        }
		        }
		        reader.onload = readerOnload;
			});
		}
	}
})


