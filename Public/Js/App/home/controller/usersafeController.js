//用户安全相关js
define(function(require , exports ,module){
	var $ = require('jquery');
    var common = require("common");
    var dialog = require("artDialog"); 
    var handle = {
    		passwordPage:function(){ //修改邮箱
    			 require.async('jquery_validate',function(){
    				 $('#signupForm').validate({
    					 errorElement: 'label',
    					 rules:{
    						 password:{
    							 required:true,
    							 remote:{
	    							url :common.U('i/checkPassword'),
	    							type:'get'
    							 }
    						 },
    						 newPassWord:{
    							 required:true,
    							 IsBlank:true,
    							 rangelength:[6,20] 
    						 },
    						 confirm_password:{
    							 required:true,
    							 equalTo: "#newPassWord"
    						 }
    					 },
    					 messages:{
    						 password:{
    							 required:'原密码不能为空',
    							 remote:"原密码有误"
    						 },
    						 newPassWord:{
    							 required:'新密码不能为空',
    							 IsBlank:'不能包含空格',
    							 rangelength:'6-20位字符，数字、字母或符号组合'
    						 },
    						 confirm_password:{
    							 required:'确认密码不能为空',
    							 equalTo: "再次输入密码不一致"
    						 }
    					 },
    					 submitHandler: function (form) {
                             common.formAjax(form,function(data){
                            	 if(data.status!='SUCCESS'){
                            		 var d = dialog({
                            			    content: data.msg
                            			});
                            			d.show();
                            			setTimeout(function () {
                              			   d.close().remove();
                              			}, 2000); 
                            	 }else{
                            		 var d = dialog({
                         			    content: data.msg
                         			 });
                         			 d.show();
                            		 setTimeout(function () {
//                            			   d.close().remove();
                            			   common.U('usersafe','',true);	
                            			}, 2000); 
                            	 }
                                 return false;
                             });
                         }
    				 });
    			 });
    		}
    };
    //验证手机
    handle.phone = function(){
    		var sendCodeValidate;
			 require.async('jquery_validate',function(){
				  $.validator.addMethod("mobile", function(value, element) {  
      			    var mobile =  /^[1][34578]\d{9}$/;
      			    return this.optional(element) || (mobile.test(value));  
      			}, "请输入正确的手机号！");
				  sendCodeValidate =  $('#sendCode').validate({
					 errorElement: 'label',
					 errorPlacement: function(error, element) {  
						    error.insertAfter($('.phone_btn1'));  
						},
					 rules:{
						 telephone:{
							 required:true,
							 mobile: true,
							 remote:{
								 url:common.U('I/ifExist'),
								 data:{
									 type:2,
									 mobile:function(){
										 return $('[name=telephone]').val();
									 }
								 }
							 }
						 }
					 },
					 messages:{
						 telephone:{
							 required:'手机号不能为空',
							 remote:'手机已经存在!'
						 }
					 },
					 submitHandler: function (form) {
						 //无效提交按钮 提示正在提交
						 $('#sendCode .phone_btn1').attr('disabled',true).html('努力获取中...');
                        common.formAjax(form,function(data){
                       	 if(data.status!='MESSAGE_CODE_SEND'){
                       		 var d = dialog({
                    			    content: data.msg
                    			 });
                    			 d.show();
                       		 setTimeout(function () {
                       			   d.close().remove();
                       			   window.location.reload();	
                       			}, 2000); 
                       	 }else{
                       		 $('#sendCode .success').html('<font color = blue>验证码发送成功</font>');
                       	 }
						 //无效提交按钮  倒计时
                       	 timer(60,$('#sendCode .phone_btn1'),163,100);
                          return false;
                        });
                    }
				 });
				 

				 $('#checkCode').validate({
					 errorElement: 'label',
					 errorClass: 'myError',
					 rules:{
						 checkCode:{
							 required:true
						 }
					 },
					 messages:{
						 checkCode:{
							 required:'验证码不能为空'
						 }
					 },
					 submitHandler: function (form) {
						 //手机是否通过验证
						 if(!sendCodeValidate.form()){
							 return;
						 }
						 if(form.action.indexOf('telephone') == -1){
							 form.action = form.action + '?telephone='+$('[name=telephone]').val();
						 }
                        common.formAjax(form,function(data){
                       	 if(data.status!='TEL_MATCH_SUCCESS'){
                       		 var d = dialog({
                    			    content: data.msg
                    			 });
                    			 d.show();
                       		 setTimeout(function () {
                       			   d.close().remove();
                       			   window.location.reload();	
                       			}, 2000); 
                       	 }else{
                       		 $('#checkCode .success').html('<font color = blue>'+data.msg+'</font>');
                       		setTimeout(function () {
                    			   common.U('i/phoneSucc','',true);
                    			}, 2000); 
                       	 }
                            return false;
                        });
                    }
				 });
				 
			 });
		}
    //绑定邮箱
    handle.checkEmailPage = function(){
     $('#verify-code').click(function(){
    	 changeCode();
     });
   	 require.async('jquery_validate',function(){
		  $('#checkEmail').validate({
			 errorElement: 'label',
			 errorPlacement: function(error, element) {  
				    error.appendTo(element.parent());  
				},
			 rules:{
				 email:{
					 required:true,
					 email: true
				 },
				 verifyCode:{
					 required:true
				 }				 
			 },
			 messages:{
				 email:{
					 required:'邮箱不能为空',
					 email:'邮箱地址格式不正确'	 
				 },
				 verifyCode:{
					 required:'验证码不能为空'
				 }
			 },
			 submitHandler: function (form) {
				 //无效提交按钮 提示正在提交
               common.formAjax(form,function(data){
              	 if(data.status!='SUCCESS'){
              		 var d = dialog({
           			    content: data.msg
           			 });
           			 d.show();
              		 setTimeout(function () {
              			   d.close().remove();
              			}, 2000); 
              	 }else{
              		 $('.success[for=email]').text('邮件发送成功');
              		  changeCode();
              		 setTimeout(function () {
            			   window.location.reload();	
            			}, 2000); 
              	 }
				 //无效提交按钮  倒计时
                 return false;
               });
           }
		 });
	 });
    }
    //倒计时
    /**
     * @param int intDiff 秒 
     * @param obj obj $对象  
     * @param start int 计时开始时元素的宽度
     * @param end int 计时结束时元素的宽度
     */
    function timer(intDiff,obj,start,end){
    	var intDiff = parseInt(intDiff);
    	obj.width(start);
        var circle = window.setInterval(function(){
        if(intDiff >= 0){
        }else{
        	obj.attr('disabled',false);
        	obj.html(obj.attr('yum'));
        	obj.width(end);
        	clearInterval(circle);
        	return;
        }
        obj.html(intDiff+' 秒');
        intDiff--;
        }, 1000);
    } 
    //变更验证码
    function changeCode(parameter){
    	parameter = parameter || {};
    	parameter = $.extend(parameter,{time: common.random(1,1000)});
    	var apiUrl = common.U('genreateVerify' , parameter);
        $('#verify-code').attr('src' , apiUrl);
    }
    handle.editemail = function(){
    	$(".button-next").click(function(){
			$(this).css("background","#ba6701");
		})
		$('[name = "changeCode"]').click(function(){
			changeCode({type:1});
		}).css({cursor:"pointer"});
		 require.async('jquery_validate',function(){
			  $('#editEmail').validate({
				 errorElement: 'label',
				 errorPlacement: function(error, element) {  
					    error.appendTo(element.parent());  
					},
				 rules:{
					 verifyCode:{
						 required:true,
						 remote:{
 							url :common.U('i/checkVerify'),
 							type:'post',
 							data:{
 								type : 1
 							}
						}
					 }				 
				 },
				 messages:{
					 verifyCode:{
						 required:'验证码不能为空',
						 remote:'验证码有误'
					 }
				 },
				 submitHandler: function (form) {
					 //无效提交按钮 提示正在提交
	               common.formAjax(form,function(data){
	              	 if(data.status!='SUCCESS'){
	              		$('label[for=verifyCode]').text(data.msg); 
	              	 }else{
	              		 $('.em-suc').show();
	              		 setTimeout(function () {
	            			   window.location.reload();	
	            			}, 2000); 
	              	 }
					 //无效提交按钮  倒计时
	                 return false;
	               });
	           }
			 });
		 });
    };
    handle.changeEmail = function(){
    	$(".button-next").click(function(){
			$(this).css("background","#ba6701");
		})
		$('[name = "changeCode"]').click(function(){
			changeCode({type:2});
		}).css({cursor:"pointer"});
    	
		 require.async('jquery_validate',function(){
			  $('#signupForm').validate({
				 errorElement: 'label',
				 errorPlacement: function(error, element) {  
					    error.appendTo(element.parent());  
					},
				 rules:{
					 verifyCode:{
						 required:true,
						 remote:{
 							url :common.U('i/checkVerify'),
							type:'post',
							data:{
								type : 2
							}
						}
					 },
					 email :{
						 required:true,
						 email:true,
						 remote:{
	 							url :common.U('i/ifExist'),
								type:'post',
								data:{
									type : 1
								}
						}
					 }
				 },
				 messages:{
					 verifyCode:{
						 required:'验证码不能为空',
						 remote:'验证码有误'
					 },
					 email:{
						 required:'邮箱不能为空',
						 email:'邮箱格式有误!',
						 remote:'邮箱已存在'
					 }
				 },
				 submitHandler: function (form) {
					 //无效提交按钮 提示正在提交
	               common.formAjax(form,function(data){
	              	 if(data.status!='SUCCESS'){
	              		$('label[for=verifyCode]').text(data.msg); 
	              	 }else{
	              		 $('.em-suc').show();
	              		 setTimeout(function () {
	            			   window.location.href = data.msg;	
	            			}, 2000); 
	              	 }
					 //无效提交按钮  倒计时
	                 return false;
	               });
	           }
			 });
		 });
    }
    handle.editmobile = function(){
    	var url = common.U('i/editMobile');
    	var time = 30;
    	var code = $(".msgs");
		$(".msgs").click(function(){
					//按钮禁用
					$('.phone_btn1').attr('disabled',true);
			    	$.post(url, function(data) {
						if (data.status == 'MESSAGE_CODE_SEND') {
							validCode = false;
							code.addClass("msgs1");
							var t = setInterval(function() {
								time--;
								code.html(time + "秒");
								if (time == 0) {
									//解除按钮禁用
									$('.phone_btn1').attr('disabled',false);
									clearInterval(t);
									code.html("重新获取");
									validCode = true;
									code.removeClass("msgs1");
									time = 30;	
								}
							}, 1000);
							$(".send-succ").show();
						}else{
							$(".send-succ").hide();
						}
			    	});
		});
		
		 require.async('jquery_validate',function(){
			  $('#signupForm').validate({
				 errorElement: 'label',
				 errorPlacement: function(error, element) {  
					    error.appendTo(element.parent());  
					},
				 rules:{
					 verifyCode:{
						 required:true,
						 remote:{
							url :common.U('i/checkOldMobile'),
							type:'post'							
						}
					 }				
				 },
				 messages:{
					 verifyCode:{
						 required:'验证码不能为空',
						 remote:'验证码有误或过期'
					 }
				 },
				 submitHandler: function (form) {
					 //无效提交按钮 提示正在提交
	               common.formAjax(form,function(data){
	              	 if(data.status!='SUCCESS'){
	              		$('label[for=verifyCode]').text(data.msg); 
	              	 }else{
	              		 $('.em-suc').show();
	              		 setTimeout(function () {
	            			   window.location.href = data.msg;	
	            			}, 2000); 
	              	 }
					 //无效提交按钮  倒计时
	                 return false;
	               });
	           }
			 });
		 });
    }
    handle.addNewMobile = function(){
		 require.async('jquery_validate',function(){
			 var validate;
			  $.validator.addMethod("mobile", function(value, element) {  
    			    var mobile =  /^[1][34578]\d{9}$/;
    			    return this.optional(element) || (mobile.test(value));  
    			}, "请输入正确的手机号！");
			  validate = $('#signupForm').validate({
				 errorElement: 'label',
				 errorPlacement: function(error, element) {  
					    error.appendTo(element.parent());  
					},
				 rules:{
					 verifyCode:{
						 required:true,
						 remote:{
							url :common.U('i/checkOldMobile'),
							type:'post',
							data:{
								mobile : function(){
									return $('[name=mobile]').val();
								},
								id : 'addNewMobile'
							}
						}
					 },
					 mobile:{
						 required:true,
						 mobile:true,
						 remote:{
								url :common.U('i/ifExist'),
								type:'post',
								data:{
									type : 2
								}
						}
					 }
				 },
				 messages:{
					 verifyCode:{
						 required:'验证码不能为空',
						 remote:'验证码有误或过期'
					 },
					 mobile:{
						 required:'手机号不能为空',
						 mobile:'手机格式不正确',
						 remote:'手机号已存在'
					 }
				 },
				 submitHandler: function (form) {
					 //无效提交按钮 提示正在提交
	               common.formAjax(form,function(data){
	              	 if(data.status!='SUCCESS'){
	              		$('label[for=verifyCode]').text(data.msg); 
	              	 }else{
	              		 $('.em-suc').show();
	              		 setTimeout(function () {
	            			   window.location.href = data.msg;	
	            			}, 2000); 
	              	 }
					 //无效提交按钮  倒计时
	                 return false;
	               });
	           }
			 });
				var url = common.U('i/addNewMobile');
		    	var time = 30;
		    	var code = $(".msgs");
				$(".msgs").click(function(e){
						common.stopDefault(e);
					      //手机是否通过验证
							if(!validate.element(document.getElementById('mobile')) || !$('label[for="mobile"]').is('label')){
								return false;
							}
							//按钮禁用
							$('.phone_btn1').attr('disabled',true);
							var mobile = $('#mobile').val();
					    	$.post(url,{'mobile':mobile}, function(data) {
								if (data.status == 'MESSAGE_CODE_SEND') {
									validCode = false;
									code.addClass("msgs1");
									var t = setInterval(function() {
										time--;
										code.html(time + "秒");
										if (time == 0) {
											//不禁用
											$('.phone_btn1').attr('disabled',false);
											clearInterval(t);
											code.html("重新获取");
											validCode = true;
											code.removeClass("msgs1");
											time = 30;	
										}
									}, 1000);
									$(".send-succ").show();
								}else{
									$(".send-succ").hide();
								}
					    	});
				});
		 });
    }
    //我的推荐
    handle.recommend = function(){

    }
    module.exports = handle;
});