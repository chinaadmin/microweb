define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	require('jquery_validate');

	/**
	 * 商品验证
	 */
	(function(){
		$('#form_validate').validate($.extend(tool.validate_setting,{
			rules : {	
				dw_delivery_way:{
					required:true
				},
                dw_code:{
                    required:true
                }
			},
			messages : {
				dw_delivery_way:{
					required:'名称不能为空'
				},
                dw_code:{
                    required:'配送代码不能为空'
                }
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
		}));
})();

    
    var main = {}
   module.exports=main;
})