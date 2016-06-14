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



.factory('listS', function($http,httpRequest){
	return {
		getList:function (data,callback) {
			var b = false;
			if (this.id("cid")) {
				data["cat_id"] = this.id("cid");
				b = true;
			};
			if (this.id("bid")) {
				data["brand_id"] = this.id("bid");
				b = true;
			};
			if (this.id("fid")) {
				data["feat"] = this.id("fid");
				b = true;
			};
			if (data.key) {
				b= true;
			};
			b&&httpRequest.post(interfaceURL.commodity.list,data).success(function (data) {
				if (data.resCode=="SUCCESS") {
					var b = data.lastPage=="0"?true:false;
					callback&&callback(data.data,b)
				};				
			})
		},
		getActivityList:function (data,callback) {
			httpRequest.post(interfaceURL.commodity.getActivityList,data).success(function (data) {
				callback&&callback(data)	
			})
		},
		searchList:function (data,callback) {
			httpRequest.post(interfaceURL.commodity.list,data).success(function (data) {
				callback&&callback(data)	
			})
		},
		id:function (idField) {
				if (!idField) { return false};
				var str = idField||""
                var url=location.href;
                var rs = new RegExp("(^|)"+str+"=([^\&]*)(\&|$)","gi").exec(url), tmp;
                if(tmp=rs){
                    return tmp[2];
                } 
                return "";            
		}
	}
})