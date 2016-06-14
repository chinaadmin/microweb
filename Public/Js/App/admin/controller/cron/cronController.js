define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#user_edit').validate($.extend({
            rules: {
                subject: {
                    required: true
                }
            },
            messages: {
                subject: {
                    required: "任务标题不能空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        edit : function(){
            edit_validate();

            $('#js_time_select').on('change', function(){
                $('#js_time_'+ $(this).val()).show().siblings('.js_time_item').hide();
            });
            $("#js_type_select").on('change', function(){
                if($(this).val() == "0"){
                    $('.js_type_item').hide();
                }else{
                    $('#type'+ $(this).val()).show().siblings('.js_type_item').hide();
                }
            });
		}
	};
	module.exports = main;
});