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
.controller("loginC",function ($scope,loginS,storage,ngMessage) {	
	storage.init()
	var Referer = storage.getOnce("referer")||"";
	var bind = storage.get("bind")||"";
	$scope.user={
		user:"",
		password:"",
		isWx:bind?true:false
	};

	$scope.login=function () {
		if ($scope.user.user&&$scope.user.password) {
			var postData= {
				"username":$scope.user.user,
				"password":$scope.user.password,
				"source":1 //登录源--微信客户端登录
				//"source":2 //登录源--手机客户端登录
			};

			if (bind) {
				postData.isPart = 1;
				postData.part = JSON.stringify(bind);			
			};

			loginS.login(postData,function (data) {
				if (data.resCode=="SUCCESS") {
					data.username = $scope.user.user;
					storage.set("user", data);
					storage.set("token", data.token);
					storage.set("uid", data.uid);
					if (Referer) {
						if (/http.+login.html/.test(Referer)) {
							storage.toPage("memcenter");
							return
						};
						storage.toPage(Referer);
					} else {
						storage.toPage("memcenter");
					}
				}else{
					ngMessage.showTip(data.resMsg)
				}
				
			})
			
		}else{
			ngMessage.showTip("请填写账号和密码！")
		}
	}	

	$scope.findPassword =function () {
		storage.toPage("findPassword");
	};
	$scope.register =function () {
		storage.toPage("register");
	};

})

.controller('registerC', function ($scope , storage,$interval, registerS,ngMessage){
	storage.init(true);
	var referer = storage.get("referer");
	var bind = storage.get("bind");
	var token = storage.get("token");
	if (token) {
		// storage.toPage("memcenter");
		storage.set("token","");
	};
	$scope.user={
		name:"",
		password:"",
		type:"1",
		code:"",
		sendSMS:false,
		text:"获取验证码"
	}
	var theClock=null;
	function clockEnd() {
		theClock = null;
		$scope.user.text = "获取验证码";
		$scope.user.sendSMS = false;
	};
	function clock (time) {
		$scope.user.text = "剩余59秒";
		$scope.user.sendSMS = true;
		var i = 59;
		theClock = $interval(function () {
			i--;
			$scope.user.text = "剩余"+i+"秒";
			if (i==0) {
				clockEnd();
			};
			
		},1000,59)
	}

	$scope.tips = false;
	$scope.init = false;
	setTimeout(function(){
		$scope.init = true;
	}, 2400);
	$scope.register = function() {
		if (!$scope.init) {
			return false
		};
		if ($scope.user.name && $scope.user.code && $scope.user.password) {
			var postData = {
				"username": $scope.user.name,
				"password": $scope.user.password,
				"type": $scope.user.type,
				"code": $scope.user.code,
				"source":1
			};
			if (bind) {
				postData.isPart = 1;
				postData.part = JSON.stringify(bind);
			};
			registerS.register(postData, function(res) {
				if (res.resCode == "SUCCESS") {
					storage.set("token",res.token);
					storage.set("uid",res.uid);
					ngMessage.showTip("注册成功！");
					setTimeout(function() {
						if (/login.html/.test(referer)) {
							storage.toPage("memcenter"); //退到上一页
						} else if (window.history.length > 1) {
							window.history.go(-1); //退到上一页
						} else {
							storage.toPage("memcenter"); //退到上一页
						}
					}, 1200);					
				} else {
					ngMessage.showTip(res.resMsg)
				}
			})
		}else{
			ngMessage.showTip("请将注册消息填写完整！")
		}

	};

	$scope.code = function () {		
		if ($scope.user.name&&/^[1][34578][0-9]{9}$/.test($scope.user.name) && !$scope.user.sendSMS) {
			clock();
			registerS.regCode({
				type:"1",
				mobile:$scope.user.name
			},function (errMsg) {				
				ngMessage.showTip(errMsg);
				if (theClock) {
					$scope.$applyAsync();
					$interval.cancel(theClock);
					clockEnd();
				};	
						
			},function(){
				$scope.tips = true;
			})
		}else{
			ngMessage.showTip("请输入有效的手机号码!");
		}
	}

})

.controller('ucenterC', function ($scope,storage) {
	storage.init();
	var token = storage.get("token");
	$scope.secLevel = "低";
	
	$scope.toPage=function (page) {
		if (!token) {
			storage.toPage("login")
		}else{
			storage.toPage(page)
		}
		
	};

	$scope.logout =function () {
		storage.clear();
		storage.toPage("memcenter");
	}

})


.controller('userC', function ($scope,storage,userS,ngResizeImage,ngMessage) {
	storage.init();
	var token = storage.get("token");
	var old={
		rname:"",
		aname:""
	}
	$scope.image = "";
	$scope.user={
		rname:"",
		aname:"",
		sex:{},
		list:[
			{"name":"男","value":1},
			{"name":"女","value":0}
		],
		image:""
	};
	//$scope.user.sex = $scope.user.list[0];

	$scope.anameEditing = false;
	$scope.anameEdit=function () {
		$scope.anameEditing = !$scope.anameEditing;
		if (!$scope.anameEditing) {

		};
	};

	$scope.rnameEditing = false;
	$scope.rnameEdit=function () {
		$scope.rnameEditing = !$scope.rnameEditing;
		if (!$scope.rnameEditing) {

		};
	};
	function user () {
		if (token) {
			userS.user({
				"token":token
			},function(res) {
				if (res.resCode=="SUCCESS") {
					$scope.user.rname = res.realName;
					$scope.user.aname = res.aliasname;
					$scope.image = res.photo;
					old.rname = res.realName;
					old.aname = res.aliasname;
					if (res.sex=="1") {
						$scope.user.sex = $scope.user.list[0];
					}else{
						$scope.user.sex = $scope.user.list[1];
					}
				}else{
					ngMessage.showTip(res.resMsg);
				}
			})
		};
		
	}
	user();

	$scope.save =function () {
			userS.changeUsername({
				"realname":$scope.user.rname,
				"sex":($scope.user.sex.value?$scope.user.sex.value:2),
				"nickname":$scope.user.aname,
				"token":token
			},function (res) {
				if (!res) {
					ngMessage.showTip('网络错误，修改失败！');
					return false;
				};
				if (res.resCode=="SUCCESS") {
					ngMessage.showTip('修改完成！');
				}else{
					ngMessage.showTip(res.resMsg);
				}
			})
		
	}
	$scope.$watch('image', function(n, old, scope) {
		if (/base64/img.test(n)) {
			userS.updAvatar({
				"base":n,
				"ext":"jpeg",
				"token":token
			},function(res){
				console.log(res);
			})
		};
	});

})



.controller('memCenterC', function ($scope,storage,userS,ngMessage) {
	$scope.isLogin = false;
	storage.init();
	var token = storage.get("token");
	var user = storage.get("user");
	if (token) {
		$scope.isLogin = true;
	};

	$scope.user={
		rname:"",
		aname:"",
		username:(user&&user.aliasname?user.aliasname:""),
		money:0,
		integral:0,
		image:""
	}
	$scope.page = {
		msg: false,
		msgBtn: function() {
			storage.toPage("login")
		}
	};

	$scope.toPage=function (page,key,value) {
		if (!token) {
			ngMessage.showTip("请先登录")
			return false
		};
		if (key&&value) {
			storage.set(key,value);
		};
		storage.toPage(page);
	};
	$scope.share =function () {
		//分享必好货APP
		// wechat.share.qq(....)
	};
	$scope.login = function(){
		storage.set("referer","memcenter");			
			storage.toPage('login')
	}
	$scope.toCart = function () {		
		if (!$scope.isLogin) {
			$scope.set("referer","cart")			
			$scope.toPage('login')
		}else{
			$scope.toPage('cart')
		}
	}

	function userMethod () {
		if (token) {
			userS.i({
				"token":token
			},function(res) {
				if (res.resCode=="SUCCESS") {
					$scope.user.money = res.money;
					$scope.user.integral = res.integral;					
				}else{
					ngMessage.showTip(res.resMsg);
				}
			});

			userS.user({
				token:token
			},function (res) {
				if (!$scope.user.username) {
					$scope.user.username = res.aliasname;
				};
				$scope.user.image = res.photo;
			})


		};
		
	};
	userMethod();

	$scope.test = function(){
		ngMessage.show("data.resMsg")
	}

	$scope.blocked = function(){
		ngMessage.showTip("即将开通，敬请期待！")
	}

})


.controller('bindPhoneC',function ($scope,$interval,bindPhoneS,storage,ngMessage){
	storage.init();
	var codeType = 2;//1:注册 2:忘记密码 3:修改手机
	$scope.phone={
		"mobile":"",
		"code":""
	};
	var token = storage.get("token");
	var isBindNew = storage.queryField("bind");
	$scope.placeholder = "请输入原手机号"
	if (isBindNew) {
		$scope.placeholder = "请输入新手机号"
	};
	$scope.show =false;
	$scope.user={
		text: "获取验证码",
		sendSMS:false,
		active:''
	};
	var theClock=null;
	function clockEnd() {
		theClock = null;
		$scope.user.text = "获取验证码";
		$scope.user.sendSMS = false;
	};
	function clock (time) {
		$scope.user.text = "剩余59秒";
		$scope.user.sendSMS = true;
		$scope.user.active = "active";
		var i = 59;

		theClock = $interval(function () {
			i--;
			$scope.user.text = "剩余"+i+"秒";
			$scope.$applyAsync();
			if (i==0) {
				clockEnd();
			};			
		},1000,59)
	}


	$scope.getCode = function () {
		$scope.show = true;
		if ($scope.phone.mobile&&/^[1][34578][0-9]{9}$/.test($scope.phone.mobile)) {
			if (!$scope.user.sendSMS) {
				clock();
				var k = "regCode"
				if (isBindNew) {
					k = "regCodeNew"
				};
				bindPhoneS[k]({
					token: token,
					mobile: $scope.phone.mobile
				}, function(errMsg) {
					ngMessage.showTip(errMsg);
					if (theClock) {
						$scope.$applyAsync();
						$interval.cancel(theClock);
						clockEnd();
					};
				});
			};
		}else{
			ngMessage.showTip("请输入有效的手机号码!");
		}
	};
	//提交验证
	$scope.submit = function() {
		if ($scope.phone.mobile && $scope.phone.code) {
			var k = "verifyPhone";
			if (isBindNew) {
				k = "verifyPhoneNew"
			};
			bindPhoneS[k]({
				"mobile": $scope.phone.mobile,
				"code": $scope.phone.code,
				"token": token
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					if (res.token) {
						//storage.set("token", res.token) ;//暂时不做安全检测
						storage.toPage("bindPhone", "?bind=true")
					} else {
						ngMessage.showTip(res.resMsg)
						setTimeout(function(){
							storage.toPage("memcenter")
						}, 1000)
						
					}
				} else {
					ngMessage.showTip(res.resMsg)
				}
			})
		} else {
			ngMessage.showTip("所有输入框都必填！")
		}
	};






	//忘记密码
	$scope.getCodeForg = function() {
		$scope.show = true;
		if ($scope.phone.mobile && /^[1][34578][0-9]{9}$/.test($scope.phone.mobile)) {
			if (!$scope.user.sendSMS) {
				clock();
				registerS.regCode({
					type: codeType,
					mobile: $scope.phone.mobile
				}, function(errMsg) {
					ngMessage.showTip(errMsg);
					if (theClock) {
						$scope.$applyAsync();
						$interval.cancel(theClock);
						clockEnd();
					};
				});
			};
		} else {
			ngMessage.showTip("请输入有效的手机号码!");
		}
	};
	//忘记密码
	$scope.findSubmit=function () {
		if ($scope.phone.mobile && $scope.phone.code) {
			registerS.verifyPhone({
				"mobile": $scope.phone.mobile,
				"code": $scope.phone.code,
				"type": codeType
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					storage.set("token",res.token);
					storage.set("pwd","change");
					storage.toPage("changePassword")
				} else {
					ngMessage.showTip(res.resMsg)
				}
			})
		} else {
			ngMessage.showTip("所有输入框都必填！")
		}
	}


	//











})

.controller('changePasswordC',function ($scope,userS,storage,ngMessage){
	storage.init();
	$scope.password={
		"old":"",
		"newa":"",
		"newb":""
	}
	$scope.show =false;
	$scope.hide=false;
	storage.get("pwd")=="change"&&($scope.hide=true);
	//提交验证
	$scope.submit = function() {
		if ($scope.password.newa != $scope.password.newb) {
			ngMessage.showTip("新密码不一致！");
			return false
		};
		if ((!$scope.password.old &&!$scope.hide)|| !$scope.password.newa || !$scope.password.newb) {

			ngMessage.showTip("所有密码框都必填！");
			return false
		};
		var data={
			"newPass": $scope.password.newa,
			"newRepass": $scope.password.newb,
			"token": storage.get("token")
		};
		if (!$scope.hide) {
			data["oldPass"] = $scope.password.old;
		};
		userS.updatePassword(data, function(res) {
			if (res) {
				if (res.resCode == "SUCCESS") {
					ngMessage.showTip("密码修改成功！");
					if ($scope.hide) {
						storage.set("token","");
						storage.toPage("home");
						return
					};
					storage.toPage("ucenter");
					return
				};
				ngMessage.showTip(res.resMsg);
			} else {
				ngMessage.showTip("网络问题,密码修改失败！")
			}
		})

	}
})