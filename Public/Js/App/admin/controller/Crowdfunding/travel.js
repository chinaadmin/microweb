define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	require('jquery_validate');
	jtDialog = require('base/jtDialog');
	
	main = {};
	main.add = function(){
		  //增加经证
	      $('#from_edit').validate($.extend({
	            rules: {
	            	tp_name: {
	                    required: true
	                },
	                tp_remark:{
	                    required: true,
	                }
	            },
	            messages: {
	            	tp_name: {
	                    required: '项目名称不能为空'
	                },
	                tp_remark:{
	                    required: '项目说明不为空',
	                }
	            }
	        },tool.validate_setting));
		
	};
	main.list = function(){
		
	}
	
	module.exports = main;
})