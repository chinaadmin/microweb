angular.module('app.controllers', [])
	.directive('errSrc', function() {
		return {
			link: function(scope, element, attrs) {
				element.bind('error', function() {
					if (attrs.src != attrs.errSrc) {
						attrs.$set('src', attrs.errSrc);
					}
				});
				if (attrs.$attr['ngSrc'] && !attrs.ngSrc) {
					attrs.$set('src', attrs.errSrc);
				};

			}
		}
	})

.directive('ngImagescale', function() {
		return {
			link: function(scope, element, attrs) {
				if (element[0].tagName == "IMG") {
					element.bind('load', function() {
						if (attrs.ngImagescale && (attrs.ngImagescale.split(":")).length == 2) {
							var size = attrs.ngImagescale.split(":");
							var w = parseInt(size[0]) || 0,
								h = parseInt(size[1]) || 0;
							if (w && h) {
								element[0].height = element[0].width * h / w;
							} else {
								element[0].height = element[0].width;
							}
						} else {
							element[0].height = element[0].width;
						}
					});
				};
			}
		}
	})
	.directive('ngBack', function(storage) {
		return {
			link: function(scope, element, attrs) {
				storage.init();
				var referer = storage.get("referer") || "";
				var url = location.href.replace(location.search, '');
				var reg = new RegExp(url, "img");
				var loginReg = /http[^?#=]+login.html/img
				element.bind('click', function() {
					// if (!reg.test(referer)&&!loginReg.test(referer)) {
					// 	storage.toPage(referer);
					// 	return false;
					// };
					if (storage.test(attrs.ngBack)) {
						storage.toPage(attrs.ngBack)
					} else {
						window.history.go(-1);
					};
				});
			}
		}
	})

.controller("historyC", function($scope, historyS, storage, ngMessage) {
	storage.init()
	var index = 1;
	var token = storage.get("token");
	if (!token) {
		storage.toPage("login");
	};
	$scope.history = [];
	$scope.hasMore = false;
	var history = storage.get("history") || [];
	var tmp = [];
	for (var i = 0; i < history.length; i++) {
		if (typeof(history[i]) == "object") {
			tmp.push(history[i]);
		};
	};
	function historyList() {
		historyS.history({
			"token": token,
			"goods": JSON.stringify(tmp), //需要按每份10个经行切割
			"currentPage": index,
			"count": 10
			
		}, function(res) {
			if (res.resCode == "SUCCESS") {
				// $scope.history = res.data;
				
				//alert(res.data.length)
				if (index>1) {
					var tmp = angular.copy($scope.history);
					$scope.history = tmp.concat(res.data)
				}else{
					$scope.history = res.data
				};
			};
			if (res.lastPage == "1") {
				$scope.hasMore = true;
			} else {
				$scope.hasMore = false;
			};
		})
	}
	$scope.more=function () {
		if ($scope.hasMore) {
			index++;
			historyList();
		};
	}
    
	historyList();
	$scope.toDetail = function(v) {
		var q = "?gid=" + v.goodsId;
		storage.toPage("detail", q);
	}

})
.controller("favC", function($scope, ngMessage,historyS, storage) {
	storage.init()
	var index = 1;
	var token = storage.get("token");
	var fkId = storage.get("fk_id");
	if (!token) {
		storage.toPage("login");
	};
	$scope.fav = [];
	$scope.hasMore = false;
	$scope.suny = false;
	$scope.shits = false;
	$scope.doCollect = [];

	function historyList() {
		$scope.suny = true;
		$scope.shits = false;
		historyS.fav({
			"token": token,
			"currentPage": index,
			"count": 20

		}, function(res) {
			if (res.resCode == "SUCCESS") {
				$scope.fav = res.data || [];
				//console.log(res.data)
			};
		})
	}

	$scope.Medise = function() {
		historyList();
	}
	$scope.chips = function() {
		$scope.shits = true;
		$scope.suny = false;
		historyS.doCollect({
			"token": token
		},function(res) {
			$scope.doCollect = res.data;
		})
	}
	$scope.fav_up = function(v, k) {
		historyS.delCollect({
			"token": token,
			"fkId": v.fkid
		}, function(res) {
			//激活图标
			if (res && res.resCode == "SUCCESS") {
				ngMessage.showTip(res.resMsg);
				angular.element(document.getElementById('doCollect'+k)).remove();
			};
		});
	};
	historyList();
	$scope.toDetail = function(v) {
		var q = "?gid=" + v.goodsId;
		storage.toPage("detail", q);
	}
	$scope.unfav = function(v, k) {
		v && historyS.favDel({
			"token": token,
			"goodsId": v.goodsId
		}, function(res) {
			//激活图标
			if (res && res.resCode == "SUCCESS") {
				ngMessage.showTip(res.resMsg);
				historyList();
			};
		});
	};

})

.controller("detailC", function ($scope, $window, $filter, detailS, storage, ngMessage, ngWechat) {
	storage.init();
	$scope.myInterval = 1000;
	var slides = $scope.slides = [];
	$scope.isShow = false;
	var selectBox = [];
	var dateFomater = $filter('date');
	var token = storage.get("token") || "";
	var cacheCartBox = storage.get("cartBox") || {};
	$scope.page = {
		image: "/images/favs.png",
		count: 0
	}
	$scope.detail = {
		"number": 1,
		"photo": [],
		"norms": {
			"norms_value": [],
			"norms_attr": []
		}
	};


	$scope.commentList = {
		"add_time":[],
		"content":'',
		"head_pic":'',
		"comment_name":'',
		"norms_value":{
			"name":[],
			"value":[]
		}
	};

	detailS.commentList({token:token,goodsId:storage.queryField('gid')},function(res) {
		$scope.commentList = res;
		var count = 0;

		angular.forEach(res.data, function(value, key){
			count++;
		});
	});



	detailS.getDetail(function(res) {
		$scope.detail = res;
//		console.log($scope.detail)
		$scope.detail.number = 1;
		$scope.detail.faved = res.isCollect == "1" ? true : false;
		
		if ($scope.detail.faved) {
			$scope.page.image = "/images/fav-active.png";
		};
		if (res.norms["norms_value"].length) {
			for (var i = 0; i < $scope.detail.norms["norms_value"].length; i++) {
				$scope.detail.norms["norms_value"][i].value[0].select = true;
				selectBox.push($scope.detail.norms["norms_value"][i].value[0].norms_value_id)
			};
		};
		storage.push("history", {
			"goods_id": res.goods_id,
			"time": dateFomater(new Date(), "yyyy-MM-dd hh:mm:ss")
		});
		//console.log(storage.get('history'))
	}, token);

	function cartCount() {
		if (token) {
			token && detailS.cartCount({
				token: token
			}, function(res) {
				if (res && res.resCode == "SUCCESS") {
					$scope.page.count = parseInt(res.cartTotal)
				};
			});
		} else if (cacheCartBox) {
			for (var k in cacheCartBox) {
				if (cacheCartBox.hasOwnProperty(k)) {
					try {
						tmp = JSON.parse(cacheCartBox[k]);
						$scope.page.count += tmp.num;
					} catch (e) {
						statements
						console.log(e);
					}
				}
			}
		}


	};
	cartCount();
	/*
	detailS.wxsdk({
		url: window.location.href
	}, function(res) {
		$scope.tt = JSON.stringify(res);
		if (res && res.resCode == "SUCCESS") {
			ngWechat && ngWechat.init({
				"appId": res.jssdk.appId,
				"timestamp": res.jssdk.timestamp,
				"nonceStr": res.jssdk.nonceStr,
				"signature": res.jssdk.signature
			},function(res){
				//ngMessage.show(JSON.stringify(res))
			})
		};
	});
	*/


    



	$scope.tab = function(index) {
		if (index) {
			$scope.active = false;
			$scope.sactive = true;
		} else {
			$scope.active = true;
			$scope.sactive = false;
		}
	}
	$scope.tab(0);

	/* $scope.share = function() {
		 alert(123)
		return false
		if (ngWechat) {
			ngWechat.setShare({
				title: $scope.detail.name,
				desc: $scope.detail.name,
				link: window.location.href,
				imgUrl: $scope.detail.photo.length ? $scope.detail.photo[0].middle : ""
			})
			ngWechat.shareTimeline(function(res) {
				ngMessage.showTip(JSON.stringify(res));
			})
		} else {
			ngMessage.showTip("分享当前商品!");
		}

	}; */
	
	 $scope.share = function() {
		if ($scope.isShow == true){
			$scope.isShow = false;
		}else{
			$scope.isShow = true;
		}
		return false;
	};
	$scope.tt = function (){
		$scope.isShow = false;
	}
	$scope.fav = function() {
		if (!$scope.detail.faved) {
			detailS.fav({
				"token": token,
				"goodsId": $scope.detail.goods_id
			}, function(res) {
				//激活图标
				if (res) {
					if (res.resCode == "SUCCESS") {
						$scope.detail.faved = true;
						$scope.page.image = "/images/fav-active.png";
						ngMessage.showTip(res.resMsg);
					} else if (/token/img.test(res.resCode)) {
						ngMessage.showTip("请先登录再收藏！");
					} else {
						ngMessage.showTip(res.resMsg);
					}
				};
			});
			// console.log($scope.detail.goods_id)
		} else {
			detailS.favDel({
				"token": token,
				"goodsId": $scope.detail.goods_id
			}, function(res) {
				//激活图标
				if (res && res.resCode == "SUCCESS") {
					$scope.detail.faved = false;
					$scope.page.image = "/images/favs.png";
					ngMessage.showTip(res.resMsg);
				};
			});
		}
		// console.log($scope.detail.goods_id+"11")
	};
	$scope.back = function() {
		if ($window.history.length) {
			$window.history.go(-1);
		} else {
			storage.toPage("home");
		}

	}
	$scope.addToCart = function() {
		var num = parseInt($scope.detail["number"]) || 1;
		var post = {
			"goodsId": $scope.detail["goods_id"],
			"num": num,
			"norms": selectBox.join("_")
		}
		if (!token&&post.goodsId&&post.num) {			
			post.num += $scope.page.count;
			storage.put("cartBox", post.goodsId, JSON.stringify(post));
			$scope.page.count++;
			ngMessage.showTip("加入购物车成功！");
			return false
		};
		post.token = token;
		$scope.detail.number = num;
		detailS.toCart(post, function(res) {
			if (res.resCode == "SUCCESS") {
				cartCount();
			} else {};
			ngMessage.showTip(res.resMsg);
		});

	};

	$scope.buy = function() {
		if (!token) {
			storage.toPage("login");
			return false
		};
		var num = parseInt($scope.detail["number"]) || 1;
		$scope.detail.number = num;
		detailS.buynow({
			"goodsId": $scope.detail["goods_id"],
			"num": num,
			"norms": selectBox.join("_"),
			"token": storage.get("token")
		}, function(res) {
			if (res.resCode == "SUCCESS") {
				storage.set("cartId", res.cartId);
				detailS.goShopping({
						"cartId": res.cartId,
						"token": token
					}, function(res) {
						if (res.resCode == "SUCCESS") {	
							storage.toPage("checkOrder");
						}else{
							 res && ngMessage.showTip(res.resMsg);
						}
				});
			}else{
				res && ngMessage.showTip(res.resMsg);
			} 

		})

	}

	$scope.$watch('detail.number', function(newValue, oldValue, scope) {
		
		if (oldValue!=newValue&&newValue) {
			newValue = newValue+"";

			var t = newValue.match(/^[1-9]\d{0,3}$/)||" ";
			if (t&&t.length) {
				$scope.detail.number = t;
			};
			
		};	
	});

	$scope.select = function(v, value, key) {
		for (var i = 0; i < value.length; i++) {
			value[i].select = false;
		};
		v.select = !v.select;
		selectBox[key] = v.norms_value_id;

	}

	$scope.add = function() {
		$scope.detail.number++;
	}
	$scope.sub = function() {
		if ($scope.detail.number > 1) {
			$scope.detail.number--;
		};
	};
	$scope.toPage = function(page) {
		storage.toPage(page)
	}
})
.controller('comment', function($scope, $window, $filter, detailS, storage, ngMessage, ngWechat) {
		var token = storage.get("token") || "";
		detailS.commentList({token:token,goodsId:storage.queryField('gid')},function(res) {
			$scope.commentList = res;
		});
})

.controller('gatareaC', function($scope, $window, $filter, tool, detailS, storage, ngMessage, ngWechat) {
		var token = storage.get("token") || "";
		storage.init();

		$scope.ByLngLat = {
			    "provice": {
		            "id": "440",
		            "name": "广东省"
		        },
		        "city": {
		            "id": "440300000000",
		            "name": "点击定位"
		        }
		};
		$scope.submit = function (){
			tool.getLocation(function(position){
		        //经度
		        var longitude =position.coords.longitude;
		        //纬度
		        var latitude = position.coords.latitude;
				// alert(longitude + '|&&|' + latitude);
				detailS.getLocation({
			    	"lng":longitude,
			    	"lat":latitude 
			    },function(res){
			    	$scope.ByLngLat = res.data;
			    	var	str = res.data.city.name;
		            var newstr=str.substring(0,str.length-1);
		            $scope.ByLngLat.city.name = newstr;
			    	storage.set('ByLngLat',res.data);
			    })
			},function(error){
			         switch(error.code){
			             case 1:
				            ngMessage.showTip("请打开GPS定位",2000);
			             break;
			             case 2:
			            	 ngMessage.showTip("暂时获取不到位置信息",2000);
			             break;
			         }
			});
		}
		$scope.gatarea = [];
		detailS.getHotCity({
	    },function(res) {
	    	$scope.gatarea = res.data;
		});
		$scope.mydown = function(v){
			// alert(JSON.stringify(v));		
			storage.toPage("home");
        	storage.set('chooseLocation',v);
        	// $(".linck_up").hide();
        }
})





