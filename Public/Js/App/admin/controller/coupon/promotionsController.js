define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
    var templates = require('template');
	
	require('jquery_validate');
    var tool = require('model/tool');
    var time_limit = require('./type3');
    jQuery.validator.addMethod("time_required", function(value, element, param) {
        if($('#start_time').val() == ''||$('#end_time').val() == ''){
            return false;
        }
        return true;
    }, "请选择活动时间");
    /**
     * 修改规则验证
     */
    var edit_validate = function(){
    	$('#user_edit').validate($.extend({
            ignore:'',
            rules: {
                name: {
                    required: true
                },
                sel_time:{
                    time_required:true
                }
            },
            messages: {
                name: {
                    required: "活动名称不能为空"
                }
            }
        },tool.validate_setting));
    };

    //过期时间
    function put_in_time(){
        require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true
            }).on('changeDate',function(ev){
                if(ev.date.valueOf() > new Date($('#end_time').val()).getTime()){
                    $('#end_time').val('');
                }
                $('.end_time').datepicker('setStartDate', new Date(ev.date));
            });
            $('.end_time').datepicker({
                autoclose:true,
                startDate:$('#start_time').val()
            });
        });
    }

    //公共事件
    var object_event = function(){
        //初始化选择对象
        var init_object = function(){
            var object_val = $('input[name="object"]').val();
            var object = [];
            if(object_val != '') {
                object = JSON.parse($('input[name="object"]').val());
            }

            sel_object = [];
            $('.js-checkbox').each(function(){
                var _self = $(this);
                _self.prop("checked", false);
                var text = _self.data('text');
                var id = _self.val();
                var name = _self.data('name');
                var type = _self.data('type');

                $.each(object,function(i,val){
                    if(val.name == name && val.id == id && type != 'all'){
                        sel_object.push({id:id,text:text,name:name});
                    }
                });
            });

            var show_text = '当前设置：无限制均可参与';
            var object = [];
            if(sel_object.length > 0){
                show_text = '可参与对象：';
                $.each(sel_object,function(i,val){
                    if(i>0){
                        show_text += '，';
                    }
                    show_text += val.text;
                    object.push({id:val.id,name:val.name});
                });
            }
            $('#js-set-user-text').html(show_text);
        }

        init_object();
        $('#js-set-user-btn').click(function(){
            //打开模态框默认选中选择对象
            var object_val = $('input[name="object"]').val();
            var object = [];
            if(object_val != '') {
                object = JSON.parse($('input[name="object"]').val());
            }
            sel_object = [];
            var is_all_sel = {
                grade : true,
                group : true
            };

            $('.js-checkbox').each(function(){
                var _self = $(this);
                _self.prop("checked", false);
                var text = _self.data('text');
                var id = _self.val();
                var name = _self.data('name');
                var type = _self.data('type');

                var is_sel = false;
                $.each(object,function(i,val){
                    if(val.name == name && val.id == id && type != 'all'){
                        _self.prop("checked", true);
                        sel_object.push({id:id,text:text,name:name});
                        is_sel = true;
                    }
                });

                if(!is_sel && type != 'all'){
                    if(name == 'grade'){
                        is_all_sel.grade = false;
                    }else if(name == 'group'){
                        is_all_sel.group = false;
                    }
                }
            });

            $('.js-checkbox').each(function(){
                if($(this).data('type') == 'all'){
                    var name = $(this).data('name');
                    if(name == 'grade'){
                        $(this).prop("checked", is_all_sel.grade);
                    }else if(name == 'group'){
                        $(this).prop("checked", is_all_sel.group);
                    }
                }
            });

            $.uniform.update('.js-checkbox');

            $('#set-user-view').modal('show').css({
                'margin-left': function () {
                    return -($(this).width() / 2);
                }
            });
        });

        var sel_object = [];
        $('#set-user-view').on('click','.js-checkbox',function(){
            var checked = $(this).is(':checked');
            var name = $(this).data('name');
            if($(this).data('type') == 'all'){
                $('.js-checkbox').each(function(){
                    if($(this).data('name') == name) {
                        $(this).prop("checked", checked);
                    }
                });
            }else{
                var all_checkbox ;
                var is_all = true;
                if(!checked){
                    is_all = false;
                }

                $('.js-checkbox').each(function(){
                    if($(this).data('name') == name) {
                        if($(this).data('type') == 'all') {
                            all_checkbox = $(this);
                        }else{
                            if(is_all && !$(this).is(':checked')){
                                is_all = false;
                            }
                        }
                    }
                });

                all_checkbox.prop("checked", is_all);
            }

            $.uniform.update('.js-checkbox');

        }).on('click','.js-confirm',function(){//确定选择对象

            sel_object = [];
            $('.js-checkbox').each(function(){
                var checked = $(this).is(':checked');
                if(checked && $(this).data('type') != 'all') {
                    var text = $(this).data('text');
                    var id = $(this).val();
                    var name = $(this).data('name');
                    sel_object.push({id:id,text:text,name:name});
                }
            });

            var show_text = '当前设置：无限制均可参与';
            var object = [];
            if(sel_object.length > 0){
                show_text = '可参与对象：';
                $.each(sel_object,function(i,val){
                    if(i>0){
                        show_text += '，';
                    }
                    show_text += val.text;
                    object.push({id:val.id,name:val.name});
                });
            }
            $('#js-set-user-text').html(show_text);
            $('input[name="object"]').val(JSON.stringify(object));

            $('#set-user-view').modal('hide');
        });
    }

    var coupon_event = function(){
        var coupon_data = {
            coupon:[], //优惠劵
            coupon_index:0
        };
        var jtDialog = require('base/jtDialog');
        var coupon_div = $('#js-coupon-list');
        $('.js-issuing-coupons').click(function(){
            var index = $(this).data('id');
            if(coupon_div.html().length == 0) {
                var opt = {
                    url: common.U('Coupon/getListsJson',{is_system:1})
                };
                common.doAjax(opt, function (data) {
                    if (data.status !== common.success_code) {
                        jtDialog.alert(data.msg);
                    } else {
                        var templates = require('template');
                        coupon_div.html(templates('tpl-coupon-list', {items: data.result})).show();
                        sel_coupon(index);
                    }
                });
            }else{
                sel_coupon(index);
            }

            $('#issuing-coupons-view').modal('show').css({
                'margin-left': function () {
                    return -($(this).width() / 2);
                }
            });
        });

        var sel_coupon = function(i){
            var coupon = $('input[name="rule_param[content_coupon]['+i+']"]').val();
            var coupon_arr = coupon.split(",");
            $('.div_coupon').removeClass('selected');

            $.each(coupon_arr, function (key, val) {
                $('.div_coupon').each(function(){
                    var coupon_id = $(this).data('id');
                    if(val == coupon_id){
                        $(this).addClass('selected');
                    }
                });
            });
            coupon_data.coupon_index = i;
            coupon_data.coupon = coupon_arr;
        }

        coupon_div.on('click','.div_coupon',function(){//选中优惠劵
            var _self = $(this);
            var coupon_id = _self.data('id');
            var is_exist_index = false;
            $.each(coupon_data.coupon, function (key, val) {
                if(val == coupon_id){
                    is_exist_index = key;
                    return false;
                }
            });

            if(is_exist_index === false){
                coupon_data.coupon.push(coupon_id);
                _self.addClass('selected');
            }else{
                coupon_data.coupon.splice(is_exist_index,1);
                _self.removeClass('selected');
            }
        });

        //确认优惠劵
        $('#issuing-coupons-view .js-confirm').click(function(){
            var coupon = coupon_data.coupon;
            var coupon_str = coupon.join(',');
            if(coupon_str.substr(0,1) == ','){
                coupon_str = coupon_str.substr(1);
            }
            $('input[name="rule_param[content_coupon]['+coupon_data.coupon_index+']"]').val(coupon_str);
            $('#issuing-coupons-view').modal('hide');
        });
    }

    //促销模板
    var promotions_template = function(data,mode){
        switch(mode){
            case 'default':
            default :
                var is_add = false;
                if(!data){//是否添加
                    data = [{condition:{},content:{}}];
                    is_add = true;
                }else{
                    data = eval("("+data+")");
                }
                $.each(data,function (i,val) {
                    var html = templates('tpl-promotions',{info:val,i:i,is_add:is_add});
                    $('.js-promotions').append(html);
                });
                var base = require('base/controller/adminbaseController');
                base.initUniform();
        }
    }

	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        edit : function(type,data){
            edit_validate();
            //选择时间
            put_in_time();

            $('.js-promotions').on('keyup paste','.numText',function(){//限制只能输入数字
                var tmptxt=$(this).val();
                $(this).val(tmptxt.replace(/[^0-9\.]/g,''));
            });
            switch(type){
                case 1:
                case 2:
                    promotions_template(data,'default');
                    //优惠劵事件
                    coupon_event();
                    break;
                case 3:
                	//限时秒杀
                    time_limit.index();
                    break;
                case 4:
                    var city_func = require("pulgins/city/city_func");
                    window.jobArea = city_func.jobArea;
                    $('.jobAreaSelect').click(function(){
                        var idsuffix = $(this).data('idsuffix');
                        idsuffix = idsuffix || '';
                        city_func.jobAreaSelect(idsuffix);
                    });
                    break;
                case 5:
                	//限量折扣
                    time_limit.index();
                    break;
                case 6:
                	//购物赠券
                    time_limit.index();
                    coupon_event();
                    break;
                case 7:
                	//注册赠券
                    coupon_event();
                    break;
            }

            //活动对象事件
            object_event();
		}
	};
	module.exports = main;
});