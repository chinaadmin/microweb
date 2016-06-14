define(function(require,exports,module){
    var $ = require('jquery');
    var common = require('common');
    var tool = {
        success_code: common.success_code,
        formAjax: function (form, callback) {
            callback = callback || tool.successCallback;
            common.formAjax(form,callback);
        },
        successCallback: function (data) {
            if (data.status !== tool.success_code) {

            } else {

            }
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
        del:function(obj){
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
				                	jtDialog.showTip('删除成功',1,function(){
				                		location.reload();
				                	});
				                }
				                return false;
			                }
			            });
						
					},'确认删除?');
        		});
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
                    	callback(data);
                     }
        	};
        	$.ajax(options);
        }
    };

    module.exports = tool;
});
