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
                            common.U('Article/cat_index','',true);
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
                    required: "名称不能空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		},
        edit : function(){
            edit_validate();
		}
	};
	module.exports = main;
});