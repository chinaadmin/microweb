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
                name: {
                    required: true,
                    maxlength:20
                },
                url:{
                	required: true,
                	url:true
                }
            },
            messages: {
                name: {
                    required: "品牌名称不能空",
                    maxlength:"品牌名称不能超过20个字"
                },
                url:{
                	required:'网址不能为空!',
                	url:"请输入正确的网址"
                }
            }
        },tool.validate_setting));
    }();
	
	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		}
	};
	module.exports = main;
});