define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
    var tool = require('model/tool');

    /**
     * 展位验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                name: {
                    required: true,
                    maxlength:10
                },
                url:{
                	url:true
                }
            },
            messages: {
                name: {
                    required: "展位名称不能空",
                    maxlength:"展位名称不能超过10个字"
                },
                url:{
                	url:"请填写正确的地址"
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