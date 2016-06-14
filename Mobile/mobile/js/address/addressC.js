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
.controller("manageC",function ($scope,manageS,storage,ngMessage) {	
	storage.init()
	var index = 1;
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
	};


	$scope.page={
		showBackbtn:true,
		currentLayout:"index",
		prevLayout:"",
		ppLayout:""
	}
	$scope.layout={
		"index":{
			"show":true,
			"title":"收货地址管理",
			"back":true,
			"history":true
		},
		"address":{
			"show":false,
			"title":"新增收货地址",
			"subTitle":"修改地址",
			"back":true
		}
	}

	$scope.switchLayout = function(layout,b) {
		if ($scope.layout.hasOwnProperty(layout)) {
			for (var i in $scope.layout) {
				if ($scope.layout.hasOwnProperty(i)) {
					$scope.layout[i].show = false;
				}
			}
			$scope.layout[layout].show = true;
			$scope.page.title = $scope.layout[layout].title+"";
			if (b) {
				$scope.page.title = $scope.layout[layout].subTitle+"";
			};	
			$scope.page.showBackbtn = $scope.layout[layout].back;
			$scope.page.ppLayout = $scope.page.prevLayout ;
			$scope.page.prevLayout = $scope.page.currentLayout;
			$scope.page.currentLayout = layout;
		};
	}
	$scope.switchLayout("index");


	//收货地址
	$scope.recAddress = {
		"list": [],
		"default": {},
		"edit":null,
		"setDefault": function(v) {
			manageS.setDefault({
				"addressId": v.id,
				"token": token
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					v.isDefault = "1";
					$scope.recAddress.get();
				};
			});
		},
		"get": function() {
			manageS.addressRec({
				"token": token
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					$scope.recAddress.list = res.recaddressList;
					var b = false;
					for (var i = 0; i < res.recaddressList.length; i++) {
						if (res.recaddressList[i].isDefault == "1") {
							$scope.recAddress['default'] = res.recaddressList[i];
							b = true;
							break;
						};
					};
					if (!b) {
						$scope.recAddress.default = res.recaddressList[0] || {};
					};
				};
			})
		},
		"setAddress":function (v) {		
			$scope.recAddress.edit = v;
			$scope.addressArea.username = v.name;
			$scope.addressArea.mobile = v.mobile;
			$scope.addressArea.address = v.address;
			$scope.addressArea.isDefault = parseInt(v.isDefault);
			for (var i = 0; i < $scope.addressArea.provice.length; i++) {
				if ($scope.addressArea.provice[i].provice_id==v.provice) {
					$scope.addressArea.useProvice = $scope.addressArea.provice[i];
					break;
				};
			};
		}
	}
	$scope.recAddress.get();
	$scope.addressArea={
		"username":"",
		"mobile":"",
		"address":"",
		"provice":[],
		"useProvice":null,
		"city":[],
		"useCity":null,
		"county":[],
		"useCounty":null,
		"isDefault":0,
		"getPr":function () {
			manageS.area({},function(res) {
				if (res) {
					$scope.addressArea.provice = res.data||[];
				};				
			});
			$scope.addressArea.onChange()
		},
		"getCi":function (pid) {
			manageS.area({
				"pid":pid
			}, function(res) {
				if (res) {
					$scope.addressArea.city = res.data || [];
					if ($scope.recAddress.edit) {
						for (var i = 0; i < res.data.length; i++) {
							if (res.data[i].city_id == $scope.recAddress.edit.city) {
								$scope.addressArea.useCity = res.data[i];
								break;
							};
						};
					};
				};
			})
		},
		"getCo":function (pid,cid) {
			manageS.area({
				"pid":pid,
				"cid": cid
			}, function(res) {
				if (res) {
					$scope.addressArea.county = res.data || [];
					if ($scope.recAddress.edit) {
						for (var n = 0; n < res.data.length; n++) {
							if (res.data[n].county_id == $scope.recAddress.edit.county) {
								$scope.addressArea.useCounty = res.data[n];
								break;
							};
						};
					};
				};
			})
		},
		"onChange":function () {
			var p = $scope.$watch("addressArea.useProvice", function(n, o) {
				$scope.addressArea.useCity=null;
				$scope.addressArea.useCounty=null;
				if (n) {
					$scope.addressArea.getCi(n.provice_id);
				};

			});
			var ci = $scope.$watch("addressArea.useCity", function(n, o) {
				$scope.addressArea.useCounty=null;
				if (n) {
					$scope.addressArea.getCo(n.province_id,n.city_id)
				};
			});
		},
		"add":function () {
			if (!$scope.addressArea.useProvice||!$scope.addressArea.useCity||!$scope.addressArea.useCounty) {
				ngMessage.showTip("请选择所在地！")
				return
			};
			var data={
				"token":token,
				"mobile":$scope.addressArea.mobile,
				"provice":$scope.addressArea.useProvice.provice_id||"",
				"city":$scope.addressArea.useCity.city_id||"",
				"county":$scope.addressArea.useCounty.county_id,
				"address":$scope.addressArea.address,
				"name":$scope.addressArea.username,
				"isDefault":$scope.addressArea.isDefault?1:0
			};

			if (!data.address) {
				ngMessage.showTip("请填写详细地址！");
				return
			};
			if (!data.mobile||!/^[1][35867][0-9]{9}$/.test(data.mobile)) {
				ngMessage.showTip("请有效的手机号！");
				return
			};

			if ($scope.recAddress.edit&&$scope.recAddress.edit.id) {
				data.addressId = $scope.recAddress.edit.id;
				manageS.updateRecAddress(data,function(res) {
					if (res.resCode=="SUCCESS") {
						ngMessage.showTip(res.resMsg);
						$scope.switchLayout("index");
						$scope.recAddress.get();
					}else{
						res&&ngMessage.showTip(res.resMsg);
					}
				});
			}else{
				manageS.addRecAddress(data,function(res) {
					if (res.resCode=="SUCCESS") {
						ngMessage.showTip(res.resMsg);
						$scope.switchLayout("index");
						$scope.recAddress.get();
					}else{
						res&&ngMessage.showTip(res.resMsg);
					}
				});
			}
		},
		"clear":function () {
			$scope.recAddress.edit = null;
			$scope.addressArea.username = "";
			$scope.addressArea.mobile = "";
			$scope.addressArea.address = "";
			$scope.addressArea.isDefault = 0;
			$scope.addressArea.useProvice = null;
			$scope.addressArea.useCity = null;
			$scope.addressArea.useCounty = null;
		},
		"del":function () {
			ngMessage.show("确定删除该收货地址？", function() {
				manageS.delRecAddress({
					"addressId": $scope.recAddress.edit.id,
					"token": token
				}, function(res) {
					res && ngMessage.showTip(res.resMsg);
					$scope.recAddress.get();
					$scope.switchLayout("index");
				})
			})
		},
		"setDefault":function () {
			if ($scope.recAddress.edit && $scope.recAddress.edit.id) {
				manageS.setDefault({
					"addressId": $scope.recAddress.edit.id,
					"token": token
				}, function(res) {
					if (res.resCode == "SUCCESS") {
						$scope.recAddress.get();
					};
				});
			};
		}
	}
	$scope.addressArea.getPr();


	$scope.back=function () {
		var layout = $scope.page.currentLayout;
		if ($scope.layout[layout].history) {
			storage.toPage(-1);
		}else{	
			if ($scope.page.ppLayout!="index") {
				$scope.switchLayout('index');
			}else{
				$scope.switchLayout($scope.page.prevLayout);
			}
			
		}		
	}

})

.controller("pushC",function ($scope,manageS,storage,ngMessage) {
	storage.init()
	var token = storage.get("token");
	$scope.contactDetail = [];

	manageS.contactDetail({
		"token":token
	}, function(res){
		$scope.contactDetail = res;
	})

})



