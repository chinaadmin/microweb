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
	return {
		list:function (data,callback) {
			httpRequest.post(interfaceURL.refund.list,data).success(function (data) {
				callback&&callback(data);				
			})
		},
		detail:function (data,callback) {
			httpRequest.post(interfaceURL.refund.detail,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		apply:function (data,callback) {
			httpRequest.post(interfaceURL.refund.apply,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		submit:function (data,callback) {
			httpRequest.post(interfaceURL.refund.submit,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})
.factory('couponS', ['httpRequest', function (httpRequest) {
	return {
		list:function (data,callback) {
			httpRequest.post(interfaceURL.coupon.list,data).success(function (data) {
				callback&&callback(data);				
			})
		},

		//获取优惠券详情
		getCouponRemark:function (data,callback) {
			httpRequest.post(interfaceURL.coupon.getCouponRemark,data).success(function (data) {
				callback&&callback(data);				
			})
		}
	};
}])