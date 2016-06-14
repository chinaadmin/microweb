angular.module('app.services', [])
.factory('httpRequest', function($http) {
	var param = function(obj) {
		var query = '',
			name, value, fullSubName, subName, subValue, innerObj, i;

		for (name in obj) {
			value = obj[name];

			if (value instanceof Array) {
				for (i = 0; i < value.length; ++i) {
					subValue = value[i];
					fullSubName = name + '[' + i + ']';
					innerObj = {};
					innerObj[fullSubName] = subValue;
					query += param(innerObj) + '&';
				}
			} else if (value instanceof Object) {
				for (subName in value) {
					subValue = value[subName];
					fullSubName = name + '[' + subName + ']';
					innerObj = {};
					innerObj[fullSubName] = subValue;
					query += param(innerObj) + '&';
				}
			} else if (value !== undefined && value !== null)
				query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
		}

		return query.length ? query.substr(0, query.length - 1) : query;
	};
	return {
		post: function(url, data) {
			var config = {
				"headers": {
					"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
				}
			}
			var data =  angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
			var http = $http.post.apply($http, [url, data, config])
			return http ;
		}
	}
})



.factory('loginS', function(httpRequest){
	return {
		login:function (data,callback) {
			httpRequest.post(interfaceURL.user.login,data).success(function (data) {
				callback&&callback(data)				
			}).error(function(e) {
				callback && callback({
					"resCode": "NETERROR",
					"resMsg": "网络错误，信息发送失败！",
					"err": e
				});
			});
		}
	}
}).factory('registerS',function(httpRequest){
	var netError={
		"resCode":"NETERROR",
		"resMsg":"网络错误，信息发送失败！"
	}
	return {
		register:function (data,callback,success) {
			httpRequest.post(interfaceURL.user.register,data).success(function (res) {
				callback&&callback(res)				
			})
		},
		accountBind:function (data,callback,success) {
			httpRequest.post(interfaceURL.user.accountBind,data).success(function (res) {
				callback&&callback(res)				
			})
		},
		regCode:function (data,callback,success) {
			httpRequest.post(interfaceURL.sms.send,data).success(function (res) {
							if (res.resCode != "SUCCESS") {
								callback&&callback(res.resMsg)
							}else{
								success&&success();
							}
			}).error(function () {
				callback&&callback(netError.resMsg)
			})
		},
		verifyPhone:function (data,callback) {
			httpRequest.post(interfaceURL.sms.verify, data).success(function(res) {
				callback&&callback(res)
			}).error(function() {
				callback && callback(netError)
			})
		}
	}
})

.factory('userS', function(httpRequest){
	return {
		user:function (data,callback) {
			httpRequest.post(interfaceURL.user.infomation,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		changeUsername:function(data,callback) {
			httpRequest.post(interfaceURL.user.updName,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		updatePassword:function (data,callback) {
			var  url = interfaceURL.user.updPassword;
			if (!data["oldPass"]) {
				url = interfaceURL.user.recPassword;
			};
			httpRequest.post(url,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		setPassword:function (data,callback) {
			httpRequest.post(interfaceURL.user.setPassword,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		i:function (data,callback) {
			httpRequest.post(interfaceURL.user.i,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		memberCenter:function (data,callback) {
			httpRequest.post(interfaceURL.user.memberCenter,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		updAvatar:function (data,callback) {
			httpRequest.post(interfaceURL.user.updAvatar,data).success(function (data) {
				callback&&callback(data)				
			})
		},
	}
})

.factory('bindPhoneS',function(httpRequest){
	var netError={
		"resCode":"NETERROR",
		"resMsg":"网络错误，信息发送失败！"
	}
	return {
		regCode:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkTelSend,data).success(function (res) {
							if (res.resCode!="SUCCESS") {
								callback&&callback(res.resMsg)
							};
			}).error(function () {
				callback&&callback(netError.resMsg)
			})
		},
		verifyPhone:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkTel, data).success(function(res) {
				callback&&callback(res)
			}).error(function() {
				callback && callback(netError)
			})
		},
		regCodeNew:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkTelNewSend,data).success(function (res) {
							if (res.resCode!="SUCCESS") {
								callback&&callback(res.resMsg)
							};
			}).error(function () {
				callback&&callback(netError.resMsg)
			})
		},
		verifyPhoneNew:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkTelNew, data).success(function(res) {
				callback&&callback(res)
			}).error(function() {
				callback && callback(netError)
			})
		},
		sendOldUserCode:function (data,callback) {
			httpRequest.post(interfaceURL.sms.sendOldUserCode,data).success(function (res) {
							if (res.resCode!="SUCCESS") {
								callback&&callback(res.resMsg)
							};
			}).error(function () {
				callback&&callback(netError.resMsg)
			})
		},
		checkUserCode:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkUserCode, data).success(function(res) {
				callback&&callback(res)
			}).error(function() {
				callback && callback(netError)
			})
		},
		wx_pay:function (data,callback) {	//微信支付接口
			httpRequest.post(interfaceURL.order.pay,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		}
	}
})

/* .factory('oldBindPhoneS',function(httpRequest){
	var netError={
		"resCode":"NETERROR",
		"resMsg":"网络错误，信息发送失败！"
	}
	return {
		regCode:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkTelSend,data).success(function (res) {
							if (res.resCode!="SUCCESS") {
								callback&&callback(res.resMsg)
							};
			}).error(function () {
				callback&&callback(netError.resMsg)
			})
		},
		checkUserCode:function (data,callback) {
			httpRequest.post(interfaceURL.sms.checkUserCode, data).success(function(res) {
				callback&&callback(res)
			}).error(function() {
				callback && callback(netError)
			})
		}
	}
}) */