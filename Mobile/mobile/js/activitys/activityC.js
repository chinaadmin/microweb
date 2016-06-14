angular.module('app.controllers', [])
.factory("timeCircl",function ($scope) {

})
.directive('ngBack', function(storage) {
	return {
		link: function(scope, element, attrs) {
			storage.init();
			var referer = storage.get("referer")||"";
			var url = location.href.replace(location.search,'');
			var reg = new RegExp(url,"img");
			var loginReg = /http[^?#=]+login.html/img
			element.bind('click', function() {
				/*
				if (!reg.test(referer)&&!loginReg.test(referer)) {
					storage.toPage(referer);
					return false;
				};
				*/
				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else {
					window.history.go(-1);
				};
			});
		}
	}
})
.directive('errSrc', function() {
  return {
    link: function(scope, element, attrs) {
      element.bind('error', function() {
        if (attrs.src != attrs.errSrc) {
          attrs.$set('src', attrs.errSrc);
        }
      });
    }
  }
})
.directive('ngImagescale', function() {
  return {
    link: function(scope, element, attrs) {  
    	if (element[0].tagName=="IMG") {
			element.bind('load', function() {
				setTimeout(function() {
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
				}, 560);
			});
    	};
    }
  }
})
.directive('ngEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.ngEnter);
                }); 
                event.preventDefault();
            }
        });
    };
}) 
.directive('ngDialog', function () {
    return function (scope, element, attrs) {
        element.bind("click", function (event) {
            $element[0].querySelector(".dialog").css({height:$(document).height()});
            $element[0].querySelector('.dialog').show();
        });
    };
}) 
.directive('ngActivity', function () {
    return function (scope, element, attrs) {
    	
        element.bind("touchstart click", function (event) {
            event.preventDefault();
            initX = endX = event.touches[0].clientX;
            initY = endY = event.touches[0].clientY;
            scope.$apply(function (){
                scope.$eval(attrs.ngActivity);
            });
        });
    };
})
.controller("indexC",function ($scope,$interval,indexS,tool,storage,ngMessage,ngWechat) {
	 storage.init();
	function isInWeiXin() {
		var ua = window.navigator.userAgent.toLowerCase();
		if (ua.match(/MicroMessenger/i) == 'micromessenger') {
			return true;
		} else {
			return false;
		}
	}
	//获取用户微信信息并缓存start
	(function(){
		var authKey = storage.queryField("info");
		if(isInWeiXin() && !sessionStorage.getItem('online')){
			authKey && indexS.mauth({ // 根据key从后台获取用户缓存
				key: authKey
			}, function(res) {		
				storage.set("wx", res.info);
				//获取用户信息并缓存end
				 storage.init();
				 var token = storage.get("token");
				 var wechat = storage.get("wx");
				 var bind = storage.get("bind");	 	
				if (!wechat) {
					storage.toPage("wxAuth");
				} else {
					!bind&&indexS.oauth({
						openId: wechat.openid,
						headPic: wechat.head,
						nick: wechat.nick,
						type: "wechat",
						unionid:wechat.unionid
					}, function(res) {
						// if (wechat.openid==res.openId) {
						// 	storage.clear()
							// storage.toPage("login")
						
						if (res) {
							storage.clear();
							if (res.data) {
								storage.set("bind", res.data.part);
							} else if (res.token) {
								var user = {
											"aliasname":res.aliasname
										};
								storage.set("token", res.token);
								storage.set("uid", res.uid);
								//登入成功测标志
								sessionStorage.setItem('online',1);
							};
						};
					});
				}
			}, function() {
					//storage.toPage(page)
			})
		}
	})()
	 $scope.myInterval = 1000;
	 $scope.page={
	 	search:"",
	 	msg:false,
	 	msgBtn:function () {
	 		$scope.toPage("cart");
	 	}
	 };

    var token = storage.get("token");
    var tt = [];
    var oDateNow = new Date();
    
    $scope.filter = true;
    

	indexS.activity({
		token:token
	},function(res){
		$scope.activity = res.activity;
	    for (var i=0;i<res.activity.length;i++) {	    	
	    	if (res.activity[i].goods.length > 2) {
	    		res.activity[i].goods.length = 2;
	    	}
	    	if(res.activity[i].type == 5)
	    		continue;
	    	if(res.activity[i].type == 3 && res.activity[i].timeType == 0){
	    		var oDateNow = new Date();
	    		$interval(upDate2, 1000, (res.activity[i].endTime*1000 - oDateNow.getTime())/1000, null,res.activity[i],i);	
	    	}
	    	for(var j = 0;j<res.activity[i].goods.length;j++){
	    		var oDateNow = new Date();
	    		$interval(upDate, 1000, (res.activity[i].goods[j].endTime*1000 - oDateNow.getTime())/1000, null,res.activity[i].goods[j],i,j);
	    	}
    		
	    }

	})

	function upDate2(v,i) {
	    var oDateNow = new Date();
		var remain = (v.endTime*1000 - oDateNow.getTime())/1000;
		var iDay = parseInt(remain/86400);
			remain%=86400;
		var iHours = parseInt(remain/3600);
			remain%=3600;
		var iMinuter = parseInt(remain/60);
			remain%=60
		var iSeconds = parseInt(remain);

		function setDigit(mun, n) {
			var str = ''+mun;
			if (str.length<n) {
				str = "0"+str;
			}
			return str;
		}
		
       var h = setDigit(iHours,2);
       var m = setDigit(iMinuter,2);
       var s = setDigit(iSeconds,2);
		$scope.activity[i].iHours = h;
		$scope.activity[i].iMinuter = m;
		$scope.activity[i].iSeconds = s;
		$scope.$applyAsync();
	}

	function upDate(v,i,j) {
	    var oDateNow = new Date();
		var remain = (v.endTime*1000 - oDateNow.getTime())/1000;
		// console.log(remain%86400)
            if (remain%86400 <= 0) {
            	$scope.activity[i].goods[j].filter = false;
            	$scope.activity[i].goods[j].option = true;
            }else{
            	$scope.activity[i].goods[j].filter = true;
            	$scope.activity[i].goods[j].option = false;
            }
            // console.log(oDateNow.getTime())
		var iDay = parseInt(remain/86400);
			remain%=86400;
		var iHours = parseInt(remain/3600);
			remain%=3600;
		var iMinuter = parseInt(remain/60);
			remain%=60
		var iSeconds = parseInt(remain);

		function setDigit(mun, n) {
			var str = ''+mun;
			if (str.length<n) {
				str = "0"+str;
			}
			return str;
		}
		
       var h = setDigit(iHours,2);
       var m = setDigit(iMinuter,2);
       var s = setDigit(iSeconds,2);
		$scope.activity[i].goods[j].iHours = h;
		$scope.activity[i].goods[j].iMinuter = m;
		$scope.activity[i].goods[j].iSeconds = s;
		$scope.$applyAsync();
	} 
	
    document.onmousemove = function (){
    	var ar = document.documentElement.offsetHeight || document.body.offsetHeight;
    	var br = document.documentElement.scrollTop || document.body.scrollTop;
    	var setColor = document.getElementById("setColor");
    	var tr = br/ar;

    	setColor.style.opacity = tr;
    }
    
	
	 // var slides = $scope.slides = [
	 // 	{
	 // 		photo:"/images/tmp/slides.jpg",
	 // 		href:"/html/tmp/action.html"
	 // 	}
	 // ];
	 var brands = $scope.brands = [];
	 $scope.categorys=[];
	 var explosions = $scope.explosions = [];
	 var recommends = $scope.recommends=[];
	 var activity = $scope.activity=[];

		indexS.wxsdk({ //从后台获取注入信息 并提供跳转参数 
			url: window.location.href
		}, function(res) {
			$scope.tt = JSON.stringify(res);
			if (res && res.resCode == "SUCCESS") {
				ngWechat && ngWechat.init({//从后台的信息初始化微信端
					"appId": res.jssdk.appId,
					"timestamp": res.jssdk.timestamp,
					"nonceStr": res.jssdk.nonceStr,
					"signature": res.jssdk.signature
				},function(e){
//					ngMessage.loading(JSON.stringify(e));
				})
			};
		});

		/*
		indexS.getBanner(function(res) {
			var tmp = [];
			for (var i = 0; i < res.length; i++) {
				if (res[i].photo) {
					tmp.push(res[i])
				};
			};
			for (var i = 0; i < tmp.length; i++) {
				tmp[i].photo = (tmp[i].photo).replace(/\s+/img, '');
				//拼接href
				if(tmp[i].url){
					tmp[i].href = tmp[i].url;		
				}else if(tmp[i].linkPoint == 1){ //linkId 商品id
					tmp[i].href = '/html/commodity/detail.html?gid='+tmp[i].linkId;
				}else if(tmp[i].linkPoint == 2){ //linkId 商品分类
					tmp[i].href = '/html/list.html?cid='+tmp[i].linkId;
				}else if(tmp[i].linkPoint == 3){ //linkId 商品类型
					//tmp[i].href = '/html/commodity/detail.html?gid='+tmp[i].linkId;
					tmp[i].href = "#"			
				}else{
					tmp[i].href = "#"
				}
				//console.log(tmp[i].href);
			};
			$scope.slides = tmp;
		});*/
		indexS.getBrand(function (res) {
			var index=-1;
			for (var i = 0; i < res.length; i++) {
				if (i%2==0) {
					index++;
					brands[index]=[];					
				};

				brands[index].push(res[i])
			};
		});
		// var staticClassify = {
		// 	"featName": "充值有礼",
		// 	"photo": "../../images/chongz.png",
		// 	"url": "#",
		// 	"click": function() {

		// 		if (!token) {
		// 			ngMessage.show("请先登录", function() {
		// 			    $scope.toPage("login")
		// 			});
		// 		} else {
		// 			$scope.toPage("balance");
		// 		}
		// 	}
		// };
		// alert(111)
		$scope.categorysClick = function(v){
			if(v.feat == 4) {
				if (!token) {
					ngMessage.showTip("请先登录", 2000, function() {
					    $scope.toPage("login");
					});
				} else {
					$scope.toPage("balance");
				}
			}
			if (v.click) {
				v.click()
			}else{
				if(v.skipUrl) {
					storage.toPage(v.skipUrl);
				}else if(v.url){
					storage.toPage(host+v.url);//host @ interface.js
				}
			}
		};
		indexS.getFeats(function (res) {
			for(i in res){
				res[i].url="/html/list.html?fid="+res[i].feat+"&title="+res[i].featName
			}
			$scope.categorys = res;
		});
		indexS.getExplosion(function (res) {
			for(i in res){
				if(res[i].urlType==1){
				 if(res[i].linkPoint==1){  //商品
					 res[i].url = "/html/commodity/detail.html?gid="+res[i].linkId;
				 }
				 if(res[i].linkPoint==2){  //分类
					 res[i].url = "/html/classification.html?cid="+res[i].linkId;
				 }
				}
				if(!res[i].url){
					res[i].url="#";
				}
			}
			$scope.explosions = res;
			$scope.$applyAsync();
		});

		// indexS.getSlides(function (res) {
		// 	// $scope.slides = slides = res;
		// 	// $scope.$applyAsync();
		// });		

		indexS.getRecommend(function(res) {
			var index = -1;
			$scope.recommends = recommends = res;
			$scope.$applyAsync();
		});
		
		$scope.toPage = function(page) {
			storage.toPage(page)
		}
		$scope.onEnter = function () {
			if ($scope.page.search) {
				storage.set("keyword",$scope.page.search);
				$scope.toPage("list")
			};
		}

	
		$scope.ByLngLat = {
			    "provice": {
		            "id": "440",
		            "name": "广东省"
		        },
		        "city": {
		            "id": "440300000000",
		            "name": "深圳"
		        }
		};
		$scope.LngLat = {
			    "provice": {
		            "id": "440",
		            "name": "广东省"
		        },
		        "city": {
		            "id": "440300000000",
		            "name": "深圳"
		        }
		};
		// $scope.LngLat = [];
		var chooseLocation = storage.get('chooseLocation');
		if(chooseLocation){
			    var	str = chooseLocation.city_name;
		        var newstr=str.substring(0,str.length-1);
				$scope.ByLngLat = {
				    "provice": {
			            "id": chooseLocation.provice_id
			        },
			        "city": {
			            "id": chooseLocation.city_id,
			            "name": newstr
			        }
				};
				$scope.LngLat = {
				    "provice": {
			            "id": chooseLocation.provice_id
			        },
			        "city": {
			            "id": chooseLocation.city_id,
			            "name": newstr
			        }
				};	
			
		}else{
			if(storage.get('ByLngLat')){
				// alert(JSON.stringify(storage.get('ByLngLat')))
				$scope.ByLngLat = storage.get('ByLngLat');
			}else{
				tool.getLocation(function(position){
			        //经度
			        $scope.longitude = position.coords.longitude;
			        //纬度
			        $scope.latitude = position.coords.latitude;
			        indexS.getLocation({
				    	"lng":$scope.longitude,
				    	"lat":$scope.latitude 
				    },function(res){
				    	var data = res.data;
				    	data.city.name = data.city.name.substring(0,data.city.name.length-1); 
				    	$scope.ByLngLat = data;
				    	storage.set('ByLngLat',data);
				    })
				},function(error){
			        switch(error.code){
			             case 1:
				            ngMessage.showTip("请打开GPS定位",1000);
			             break;
			             case 2:
			            	 ngMessage.showTip("暂时获取不到位置信息",1000);
			             break;
			         }
			    });
		   }	
		}
		$scope.area = {
			"puy":''
		};
		indexS.area({
				"pid":$scope.ByLngLat.provice.id,
				"cid":$scope.ByLngLat.city.id
			},function(res){
				$scope.area.puy = res;
		})

        $scope.toTop = function(name){
        	$scope.ByLngLat.city.name = name;
        	$(".linck_up").hide();
        }
        $scope.minu = function(){
        	if(chooseLocation) {
        		$scope.ByLngLat.city.name = newstr;
        		$scope.LngLat.city.name = newstr;
        	    $(".linck_up").hide();
        	}else{
        		$scope.ByLngLat.city.name = "深圳";
        		$(".linck_up").hide();
        	}
        }


		$(function(){
			$(".linck_up").hide();
			$(".logo-box").on("click",function(){
				$(".linck_up").toggle();
				return false;
			})
			$(document.body).on("click",function(){
				$(".linck_up").hide();
			})
		})




})
.controller("classificationC",function ($scope, $document,indexS,storage,ngMessage,ngWechat) {

	function isInWeiXin() {
		var ua = window.navigator.userAgent.toLowerCase();
		if (ua.match(/MicroMessenger/i) == 'micromessenger') {
			return true;
		} else {
			return false;
		}
	}
	function id (idField) {
		if (!idField) { return false};
		var str = idField||"";
        var url=location.href;
        var rs = new RegExp("(^|)"+str+"=([^\&]*)(\&|$)","gi").exec(url), tmp;
        if(tmp=rs){
            return tmp[2];
        } 
        return "";            
    }
	 storage.init();

	 var token = storage.get("token");
	 var bind = storage.get("bind");
	 // var catId = storage.get("catId");
	 var catId,index = 2,stopEnd = false;
	 $scope.myInterval = 1000;
	 $scope.list=[];
	 $scope.categorys=[];
	 $scope.goods_lis = [];

	 // $scope.isLastPage = true;
	 var getList = storage.get("getList") || [];
	 var tmp = [];
     for (var i = 0; i < getList.length; i++) {
		if (typeof(getList[i]) == "object") {
			tmp.push(getList[i]);
		};
	 };
		 //后期开放

		indexS.getCategory(function (res) {
			var cid = id('cid') || 0;
			$scope.categorys = res;
			if (res.length) {
				var k = 0;
				for(i in res){
					if(res[i].catId==cid){
						k=i;
						break;
					}
					for(j in res[i].child){
						if(res[i].child[j].catId==cid){
							k=i;
							break;
						}
					}
				}
				$scope.switch($scope.categorys[k])
			};
		});

		$scope.switch = function(v){
			index = 2;
			stopEnd = false;
			catId = v.catId;
			if (v) {
				for (var i = 0; i < $scope.categorys.length; i++) {
					$scope.categorys[i].active = false;
				};
				v.active = true;
				$scope.list = v.child;
				$scope.goods_lis = v.goods_list;
			};
			$scope.isLastPage = true;
		}

		$scope.toPage = function(page) {
			storage.toPage(page)
		}
		$scope.onEnter = function () {
			if ($scope.page.search) {
				storage.set("keyword",$scope.page.search);
				$scope.toPage("list")
			};
		}
		$scope.load = function() {
			if(stopEnd){
				return;
			}
			indexS.getList({
				cat_id:catId,
				count:10,
				currentPage:index
			},function(res,lastPage) {
				index++;
				if(lastPage == 0){
					stopEnd = 1;
					$scope.isLastPage = false;
				}else{
					$scope.isLastPage = true;
				}
				var tmp = angular.copy($scope.goods_lis.data);
				$scope.goods_lis.data = tmp.concat(res);
			});
		}
	$(function (){
		$(document).ready(function(){
			var iHeight = ($(window).height()-50)+"px";
			$(".bwrap").css("height",iHeight)
		})
		// alert(iHeight)
		// $(".bwrap").css("height","iHeight")
	})	


}).controller("activitywwC",function ($rootScope,$element,$scope,$interval,$timeout,activitywwS,ngMessage,storage,$element,ngWechat) {
		storage.init();
		function isInWeiXin() {
			var ua = window.navigator.userAgent.toLowerCase();
			if (ua.match(/MicroMessenger/i) == 'micromessenger') {
				return true;
			} else {
				return false;
			}
		}
		//获取用户微信信息并缓存start
	(function(){
		var authKey = storage.queryField("info");
		if(isInWeiXin() && !sessionStorage.getItem('online')){
			authKey && activitywwS.mauth({ // 根据key从后台获取用户缓存
				key: authKey
			}, function(res) {		
				storage.set("wx", res.info);
				//获取用户信息并缓存end
				 storage.init();
				 var token = storage.get("token");
				 var wechat = storage.get("wx");
				 var bind = storage.get("bind");	 	
				if (!wechat) {
					storage.toPage("wxAuth");
				} else {
					!bind&&activitywwS.oauth({
						openId: wechat.openid,
						headPic: wechat.head,
						nick: wechat.nick,
						type: "wechat",
						unionid:wechat.unionid
					}, function(res) {
						// if (wechat.openid==res.openId) {
						// 	storage.clear()
							// storage.toPage("login")
						
						if (res) {
							storage.clear();
							if (res.data) {
								storage.set("bind", res.data.part);
							} else if (res.token) {
								var user = {
											"aliasname":res.aliasname
										};
								storage.set("token", res.token);
								storage.set("uid", res.uid);
								//登入成功测标志
								sessionStorage.setItem('online',1);
							};
						};
					});
				}
			}, function() {
					//storage.toPage(page)
			})
		}
	})();
 	  var token = storage.get("token");
 	  var isWX = storage.isWXBrowen();
 	  //获取用户微信信息并缓存start
 	 
	$rootScope.shareToken = token;//分享出去的code
	//去看自己的加蜜
	$scope.toAddmi = function(){
			activitywwS.activityHelpCode({token:token},function(res){
				location.href = "addmi.html?isMe=1&code="+res.code;
			});
	}
	$scope.toNoticle = function(e){
		e.preventDefault();
		storage.toPage("wengNotice");
	}
	//微信分享接口
	 activitywwS.wxsdk({ //从后台获取注入信息 并提供跳转参数 
			url: window.location.href
		}, function(res) {
			$scope.tt = JSON.stringify(res);
			if (res && res.resCode == "SUCCESS") {
				var data = JSON.stringify(res.jssdk);				
				ngWechat && ngWechat.init({//从后台的信息初始化微信端
					"debug":false,
					"appId": res.jssdk.appId,
					"timestamp": res.jssdk.timestamp,
					"nonceStr": res.jssdk.nonceStr,
					"signature": res.jssdk.signature
				},function(e){
//					ngMessage.loading(JSON.stringify(e));
				});
			};
		});
		//增加个判断是否是微信且关注的公众号
	  //activity status/timer
	   
	   //抽奖
	   $rootScope.timeCode = true;
	   $rootScope.awardActivity = function(){
		   	if($rootScope.clickTimes>=100){
		   		$rootScope.clickTimes=100;
		   	};
	   		activitywwS.activityAward({
					token:token,
					clickTimes:$rootScope.clickTimes
				},function(res){
//					if(res.resCode == "SUCCESS"){
					$rootScope.clickTimes=0;
					$rootScope.activityParam = {
							priceNumber:res.priceNumber,
							priceTotal:res.priceTotal,
							isAwardStart:res.isAwardStart,
							isAllowUSer:res.isAllowUser,
							gameNumber:res.gameNumber
						}
					$rootScope.activityParam.gameNumber = res.gameNumber;
					
					if(res.priceNumber != -1){
						$rootScope.param.money = res.priceNumber;
						$rootScope.showDialog = true;//控制红包的弹出框
						$rootScope.activityShow = false;//控制活动的弹出框
						$rootScope.timeCode = false;//控制显示认筹
						
						//$('#getMoney').html(res.priceNumber)
					}else{
									$rootScope.showDialog = false;//控制红包的弹出框
									$rootScope.activityShow = true;//控制活动的弹出框
									$rootScope.activityStatus ="您与红包擦肩而过";
					}
//					if(res.gameNumber == 0){
//						$rootScope.money = res.priceTotal;
//					}
									
							$scope.time = $rootScope.currentSecond;
							$('.flower').removeClass('disabled');		
//			}
				$scope.$applyAsync();
				},function(){
					$('.flower').removeClass('disabled');
				})
	   };
	   //倒计时时间到
	   var timerActivity = null;
	   $rootScope.clickNumber = 0;
	   $rootScope.clickTimes = 0;
	   $rootScope.currentSecond = 10;
	   $rootScope.timerActive = function(){
		  	timerActivity = $interval(function(){
			    	--$scope.time;
			    	
			    	if($scope.time == 0){
			    			//请求抽奖到的接口数据
			    			$rootScope.timeCode = true;
			    			$interval.cancel(timerActivity);
							$rootScope.clickNumber = 0;//次数
			    			$rootScope.awardActivity();
			    			$scope.time = $rootScope.currentSecond;
			    			$scope.$applyAsync();	
			    	}
			  	},1000);
	  };
	  
	  //请求轮数/抽奖资格等
	  $scope.toPage = function(page) {
				storage.toPage(page)
			}
	  $rootScope.activityParam ={};
	  $rootScope.activityParam.gameNumber = 0;
	   if(token){//isLogin
	   		$scope.key = token;
				activitywwS.activity({
					token:token
				},function(res){
						$rootScope.activityParam = {
							isAwardStart:res.isAwardStart,
							isAllowUSer:res.isAllowUser,
							gameNumber:res.gameNumber,
							gameTime:res.gameTime
						};
						storage.set("wwfkid",res.fkId);
						$rootScope.fkId = res.fkId;
						$scope.time = $rootScope.activityParam.gameTime;
						$rootScope.currentSecond = res.gameTime;
						$rootScope.getData = ($rootScope.activityParam.isAwardStart==1) && ($rootScope.activityParam.isAllowUSer== 1);
						if($rootScope.activityParam.isAwardStart == -1){
							$rootScope.activityShow = true;
							$rootScope.activityStatus ="活动已经结束";
						}else if($rootScope.activityParam.isAwardStart == 0){
							$rootScope.activityShow = true;
							$rootScope.activityStatus ="活动3月25日开启哦！";
						}else if($rootScope.activityParam.isAwardStart == 1){
							//activitying
							$rootScope.activityShow = false;
							// if($rootScope.activityParam.isAllowUSer == 0){
							// 		//没有抽奖资格
							// 	}else if($rootScope.activityParam.isAllowUSer == 1){
							// 		//可以抽奖
							// 	}
						}
						$scope.$applyAsync();	
				});
			}else{
							//ngMessage.showTip("请先登录", 2000, function() {
								$scope.time = 10;
					    	$scope.toPage("login");
							//});
			}
			
			//有资格抽奖
			//点击
			$('.flower').on('touchend',function(e){
					e.preventDefault();
					++$rootScope.clickNumber;
					var thisX = $('#mfMove').offset().left;
					var thisY = $('#mfMove').offset().top;
					var windowWidth = $(window).width();
					var x = e.changedTouches[0].clientX-60;
					var y = e.changedTouches[0].clientY-62;
//					var x = e.pageX-60;
//					var y = e.pageY-62;
					if(x<0){x=0;}
					if(x>windowWidth){y=windowWidth;}
					if(x>=thisX){
						if($('#mfMove').css('transform') =="rotateY(180deg)"){
							$('#mfMove').css({"transform":'rotateY(360deg)'});
						};
						$('#mfMove').animate({"left":x+'px',"top":y+'px',"z-index":100},300);
					}else{
						$('#mfMove').css({"transform":'rotateY(180deg)'});
						$('#mfMove').animate({"left":x+'px',"top":y+'px',"z-index":100},300);
					};
					if($rootScope.activityParam.isAwardStart == -1){
							$rootScope.activityShow = true;
							$rootScope.activityStatus ="活动已经结束";
							$scope.$applyAsync();	
							return;
					}else if($rootScope.activityParam.isAwardStart == 0){
						$rootScope.activityShow = true;
						$rootScope.activityStatus ="活动3月25日开启哦！";
						$scope.$applyAsync();	
						return;
					}
					if($rootScope.activityParam.gameNumber==0 || $rootScope.activityParam.isAllowUSer==0){
						$rootScope.goTorc = true;
						$rootScope.clickNumber = 0;
						$scope.$applyAsync();	
						return;
					}
					if(!$rootScope.getData){$rootScope.clickNumber = 0;return;}
					
					
					$('.timerBanner').css("height","90px");
					$('.bannerWrap').animate({"top":"-90px"},600);
					if(!$(this).hasClass('disabled')){$rootScope.timerActive();};
	  				$(this).addClass('disabled');
					if($rootScope.clickNumber==10){
						$rootScope.clickTimes = $rootScope.clickTimes+$rootScope.clickNumber;
						$rootScope.clickNumber = 0;//次数
						$('.jinbi').addClass('jinbiAnimate');
						$timeout(function(){$('.jinbi').removeClass('jinbiAnimate')},800);
						$scope.$applyAsync();	
					}
			});
	  
	  
   
	  
	  
}).controller("authC", function ($scope, storage, ngMessage, indexS) {
	storage.init();
  var wechat = storage.get("wx");
	var authKey = storage.queryField("info");
	var page = storage.getOnce("wxReferer") || "home";
	if (!wechat && !authKey) {
		var u = interfaceURL.wechat.pub + "?jump_url=" + window.location.href;
		storage.toPage(u);
		return false;
	};
	if (wechat){ //有微信用户信息再次存储并跳转
		storage.set("wx",wechat);
		setTimeout(function() {
			storage.toPage(page)
		}, 20)
		return false;
	};
	authKey && indexS.mauth({ // 根据key从后台获取用户缓存
		key: authKey
	}, function(res) {		
		storage.set("wx", res.info);
		setTimeout(function() {
			storage.toPage(page)
		}, 200)
	}, function() {
		alert("验证失败")
			//storage.toPage(page)
	})
})


.controller("downloadC", function ($scope, storage, ngMessage, ngMessage, tool) {
	storage.init();
	var url;
	$(function () {
        $("#btn_dowload").on("click", function (e) {
			if (storage.isInWeiXin()) {
				window.location.href = '#';
				$("#mytop").show();
				return;
			}; 
            if(tool.isiPhone) {
                url = "https://itunes.apple.com/cn/app/bi-hao-huo-shang-cheng/id1044911371?mt=8"
            }else{
            	url = "http://www.bihaohuo.cn/download/android/bihaohuo.apk";
            }
            window.location.href = url;
        });
        $("#output").on("click", function () {
        	if (wx) {
        		$("#mytop").hide();
        		$("#myshow").show();
        	}
        })
    });
    
})