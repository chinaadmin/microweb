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
                remark: {
                    required: true
                }
            },
            messages: {
                remark: {
                    required: "帐户变动原因不能空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
		},
        edit : function(){
            edit_validate();
		}
	};
	module.exports = main;
});