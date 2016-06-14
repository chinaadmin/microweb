/**
 * 发货管理
 */
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
    var tool = require('model/tool');

    /**
     * 订单备注
     */
    var mark = function(){
        $('#send_mark').validate($.extend({
            rules: {
                mark: {
                    required: true,
                }
            },
            messages: {
                mark: {
                    required: "备注不能为空",
                }
            }
        },tool.validate_setting));
    };
    
    /**
     * 时间控件
     */
    var dateControl=function(){
    	require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true
            });
            $('.end_time').datepicker({
                autoclose:true
            });
        });
    }
    /**
     * 确认收货
     */
    var confirm_receipt=function(){
    	$(".confirm_receipt").on("click",function(){
    		var url = $(this).attr("href");
    		tool.doAjax({
    			url:url
    		});
    		return false;
    	})
    }
	var main ={
		index : function(){
			dateControl();
			confirm_receipt();
		},
		info:function(){
			mark();
		}
	};
	module.exports = main;
});