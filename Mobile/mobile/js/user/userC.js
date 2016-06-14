function isInWeiXin() {
	var ua = window.navigator.userAgent.toLowerCase();
	if (ua.match(/MicroMessenger/i) == 'micromessenger') {
		return true;
	} else {
		return false;
	}
}
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
.directive('mychange',function(storage,ngMessage,userS,ngResizeImage){
	storage.init();
	return {
		restrict:'E',
		template:'<label><input type="file"   capture="camera" /></label>',
		link:function(scope, element, attrs){
			element.bind('change',function(e){
				var obj = e.target,file = obj.files[0];      
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
								userS.updAvatar({
									"base":src,
									"ext":"jpeg",
									"token":storage.get('token')
								},function(rtn){
									if(rtn.resCode == 'SUCCESS'){
				    					if(rtn.src){
				    						/*document.getElementById('image').setAttribute('src',rtn.src);
				    						$('#image').attr('src',rtn.src);*/
				    						angular.element(document.getElementById('image')).attr('src',rtn.src);
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
			        	userS.updAvatar({
							"base": _self.result,
							"ext":ext,
							"token":storage.get('token')
						},function(rtn){
							if(rtn.resCode == 'SUCCESS'){
		    					if(rtn.src){
		    						/*document.getElementById('image').setAttribute('src',rtn.src);
		    						$('#image').attr('src',rtn.src);*/
		    						angular.element(document.getElementById('image')).attr('src',rtn.src);
		    						ngMessage.hide();	
		    					}else{
		    						location.reload();
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
.controller("loginC",function ($scope,loginS,storage,ngMessage) {	
	storage.init()
	
	var Referer = storage.getOnce("referer")||"";
	var bind = storage.get("bind")||"";
	$scope.user={
		user:"",
		password:"",
		isWx:bind ? true : false
	};
	$scope.myKeyup = function (ev) {
		var keycode = window.event?ev.keyCode:ev.which;
		if (keycode == 13) {
			$scope.login();
		}
	}
	$scope.login=function () {
		if ($scope.user.user&&$scope.user.password) {
			var postData= {
				"username":$scope.user.user,
				"password":$scope.user.password,
				"source": bind ? 1 : 2 //1登录源--微信客户端登录	2登录源--手机客户端登录
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
					$scope.myVal = isInWeiXin()?false:true;
					if (data.accountStatus == "1"){
						storage.toPage("collection");					
					}else if(Referer) {
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

.controller('registerC', function ($scope ,$interval,$location,storage, registerS,ngMessage){
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
		text:"获取验证码",
		invite_code:''
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

	//获取邀请链接url里的uid参数
	var sValue=$location.absUrl().match(new RegExp("[\?\&]uid=([^\&]*)(\&?)","i"));
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
				"source":1,
				"uid":sValue?sValue[1]:'',
				"invite_code":$scope.user.invite_code,
			};
			if (bind) {
				postData.isPart = 1;
				postData.part = JSON.stringify(bind);
			};
			registerS.register(postData, function(res) {
				if (res.resCode == "SUCCESS") {
					storage.set("token",res.token);
					storage.set("uid",res.uid);
					ngMessage.showTip("注册成功！ 自动登录中...", 4000, function () {
						storage.toPage("login");
					});
					
					setTimeout(function() {
						var onceReferer = storage.getOnce('onceReferer');
						
						if(onceReferer){
							location.href = onceReferer;
						}else if(/login.html/.test(referer)) {
							storage.toPage("memcenter"); //用户中心
						}else if (window.history.length > 1) {
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

.controller('accountBindC', function ($scope , storage,$interval, registerS,ngMessage){
	storage.init(true);
	var referer = storage.get("referer");
	var bind = storage.get("bind");
	var token = storage.get("token");
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
				"source":1,
				"token":token
			};
			if (bind) {
				postData.isPart = 1;
				postData.part = JSON.stringify(bind);
			};
			registerS.accountBind(postData, function(res) {
				if (res.resCode == "SUCCESS") {
					storage.set("token",res.token);
					storage.set("uid",res.uid);
					ngMessage.showTip("补充成功！");
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
			ngMessage.showTip("请将补充消息填写完整！")
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
	//$scope.myVal = true;
    $scope.myVal = isInWeiXin()?false:true;
	$scope.logout =function () {
		storage.clear();
		storage.toPage("memcenter");
	};

})


.controller('userC', function ($scope,$filter,storage,userS,ngResizeImage,ngMessage) {
	storage.init();
	var token = storage.get("token");
//	var has_realName = storage.get("res.realName");
//	alert(has_aliasname);
//	var has_aliasname = storage.get("res.aliasname");
//	var has_birthday = storage.get("res.birthday");
//	var has_username = storage.get("res.username");
//	if(has_realName&&has_aliasname&&has_birthday&&has_username){
//		$scope.hasAll = true;
//	};
	
	
//	var hasUserName = storage.get("res.userName");
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
	$scope.birEditing = false;
	$scope.birEdit=function () {
		$scope.birEditing = !$scope.birEditing;
	if (!$scope.birEditing) {

		};
	};
	
	$scope.cancel=function () {
		$scope.display = {"display":"none"}
	};
	//$scope.dt1 = new Date();
	//$scope.dt2 = $filter("date")(1448864369815, "yyyy-MM-dd HH:mm:ss"); 
	function user () {
		if (token) {
			userS.user({
				"token":token
			},function(res) {
				if (res.resCode=="SUCCESS") {
					$scope.user.rname = res.realName;
					$scope.user.aname = res.aliasname;
					//console.log($scope.user.birthdays);
					$scope.user.birthdays= res.birthday*1000;
					$scope.user.birthday = $filter("date")($scope.user.birthdays, "yyyy-MM-dd");  
//					$scope.user.mobile = res.mobile;
					$scope.user.username = res.username;
//					$scope.user.mobileStatus = res.mobileStatus;
					$scope.user.photo = res.photo;
					old.rname = res.realName;
					old.aname = res.aliasname;
					if (res.sex=="1") {
						$scope.user.sex = $scope.user.list[0];
					}else{
						$scope.user.sex = $scope.user.list[1];
					};
					//判断个人信息是否有空
					if(res.realName!=0&&res.aliasname!=0&&res.aliasname!=0&&res.birthday!=0&&res.username!=0){
						$scope.hasAll = true;
					}else{
						$scope.hasAll = false;
					};
					
				}else{
					ngMessage.showTip(res.resMsg);
				}
			});
			
		};
		
	}
	user();
	/*
	app.filter("date", function($filter) {
            return function(input, format) {

                //从字符串 /Date(1448864369815)/ 得到时间戳 1448864369815
                var timestamp = Number(input.replace(/\/Date\((\d+)\)\//, "$1"));

                //转成指定格式
                return $filter("date")(timestamp, format);
            };
        });
	*/
	$scope.toPage=function (page) {
		if (!token) {
			storage.toPage("login")
		}else{
			storage.toPage(page)
		}
		
	};
	
	$scope.save =function () {
			userS.changeUsername({
				"realname":$scope.user.rname,
				"sex":($scope.user.sex.value?$scope.user.sex.value:2),
				"nickname":$scope.user.aname,
				"birthday":$scope.user.birthday,
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
	/*$scope.$watch('image', function(n, old, scope) {
		if (/base64/img.test(n)) {
			userS.updAvatar({
				"base":n,
				"ext":"jpeg",
				"token":token
			},function(res){
			})
		};
	});*/
})



.controller('memCenterC', function ($scope,storage,userS,ngMessage) {
	$scope.isLogin = false;
	storage.init();
	var token = storage.get("token");
	var user = storage.get("user");
	if (token) {
		$scope.isLogin = true;
	} else {
		$scope.isLogin = false;
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
			$scope.isLogin = false;
			return false
		} else {
			$scope.isLogin = true;
		};
		if (key&&value) {
			storage.set(key,value);
		};
		storage.toPage(page);
		if (!$scope.user.image) {
			$scope.user.image = res.photo;
		};
	};
	$scope.share =function () {
		//分享必好货APP
		// wechat.share.qq(....)
	};
	$scope.login = function(){
		storage.set("referer","memcenter");			
			storage.toPage('login')
	};
	$scope.toCart = function () {		
		if (!$scope.isLogin) {
			$scope.set("referer","cart")			
			$scope.toPage('login')
		}else{
			$scope.toPage('cart')
		}
	};
	$scope.clear = function(){
		storage.clear();
		alert("缓存清除完成！");
	};

	function userMethod () {
		if (token) {
			userS.i({
				"token":token
			},function(res) {
				if (res.resCode=="SUCCESS") {
					$scope.user.money = res.money;
					$scope.user.integral = res.integral;
					$scope.user.grade_name = res.grade_name;
					//根据等级显示特权
					if(res.grade_name=="银卡会员"){
						$scope.isLevel1 = true;
						$scope.color1 = {"color":"orange"}
					}else{
						$scope.isLevel1 = false;
					};
					if(res.grade_name=="金卡会员"){
						$scope.isLevel2 = true;
						$scope.color2 = {"color":"orange"};
					}else{
						$scope.isLevel2 = false;
					};
					if(res.grade_name=="钻石会员"){
						$scope.isLevel3 = true;
						$scope.color3 = {"color":"orange"};
					}else{
						$scope.isLevel3 = false;
					};
					//点击等级显示拥有特权
					$scope.show1 = function(){
						$scope.isLevel1 = true;
						$scope.isLevel2 = false;
						$scope.isLevel3 = false;
					};
					$scope.show2 = function(){
						$scope.isLevel1 = false;
						$scope.isLevel2 = true;
						$scope.isLevel3 = false;
					};
					$scope.show3 = function(){
						$scope.isLevel1 = false;
						$scope.isLevel2 = false;
						$scope.isLevel3 = true;
					};
//					$scope.show = function(a){
//						alert(222);
//						
//						$scope.isLevel1 = false;
//						$scope.isLevel2 = true;
//						$scope.isLevel3 = false;
//					};
//					$scope.show = function(a){
//						alert(333);
//						
//						$scope.isLevel1 = false;
//						$scope.isLevel2 = false;
//						$scope.isLevel3 = true;
//					};
					
				}else{
					ngMessage.showTip(res.resMsg);
				};
			});
			
			userS.memberCenter({
				"token":token
			},function(res) {
				if (res.resCode=="SUCCESS") {
					$scope.user.next_grade = res.next_grade;
					$scope.user.growth = res.growth;
					$scope.user.differGrowth = res.differGrowth;
					$scope.user.aliasname = res.aliasname;
				}else{
					ngMessage.showTip(res.resMsg);
				};
				//成长值转换成百分比
				if(res.growth<=8000){
					$scope.growthpercent = res.growth*50/8000;
				}
				else if(res.growth>8000){
					$scope.growthpercent = 50+((res.growth-8000)/840);
				}
			});

			userS.user({
				token:token
			},function (res) {
				if (!$scope.user.aname) {
					$scope.user.aname = res.aliasname;
				};
				if (!$scope.user.image) {
					$scope.user.image = res.photo;
				};
			})
		};
		
	};
	userMethod();
	
	
	$scope.blocked = function(){
		ngMessage.showTip("即将开通，敬请期待！")
	}

})


.controller('bindPhoneC',function ($scope,$interval,bindPhoneS,storage,ngMessage,registerS){
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
		// $scope.user.active = "active";
		var i = 59;

		theClock = $interval(function () {
			i--;
			$scope.user.text = "剩余"+i+"秒";
			$scope.$applyAsync();
			if (i==0) {
				clockEnd();
			};
			$scope.user.active = "active";			
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

})

.controller('oldBindPhoneC',function ($scope,$interval,bindPhoneS,storage,ngMessage,registerS){
	storage.init();
	// storage.clear();
	// var codeType = 2;//1:注册 2:忘记密码 3:修改手机
	var uid = storage.get("uid");
	var cart_id = storage.get("cart_id");
	var is_order = storage.get("is_order");
	// console.log(is_order);
	$scope.phone={
		"mobile":"",
		"code":""
	};
	var token = storage.get("token");
	$scope.placeholder = "请输入手机号"
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
		// $scope.user.active = "active";
		var i = 59;

		theClock = $interval(function () {
			i--;
			$scope.user.text = "剩余"+i+"秒";
			$scope.$applyAsync();
			if (i==0) {
				clockEnd();
			};
			$scope.user.active = "active";			
		},1000,59)
	}


	$scope.getCode = function () {
		$scope.show = true;
		if ($scope.phone.mobile&&/^[1][34578][0-9]{9}$/.test($scope.phone.mobile)) {
			if (!$scope.user.sendSMS) {
				clock();
				var k = "regCode"
				// if (isBindNew) {
					// k = "regCodeNew"
				// };
				bindPhoneS.sendOldUserCode({
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
			/* if (isBindNew) {
				k = "verifyPhoneNew"
			}; */
			bindPhoneS.checkUserCode({
				"mobile": $scope.phone.mobile,
				"code": $scope.phone.code,
				"token": token,
				"uid":uid,
				"cart_id":cart_id,
				"is_order":is_order,
			}, function(res) {
				if (res.resCode == "SUCCESS") {
					if(!res.uid){
						storage.toPage("setPassword");
						return false;
					}else{
						if(res.is_order=="1"){	//如果是订单页面来的跳转 （已绑定）
							var payRes = storage.get('payRes');
							if(payRes=='SUCCESS'){
								storage.set("is_order","");
								storage.set("payRes","");
								storage.set("toBind","");
								
								storage.toPage("paySuccess");
							}else{
								storage.set("is_order","");
								storage.set("payRes","");
								storage.set("toBind","");
								
								storage.toPage("payFailure");
							}
							// storage.toPage("checkOrder");
						}else{	//否则，切换账号并跳至首页
							/* var dat = new Array();
							dat['mobile']=res.mobile;
							dat['accountStatus']=res.accountStatus;
							dat['aliasname']=res.aliasname;
							dat['token']=res.token;
							dat['uid']=res.uid;
							dat['resCode']=res.resCode;
							dat['resMsg']=res.resMsg;
							
							//退出旧账号，登陆绑定的新账号
							storage.clear();
							storage.set("user",dat);
							storage.set("token", res.token);
							storage.set("uid", res.uid); */
						
							storage.toPage("home");
						}
					}
					
					
					/* if (res.token) {
						//storage.set("token", res.token) ;//暂时不做安全检测
						storage.toPage("bindPhone", "?bind=true")
					} else {
						ngMessage.showTip(res.resMsg)
						setTimeout(function(){
							storage.toPage("memcenter")
						}, 1000)
						
					} */
				} else {
					ngMessage.showTip(res.resMsg)
				}
			})
		} else {
			ngMessage.showTip("所有输入框都必填！")
		}
	};

	/* //忘记密码
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
	} */

})

.controller('setPasswordC',function ($scope,userS,storage,ngMessage){
	storage.init();
	$scope.password={
		"newa":"",
		"newb":""
	}
	/* $scope.show =false;
	$scope.hide=false;
	storage.get("pwd")=="change"&&($scope.hide=true); */
	
	//提交验证
	$scope.submit = function() {
		if ($scope.password.newa != $scope.password.newb) {
			ngMessage.showTip("新密码不一致！");
			return false
		};
		if (!$scope.password.newa || !$scope.password.newb) {

			ngMessage.showTip("所有密码框都必填！");
			return false
		};
		var data={
			"newPass": $scope.password.newa,
			"newRepass": $scope.password.newb,
			"token": storage.get("token")
		};
		/* if (!$scope.hide) {
			data["oldPass"] = $scope.password.old;
		}; */
		userS.setPassword(data, function(res) {
			if (res) {
				if (res.resCode == "SUCCESS") {
					
					ngMessage.showTip("密码设置成功！请重新登录！",3000,function(){
						
					storage.clear();
		            storage.toPage("memcenter");
					return	
					});
				}else{
					ngMessage.showTip(res.resMsg);
				}				
			} else {
				ngMessage.showTip("网络问题,密码修改失败！")
			}
		})

	}
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
					
					ngMessage.showTip("密码修改成功！请重新登录！",3000,function(){
						
					if ($scope.hide) {
						storage.set("token","");
						storage.toPage("home");
						return
					};
					storage.clear();
		            storage.toPage("memcenter");
					return	
					});
				}else{
					ngMessage.showTip(res.resMsg);
				}				
			} else {
				ngMessage.showTip("网络问题,密码修改失败！")
			}
		})

	}
})