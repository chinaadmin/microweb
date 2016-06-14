/*
 listFilter 筛选控件使用方法:
var sundry = require('base/sundry');

//初始化筛选控件: listFilter('控件容器或容器ID',回调函数),filterParams 为收集到的参数

var filter = sundry.listFilter('confilter',function(filterParams){
		main.getDemand(filterParams); //你的请求接口
	});
	sundry.conFilter 方法返回的对象有三个方法一个属性: 
		filter.paramOptions(rel) 切换、显示标签,rel 为点击的标签
		filter.collectParams() 收集筛选参数
		filter.search() 发起筛选请求
		filter.paramSelMark 属性，标签被选中的样式雷，默认为 cur

html 属性设置：
	标签选中样式类，默认为 cur,
	一组标签的容器标记为 class="js-filterParam"
	如果有关键字搜索，标记为 class="js-kwd" ,搜索按钮标记为 class="js-searchBtn",
		参数名保存在 data-param, 如 data-param="keyword" 
	标签的筛选参数值标记在 data 属性，只能用小写，如 status=1 标记为 data-status="1" ,
	
	有切换筛选参数项的情况：
	 data-bindparams="a1,a2" 表示点击该标签时，设置有 data-param="a1" data-param="a2" 的标签被显示
	 data-unbindparams="a1,a2" 表示点击时隐藏 data-param="a1" data-param="a2" 的标签
	 如果 data-bindparams ,data-unbindparams 都设置，则对没有特别指定的标签默认显示
	 如果只设置  data-bindparams ，未指定要显示的标签被将被隐藏
	 如果只设置  data-unbindparams ，未指定要隐藏的标签被将被显示

*/


define(function(require,exports,module){
    var $ = require('jquery');
	//数据列表筛选控件
	function listFilter(filter,getList,paramSelMark){
		if(typeof(filter)=='function'){//省略 filter 参数
			paramSelMark = getList;
			getList = filter;
			filter = $("#js-list-filter");
		}else if(typeof(filter)=='string'){
			filter = $("#"+filter);
		}else{
			filter = $(filter);
		}
		getList = getList||function(){};
		

		filter.on("click",".js-filterParam a",function(e){
			e.preventDefault();//给搜索项绑定事件
			var paramSelMark = paramSelMark||actions.paramSelMark;		
			$(this).addClass(paramSelMark).siblings().removeClass(paramSelMark);
			actions.paramOptions($(this));	
			actions.search();
		}).on("click",".js-searchBtn",function(e){
			e.preventDefault();
			actions.search();
		}).on("keyup",".js-kwd",function(e){			
			if(e.keyCode==13){
				actions.search();
			}
		});
		

		var actions = {
				paramSelMark:'cur',
				paramOptions:function(rel){

					var bindparams = rel.data('bindparams')||'';
					var unbindparams = rel.data('unbindparams')||'';

					bindparams = bindparams.match(/[\w-]+/g)||[];
					unbindparams = unbindparams.match(/[\w-]+/g)||[];
					
					var paramKey;
					var paramSelMark = this.paramSelMark;
					filter.find("[data-param]").each(function(){
						if($(this)[0]===rel[0]){ return; }

						paramKey = $(this).data('param');
						if($.inArray(paramKey,bindparams)>-1){
							$(this).show();
						}else if($.inArray(paramKey,unbindparams)>-1){
							$(this).removeClass(paramSelMark);
							$(this).hide();
						}else if($(this).parents(".js-filterParam:first")[0]!=rel.parents(".js-filterParam:first")[0]){ 
							//非同辈、未指定。默认显示
							$(this).show();
						}
					});
				},
				collectParams:function(){
					var params = {};
					filter.find("."+this.paramSelMark).each(function(){
						var dataList = $(this).data();
						params = $.extend(params,dataList);
					});

					if(filter.find(".js-kwd").length){
                        filter.find(".js-kwd").each(function(){
                            var val = $(this).val().trim();
                            var key = $(this).data("param");
                            val && (params[key]=val);
                        });
					}
					
					return params;
				},
				search:function(){
					getList(this.collectParams());
				}
			};

		return actions;
	}

	var main = {
        listFilter:listFilter
	};


	return main;
});