define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');
    var main = {};
    main.userinfo = function(param){
    		param = param || {}; 
    		if(!param.ifOpenSidebar){
    		    //关闭侧边导航
    			$(".page-header-fixed").addClass("page-full-width");
    			$(".page-sidebar").addClass("visible-phone");
    			$(".page-sidebar").addClass("visible-tablet");	
    		}
            $('#user_edit').validate($.extend({
                rules: {
                    nickname: {
                    	required: true
                    },
                    mobile: {
                    	mobile:true,
    		        	required: true
    		        },
    		        email: {
                    	required: true,
                    	email:true
                    },             
                    confirmPassword: {
                    	equalTo: '[name="password"]'
                    }
                },
                messages: {
                    nickname: {
                    	required: '真实姓名不能为空'
                    },
                    mobile: {
    		        	required: '手机号不能为空'
    		        },
    		        email: {
                    	required: '邮箱不能为空',
                    	email:'邮箱格式不正确'
                    },                
                    confirmPassword: {
                    	equalTo: '两次密码不一致'
                    }
                }
            },tool.validate_setting));
    }
    
	module.exports = main;
});