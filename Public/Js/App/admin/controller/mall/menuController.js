define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    tool.validate_setting = $.extend({
        submitHandler: function (form) {
            tool.formAjax(form,function(data){
                require.async('base/jtDialog',function(jtDialog){
                    if(data.status != tool.success_code){
                        jtDialog.showTip(data.msg);
                    }else{
                        jtDialog.showTip(data.msg,1,function(){
                            common.U('MallMenu/index','',true);
                        });
                    }
                });
                return false;
            });
        }
    },tool.validate_setting);

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
                    required: "菜单名称不能空"
                }
            }
        },tool.validate_setting));
    };

    var cur_tab = function (type_val) {
        $('.js-tab').hide().each(function(key,val){
            if($(val).data('type') == type_val){
                $(this).show();
            }
        });
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		},
        edit : function(){
            edit_validate();
            cur_tab($('#type').val());
            $('#type').change(function(){
                cur_tab($(this).val());
            });
		}
	};
	module.exports = main;
});