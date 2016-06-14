angular.module('tryEat.services', [])
.factory("resSvr",function (httpRequest,cache) {
	cache.init();
	return{		
		getImages:function (indexPage,size,keywords,callbak) {		
			var pdata = {
				"currentPage": indexPage,
				"count": size
			};
			if (cache.getUid()) {
				pdata.uid = cache.getUid();
			};
			if (keywords) {
				pdata.keyword = keywords;
			};
			httpRequest&&httpRequest.post(appURLRES.images,pdata).success(function (data) {
				if (data.resCode=="UID_REQUIRE") {
					cache.clearAndReload();
					return false
				};	
				callbak&&callbak(data);
			})
		},
		vote:function(id,callbak) {
			httpRequest&&httpRequest.post(appURLRES.vote,{
				"photo_id":id,
				"uid":cache.getUid()
			}).success(function (data) {
				if (data.resCode=="UID_REQUIRE") {
					cache.clearAndReload();
					return false
				};	
				callbak&&callbak(data)			
			})
		}
	}
})
.factory('captureSvr', function (httpRequest,cache) {
	cache.init();
	return {
		get:function (callbak) {
			httpRequest.post(appURLRES.photoShow,{
				"uid":cache.getUid()
			}).success(function (data) {
				if (data.resCode=="UID_REQUIRE") {
					cache.clearAndReload();
					return false
				};	
				callbak&&callbak(data);				
			})
		}
	};
})

.factory('detailSvr', function (httpRequest,cache) {
	cache.init();
	return {
		getByPid:function (pid,callbak) {
			var d ={
				"photo_id":pid
			};
			if (cache.getUid()) {
				d.uid = cache.getUid();
			};
			httpRequest.post(appURLRES.photoinfo,d).success(function (data) {
				if (data.resCode=="SUCCESS") {
					callbak&&callbak(data["return"]);
				};				
			})
		},
		getByUid:function (callbak) {
			httpRequest.post(appURLRES.myPhtoInfo,{
				"uid":cache.getUid()
			}).success(function (data) {
				if (data.resCode=="SUCCESS") {
					callbak&&callbak(data["return"]);
				};
				if (data.resCode=="UID_REQUIRE") {
					cache.clearAndReload();
				};		
			})
		},
		getPid:function () {
				return cache.getPid();         
		}
	};
})

.factory('cache', function() {
	var phoneNumber = "";//13168074741
	var uid="";//y01hbGwSNAI2aml0d
	var pid = "";
	var url = "";
	var isInit = false;	
	var store={};
	var cacheTime = 2*60*60*1000;//2h
	return {
		init: function() {
			if (isInit) {
				return false
			};
			if (this.queryId("uid")) {
				uid = this.queryId("uid");
			};
			if (this.queryId("phoneNumber")) {
				phoneNumber = this.queryId("phoneNumber");
			};
			pid = this.queryId("pid");		
			url = this.queryId("url");					
			window.location.hash="";
			isInit = true;
			if (!uid||!phoneNumber||!pid) {
				this._store();
				if (store) {
					uid = store.uid;
					pid = store.pid;
					phoneNumber = store.phoneNumber;
				}else{
					store={}
				}
			};
			if (!uid) {
				window.location.href = staticPages.login+"#uid=1&url="+window.location.href
			};
		},
		getPhoneNumber: function() {
			this.init();
			return phoneNumber;
		},
		setPhoneNumber: function(pn) {
			this.init();
			if (!phoneNumber) {
				phoneNumber = pn;
				store.phoneNumber = pn;
				this._save();
			};
		},
		getUid:function  () {
			this.init();
			return uid||null;
		},
		setUid:function (userid,callbak) {
			this.init();
			if (userid) {
				uid = userid;
				store.uid = uid;
				this._save();
				callbak && callbak()
			};			
		},
		getPid:function () {
			return pid
		},
		getUrl:function () {
			return url;
		},
		_store:function () {
			var sto = window.localStorage.getItem("store");
			if (sto) {
				store = JSON.parse(sto);				
			};
		},
		_save:function () {
			if (store) {
				window.localStorage.setItem("store",JSON.stringify(store));
			};			
		},
		query:function () {
			var q="";
				q+="phoneNumber="+(phoneNumber||"");
				q+="&uid="+(uid||"");
			return window.encodeURI(q);
		},
		queryId:function (id) {
			if (!id) {return false};
			var str = id
			var url = window.location.hash;
			var rs = new RegExp("(^|)" + str + "=([^\&]*)(\&|$)", "gi").exec(url),
				tmp;
			if (tmp = rs) {
				return tmp[2];
			}
			return "";
		},
		toPage:function (staticPage) {
			if (staticPages[staticPage]) {
				window.location.href = staticPages[staticPage]+"#"+this.query();
			};
			
		},
		clear:function () {
			store={};
			uid="";
			pid="";
			phoneNumber="";
			this._save();
		},
		clearAndReload:function () {
			this.clear();
			window.location.href = staticPages.login+"#uid=1&url="+window.location.href
		}
	}
})

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

.factory('msgPopup', function ($timeout) {
	var $scope=null;
	return {
		init:function (scope) {
			$scope = scope;
			$scope.msgPopup = {};
			$scope.msgPopup.hide = true;
			$scope.msgPopup.msg = ""
		},
		show:function (msg,auto) {
			
			$scope.msgPopup.msg = msg;
			$scope.msgPopup.hide = false;		
			$scope.$applyAsync();	
			var that = this;
			!auto&&$timeout(function () {
				that.hide();
			},1600)
		},
		hide:function () {
			$scope.msgPopup.hide = true;
		}
	};
})
.factory('wxPreview', [function() {
	return {
		show: function(imageSrc) {
			if (window.WeixinJSBridge) {
				window.WeixinJSBridge.invoke("imagePreview", {
					'current': imageSrc,
					'urls': [imageSrc]
				})
			};
		}
	};
}])