define(function(require , exports ,module){
	var $ = require('jquery');
	require('jquery_validate');
	var common = require('common');
	
   	/**
   	 * 登录表单验证
   	 */
	var validate = function(){
        $('.login-form').validate({
            errorElement: 'label', //default input error message container
            errorClass: 'help-inline', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                username: {
                    required: true
                },
                password: {
                    required: true
                },
                verify : {
                    required : true,
                    minlength : 4,
                    maxlength : 4
                }
            },
            messages: {
                username: {
                    required: "用户名不能空"
                },
                password: {
                    required: "密码不能为空"
                },
                verify : {
                    required : '验证码不能为空',
                    minlength : '验证码为{0}个字符',
                    maxlength : '验证码为{0}个字符'
                }
            },
            highlight: function (element) { // hightlight error inputs
                $(element).closest('.control-group').addClass('error'); // set error class to the control group
            },
            success: function (label) {
                label.closest('.control-group').removeClass('error');
                label.remove();
            },
            errorPlacement: function (error, element) {
                error.addClass('help-small no-left-padding').insertAfter(element.closest('.input-icon'));
            },
            submitHandler: function (form) {
                var tool = require('model/tool');
                tool.formAjax(form,function(data){
                    if(data.status != tool.success_code){
                        error_tip(data.msg);
                        if(data.status != 'VERIFICATION_CODE_ERROR'){
                            var apiUrl = common.U('Public/genreateVerify' , {time: common.random(1,1000)});
                            $('#verify-code').attr('src' , apiUrl);
                        }
                    }else{
                        common.U('index/index','',true);
                    }
                    return false;
                });
            }
        });
	};

    var error_tip = function(msg){
        var error_html = '<div class="alert alert-error">'
            +'<a class="close" data-dismiss="alert"></a>'
            +'<span class="error">'+msg+'</span>'
            +'</div>';
        $('.form-error').html(error_html);
    };
    
    /**
     * 获取验证码
     */
    var getVerifyCode = function(){
        $('#verify-code').click(function(event){
            var apiUrl = common.U('Public/genreateVerify' , {time: common.random(1,1000)});
            $(this).attr('src' , apiUrl);
            event.preventDefault();
        });
    }

	var main ={
		init : function(){
			validate();
            getVerifyCode();
		}
	};
	module.exports = main;
});