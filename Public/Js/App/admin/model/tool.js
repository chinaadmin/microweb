define(function(require,exports,module){
    var $ = require('jquery');
    var common = require('common');
    var tool = {
        success_code: common.success_code,
        formAjax: function (form, callback) {
            callback = callback || tool.successCallback;
            common.formAjax(form,callback);
        },
        successCallback: function (data,success_callback,failure_callback) {
            require.async('base/jtDialog',function(jtDialog) {
                if (data.status !== tool.success_code) {
                    failure_callback || jtDialog.error(data.msg);
                } else {
                    success_callback(data);
                }
            });
        },
        validate_setting: {
            errorElement: 'label',
            errorClass: 'help-inline',
            focusInvalid: false,
            highlight: function (element) {
                $(element)
                    .closest('.help-inline').removeClass('ok');
                $(element)
                    .closest('.control-group').removeClass('success').addClass('error');
            },
            unhighlight: function (element) {
                $(element)
                    .closest('.control-group').removeClass('error');
            },
            success: function (label) {
                if (label.attr("for") == "service" || label.attr("for") == "membership") {
                    label
                        .closest('.control-group').removeClass('error').addClass('success');
                    label.remove();
                } else {
                    label
                        .addClass('valid').addClass('help-inline ok')
                        .closest('.control-group').removeClass('error').addClass('success');
                }
            },
            errorPlacement: function (error, element) {
                error.insertAfter(element);
            },
            submitHandler: function (form) {
                tool.formAjax(form,function(data){
                    require.async('base/jtDialog',function(jtDialog){
                        if(data.status != tool.success_code){
                            jtDialog.showTip(data.msg);
                        }else{
                            jtDialog.showTip(data.msg,1,function(){
                                location.href = document.referrer
                            });
                        }
                    });
                    return false;
                });
            }
        },
        /**
         * 删除
         * @param obj 操作对象
         */
        del:function(obj,mess){
        	mess = mess || '删除';
        	obj.click(function(){
                var _self = $(this);
        		require.async('base/jtDialog',function(jtDialog){
        			jtDialog.confirm(function(){
						var url = _self.attr('url');
						$.ajax({
			                type: 'GET',
			                url: url,
			                data: {},
			                dataType: "json",
			                cache: false,
			                success: function(data){
			                	if(data.status != tool.success_code){
				                    jtDialog.error(data.msg);
				                }else{	
				                	jtDialog.showTip(mess+'成功',1,function(){
				                		if(_self.data('parentselector')){ //删除父节点
				                			_self.closest(_self.data('parentselector')).remove();
				                			return;
				                		}
				                		location.reload();
				                	});
				                }
				                return false;
			                }
			            });
						
					},'确认'+mess+'?');
        		});
			});
        },
        /**
         * 批量删除
         * @param 删除按钮对象
         */
        batch_del:function(obj,check_obj,mess){
        	mess = mess || '删除';
        	if(typeof(obj)  == 'string'){
        		obj = $(obj);
        	}
        	if(typeof(check_obj)  == 'string'){
        		check_obj = $(check_obj);
        	}
        	obj.on('click',function(){
        		var url = $(this).attr("url");
        		require.async('base/jtDialog',function(jtDialog){
        			var data = check_obj.serializeArray();
            		if($.isEmptyObject(data)){
            			jtDialog.showTip("请选择要"+mess+"的值！");
            			return false;
            		}
        			jtDialog.confirm(function(){
        				//var data = check_obj.serializeArray();
        				$.post(url,data,function(data){
        					if(data.status != tool.success_code){
        						jtDialog.showTip(data.msg);
        					}else{
        						 jtDialog.showTip(data.msg, 2, function () {
                                    location.reload();
                                 });
        					}
        				})
        			},"确认"+mess+"?");
        		});
        		return false;
        	})
        },
        //发送带提示的请求
        ajaxWithTip:function(obj,mess,type,selectObj){
        	var mess = mess || '删除';
        	var type = type || 0;
        	var data = {};
        	var jtDialog = require('base/jtDialog'); 
        	obj.click(function(){
                var _self = $(this),myFn;
            	if(selectObj){
            		data = selectObj.serializeArray();
            		if($.isEmptyObject(data)){
            			jtDialog.showTip("请选择要"+mess+"的值！");
            			return false;
            		}
            	}
                myFn = function(){
					var url = _self.attr('url');
					$.ajax({
		                type: 'GET',
		                url: url,
		                data: data,
		                dataType: "json",
		                cache: false,
		                success: function(data){
		                	if(data.status != tool.success_code){
			                    jtDialog.error(data.msg);
			                }else{	
			                	jtDialog.showTip(mess+'成功',1,function(){
			                		if(_self.data('parentselector')){ //删除父节点
			                			_self.closest(_self.data('parentselector')).remove();
			                			return;
			                		}
			                		location.reload();
			                	});
			                }
			                return false;
		                }
		            });
					
				};
    			if(type == 0){
    				jtDialog.confirm(myFn,'确认'+mess+'?');
    				return;
    			}
    			myFn();
			});
        },
        /**
         * 保存排序
         * @param obj 操作对象
         * @param inputs 排序class名
         * @param url 保存操作地址
         */
        saveSort : function(obj , inputs ,url){
            obj.on("click" , function(e){
                e.preventDefault();
                if (!url){
                    url = $(this).attr('url');
                }
                if(!inputs){
                    inputs = 'js-sort';
                }
                var data = $('.'+ inputs).serializeArray();
                $.post(url , data ,function(data){
                    require.async('base/jtDialog',function(jtDialog) {
                        if (data.status != tool.success_code) {
                            jtDialog.showTip(data.msg);
                        } else {
                            jtDialog.showTip(data.msg, 2, function () {
                                location.reload();
                            });
                        }
                    });
                });
            });
        },
        /**
         * ajax发送
         */
        doAjax:function(opt,callback){
        	opt = opt || {};
        	var options = {
        			 url:opt.url,
        			 type: opt.method || 'POST',
                     data: opt.data,
                     dataType: "json",
                     cache: false,
                     success: function(data){
                    	if(typeof(callback)=="function"){
                    		callback(data);
                    	}else{
                    		  require.async('base/jtDialog',function(jtDialog) {
                                  if (data.status != tool.success_code) {
                                      jtDialog.showTip(data.msg);
                                  } else {
                                      jtDialog.showTip(data.msg, 2, function () {
                                          location.reload();
                                      });
                                  }
                              });
                    	}
                     }
        	};
        	$.ajax(options);
        },
        /**
         * 全选
         * @param all_obj 全选按钮对象
         * @param check_obj 被选对象
         */
        check_all:function(all_obj,check_obj){
        	$(all_obj).on('click',function(){
        		$(check_obj).each(function(i){
        			if($(all_obj).is(":checked")){
        				$(check_obj).eq(i).prop("checked",true);
        			}else{
        				$(check_obj).eq(i).prop("checked",false);
        			}
        		})
        		$.uniform.update(check_obj);
        	});
        	$(check_obj).on('click',function(){
        		var length = $(check_obj).length;
        		var j=0;
        		$(check_obj).each(function(i){
        			if($(check_obj).eq(i).is(':checked')){
        				j++;
        			}
        		})
        		if(j==length){
        			$(all_obj).prop("checked",true);
        		}else{
        			$(all_obj).prop("checked",false);
        		}
        		$.uniform.update(all_obj);
        	})
        }
    };
    module.exports = tool;
});
