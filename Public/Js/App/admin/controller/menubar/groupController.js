define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "分组名称不能空"
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
            //图标显示
            $('#icon').on('keyup change',function(){
                $('#group-icon').html('<i class="'+ $(this).val() +'"></i>');
            });
		}
	};
	module.exports = main;
});