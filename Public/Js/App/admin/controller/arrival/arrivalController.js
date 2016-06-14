define(function(require , exports ,module){
	var $ = require("jquery");
	var tool = require('model/tool');
	var common = require('common');
	var main = {
			index:function(){
				tool.check_all("#check_all",".order_check");
				tool.batch_del($('.order-del'),$('.order_check'));
			}
	};
	module.exports = main;
})