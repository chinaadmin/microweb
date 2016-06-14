define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	main = {};
	main.list = function(){
		tool.ajaxWithTip($('.doReceive'),'领取',1);
	}
	module.exports = main;
});