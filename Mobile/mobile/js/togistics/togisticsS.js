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



.factory('orderS', function(httpRequest){
	return {
		order:function (data,callback) {
			httpRequest.post(interfaceURL.order.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		address:function (data,callback) {
			httpRequest.post(interfaceURL.address.option,data).success(function (data) {
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
		goShopping:function (data,callback) {
			httpRequest.post(interfaceURL.cart.clearing,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		pay:function (data,callback) {
			httpRequest.post(interfaceURL.order.pay,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		apay:function (data,callback) {
			httpRequest.post(interfaceURL.order.apay,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		submit:function (data,callback) {
			data.source = 1;
			httpRequest.post(interfaceURL.order.submit,data).success(function (data) {
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
		detail:function (data,callback) {
			httpRequest.post(interfaceURL.order.detail,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		cancel:function (data,callback) {
			httpRequest.post(interfaceURL.order.cancal,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		del:function (data,callback) {
			httpRequest.post(interfaceURL.order.del,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		sure:function (data,callback) {
			httpRequest.post(interfaceURL.order.receipt,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		coupon:function (data,callback) {
			httpRequest.post(interfaceURL.coupon.byOrder,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		coin:function (data,callback) {
			httpRequest.post(interfaceURL.user.i,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		storesDistance:function (data,callback) {
			httpRequest.post(interfaceURL.address.storesDistance,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		wxsdk:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.sdk,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})

.factory('invoiceS', function(httpRequest){
	return {
		list:function (data,callback) {
			httpRequest.post(interfaceURL.invoice.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		add:function (data,callback) {
			httpRequest.post(interfaceURL.invoice.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		update:function (data,callback) {
			httpRequest.post(interfaceURL.invoice.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		del:function (data,callback) {
			httpRequest.post(interfaceURL.invoice.list,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})

.factory('togisticsS', function(httpRequest){
	return {
		togistics:function (data,callback) {
			httpRequest.post(interfaceURL.togistics.tost,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})
