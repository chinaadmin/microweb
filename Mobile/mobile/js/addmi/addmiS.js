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



.factory('addmiS', function($rootScope,$http,httpRequest){
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
		},
		getList:function (data,callback) {
			httpRequest.post(interfaceURL.commodity.list,data).success(function (data) {
				if (data.resCode=="SUCCESS") {
					// var b = data.lastPage=="0"?true:false;
					callback&&callback(data.data,data.lastPage)
				};				
			})
		},
		area:function (data,callback) {
			httpRequest.post(interfaceURL.other.area,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getLocation:function (data,callback) {
			httpRequest.post(interfaceURL.other.getLocation,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getBrand:function (callback) {
			$http.post(interfaceURL.home.brand).success(function (data) {
				if (data.resCode=="SUCCESS") {
					callback&&callback(data.brands)
				};				
			})
		},
		getCategory:function (callback) {
			$http.post(interfaceURL.home.category).success(function (data) {
				if (data.resCode=="SUCCESS") {
					callback&&callback(data.cats)
				};				
			})
		},
		getExplosion:function (callback) {
			httpRequest.post(interfaceURL.home.explosion,{
				count:4
			}).success(function (data) {
				if (data.resCode=="SUCCESS") {
					callback&&callback(data.goodsList)
				};				
			})
		},
		getRecommend:function (callback) {
			httpRequest.post(interfaceURL.home.recommend,{
				feat:1
			}).success(function (data) {
				if (data.resCode=="SUCCESS") {
					callback&&callback(data.goodsList)
				};				
			})
		},
		getFeats:function(callback){
			httpRequest.post(interfaceURL.home.feats).success(function(data) {
				if (data.resCode == "SUCCESS") {
					callback && callback(data.featList)
				};
			})
		},
		oauth:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.oauth,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		wxsdk:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.sdk,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		mauth:function (data,callback,fail) {
			httpRequest.post(interfaceURL.wechat.mauth,data).success(function (data) {
				if (data.resCode) {
					callback&&callback(data)	
				}else{
					fail&&fail()
				}							
			}).error(function() {
				fail&&fail()
			});
		},
		receiveCoupon:function (data,callback) {
			httpRequest.post(interfaceURL.coupon.receiveCoupon,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		activity:function (data,callback) {
			httpRequest.post(interfaceURL.activitys.activityUrl,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		activityGetFriend:function (data,callback) {
			httpRequest.post(interfaceURL.activitys.activityGetFriend,data).success(function (data) {
				callback&&callback(data)				
			}).error(function() {
				fail&&fail()
			});
		},
		validateMp:function(data,callback){
			httpRequest.post(interfaceURL.wechat.isLook,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		activityFriendCode:function (data,callback) {
			httpRequest.post(interfaceURL.activitys.activityFriendCode,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getHotCity:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.getHotCity,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})