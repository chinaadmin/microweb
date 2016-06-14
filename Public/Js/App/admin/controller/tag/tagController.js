/**
 * 商品标签管理
 */
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');
    //新增编辑
    var edit_validate = function(){
        $('#form_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                },
               
            },
            messages: {
                name: {
                    required: "标签名称不能为空"
                }
            }
        },tool.validate_setting));
    };
    
    var main = {
    		
    };
    
    main.index = function(){
    	tool.check_all("#check_all",".tag_check");
    	tool.batch_del($('.tag-del'),$('.tag_check'));
    }
    module. exports = main;
})