define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');
   	/**
   	 * 添加或修改验证
   	 */
	var update_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
            	cg_title: {
                    required: true
                },
                cg_url:{
                    required: true,
                },
                cg_content:{
                    required: true
                }
            },
            messages: {
            	cg_title: {
                    required: "自定义页面标题不能空"
                },
                cg_url:{
                    required: "链接地址不能为空",
                },
                cg_content:{
                    required: "内容不能为空"
                }
            }
        },tool.validate_setting));
	};
	var main ={
		index : function(){
			tool.check_all("#check_all",".check");
			tool.batch_del($('#delAll'),$('.check'));
		},
        update : function(){
            	update_validate();
            }
        }
	module.exports = main;
});