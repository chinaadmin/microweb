define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
    var tool = require('model/tool');

    /**
     * 分类验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                title: {
                    required: true,
                }
            },
            messages: {
                title: {
                    required: "标题不能为空",                 
                }
            }
        },tool.validate_setting));
    }();
    
    //投放时间
    var dateControl=function(){
    	require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true
            });
            $('.end_time').datepicker({
                autoclose:true
            });
          //快捷选择时间
            $("#sel_time").on("change",function(){
        		var val = $(this).val();
        		$("input[name='end_time']").val(val);
        		$('.end_time').datepicker('update');
        	})
        });
    }
	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		},
		edit : function(){
			dateControl();
		}
	};
	module.exports = main;
});