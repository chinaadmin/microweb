angular.module('app.dataStore', [])
.factory("ngMessage",function () {
	var event={};
	var data={
		"cancle":"取消",
		"sure":"确定",
		"title":"尚未登陆，请先登陆再进行操作！",
		"desc":""
	};
	var st=null;
	return{
		"on":function(type,fn,once) {
			if (fn) {
				if (!event[type]) {
					event[type] = []
				};
				if (!once) {
					event[type]=[fn];
				}else{
					event[type].push(fn);
				}				
			}else{
				event.length=0;
			}
		},
		"fire":function(type,args) {
			if (event[type]) {
				for (var i = 0; i < event[type].length; i++) {
					event[type][i].apply(event[type][i], args)
				};
			};
		},
		set:function (key,value) {
			data[key]=value;
		},
		get:function (key) {
			return data[key];
		},
		show:function (msg,success,fail) {
			if (st) {
				clearTimeout(st);
			};			
			this.fire("show",[msg,false]);
			if (success&&typeof(success)=="function") {
				this.on("sure",success,false);
			};
			if (fail&&typeof(fail)=="function") {
				this.on("cancle",fail,false);
			};			
		},
		showTip:function (msg,time,callBack) {
			if (st) {
				clearTimeout(st);
			};
			this.fire("show",[msg,true]);
			var self = this,
				time = time||1000;
			st = setTimeout(function () {
				self.fire("hide");
				st = null;
				if (callBack&&typeof(callBack)=="function") {
					callBack();	
				}
			}, time)
		},
		loading:function(msg){
			if (st) {
				clearTimeout(st);
			};
			this.fire("show",[msg,true]);
		},
		hide:function () {
			this.fire("hide");
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
.directive("islook",function(ngMessage){
	var HTML = ""
	+ "<div class=\"dialogBox\" ng-show=\"isLookShow\">"
	+ "<img src=\"../../images/activity/tipsLook.png\" width=\"100%\" height=\"100%\" alt=\"关注我们\" />"
	+ "</div>";
	return{
		restrict: 'E',
		template: HTML,
		controller:function ($rootScope,$scope,$element,$timeout) {
			$rootScope.isLookShow = false;
			//此地方可能需要控制大图(关注微信号图)加载完再运行
//			$timeout(function(){$rootScope.isLookShow = false},3000);
			$('.dialogBox').on('click',function(){
				$rootScope.isLookShow = !$rootScope.isLookShow;
				$scope.$applyAsync();	
			})
		}
	}
})
.directive("message",function (ngMessage) {
	var MSGHTML=""
	  +"<div class=\"msgBox\" ng-show=\"_message.show\" style='display:none'>"
	  +"  <div class=\"wrap\">"
	  +"    <h3><div class=\"_messageTitle\"></div></h3>"
	  +"    <div class=\"btnBox\" ng-hide=\"_message.tip\">"
	  +"      <a class=\"btn btnCancle\" ng-click=\"_message.cancle()\" ng-bind=\"_message.cancleText\">取消</a>"
	  +"      <a class=\"btn btnSure\" ng-click=\"_message.sure()\" ng-bind=\"_message.sureText\">确定</a>"
	  +"    </div>"
	  +"  </div>"
	  +"</div>";
	return {
		restrict: 'E',
		template: MSGHTML,
		controller:function ($scope,$element) {
			var init = false;
			$scope._message = {
				show:false,
				tip:false,
				title:ngMessage.get("title"),
				desc:ngMessage.get("desc"),				
				cancleText:ngMessage.get("cancle"),
				sureText:ngMessage.get("sure"),
				switcher:function () {
					$scope._message.show = !$scope._message.show;
				},
				cancle:function () {
					$scope._message.show = !$scope._message.show;
					ngMessage.fire("cancle");
				},
				sure:function () {
					$scope._message.show = !$scope._message.show;
					ngMessage.fire("sure");					
				},
			};
			ngMessage.on("show",function (msg,tip) {
				if (!init) {
					$element[0].setAttribute("style","");
					$element[0].firstChild.setAttribute("style","");
					init = true;
				};				
				$scope._message.show = true;
				if (msg) {
					$element[0].querySelector("._messageTitle").innerHTML = msg;
					$scope._message.title = msg
				};
				$scope._message.tip = tip;
				$scope.$applyAsync();
			},false);
			ngMessage.on("hide", function() {
				$scope._message.show = false;
				$scope.$applyAsync();						
			}, false)
		}
	};
})
.directive("addmitip",function (ngMessage,$rootScope) {
	var TEMP=""
	  	+"<div class=\"shadow\" id=\"shadow\" ng-show=\"addmiShow\">"
	  	+"<div class=\"window window-in\" id=\"windowS\">"
	  	+"<h3 class=\"text-center\"><span class=\"tip\"></span><span class=\"closed\" id=\"close\" ng-click=\"tips.close()\"></span></h3>"
	  	+"<p class=\"text-center\">{{addMiStatus}}</p>"
	  	+"</div>"
      	+"</div>"
      	+"<div class=\"miDialog\" ng-show=\"tipsAwardS\"></div><div class=\"tipsWord\" ng-show=\"tipsAwardS\">"
		+"<div class=\"tipsInfo\">"
		+"<div class=\"tipInfomation\">"
		+"<span class=\"closed\" ng-click=\"tips.closeTips()\"></span>"
		+"<img src=\"../../images/activity/zj.png\" width=\"170\" class=\"picTip\" alt=\"恭喜您！\" />"
		+"</div>"
		+"</div>"
		+"<div class=\"tipsMoney\">"
		+"<div class=\"tipsMoneyAlign\">"
		+"<p class=\"getFriendThink\">您已成功帮好友助力<span class=\"helpCash\">{{friendMoney}}</span>元<br />同时您获得以下现金券</p>"
		+"<div class=\"cashCard\"><span id=\"cashNum\">{{myselfGift}}</span>&yen;</div>"
		+"<p>必好货商城满27元使用</p>"
		+"</div>"
		+"</div>"
		+"</div>";
      return {
		restrict: 'E',
		template: TEMP,
		controller:function ($scope,$element,$rootScope,addmiS,storage) {
			var init = false;
			storage.init();
			$rootScope.tipsAwardS = false;
			$rootScope.friendMoney = 0;
			$rootScope.myselfGift = 0;
 	  		var token = storage.get("token");
 	  		$rootScope.addmiShow = false;
 	  		$rootScope.addMiStatus="";
			$scope.tips = {
				closeTips:function () {
					$rootScope.tipsAwardS = !$rootScope.tipsAwardS;
				},
				cancle:function () {},
				close:function () {
					$rootScope.addmiShow = !$rootScope.addmiShow;
				}
			}
	  	}
	}
})
.directive("winning",function (ngMessage,$rootScope) {
	var TEMP=""
	  	+"<div class=\"getIsAwardDialog\" ng-show=\"goTorc\">"
		+"<div class=\"getIsAwardDialog_bg\">"
		+"<div class=\"getIsAwardDialog_close\"><div class=\"closeX right\" ng-click=\"tips.closeRC()\"></div><img src=\"../../images/activity/bird.png\" width=\"80\" class=\"bird\"/></div>"
		+"<div class=\"tips_word fontYH\">您没有抽奖资格，请参与认筹蜂蜜568元/3年抽奖吧</div>"
		+"<a href=\"road.html?fkId={{fkId}}\" class=\"tips_btn\">我要认筹</a>"
		+"</div>"
	  +"</div>"	
	  +"<div class=\"shadow\" id=\"shadow\" ng-show=\"activityShow\">"
	  +"<div class=\"window window-in\" id=\"windowS\">"
	  +"<h3 class=\"text-center\"><span class=\"tip\"></span><span class=\"closed\" id=\"close\" ng-click=\"tips.close()\"></span></h3>"
	  +"<p class=\"text-center\">{{activityStatus}}</p>"
	  +"</div>"
      +"</div>"
	  +"<div class=\"dialogBox\" ng-show=\"showDialog\">"
	  +"<div class=\"shareArrow\"><img src=\"../../images/activity/arrow.png\" class=\"arrow\"></div>"
	  +"<div class=\"shareTips\"><p>点这里分享给好友助力加蜜，可获得额外的红包哦</p></div>"
	  +"<div class=\"money-hb\">"
	  +"<div class=\"hb-img\">"
	  +"<p class=\"endActivity\">本轮活动结束</p>"
	  +"<p id=\"activityGetStatus\">您累计获得红包<span id=\"getMoney\" ng-model=\"param.money\">{{param.money}}</span>元</p>"
	  +"</div>"
	  +"<a href=\"#\" id=\"sure-get-hb\" ng-click=\"tips.cancle()\" ></a>"
	  +"</div>"
	  +"</div>";
	return {
		restrict: 'E',
		template: TEMP,
		controller:function ($scope,$element,$timeout,$rootScope,activitywwS,storage) {
			var init = false;
			storage.init();
			$rootScope.activityShow = false;
			$rootScope.showDialog = false;
			$rootScope.goTorc = false;
 	  		var token = storage.get("token");
			$rootScope.param ={};
			$rootScope.param.money = 0;//controller money
			$scope.tips = {
				closeRC:function(){
					$rootScope.goTorc = !$rootScope.goTorc;
				},
				cancle:function () {
					$rootScope.showDialog = !$rootScope.showDialog;
						$('.jinbi').removeClass('jinbiAnimate');
						$('.bannerWrap').animate({"top":"0"},600, 'ease-out',function(){});
						$('.timerBanner').css("height",'90px');
					
				},
				close:function(){
					$rootScope.activityShow = !$rootScope.activityShow;
				}
			};
			
		}
	};
})
.directive('packerweng',function(ngMessage,$rootScope){
	var TEMP=""
			+"<div class=\"getIsAwardDialog\" ng-show=\"goRC\">"
				+"<div class=\"getIsAwardDialog_bg\">"
				+"<div class=\"getIsAwardDialog_close\"><div class=\"closeX right\" ng-click=\"tips.closesRC()\"></div><img src=\"../../images/activity/bird.png\" width=\"80\" class=\"bird\"/></div>"
				+"<div class=\"tips_word fontYH\">您没有抽奖资格，请参与认筹蜂蜜568元/3年抽奖吧</div>"
				+"<a href=\"../activitys/road.html\" class=\"tips_btn\">我要认筹</a>"
				+"</div>"
			  +"</div>"	
			+"<div class=\"turntable\">"
			+"<div class=\"rotateBg\"><img src=\"../../images/wecharActivily/rotary_table@2x.png\" /></div>"
			+"<div class=\"rotateStaic\" id=\"click\" ><input type='button' class='click-btn' ng-disabled=\"inputDisabled\" ng-click=\"tips.tenYearAward();\"></input></div>"
			+"</div>"
			+"<div class=\"shadow\" ng-show='showPecker'>"
			+"<div id=\"packer\"><div class='top'><span class=\"packer-money\">&yen;<span id=\"moneyGet\">{{Param.cash | number:0}}</span></span><span class='text'>现金红包</span></div><div class='tiket-tips' ng-show='tiketShow'><img src=\"../../images/wecharActivily/money_100@2x.png\" width=\"170px\" class=\"\"/></div>"
			+"<span class=\"btn-sure\" ng-click='tips.closePecker();'></span>"
			+"</div>"
			+"</div>"
			+"<div class=\"showTips\" ng-show='showActivity'>"
			+"<div class=\"window window-in\">"
			+"<h3 class=\"text-center\"><span class=\"tip\"></span><span class=\"closed\" id=\"close\" ng-click=\"tips.close()\"></span></h3>"
			+"<p class=\"text-center\" ng-bind=\"activityStatus\"></p>"
			+"</div>"
			+"</div>";
			
	return{
		restrice:'E',
		template:TEMP,
		controller:function($scope,$rootScope,$element,$timeout,anniversaryS,storage){
			var init = false;
			storage.init();
			var token = storage.get("token");
			$rootScope.goRC = false;
			$rootScope.getIsAllowGift = false;
			anniversaryS.anniversary({
      	 	   token:token
	      	 },function(res){
	      	 		$scope.inputDisabled = true;
	      	 		 $rootScope.Param = {
						 	isStart:res.isStart,
						 	isAllow:res.isAllow,
						 	gameNumber:res.gameNumber
					  }
					  storage.set("wwfkid",res.fkId);
	      	 		 if($rootScope.Param.isStart==-1){
					 		$rootScope.showActivity =true;
						 	$rootScope.activityStatus = '活动已经结束';
						 	
					 }else if($rootScope.Param.isStart==0){
					 		$rootScope.showActivity =true;
					 		$rootScope.activityStatus = '活动2016.3.30 9:00 开启';
					 		
					 }else if($rootScope.Param.isStart==1){
					 		 $rootScope.showActivity = false;
					 	    if($rootScope.Param.isAllow==0){
								//$rootScope.goRC = true; 
					 	    }else if($rootScope.Param.isAllow==1){
					 	    	$rootScope.getIsAllowGift = true;
					 	    	$scope.inputDisabled = false;
					 	    }
					 }
					 $scope.$applyAsync();	
	      	 })
			
			$rootScope.tips = {
				closesRC:function(){
					$rootScope.goRC = !$rootScope.goRC;
					$scope.inputDisabled = false; 
				},
				tenYearAward:function(){
							$scope.inputDisabled = true;
							if($rootScope.Param.gameNumber<=0){	
			 	    			 $rootScope.showActivity =true;
			 	    			 $rootScope.activityStatus ="您没有抽奖资格";
			 	    			$rootScope.goRC = false; 
			 	    			$scope.inputDisabled = false;
			 	    			$('.rotateBg').removeClass('Action');
    							$scope.$applyAsync();				
    							return;
 	    					}
 	    					if(!$rootScope.getIsAllowGift){
   	    						 $rootScope.showActivity =true;
			 	    			 $rootScope.activityStatus ="您没有抽奖资格";
			 	    			$('.rotateBg').removeClass('Action');
    							$scope.$applyAsync();				
 	    						return;
 	    					}
							$('.rotateBg').removeClass('Action');
							if(token){
								anniversaryS.anniversaryGame({
									token:token
								},function(res){
									 $rootScope.Param = {
									 	isWin:res.isWin,
									 	cash:res.cash,  
									 	isStart:res.isStart,
									 	isAllow:res.isAllow,
									 	gameNumber:res.gameNumber,  
									 	tiket:res.tiket
									 }	
									 $rootScope.Param.gameNumber = res.gameNumber;
										if($rootScope.Param.isStart!=1 && isAllow != 1){
											$rootScope.getIsAllowGift = false;
										}
						 	    			$('.rotateBg').addClass('Action');
						 	    			if($rootScope.Param.isWin==1){
						 	    				$timeout(function(){
						 	    					$rootScope.showPecker=true;
						 	    					$('.rotateBg').removeClass('Action');
						 	    				},2000);
					 	    					if($rootScope.Param.tiket != 0){$rootScope.tiketShow=true;}
						 	    			}else{
						 	    				$timeout(function(){
						 	    					$rootScope.showActivity =true;
							 	    				$rootScope.activityStatus ="太可惜了竟然与红包擦肩而过";
							 	    				$('.rotateBg').removeClass('Action');
						 	    				},2000)
						 	    			}
		 	    							$scope.$applyAsync();						
						 	    		
								})
							}
						},
				close:function(){
					$rootScope.showActivity = !$rootScope.showActivity;
					$scope.inputDisabled = false;
				},
				closePecker:function(){
					$rootScope.showPecker = !$rootScope.showPecker;
					$scope.inputDisabled = false;
				}
			}
		}
	}
})
.directive("datachoose",function(ngMessage,$rootScope){
	 var TEMP=""
	 		 +"<div class='date-choose'><input type='text' name='USER_AGE' id='USER_AGE'  class='input'/><span class='yearT'>{{timeY+'年'}}</span><span class='monthT'>{{timeM}}<i class='mon'>月</i></span><i class='triangleDown'></i></div>";
	  return {
	  		restrict:'E',
	  		template:TEMP,
	  		controller:function($scope,depositS,storage,$element,$rootScope){
	  			storage.init();
				//日期选择d
				var currYear = (new Date()).getFullYear();	
				var opt={};
				opt.date = {preset : 'date'};
				opt.datetime = {preset : 'datetime'};
				opt.time = {preset : 'time'};
				opt.default = {
				theme: 'android-ics light', //皮肤样式
				display: 'modal', //显示方式 
				mode: 'scroller', //日期选择模式
				dateFormat: 'yyyy-mm',
				lang: 'zh',
				showNow: true,
				nowText: "今天",
				startYear: currYear - 50, //开始年份
				endYear: currYear + 10 //结束年份
				};
				$("#USER_AGE").mobiscroll($.extend(opt['date'], opt['default'])); 
				$scope.$applyAsync();	 	
	  		}
	  }
})
.directive("flower",function (ngMessage) {
	var TEMP=""
	  +"<div class=\"flower\"></div><div class=\"flowerBox\"><img src=\"../../images/activity/money@2x.png\" class=\"jinbi\" alt=\"金币\" />"
	  +""
	  +"</div>";
	return {
		restrict: 'E',
		template: TEMP,
		controller:function ($scope,activitywwS,storage,$element,$rootScope) {
			var init = false;
			storage.init();
			//get  activity  status/time/number
		}
	};
})
.directive('uploadnews',function(ngMessage,$rootScope){
	var TEMP=""
	         +"<div  class='content' ng-repeat='(key,value) in data' >"
			 +"<a class='list-group-item bdN borN ' ng-click='updateP(value.id,value.push_url)'>"
			 +"<p class='text-center' ng-bind='value.push_addtime'></p>"
			 +"<div class='media Stores' ng-class=\"{2:'read'}[value.state]\">"
			 +"<div class='media-top'>"
			 +"<img class='media-object' ng-src='{{value.img_url}}'/>"
		     +"</div>"
			 +"<div class='media-body pdl-0 pdt-15'>"
			 +"<h3 ng-bind='value.push_title' ng-class=\"{2:'read'}[value.state]\"></h3>"
		     +"<p ng-bind='value.push_brief' ng-class=\"{2:'read'}[value.state]\"></p>"
			 +"</div>"
			 +"<div class='media-footer pdt-15'>"
		     +"<p>阅读全文<span class='right'>></span></p>"
			 +"</div>"
			 +"</div>"
			 +"</a>"
			 +"</div>";
	return {
		  restrict:'E',
		  template:TEMP,
		  controller:function($scope,messagepushS,storage,$element,$rootScope){
		  	storage.init();
		  	$scope.Title= new Array();
		    $scope.Title[0] = '活动促销';
		    $scope.Title[1] = '新品上架';
		  	
		  	var token = storage.get('token');
		  	    
		  	var name_id = storage.getUrlParam('name_id');
		  	$scope.listStatus = false;
	        $scope.emptyStatus = false;
			
		  	var counter = 0;
		    // 每页展示4个
		    var num = 5;
		    var pageStart = 0,pageEnd = 10;
		    $scope.data = [];
		  	$('.myListBg').dropload({
		        scrollArea : window,
		        domDown : {
		            domClass   : 'dropload-down',
		            domRefresh : '<div class="dropload-refresh">↑上拉加载更多</div>',
		            domLoad    : '<div class="dropload-load"><span class="loading"></span>加载中...</div>',
		            domNoData  : '<div class="dropload-noData">已经没有数据</div>'
		        },
		       
		        loadDownFn : function(me){
		        	  if(name_id){
				    	  $scope.listStatus = true;
				    	    messagepushS.getMsg({
								    	"token":token,
								    	"name_id":name_id
								    },function(res){
								    	 counter++
								    	 $scope.name_id = name_id;
								    	 if(!res.data){
								    	 	 $scope.emptyStatus = true;
								    	 	 $('.dropload-down').html('')
								    	 	 return false;
								    	 }
								    	 maxLen = res.data.length;
								    	
								    	 pageEnd = num*counter;
								    	 pageStart = pageEnd-num;
								    	
								    	 if(pageEnd>maxLen){
								    		 pageEnd=maxLen
								    	}
								    	
								    	 for(var i = pageStart; i<pageEnd;i++){
								    	 	  $scope.data.push(res.data[i])
								    	 	  if((i + 1) >= res.data.length){
						                            // 无数据
						                            me.noData();
						                            break;
						                        }
								    	 }
								    	 setTimeout(function(){
						                        // 每次数据加载完，必须重置
						                        me.resetload();
						                 },1000);
					                 
								    })
				        }else{
				        	 $scope.emptyStatus = true
				        }
		        },
		        threshold : 50
   		 });
		  
		  $scope.updateP = function(data,data2){
		  	   messagepushS.updateM({
		  	   	 "token":token,
		  	   	 "id":data
		  	   },function(res){
		  	   	   window.location.href=data2
		  	   })
		  	   
		  	    
		  }
		}
	}
})


.directive('uploadsnew',function(ngMessage,$rootScope){
	var TEMP=""
	         +"<div  class='content' ng-repeat='(key,value) in data' >"
			 +"<a class='list-group-item bdN borN ' ng-click='updateP(value.id,value.push_url)'>"
			 +"<p class='text-center' ng-bind='value.push_addtime'></p>"
			 +"<div class='media Stores' ng-class=\"{2:'read'}[value.state]\">"
			 +"<div class='media-top'>"
		     +"</div>"
			 +"<div class='media-body pdl-0 pdt-15'>"
			 +"<h3 ng-bind='value.push_title' ng-class=\"{2:'read'}[value.state]\"></h3>"
		     +"<p ng-bind='value.push_brief' ng-class=\"{2:'read'}[value.state]\"></p>"
			 +"</div>"
			 +"<div class='media-footer pdt-15'>"
		     +"<p>阅读全文<span class='right'>></span></p>"
			 +"</div>"
			 +"</div>"
			 +"</a>"
			 +"</div>";
	return {
		  restrict:'E',
		  template:TEMP,
		  controller:function($scope,messagepushS,storage,$element,$rootScope){
		  	storage.init();
		  	$scope.Title= new Array();
		    $scope.Title[0] = '活动促销';
		    $scope.Title[1] = '新品上架';
		  	
		  	var token = storage.get('token');
		  	    
		  	var name_id = storage.getUrlParam('name_id');
		  	$scope.listStatus = false;
	        $scope.emptyStatus = false;
			
		  	var counter = 0;
		    // 每页展示4个
		    var num = 5;
		    var pageStart = 0,pageEnd = 10;
		    $scope.data = [];
		  	$('.myListBg').dropload({
		        scrollArea : window,
		        domDown : {
		            domClass   : 'dropload-down',
		            domRefresh : '<div class="dropload-refresh">↑上拉加载更多</div>',
		            domLoad    : '<div class="dropload-load"><span class="loading"></span>加载中...</div>',
		            domNoData  : '<div class="dropload-noData">已经没有数据</div>'
		        },
		       
		        loadDownFn : function(me){
		        	  if(name_id){
				    	  $scope.listStatus = true;
				    	    messagepushS.getMsg({
								    	"token":token,
								    	"name_id":name_id
								    },function(res){
								    	 counter++
								    	 $scope.name_id = name_id;
								    	 if(!res.data){
								    	 	 $scope.emptyStatus = true;
								    	 	 $('.dropload-down').html('')
								    	 	 return false;
								    	 }
								    	 maxLen = res.data.length;
								    	
								    	 pageEnd = num*counter;
								    	 pageStart = pageEnd-num;
								    	
								    	 if(pageEnd>maxLen){
								    		 pageEnd=maxLen
								    	}
								    	
								    	 for(var i = pageStart; i<pageEnd;i++){
								    	 	  $scope.data.push(res.data[i])
								    	 	  if((i + 1) >= res.data.length){
						                            // 无数据
						                            me.noData();
						                            break;
						                        }
								    	 }
								    	 setTimeout(function(){
						                        // 每次数据加载完，必须重置
						                        me.resetload();
						                 },1000);
					                 
								    })
				        }else{
				        	 $scope.emptyStatus = true
				        }
		        },
		        threshold : 50
   		 });
		  
		  $scope.updateP = function(data,data2){
		  	   messagepushS.updateM({
		  	   	 "token":token,
		  	   	 "id":data
		  	   },function(res){
		  	   	   window.location.href=data2
		  	   })
		  	   
		  	    
		  }
		}
	}
})

.directive("loading",function () {
	var MSGHTML="<div class=\"overlay\" ng-hide=\"_overlay.show\"><span class=\"loading\"><img src=\"/images/process.png\"/></span><i></i></div>"
	return {
		restrict: 'E',
		template: MSGHTML,
		controller:function ($scope) {
			$scope._overlay = {
				show:false
			};
			setTimeout(function(){
				$scope._overlay.show = true;
				$scope.$applyAsync();
			}, 720)
		}
	};
})

.directive('ngLoading', function() {
	var aChild = window.document.createElement("div")
		aChild.className = "msgBox ngLoading";
		aChild.innerHTML="<img src='../../images/loading.gif' alt='加载中...' /><span></span>";
	var showTime = 1000;
  return {
    link: function(scope, element, attrs) {  
    	element[0].setAttribute("style","");
    	element[0].appendChild(aChild);
    	var tmp = parseInt(attrs.ngLoading)
		if (tmp&&tmp>200) {
			showTime = tmp;
		};
    	setTimeout(function(){
    		element[0].removeChild(aChild)
    	}, showTime)
    }
  }
})

.directive('ngLayload', function() {
	var showTime = 360;
  return {
    link: function(scope, element, attrs) { 
    	element[0].style.display = "none" ;
		attrs.ngLayload && scope.$watch(attrs.ngLayload, function(newValue, oldValue, scope) {
			if (newValue) {

				setTimeout(function() {
					element[0].setAttribute("style", "");
				}, showTime)
			} else {
				element[0].style.display = "none";
			}
		});
    }
  }
})


.directive('ngClearstyle', function() {
  return {
    link: function(scope, element, attrs) {  
      setTimeout(function(){
         element[0].setAttribute("style","");
       }, 1000)     
    }
  }
})



.factory('storage', function(httpRequest,ngMessage){
	var data={};
	var cacheTime = 2*60*60*1000; //2h
	var initHash = false,init=false;
	var history=[];
	var srcText = "";
	var wxdata = "";
	return {
		init:function (reg) { //针对微信用户 获取到用户token uid  信息或者缓存绑定需要信息
			if (init) {
				return false
			};
			this._fromLocalStorage();//读出本地缓存	
			//this._fromHash();			
			init = true;
			var time = new Date().getTime();
			if (time>cacheTime+data.cacheTime) { //过了缓存期 重新存入本地
				data = {};
				this._toLocalStorage();
				return false
			};			
			var wechat = data.wx;//微信使用者信息	
			if (wechat&&!reg) { //存在微信信息并且不是注册
				var self = this;
				httpRequest.post(interfaceURL.wechat.oauth, { //向后台询问是否有本用户或者缓存用户信息
					openId: wechat.openid,
					headPic: wechat.head,
					nick: wechat.nick,
					type: "wechat"
				}).success(function(res) {
					if (res) {
						if (res.data) {
							self.set("bind", res.data.part);//用户未绑定 缓存绑定需要参数
						} else if (res.token) {//已绑定缓存登入信息
							var user = {
								"aliasname": res.aliasname
							}; 
							self.set("wx", wechat);
							self.set("user", user);
							self.set("token", res.token);
							self.set("uid", res.uid);
						};
					};
				})
			};
			return true;
		},
		canvasAddMi:function(x,y){
			var c=document.getElementById('canvas');
		  var ctx=canvas.getContext('2d');
		  //绘制起始点、控制点、终点 
		  ctx.clearRect(0,0,c.width,c.height); 
		  var img=document.getElementById("canvasImg");
		  var pat=ctx.createPattern(img,"no-repeat");
		  ctx.rect(0,0,153,173);
		  ctx.fillStyle=pat;
		  ctx.fill();
		  ctx.clearRect(0,20,x,y);
		},
		getUrlParam:function(name){
   			var ps = decodeURI(location.search);
			if (ps == '') return '';
			var params = ps.substring(1).split('&');
			var param = [];
			for (var i = 0; i < params.length; i++) {
				var temp = params[i].split('=');
				param[temp[0]] = temp[1];
			}
			return param[name];
  		},
		_fromHash:function () {
			if (initHash) {return initHash};
			var hs = window.location.hash.replace("#", '');
			if (!hs) {
				return false;
			};
				hs = decodeURI(hs);
				try{
					d = JSON.parse(hs);
					for (var i in d) {
						if (d.hasOwnProperty(i)) {
							data[i] = d[i]
						}
					}
				}catch(e){}	
			initHash = true;
			window.location.hash = "";			
		},
		isWXBrowen:function(){
			var ua = window.navigator.userAgent.toLowerCase();
			if (ua.match(/MicroMessenger/i) == 'micromessenger') {
				return true;
			} else {
				return false;
			}
		},
		toHash:function () {			
			var res = JSON.stringify(data);
			res = encodeURI(res);
			return res;
		},
		_fromLocalStorage:function () { //读出本地缓存内容 解序
			var store = window.localStorage.getItem("dstore"); //本地token uid
			wxdata = window.localStorage.getItem("wechatdstore");//微信使用者的相关信息
			if (store) {
				srcText = store;
				var d = JSON.parse(store);
				for (var i in d) {
					if (d.hasOwnProperty(i)) {
						data[i] = d[i]
					}
				}				
			}else{
				data.cacheTime = new Date().getTime();
			};
			if (wxdata) {
				data.wx = JSON.parse(wxdata);
			};
		},
		_toLocalStorage:function () {//存入本地缓存 
			data.cacheTime = new Date().getTime();
			window.localStorage.setItem("dstore",JSON.stringify(data));	
			wxdata&&window.localStorage.setItem("wechatdstore",wxdata);
		},
		set:function (key,value) {
			data[key] = value;
			if (key=="wx") {
				wxdata = JSON.stringify(value);
			};
			this._toLocalStorage();			
		},
		push: function(key, value) {
			if (!data[key]) {
				data[key] = [];
			};
			if (data[key].indexOf(value) < 0) {
				data[key].push(value);
				this._toLocalStorage();
			};			
		},
		put:function(key,k,value){
			if (!data[key]) {
				data[key] = {};
			};
			data[key][k] = value;
			this._toLocalStorage();
		},
		copyFrom:function (res) {
			if (res&&angular.isObject(res)) {
				for (var i in res) {
					if (res.hasOwnProperty(i)) {
						data[i] = res[i]
					}
				}
			};			
		},
		get:function (key) {
			return angular.copy(data[key])
		},
		del:function (key) {
			delete data[key];	
			this._toLocalStorage();
		},
		getOnce:function (key) {
			var res = data[key];
			if (res) {
				delete data[key];
				this._toLocalStorage();
			};
			return res;
		},
		toPage:function (staticPage,query) {
			this.init();
			if (staticPages[staticPage]) {		
			    var query = query||""	;
				this.set("referer",window.location.href);
				window.location.href = staticPages[staticPage]+query;//+"#"  +this.toHash();				
			}else if(staticPage=="-1"){
				window.history.go(staticPage)
			}else if (/^http/.test(staticPage)) {
				this.set("referer",window.location.href);
				window.location.href = staticPage;
			}
		},
		test:function (staticPage) {
			return staticPages[staticPage]
		},
		isInWeiXin:function(){
			var ua = window.navigator.userAgent.toLowerCase();
			if (ua.match(/MicroMessenger/i) == 'micromessenger') {
				return true;
			} else {
				return false;
			}
		},
		debug:function () {
			return [data,srcText];
		},
		clear:function () {
			data = {};
			this._toLocalStorage();
		},
		queryField:function (field) {
				if (!field) { return false};
				var str = field||""
                var url = location.href;
                var rs  = new RegExp("(^|)"+str+"=([^\&]*)(\&|$)","gi").exec(url), tmp;
                if(tmp=rs){
                    return tmp[2];
                } 
                return "";            
		},
		index:function () {
			//this.clear();
			this.toPage("home")
		},
		isPc:(function(){
			var system ={};  
		    var p = navigator.platform;       
		    system.win = p.indexOf("Win") == 0;  
		    system.mac = p.indexOf("Mac") == 0;  
		    system.x11 = (p == "X11") || (p.indexOf("Linux") == 0);    
		    if(system.win||system.mac||system.xll){//如果是电脑跳转到百度  
		       return true; 
		    }else{  //如果是手机,跳转到谷歌
		    	return false;
		    }
		})(),
		checkLogin:function(){ //验证是否已登入
			this.init();
			var _self = this;
			if(this.get('token') == '' || typeof  this.get('token') == 'undefined'){
				ngMessage.showTip("请先登入！",1000,function(){
					_self.toPage('login');
				});
				return false;
			}
			return true;
		}
	}
})
.factory('tool',function(){
	var main = {},getLocationObj = {};
	//获取位置
	 getLocationObj = {
			 options:{
	             enableHighAccuracy:true,　　　//boolean 是否要求高精度的地理信息
	             timeout:8000,　　　　　　　　　//表示等待响应的最大时间，默认是0毫秒，表示无穷时间
	             maximumAge:2000　　　　　//应用程序的缓存时间
	         },
			 getLocation:function(callBackSuccess,callBackError){
		             if(navigator.geolocation){
		                 //浏览器支持geolocation
		                 navigator.geolocation.getCurrentPosition( 
		                   angular.isFunction(callBackSuccess) ? callBackSuccess : getLocationObj.onSuccess,
			               angular.isFunction(callBackError) ? callBackError : getLocationObj.onError,
			               getLocationObj.options
		                 );
		             }else{
		                 //浏览器不支持geolocation
		                 alert('浏览器不支持定位');
		             }
			 } ,
			 onSuccess:function(position){
		         //返回用户位置
		         //经度
		         var longitude =position.coords.longitude;
		         //纬度
		         var latitude = position.coords.latitude;
				alert(longitude + '||' + latitude);
		     },
			 onError:function(error){
		         switch(error.code){
		             case 1:
		             alert("位置服务被拒绝");
		             break;
		             
		             case 2:
		             alert("暂时获取不到位置信息");
		             break;
		             
		             case 3:
		             alert("获取信息超时");
		             break;
		             
		             case 4:
		              alert("未知错误");
		             break;
		         }
			 }
	 };
	 //判断浏览器ios或andriod
	 var browser = {
			    'versions':function(){
			           var u = navigator.userAgent, app = navigator.appVersion; 
			    return { //浏览器版本信息 
			      trident: u.indexOf('Trident') > -1, //IE内核
			      
			      presto: u.indexOf('Presto') > -1, //opera内核
			       
			      webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
			      
			      gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
			      
			      mobile: !!u.match(/AppleWebKit.*Mobile.*/)||!!u.match(/AppleWebKit/), //是否为移动终端
			       
			      ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
			      
			      android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
			      
			      iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
			      
			      iPad: u.indexOf('iPad') > -1, //是否iPad
			      
			      webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
			    }
			  }(),
		    'language':(navigator.browserLanguage || navigator.language).toLowerCase(),
		};
     main = {
    		 getLocation:getLocationObj.getLocation,//获取经纬度
    		 isiPhone:browser.versions.iPhone
     }
/*     获取经纬度示例
     main.getLocation(function(position){
        //经度
        var longitude =position.coords.longitude;
        //纬度
        var latitude = position.coords.latitude;
		alert(longitude + '|&&|' + latitude);
    });*/
	return main;
});


window.addEventListener("load", function(){
	setTimeout(function(){
		document.body.style.display = 'inherit';
		document.body.setAttribute("style", "")
	}, 1200)
	
})