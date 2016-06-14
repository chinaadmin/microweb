define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    var error_tip = function(msg){
        $('.error_tips').show().html(msg);
    };
    function stopDefault( e ) {//阻止默认事件
    	if ( e && e.preventDefault )
    	   e.preventDefault();
    	    else
    	   window.event.returnValue = false;
    	    return false;
    	}
    /**
     *倒计时
     *@param int time 秒
     *@param int target 显示计时的input jquery对象
     *@param int target 显示计时的input jquery对象
    */
    function countDown(time,target,disabledClass){
    	disabledClass = disabledClass || '';
    	target.attr('disabled',true).addClass(disabledClass);
    	var t = setInterval(function() {
			time--;
			target.val(time + "秒").html(time + "秒");
			if (time <= 0) {
				clearInterval(t);
				target.val("重新获取").html("重新获取");;
				target.attr('disabled',false).removeClass(disabledClass);
			}
		}, 1000)
    }
    /**
     * 改变验证码
     */
    var chengeVerify = function(url,id){
    	if(!url){
    		url = 'Passport/genreateVerify';
    	}
    	if(!id){
    		id = 'code_img';
    	}
        var apiUrl = common.U(url , {time: common.random(1,1000)});
        $('#'+id).attr('src' , apiUrl);
    };

    var main ={
        login:function(){
            require.async('jquery_validate',function(){
                var validate = function(){
                    $('#signupForm').validate({
                        errorElement: 'label',
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
                        submitHandler: function (form) {
                            common.formAjax(form,function(data){
                                if(data.status != common.success_code){
//                                    error_tip(data.msg);
                                    if(data.status != 'VERIFICATION_CODE_ERROR'){
                                        chengeVerify();
                                    }
                                    if(data.status == 'VERIFICATION_CODE_ERROR'){
                                    	$('label[for="verify"]').html(data.msg);
                                    }else if(data.status == 'PASSWORD_ERROR'){
                                    	$('label[for="passWord"]').html(data.msg);
	                                }else if(data.status == 'USER_NOT_EXIST'){
	                                	$('label[for="username"]').html(data.msg);
	                                }
                                    if(data.result && data.result.need_verify){
                                        $('#verify_code').show();
                                    }
                                }else{
                                	common.U('home/index/index','',true);
                                }
                                return false;
                            });
                        }
                    });
                };
                validate();
                $('.verify-code,#code_img').click(function(){
                    chengeVerify();
                }).css({cursor:'pointer'});
            });
        },
        poplogin:function(){
            require.async(['jquery_validate','model/diDialog'],function(validate,diDialog){
                var validate = function(){
                    $('#signupForm').validate({
                        errorElement: 'label',
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
                        submitHandler: function (form) {
                            common.formAjax(form,function(data){
                                if(data.status != common.success_code){
                                    diDialog.Alert(data.msg);
                                    if(data.status != 'VERIFICATION_CODE_ERROR'){
                                        chengeVerify();
                                    }
                                    if(data.result.need_verify){
                                        $('#verify_code').show();
                                    }
                                }else{
                                    location.reload();
                                }
                                return false;
                            });
                        }
                    });
                };
                validate();
                $('.verify-code').click(function(){
                    chengeVerify();
                });
            });
        },
        reg:function(){
        	  require.async('jquery_validate',function(){
        		   var validate;
        		   common.dealCode.init('code_img','','',{type:1,id:'regCode'});	//参数图片验证
        		   $.validator.addMethod("mobile", function(value, element) {
        			    var mobile =  /^[1][34578]\d{9}$/;
        			    return this.optional(element) || (mobile.test(value));
        			}, "请输入正确的手机号！");
        		   validate = $('#regForm').validate({
                      errorElement: 'label',
                      rules: {
                          username: {
                              required: true,
                              mobile: true,
                              remote:common.U("Passport/verifyTel")
                          },
                          verifyCode:{
                        	  required: true,
                        	  remote:common.U("verify/dealcode",{type:2,id:'regCode'})
                          },
                          password: {
                              required: true,
                              IsBlank:true,
                              rangelength:[6,20]
                          },
                          confirm_password:{
                        	  required:true,
                        	  equalTo:"#password"
                          },
                          verify : {
                              required : true,
                              minlength : 4,
                              maxlength : 4
                          },
                          mobile_code : {
                        	  required:true,
                        	  remote:{
                        		  url:common.U("Passport/checkRegMobCode"),
                        		  data:{
                        			  username:function(){
                        				  return $('input[name="username"]').val();
                        			  }
                        		  }
                        	}
                          },
                          invite_code : {
                              minlength : 4,
                              maxlength : 4,
                              remote : {
                            	  url:common.U('Passport/checkInviteCode'),
                            	  data:{
                            		  invite_code:function(){
                            			  return $('input[name="invite_code"]').val();
                            		  }
                            	  }
                              }
                          }
                      },
                      messages: {
                          username: {
                              required: "手机号不能空",
                              remote:"手机号码已存在"
                          },
                          verifyCode: {
                        	  required:'图型验证码不为空',
                        	  remote:'图型验证码有误'
                          },
                          password: {
                              required: "密码不能为空",
                              IsBlank:'不能包含空格',
                              rangelength:'6-20位字符，数字、字母或符号组合'
                          },
                          confirm_password:{
                        	  required:"确认密码不能为空",
                        	  equalTo:"两次密码不一致"
                          },
                          verify : {
                              required : '验证码不能为空',
                              minlength : '验证码为{0}个字符',
                              maxlength : '验证码为{0}个字符'
                          },
                          mobile_code:{
                        	  required:"短信验证码不能为空",
                        	  remote:'短信验证码不正确'
                          },
                          invite_code : {
                              minlength : '邀请码为{0}个字符',
                              maxlength : '邀请码为{0}个字符',
                              remote : '邀请码不存在!'
                          }
                      },
                      success:function(label){
                    	  if(label.attr('for') == 'verifyCode'){
	                   			 //验证手机号是否合法
                    		   var usernameObj = document.getElementById('username');
	      	              		var checkMob = validate.element(usernameObj);
	      	              		if(!checkMob){
	      	              			$(usernameObj).focus();
	      	              			return;
	      	              		}
	      	              		if($('#send_code').attr('disabled') == 'disabled'){ //已经发送过正在倒计时
	      	              			return;
	      	              		}
	      	              		if($('[name=code]').css('display') == 'none'){ //图形验证码已经隐藏
	      	              			return;
	      	              		}
	      	              		$('[name="tel"]').show();
	      	              		$('[name="code"]').hide();
	      	              		//发送短信验证码
	      	              		$('#send_code').trigger('click');	
                    	  }
                      },
                      submitHandler: function (form) {
                          common.formAjax(form,function(data){
                              if(data.status != common.success_code){
                                  error_tip(data.msg);
                                  chengeVerify();
                              }else{
                                  common.U('home/index/index','',true);
                              }
                              return false;
                          });
                      }
        	  });
	        	 $('#username').bind('keyup',function(){
	        		 	if($('#verifyCode').val() == ''){
	        		 		return;
	        		 	}
	        		 	validate.element(document.getElementById('verifyCode'));
        		 }); 
        		 //发送验证码
              	 $('#send_code').click(function(){
              		   var data = {},checkMob;
	                   var that = $(this);
	                   setTimeout(function (argument) {
	                     that.blur();
	                   },320)
	              		 //验证手机号是否合法
	              		checkMob = validate.element(document.getElementById('username'));
	              		if(!checkMob){
	              			return;
	              		}
	              		countDown(60,$('#send_code'),'disabledClass');
	              		 data.username = $('input[name="username"]').val();
	              		 $.post(common.U('regMobCode'),data,function(rtn){
	              			 $('#send_code').next().html(rtn.msg);
	              		 });
              	 });
        })
    }
}
    //找加密码
    main.findPassport = {
    		accountPage :function(){
    			   require('jquery_validate');
    			   $('#code_img,#changeCode').click(function(){
                       chengeVerify('Passport/backpasswordverify','code_img');
                   }).css({cursor:'pointer'});

    				$("#signupForm").validate({
    					errorPlacement: function(error, element) {
    					    error.appendTo(element.parent());
    					},
    					rules: {
    						userName: {
    							required: true,
    							remote:{
    								url:common.U('Passport/ifExistAccount')
    							}
    						},
    						verify:{
    							required: true,
    							rangelength: [4,4]
    						},
    						email: {
    							required: true,
    							email: true,
    						},
    						telephone: {
    							required: true,
    							rangelength: [11, 11],
    							digits: "只能输入整数"
    						},
    					},
    					messages: {
    						userName: {
    							required: "请输入帐号信息",
    							remote: '帐号不存在!'
    						},
    						verify:{
    							required: "请输入验证码信息",
    							rangelength: '验证码为四位'
    						},
    						email: {
    							required: "请输入Email地址",
    							email: "请输入正确的email地址"
    						},
    						telephone: {
    							required: "请输入手机号",
    							rangelength: "请输入正确的手机号",
    						}
    					},
    					submitHandler: function(form) {
    	                       common.formAjax(form,function(data){
    	                              if(data.status != 'SUCCESS'){
    	                            	  if(data.status == 'USER_NOT_EXIST') $('em[for=userName]').html(data.msg);
    	                            	 if(data.status == 'VERIFICATION_CODE_ERROR') $('em[for=verify]').html(data.msg);
    	                                  chengeVerify('Passport/backpasswordverify','code_img');
    	                              }else{
    	                                  common.U('passwordWay','',true);
    	                              }
    	                              return false;
    	                          });
    					}
    				});

    		},
    		passwordWay:function(){
    			$(".msgs").click(function(event){
    				stopDefault(event);
    				 var options = {
    				            url:common.U('sendMess')
    				        };
    				 countDown(30,$(".msgs"),'msgs1');
    				 common.doAjax(options,function(data){
                         if(data.status != 'MESSAGE_CODE_SEND'){
                        	 $('span.suc-text').html(data.msg);
                         }else{
     		    			$(".suc-text").show();
                         }
                         return false;
                     });
    			});
    			//selet框选取
    			$(".choice-select").change(function(){
    				if($(this).val()=='phone'){
    					$(".code-box").show();
    					$(".email-code").hide();
    				}
    				else if($(this).val()=='email'){
    					$(".code-box").hide();
    					$(".email-code").show();
    				}

    			})
    			require.async('jquery_validate',function(){
    				$("#signupForm").validate({
    					errorPlacement: function(error, element) {
    					    error.appendTo(element.parent());
    					},
    					rules: {
    						mess_code:{
    							required: true,
    							rangelength: [6,6]
    						}
    					},
    					messages: {
    						mess_code:{
    							required: "请输入验证码信息",
    							rangelength: '验证码为六位'
    						}
    					},
    					submitHandler: function(form) {
    	                       common.formAjax(form,function(data){
    	                              if(data.status != 'TEL_MATCH_SUCCESS'){
    	                            	  if(data.status == 'SUCCESS') location.href = location.origin+"/"+common.U("sendingEmail");
    	                            	  $('em[for=mess_code]').html(data.msg);
    	                              }else{
    	                                  common.U('setPassword','',true);
    	                              }
    	                              return false;
    	                          });
    					}
    				});
    			});
    		},
    		setPassword:function(){
    			require.async('jquery_validate',function(){
    				$("#signupForm").validate({
    					errorPlacement: function(error, element) {
    					    error.appendTo(element.parent());
    					},
    					rules: {
    						passWord: {
    							required: true,
    							IsBlank:true,
    							rangelength: [6, 20]
    						},
    						confirm_password: {
    							required: true,
    							equalTo: "#passWord",
    							rangelength: [6, 20]
    						}
    					},
    					messages: {
    						passWord: {
    							required: "请输入密码",
    							IsBlank:'不能包含空格',
    							rangelength: "密码在6~20个字符之间",
    						},
    						confirm_password: {
    							required: "请输入确认密码",
    							rangelength: "密码在6~20个字符之间",
    							equalTo: "两次输入密码不一致"
    						}
    					},
    					submitHandler: function(form) {
    	                       common.formAjax(form,function(data){
    	                              if(data.status != 'SUCCESS'){
    	                            	  console.log('wrong!');
    	                              }else{
    	                                  common.U('backPasswordSuc','',true);
    	                              }
    	                              return false;
    	                          });
    					}
    				});
    			});
    		}
    };
    module.exports = main;
});
