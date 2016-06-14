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



.factory('historyS', function(httpRequest){
	return {
		history:function (data,callback) {
			httpRequest.post(interfaceURL.other.history,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		fav:function (data,callback) {
			httpRequest.post(interfaceURL.fav.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		favDel:function (data,callback) {
			httpRequest.post(interfaceURL.fav.del,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})

.factory('detailS', function($http,httpRequest){
	return {
		goShopping:function (data,callback) {
			httpRequest.post(interfaceURL.cart.clearing,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		chips:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.chips,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getProject:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.getProject,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getLogistic:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.getLogistic,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		doCollec:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.doCollec,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		delCollect:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.delCollect,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		travelReg:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.travelReg,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		chipsGoods:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.chipsGoods,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getDetail:function (callback,token) {
			var id = this.id('gid');	
			var post = 	{
				"goods_id":id
			};
			if (token) {
				post.token = token;
			};
			id&&httpRequest.post(interfaceURL.commodity.detail,post).success(function (data) {
				if (data.resCode=="SUCCESS") {
					alert(55)
					callback&&callback(data.goods)
				};				
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
		},
		toCart:function (data,callback) {
			httpRequest.post(interfaceURL.cart.add,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		fav:function (data,callback) {
			httpRequest.post(interfaceURL.fav.add,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		favDel:function (data,callback) {
			httpRequest.post(interfaceURL.fav.del,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		buynow:function (data,callback) {
			httpRequest.post(interfaceURL.cart.buynow,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		cartCount:function (data,callback) {
			httpRequest.post(interfaceURL.cart.count,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		wxsdk:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.sdk,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		commentList:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.commentList,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getHotCity:function (data,callback) {
			httpRequest.post(interfaceURL.wechat.getHotCity,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		getLocation:function (data,callback) {
			httpRequest.post(interfaceURL.other.getLocation,data).success(function (data) {
				callback&&callback(data)				
			})
		}
	}
})

.factory('orderS', function(httpRequest){
	return {
		exitChips:function (data,callback) { //退出众筹
			httpRequest.post(interfaceURL.allChips.exitChips,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		awardTo:function (data,callback) {
			httpRequest.post(interfaceURL.activitys.isDraw,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		order:function (data,callback) {
			httpRequest.post(interfaceURL.order.list,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		orderShow:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.orderShow,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		allOrders:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.allOrders,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		confirmReceive:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.receiveConfirm,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		i:function (data,callback) {
			httpRequest.post(interfaceURL.user.i,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		submitOrder:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.submitOrder,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		accountPay:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.accountPay,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		wechatPay:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.wechatPay,data).success(function (data) {
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
		},
		commitComment:function (data,callback) {
			httpRequest.post(interfaceURL.address.commitComment,data).success(function (data) {
				callback&&callback(data)				
			})
		},
		orderInfo:function (data,callback) {
			httpRequest.post(interfaceURL.allChips.orderInfo,data).success(function (data) {
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