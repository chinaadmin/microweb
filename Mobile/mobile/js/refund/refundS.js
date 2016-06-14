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



.factory('refundS', function(httpRequest){
	var netError = {
		"resCode": "NETERROR",
		"resMsg": "网络错误，数据发送失败！"
	}
	return {
		list:function (data,callback) {
			httpRequest.post(interfaceURL.refund.list,data).success(function (data) {
				callback&&callback(data);				
			}).error(function() {
				callback && callback(netError)
			})
		},
		detail:function (data,callback) {
			httpRequest.post(interfaceURL.refund.detail,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		},
		apply:function (data,callback) {
			httpRequest.post(interfaceURL.refund.apply,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		},
		applyAll:function (data,callback) {
			httpRequest.post(interfaceURL.refund.applyAll,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		},
		submit:function (data,callback) {
			httpRequest.post(interfaceURL.refund.submit,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		},
		submitAll:function (data,callback) {
			httpRequest.post(interfaceURL.refund.submitAll,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		},
		cancel:function (data,callback) {
			httpRequest.post(interfaceURL.refund.cancel,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				callback && callback(netError)
			})
		},
		updAvatar:function (data,callback) {
			httpRequest.post(interfaceURL.refund.uploadVoucher,data).success(function (data) {
				callback&&callback(data)				
			})
		}
		
	}
})