define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    var diDialog = require('model/diDialog');
    var main = {};
         main.bindOpen = function(){ 
        	       common.dealCode.init('code_img','changeCode','',{id:'bindUser'});
        			require.async('jquery_validate',function(){
        				var validateBind;
        				validateBind = $("#bind").validate({
        					errorElement: 'label',
        					errorPlacement: function(error, element) {  
        					    error.appendTo(element.parent());  
        					},
        					rules: {
        						userName: {
        							required: true,
        							remote:common.U('checkUserName')	
        						},
        						passWord: {
        							required: true,
        							rangelength: [6, 20]
        						},
        						verifyCode: {
        							required: true,
        							remote:{
        								url:common.U('Verify/dealCode',{id:'bindUser',type:2}),
        							}
        						},
        					},
        					messages: {
        						userName: {
        							required: "请输入用户名",
        							remote:'用户不存在!'
        						},
        						passWord: {
        							required: "请输入密码",
        							rangelength: "密码在6~20个字符之间",
        						},
        						verifyCode: {
        							required: "请输入验证码",
        							remote:'验证码不正确!'
        						}
        					},
        					submitHandler: function(form) {
        		                   common.formAjax(form,function(data){
                                       if(data.status == 'PASSWORD_ERROR'){
                                           $('label[for="passWord"]').html(data.msg);
                                       }else{
                                            location.href = location.origin;
                                       }
                                       return false;
                                   });	
        					}
        				});
        				
        				
        				$("#signupForm").validate({
        					errorElement: 'label',
        					rules: {
        						userName: {
        							required: true,
        							remote:common.U('checkUserName',{type:2})
        						},
        						passWord: {
        							required: true,
        							rangelength: [6, 20]
        						},
        						confirm_password: {
        							required: true,
        							equalTo: "#passWord",
        							rangelength: [6, 20]
        						},
        						email: {
        							required: true,
        							email: true,
        						},
        						telephone: {
        							required: true,
        							rangelength: [11, 11],
        							digits: true,
        							remote:common.U('checkTel')
        						},
        					},
        					messages: {
        						userName: {
        							required: "请输入用户名",
        							remote:'用户名已存在'
        						},
        						passWord: {
        							required: "请输入密码",
        							rangelength: jQuery.format("密码在6~20个字符之间"),
        						},
        						confirm_password: {
        							required: "请输入确认密码",
        							rangelength: jQuery.format("密码在6~20个字符之间"),
        							equalTo: "两次输入密码不一致"
        						},
        						email: {
        							required: "请输入Email地址",
        							email: "请输入正确的email地址"
        						},
        						telephone: {
        							required: "请输入手机号",
        							rangelength: jQuery.format("请输入正确的手机号"),
        							digits: "只能输入整数",
        							remote: '手机号已被使用'
        						}
        					},
        					submitHandler: function(form) {
        		                   common.formAjax(form,function(data){
                                       if(data.status != 'SUCCESS'){
                                           diDialog.Alert(data.msg);
                                       }else{
                                    	   location.href = location.origin;
                                       }
                                       return false;
                                   });	
        					}
        				});
        			});
        		}
    module.exports = main;
})