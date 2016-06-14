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

				if (storage.test(attrs.ngBack)) {
					storage.toPage(attrs.ngBack)
				} else if (!reg.test(referer) && !loginReg.test(referer)) {
					storage.toPage(referer);
					return false;
				} else {
					window.history.go(-1);
				};

			});
		}
	}
})
.controller("trytoeatC",function ($scope,trytoeathttpS,storage,ngMessage) {	
	storage.init();

	$scope.company = '';	
	$scope.company_address = '';
	$scope.people_num = '';
	$scope.pageCode = '';
	$scope.position = '';
	$scope.name = '';
	$scope.office_address = '';
	$scope.perst='';
	$scope.mobile = '';

	var cook = document.cookie;
	$scope.session='';
	if(cook){
		var cook_data = cook.split(";");
		for(i in cook_data){
			if(cook_data[i].indexOf("PHPSESSID")!='-1'){
				var d = cook_data[i].split("=");
				$scope.session = d[1];
			}
		}
	}
	$scope.pageCode = '';

    $scope.opp = function(){
    	var url = "http://act.tp-bihaohuo.cn/Tast/verify?"+Math.random()*1000;
    	document.getElementById("verify").setAttribute("src",url);
    }

	$scope.add = function(){
		
		//$scope.opp();
		var data = {
			company:$scope.company,
			company_address:$scope.company_address,
			people_num:$scope.people_num.key,
			sector:$scope.sector,
			position:$scope.position.key,
			name:$scope.name,
			office_address:$scope.office_address,
			mobile:$scope.mobile,
			code:$scope.pageCode,
			wechat:$scope.perst,
	        session_hao:$scope.session
		};
		//验证数据
		if (!$scope.company) {
			    ngMessage.showTip("请填写您公司全称！")
			    return
		    };

		if (!$scope.company_address) {
			    ngMessage.showTip("请填写您公司的地址！")
			    return
		    };

    	if (!$scope.people_num) {
			    ngMessage.showTip("请选择人数！")
			    return
		    };

		if (!$scope.sector) {
			    ngMessage.showTip("请填写您所在的部门！")
			    return
		    };  

		if (!$scope.position) {
			    ngMessage.showTip("请选择职位！")
			    return
		    };    	  
		
 		if (!$scope.name) {
			    ngMessage.showTip("请填写您是姓名！")
			    return
		    }; 

		if (!$scope.office_address) {
			    ngMessage.showTip("请填写您的办公的地址！")
			    return
		    }; 

		if (!$scope.mobile||!/^[1][35867][0-9]{9}$/.test(data.mobile)) {
			    ngMessage.showTip("请填写有效的手机号！")
			    return
		    };     
		    
		if (!$scope.perst) {
			    ngMessage.showTip("请填写您的微信名！")
			    return
		    };
		

		if (!$scope.pageCode) {
			    ngMessage.showTip("请输入验证码！")
			    return
		    };     	   	

		trytoeathttpS.addEat(data,function(res){
				if(res.resCode == "SUCCESS"){
					ngMessage.showTip(res.resMsg);
					$scope.clear();
				}else{
					ngMessage.showTip(res.resMsg);
					$scope.opp();
				}
				// console.log(res);

		});

	};
	$scope.numList = [
		{'step':'20-50人','key':1},
		{'step':'50-100人','key':2},
		{'step':'100-200人','key':3},
		{'step':'200以上','key':4},
	];

	$scope.sunList = [
        {'stop':'员工','key':1},
        {'stop':'主管','key':2},
        {'stop':'经理','key':3},
        {'stop':'总监','key':4},
        {'stop':'董事长','key':5}
	];

	$scope.clear = function () {
			$scope.company = "";
			$scope.company_address = "";
			$scope.people_num = "";
			$scope.sector = "";
			$scope.position = 0;
			$scope.name = null;
			$scope.office_address = null;
			$scope.mobile = null;
			$scope.pageCode = null;
			$scope.perst = null;
			$scope.pageCode = null;
		}

})














