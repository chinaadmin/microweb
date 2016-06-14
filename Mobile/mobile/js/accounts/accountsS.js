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

.factory('balanceS', function(httpRequest){
	return {
		recharge:function (data,callback) {
			httpRequest.post(interfaceURL.order.recharge,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		coin:function (data,callback) {
			httpRequest.post(interfaceURL.other.coin,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		wxsdk:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.sdk,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		i:function (data,callback) {
			httpRequest.post(interfaceURL.user.i,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		history:function (data,callback) {
			httpRequest.post(interfaceURL.other.recHistory,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		payRecord:function (data,callback) {
			httpRequest.post(interfaceURL.other.payRecord,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		allRecord:function (data,callback) {
			httpRequest.post(interfaceURL.other.allRecord,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})
