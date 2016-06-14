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



.factory('manageS', function(httpRequest){
	return {
		list:function (data,callback) {
			httpRequest.post(interfaceURL.address.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		add:function (data,callback) {
			httpRequest.post(interfaceURL.address.add,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		update:function (data,callback) {
			httpRequest.post(interfaceURL.address.update,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		area:function (data,callback) {
			httpRequest.post(interfaceURL.other.area,data).success(function (data) {
				callback&&callback(data)				
			})
		},				
		setDefault:function (data,callback) {
			httpRequest.post(interfaceURL.address.setDefault,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		addressRec:function (data,callback) {
			httpRequest.post(interfaceURL.address.listRec,data).success(function (data) {
				callback&&callback(data)				
			})
		},		
		addRecAddress:function (data,callback) {
			httpRequest.post(interfaceURL.address.addRec,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		updateRecAddress:function (data,callback) {
			httpRequest.post(interfaceURL.address.updateRec,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		delRecAddress:function (data,callback) {
			httpRequest.post(interfaceURL.address.delRec,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		contactDetail:function (data,callback) {
			httpRequest.post(interfaceURL.push.contactDetail,data).success(function (data) {
				callback&&callback(data)				
			})
		},
	}
})
