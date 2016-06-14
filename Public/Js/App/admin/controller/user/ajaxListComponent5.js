/**
 * 异步列表控件使用方法:
     var list = require('base/ajaxListComponent');
     var config = {
                    page_id : 'pagebar', //分页控件id
                    list_id : 'notice-list', //加载列表id
                    list_tpl_id : 'tpl-notice-list', //模板id
                    url : '/instances_notice/get_send_lists/type/school' //请求路径
                };
     list.init(config);
     list.get_lists();
 */
define(function(require,exports,module){
    var $ = require('jquery');
    var artTemplate = require('template');
    var sundry = require("base/sundry.js");
    var common = require("common");

    var defaults = {
        list_id : 'js-list',//渲染列表id
        list_tpl_id : 'js-tpl-list',//模板id
        filter_id:'js-list-filter',//搜索控件id
        url : '',//请求url
        params:{},//额外url参数
        has_page:true,//是否开启分页
        page_id : 'js-pagebar',//分页控件id
        page_size:10,
        async:true //请求数据是否异步请求
    };

    var page_configs = {};

    var filter = '';

    //格式化时间
    artTemplate.helper('dateFormat',function(d,s){
        return common.date.format(d,s||'%Y/%M/%D %H:%I:%S');
    });

    //按字节截取字符串
    artTemplate.helper('msubstr',function(str,start,length,basebyte){
        str = str==null?'':str+'';
        var sl = str.replace(/[^x00-xFF]/g,'**').length;
        var end_str = sl > length?'...':'';
        return common.str.substr(str,start,length,basebyte)+end_str;
    });

    /**
     * 生成url路径
     * @param {String} url 支持[模块/操作]
     * @param {Object} vars 额外参数(支持数组传值)
     * @param {Boolean} suffix 是否伪静态默认是
     * @param {Boolean} redirect 是否跳转默认否
     * @author cwh
     */
    artTemplate.helper('U',function(url,vars,suffix,redirect){
        redirect = redirect === true?true:false;
        return common.U(url,vars,redirect,suffix);
    });

	var ajax_list = {
        settings:{},
        init:function(configs){//初始化
            var is_init = ajax_list.settings.is_init;
            ajax_list.settings = $.extend({},defaults,configs);
            if(!is_init){//防止重复初始化
                filter = sundry.listFilter(ajax_list.settings.filter_id,function(filterParams){
                    ajax_list.request_lists(filterParams);
                });
            }
            ajax_list.settings.is_init = true;
        },
        set_configs:function(configs){//设置配置
            ajax_list.settings = $.extend({},ajax_list.settings, configs);
        },
        set_params:function(params){//设置额外参数
            ajax_list.settings.params = $.extend({},ajax_list.settings.params,params);
        },
        get_lists:function(){//获取列表
            filter.search();
        },
        get_current_lists:function(num){//重新加载当前页
            var params ={};
            if(ajax_list.settings.has_page === true){//开启分页
                num = num||0;//减少条数
                var pagecount = Math.ceil(page_configs.count - num/ page_configs.pagesize);
                params.page = page_configs.curpage>pagecount?pagecount:page_configs.curpage;
                params = $.extend(params,filter.collectParams());
                ajax_list.request_lists(params);
            }else{
                ajax_list.request_lists(params);
            }
        },
        before_events:function(data,params){//渲染列表页面前的回调方法，返回false将不执行页面的渲染操作
            return true;
        },
        after_events:function(data,params,listSheet){//渲染列表页面后的回调方法，用于绑定页面的事件操作

        },
        once_after_events:function(data,params,listSheet){//渲染列表页面后的回调方法，用于绑定页面的事件操作(只执行一次执行完后销毁)

        },
        render_lists:function(data,params){//渲染列表页面
            data.items = data.items||[];
            var tpl = ajax_list.settings.list_tpl_id;
            var listSheet = $("#"+ ajax_list.settings.list_id);
            listSheet.html(artTemplate(tpl,data));

            ajax_list.after_events(data,params,listSheet);
            ajax_list.once_after_events(data,params,listSheet);
            ajax_list.once_after_events = function(data,params,listSheet){};

            if(ajax_list.settings.has_page === true){//开启分页
                var pagebar = require("base/pagebar.js");
                var pageConfig = {};
                pageConfig.pagesize = params.pageSize||ajax_list.settings.page_size;
                pageConfig.pagecount = Math.ceil(data.count/ pageConfig.pagesize);
                pageConfig.curpage = params.page||1;


                page_configs = pageConfig;
                page_configs.count = data.count;//总数
                pagebar.init(pageConfig,$("#" + ajax_list.settings.page_id),function(pageInfo){
                    var params = {page:pageInfo.forward};
                    params = $.extend(params,filter.collectParams());
                    ajax_list.request_lists(params);
                });
            }
        },
        request_lists:function(params,callback){//请求列表页面
            params = params||filter.collectParams();
            params = $.extend(params,ajax_list.settings.params);
            callback = callback||ajax_list.render_lists;
            var url = ajax_list.settings.url;
            params = params||{};
            $.ajax({
                type: 'GET',
                url: url,
                data: params,
                dataType: "json",
                async:this.settings.async, //自定义是否异步 方便一个页面同时多昝使用
                success:function(resp){
                    if(resp.status==common.success_code && ajax_list.before_events(resp,params)){
                        callback(resp.result,params);
                    }
                }
            });
/*            $.get(url,params,function(resp){
                if(resp.status==common.success_code && ajax_list.before_events(resp,params)){
                    callback(resp.result,params);
                }
            },'json');*/
        }
	};
	return ajax_list;
});