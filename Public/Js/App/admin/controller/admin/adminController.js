define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

   	/**
   	 * 添加管理员验证
   	 */
	var add_validate = function(){
		$('#user_edit').validate($.extend({
            rules: {
                username: {
                    required: true
                },
                nickname: {
                	required: true
                },
                password:{
                    required: true
                },
//                email:{
//                    required: true,
//                    email: true
//                },
                mobile:{
                	required: true,
                	//mobile: true
                },
                role_id:{
                	required: true
                }
            },
            messages: {
                username: {
                    required: "用户名不能空"
                },
                nickname: {
                	required: "真实姓名不能空"
                },
                password:{
                    required: "密码不能空"
                },
//                email:{
//                    required: "email不能为空",
//                    email: "请输入正确的email地址"
//                },
                mobile:{
                	required: '手机不能为空',
                	//mobile: '手机格式不正确'
                },
                role_id:{
                	required: '角色不能为空'
                }
            }
        },tool.validate_setting));
	};

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
    	$('#user_edit').validate($.extend({
            rules: {
                username: {
                    required: true
                },
                nickname: {
                	required: true
                },
                email:{
                    required: true,
                    email: true
                },
                mobile:{
                	required: true,
                	mobile: true
                },
                role_id:{
                	required: true
                }
            },
            messages: {
                username: {
                    required: "用户名不能空"
                },
                nickname: {
                	required: "真实姓名不能空"
                },
                email:{
                    required: "email不能为空",
                    email: "请输入正确的email地址"
                },
                mobile:{
                	required: '手机不能为空',
                	mobile: '手机格式不正确'
                },
                role_id:{
                	required: '角色不能为空'
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        add : function(){
            add_validate();
        },
        edit : function(){
            edit_validate();
		}
	};
	module.exports = main;
});