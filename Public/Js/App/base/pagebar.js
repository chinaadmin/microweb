/*
*使用方法

var pagebar = require('base/pagebar');
var pageConfig = {};
pageConfig.pagesize = params.pageSize||10; //默认每页10条
pageConfig.pagecount = Math.ceil(data.count/ pageConfig.pagesize); //总页数
pageConfig.curpage = params.page||1; //当前页码

pagebar.initPagebar(pageConfig,$("#pagebar"),function(pageInfo){
	var params = {page:pageInfo.forward}; // pageInfo.forward 跳转的页码
	params = $.extend(params,filter.collectParams());
	main.getDemand(params); //你的请求接口
});
*/

define(function(require,exports,module){
    var $ = require('jquery');
	var artTemplate = require('template');
//    var tpl = '<div id="pagination" data-maxnum="<%=pagecount%>" data-currentnum="<%=curpage||1%>" data-pagesize="<%=pagesize||10%>" class="tb-page-bottom js-pagination"> <%if(curpage==1){%> <span class="page-start">上一页</span> <%}else{%> <a class="page-start" data-val="<%=curpage-1%>" data-act="page" href="javascript:void(0)">上一页 </a> <a class="page" data-val="1" data-act="page" href="javascript:void(0)">首页 </a><%}%> <% var start = (curpage-2)>=1?curpage-2:1; var end = (curpage+2)>=5?curpage+2:5;end=end<=pagecount?end:pagecount; for(var i=start;i<=end;i++){ if(i==curpage){ %> <span class="page-cur"><%=i%></span> <%}else{%> <a data-val="<%=i%>" data-act="page" href="javascript:void(0)"><%=i%></a> <%} }%> <%if(curpage==pagecount){%> <span class="page-end">下一页</span>  <%}else{%> <a class="page" data-val="<%=pagecount%>" data-act="page" href="javascript:void(0)">尾页 </a> <a class="page-next" data-val="<%=curpage+1%>" data-act="page" href="javascript:void(0)">下一页 </a><%}%> </div>';
	var main = {
		tpl:'<ul id="pagination" data-maxnum="<%=pagecount%>" data-currentnum="<%=curpage||1%>" data-pagesize="<%=pagesize||10%>" class="pagination js-pagination"><%if(curpage==1){%><li class="disabled"><span>«</span></li><%}else{%><li><a data-val="<%=curpage-1%>" data-act="page" href="javascript:void(0)">«</a></li><%}%><% var start = (curpage-2)>=1?curpage-2:1;var end = (curpage+2)>=5?curpage+2:5;end=end<=pagecount?end:pagecount; for(var i=start;i<=end;i++){ if(i==curpage){ %><li ><a class="active" data-val="<%=1%>"><%=i%></a></li><%}else{%><li><a data-val="<%=i%>" data-act="page" href="javascript:void(0)"><%=i%></a></li><%} }%> <%if(curpage==pagecount){%><li class="disabled"><span>»</span></li><%}else{%><li><a data-val="<%=curpage+1%>" data-act="page" href="javascript:void(0)">»</a></li><%}%></ul>',

		/*
		* pageConfig object 页码参数 
		*	pageConfig.pagesize 每页记录数，默认10
			pageConfig.pagecount 总页数，默认100
			pageConfig.curpage 当前页码，默认 1
		* holder 页码控件 父元素或其ID
		* callback 切换页码时调用的函数，该函数的第一个参数是 pageInfo
		*/
		initPagebar:function(pageConfig,holder,callback){		
			if(typeof(holder)=='string'){
				holder = $("#"+holder);
			}
			if(pageConfig.pagecount==0){
				$(holder).html('');
			}else{
				pageConfig.pagesize = pageConfig.pagesize*1||10;
				pageConfig.curpage = pageConfig.curpage*1||1;
				pageConfig.pagecount = pageConfig.pagecount*1||100;
				$(holder).html(artTemplate.compile(this.tpl).call(null,pageConfig));
			}
			$(holder).children().on("click","a",function(e){				
				e.preventDefault();				
				var pagination = $(this).parents(".js-pagination:first");
				var pageInfo = {};
				pageInfo.pagesize = pagination.data("pagesize");
				pageInfo.pagecount = pagination.data("maxnum");
				pageInfo.curpage = pagination.data("currentnum");
				pageInfo.forward = $(this).data("val");
				callback&&callback(pageInfo);
			});
		},
		init:function(){
			this.initPagebar.apply(this,arguments);
		}
	};


	module.exports = main;
	
});