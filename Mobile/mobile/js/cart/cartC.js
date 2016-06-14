angular.module('app.controllers', [])
.directive('errSrc', function() {
  return {
    link: function(scope, element, attrs) {    	
      element.bind('error', function() {      	
        if (attrs.src != attrs.errSrc) {
          attrs.$set('src', attrs.errSrc);          
        }
      });
      if (attrs.$attr['ngSrc']&&!attrs.ngSrc) {
      	 attrs.$set('src', attrs.errSrc);  
      };

    }
  }
})

.directive('ngImagescale', function() {
  return {
    link: function(scope, element, attrs) {  
    	if (element[0].tagName=="IMG") {
			element.bind('load', function() {
				if (attrs.ngImagescale&&(attrs.ngImagescale.split(":")).length==2) {
					var size = attrs.ngImagescale.split(":");
					var w= parseInt(size[0])||0,
						h = parseInt(size[1])||0;
						if (w&&h) {
							element[0].height = element[0].width*h/w;							
						}else{
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
			var referer = storage.get("referer")||"";
			var url = location.href.replace(location.search,'');
			var reg = new RegExp(url,"img");
			var loginReg = /http[^?#=]+login.html/img
			element.bind('click', function() {
				if (!reg.test(referer)&&!loginReg.test(referer)) {
					storage.toPage(referer);
					return false;
				};
				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else {
					window.history.go(-1);
				};
			});
		}
	}
})
.directive('ngDisableSelect', function() {
  return {
    link: function(scope, element, attrs) {    
      element.bind('selectstart', function() {      	
        return false;
      });
    }
  }
})

.controller('cartC', function ($scope,storage,cartS,ngMessage) {
	function countPrice() {
		var list = $scope.cart;
		var n = 0;
		for (var i = 0; i < list.length; i++) {
			if (list[i].check) {
				n += (parseInt(list[i].number) * parseFloat(list[i].goods.price));
			};			
		};
		n = Math.round(n*100)/100;
		$scope.priceSum = n;
	};
	
	storage.init();
	$scope.cart=[];
	$scope.hasRes = true;
	$scope.checks = false;
	$scope.priceSum=0;
	var token = storage.get("token")||"";
	var ids = storage.get("cartId");
	var cacheCartBox = storage.get("cartBox")||{};
	if (!token) {
		//storage.toPage("login");
		$scope.cartTips = true;
		
	};
	var postCart ={
		"token":token
	};
	var cacheCart={
		data:[],
		change:function(id,n){
			var d = this.data;
			
			for (var i = 0; i < d.length; i++) {
				if (parseInt(d[i].goodsId)==id) {
					d[i].num = n;
					break;
				};
			};
			this.data = d;
			console.log(d,id)
			this.save();
		},
		del:function(id){
			var d = this.data;
			var n = -1;

			for (var i = 0; i < d.length; i++) {
				if (d[i].goodsId==id) {
					n=i;
					break;
				};
			};
			if (n>=0) {
				this.data.splice(n,1);
				this.save();
			};
			return n
		},
		save:function(){
			var a={};
			var d = this.data;
			for (var i = 0; i < d.length; i++) {
				a[d[i].goodsId] = JSON.stringify(d[i]);
			};
			storage.set("cartBox",a);

		}
	};
	if (cacheCartBox&&Object.keys(cacheCartBox).length) {
		var cc=[];
		for (var k in cacheCartBox) {
			if (cacheCartBox.hasOwnProperty(k)&&parseInt(k)&&JSON.parse(cacheCartBox[k])) {
				cc.push(JSON.parse(cacheCartBox[k]));
			}
		}
		cacheCart.data = angular.copy(cc);
		
		postCart.carts = JSON.stringify(cc);
	};
	cartS.list(postCart,function (res) {
		console.log(res)
		if (res.resCode=="SUCCESS") {
			$scope.cart=res.cartList;
			$scope.checkAll();
			if (!$scope.cart.length) {
				$scope.hasRes = false;
			};
			if (token) {
				storage.set("cartBox",[])
			};
			countPrice();
		};
	})

	$scope.check=function (v) {
		v.check = !v.check;
		console.log(v)
		if (!v.check) {
			$scope.checks = v.check;
			countPrice();
			return false;
		};
		$scope.checks = true;
		for (var i = 0; i < $scope.cart.length; i++) {
			if (!$scope.cart[i].check) {
				$scope.checks = $scope.cart[i].check;
				break;
			};
		};
		countPrice();
	}
	$scope.checkAll = function () {
		$scope.checks = !$scope.checks;
		for (var i = 0; i < $scope.cart.length; i++) {
			$scope.cart[i].check = $scope.checks;
		};
		countPrice();
	};
	
	
	var delVar = true;
	var myVar = false;

	$scope.delChange = function(){
		$scope.myVar = !$scope.myVar;
		$scope.delVar = !$scope.delVar;
	}
	
	$scope.toSubmit = function(){
		$scope.myVar = !$scope.myVar;
		$scope.delVar = !$scope.delVar;
	}
	
	//批量删除
	$scope.delAllGoods = function (){
		var ids=[];
		for (var i = 0; i < $scope.cart.length; i++) {
			if ($scope.cart[i].check) {
				ids.push($scope.cart[i].id)
			};
		};
		if(ids.length>0){
			ngMessage.show("是否删除选中的数据？",function(){
				cartS.delAllGoods({
						"cartId": ids,
						"token": token
					}, function(res) {
						if (res.resCode == "SUCCESS") {	
							storage.toPage("cart");
						}else{
							 res && ngMessage.showTip(res.resMsg);
						}
				});
			});	
		}
	};


	$scope.submit = function () {
		cartS.list(postCart,function (res) {
			if (!token) {
				storage.toPage("login");
				return false;
			};
			var ids=[];
			for (var i = 0; i < $scope.cart.length; i++) {			
				if ($scope.cart[i].check) {
					ids.push($scope.cart[i].id)
				};
			};
			ids = ids.join(",");		
			// if (ids) {
			// 	storage.set("cartId",ids);
			// 	storage.toPage("checkOrder");
			// };
			if (res.resCode == "SUCCESS") {
				// storage.set("cartId", res.cartId);
				cartS.clearing({
						"cartId": ids,
						"token": token
					}, function(res) {
						if (res.resCode == "SUCCESS") {	
							storage.toPage("checkOrder");
							if (ids) {
								storage.set("cartId",ids);
								storage.toPage("checkOrder");
							};
						}else{
							 res && ngMessage.showTip(res.resMsg);
						}
				});
			}else{
				res && ngMessage.showTip(res.resMsg);
			} 
		})
			
	}


	$scope.onChange =function (v) {
		var n = parseInt(v.number)||1;
		if (!token) {
			cacheCart.change(v.goods.id,n)
			return false
		};
		cartS.update({
			"cartId":v.id,
			"num":n,
			"token":token
		},function (res) {
			if (res&&res.resCode=="SUCCESS") {
				countPrice()
			};
		})
	}

	$scope.onClick = function (v,iBoolean) {
		for (var i = 0; i < $scope.cart.length; i++) {			
			if (v.id != $scope.cart[i].id) {
				$scope.cart[i].isEditing = false;
			}
		};
		v.isEditing = iBoolean;		
	}

	$scope.del = function (v,index) {
		if (!token) {
			var n = cacheCart.del(v.goods.id);
			$scope.cart.splice(index,1);
			ngMessage.showTip("删除成功！")
			return false
		};

		ngMessage.show("确定删除该商品？", function() {
			cartS.del({
				cartId: v.id,
				token: token
			}, function(res) {
				if (res && res.resCode == "SUCCESS") {
					$scope.cart.splice(index, 1);
					if (!$scope.cart.length) {
						$scope.hasRes = false;
					};
					ngMessage.showTip("删除成功！")
					countPrice()
				};
			})
		})
	}
	$scope.toPage = function (page) {
		storage.toPage(page);
	}

	$scope.add = function (v) {
		var n = parseInt(v.number)||1;
		n++;
		v.number = n;
		$scope.onChange(v);
	};
	$scope.sub = function (v) {
		var n = parseInt(v.number)||1;
		n--;
		v.number = n||1;
		$scope.onChange(v);
	}

})

.controller('friendC', function ($scope,storage,ngMessage){
	storage.init();	
	$scope.isfriend = true;
	$scope.list = false;
	$scope.ersend = '';
	$scope.submit = function() {
		$scope.ersend = $scope.ersend;
		if(!$scope.ersend) {
			ngMessage.showTip("请填写你 “密” 友的姓名！")
			return
		}
		$scope.isfriend = false;
	}
	$scope.back = function(){
		if(!$scope.isfriend){
			$scope.isfriend = true;
		}else{
			window.history.back();
		}
	}
	$scope.tt = function(target,i,currentIndex) {
		var tmp = [];
		var a = target.target.getAttribute('value');
		if (a == 'A') {
			tmp.push(a)
		};
		target.target.setAttribute('class', 'pull-right topic active');
		var index = 0;
		if(i>0){
			// console.log(target.target.getAttribute('value'));
          index = currentIndex-1;   
		}else{
		  index = currentIndex+1;
		}
		$(".topic").eq(index).removeClass("active");
		
	}
	$scope.submitQuestion = function(){
	    var dd = true;
	    var value = [];
		$(".radio_bg").each(function(i,obj){
			 var val = $(obj).find(".active").attr('value');
			 if(val){
			 	if(val=='A'){
                 value[i] = val;
                }
			 }else{
			 	ngMessage.showTip("哎呀我去,你咋还漏选了！")
			 	dd=false;
			 	return false;
			 }
		})
		if(dd){
			var aLength = value.length;
			storage.set("aLength",aLength);
			storage.toPage("results",'?ersend='+encodeURIComponent($scope.ersend));
	    } 
	}
})

.controller('resuitsC', function ($scope, storage, ngMessage) {
	storage.init();
	var iErsend = decodeURIComponent(storage.queryField("ersend"));
	
	$scope.Results = '';
	$scope.header = '';
	$scope.iErsend = iErsend;
	$scope.prev = {
		"a": '他拥有美丽的外表和脱俗的个人品味，且有着洞察世事、随机应变的高情商，能够很快适应周边环境，总给人温暖亲切的感觉！他对你的爱护不动声色，需要你用心去发现哦！',
		"b": '他娴静、成熟、优雅、浪漫，与他在一起，能令你倍感轻松惬意，回归到你最真实最自我的状态！他绝对是可以与你安暖相陪，优雅到老的蜜友，千万要珍惜哦！',
		"c": '他是坚强而隐忍的智慧者，生活有很强的目的性。同时，他很善解人意，非常了解你，也愿意包容你的不足，在你迷茫的时候为你指路，无条件支持你！是不可多得的益友，亦是良师！',
		"d": '他是坚强而大胆的人，往往不拘一格，思想行动极具颠覆性和创新性，或许你自身并不会像他那么叛逆不羁，但他绝地是你膜拜和向往成为的偶象！跟他在一起的每天都充满惊喜和活力',
		"e": '他乐观、精力充沛、迷人、好动、三分钟热度……玩得开心，就是他的生活哲学！他很需要生活有新鲜感，不喜欢被束缚、被控制。他偶尔也有不耐烦、冲动的小情绪，你也要学会包容哦，因为他对你们的友谊绝对百分百的忠诚！',
		"f": '机灵聪明同时又有点风趣的人，跟他在一起，你绝不会感觉到无聊孤单，而是满满的幸福感。别看他一副嘻嘻哈哈的样子，其实他拥有一颗正直善良、正经到爆的心。',
		"g": '他是内心平静、容易满足的人，从不会给身边的人带来压迫感，令人如沐春风的舒适自在。当然，他也是个润物细无声、乐于奉献的人，常常在平淡琐碎的生活细节中，用简单的爱，令你感动到一塌糊涂。'
	}
	$scope.header = {
		"A": '洋槐蜜型 “蜜” 友',
		"B": '薰衣草蜜型 “蜜” 友',
		"C": '枣花蜜型 “蜜” 友',
		"D": '椴树蜜型 “蜜” 友',
		"E": '荆条蜜型 “蜜” 友',
		"F": '紫云英蜜型 “蜜” 友',
		"G": '油菜花蜜型 “蜜” 友'
	}

	var aLength = storage.get("aLength");
	if(aLength) {
		if(aLength == 1){
			$scope.Results = $scope.prev.a;
			$scope.header = $scope.header.A;
		}else if(aLength == 2) {
			$scope.Results = $scope.prev.b;
			$scope.header = $scope.header.B;
		}else if(aLength == 3) {
			$scope.Results = $scope.prev.c;
			$scope.header = $scope.header.C;
		}else if(aLength == 4) {
			$scope.Results = $scope.prev.d;
			$scope.header = $scope.header.D;
		}else if(aLength == 5) {
			$scope.Results = $scope.prev.e;
			$scope.header = $scope.header.E;
		}else if(aLength == 6) {
			$scope.Results = $scope.prev.f;
			$scope.header = $scope.header.F;
		}else if(aLength == 7) {
			$scope.Results = $scope.prev.g;
			$scope.header = $scope.header.G;
		}
	}else{
		$scope.Results = $scope.prev.g;
		$scope.header = $scope.header.G;
	};

	$scope.toReturn = function() {
		storage.toPage("friendTes");
	}
})

.controller('luckyDrawC', function ($scope,storage,ngMessage){
	
	var lottery={
		index:0,	//当前转动到哪个位置
		count:8,	//总共有多少个位置
		timer:0,	//setTimeout的ID，用clearTimeout清除
		speed:200,	//初始转动速度
		times:0,	//转动次数
		cycle:50,	//转动基本次数：即至少需要转动多少次再进入抽奖环节
		prize:2,	//中奖位置
		init:function(id){
			if ($("#"+id).find(".lottery-unit").length>0) {
				$lottery = $("#"+id);
				$units = $lottery.find(".lottery-unit");
				this.obj = $lottery;
				this.count = $units.length;
				$lottery.find(".lottery-unit-"+this.index).addClass("active");
			};
		},
		roll:function(){
			var index = this.index;
			var count = this.count;
			var lottery = this.obj;
			$(lottery).find(".lottery-unit-"+index).removeClass("active");
			index += 1;
			if (index>count-1) {
				index = 0;
			};
			$(lottery).find(".lottery-unit-"+index).addClass("active");
			this.index=index;
			return false;
		},
		stop:function(index){
			this.prize=index;
			return false;
		}
	};

	function roll(){
		lottery.times += 1;
		lottery.roll();
		if (lottery.times > lottery.cycle+10 && lottery.prize==lottery.index) {
			clearTimeout(lottery.timer);
			//lottery.prize=-1;
			lottery.times=0;
			click=false;
		}else{
			if (lottery.times<lottery.cycle) {
				lottery.speed -= 10;
			}else if(lottery.times==lottery.cycle) {
				//var index = Math.random()*(lottery.count)|0;
				//lottery.prize = index;		
			}else{
				if (lottery.times > lottery.cycle+10 && ((lottery.prize==0 && lottery.index==7) || lottery.prize==lottery.index+1)) {
					lottery.speed += 110;
				}else{
					lottery.speed += 20;
				}
			}
			if (lottery.speed<40) {
				lottery.speed=40;
			};
			//console.log(lottery.times+'^^^^^^'+lottery.speed+'^^^^^^^'+lottery.prize);
			lottery.timer = setTimeout(roll,lottery.speed);
		}
		return false;
	}

	var click=false;

	window.onload=function(){
		lottery.init('lottery');
		$("#lottery a").click(function(){
			if (click) {
				return false;
			}else{
				lottery.speed=100;
				roll();
				click=true;
				return false;
			}
		});
	};

})
