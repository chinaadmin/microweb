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
					lc_name:{
						required:true
					},
					lc_url:{
						required:true,
						url:true
					},
					lc_tel:{
						required:true,
						// mobilephone:true
					},
					lc_code:{
						required:true
					}
				},
				messages : {
					lc_name:{
						required:'物流公司名称不能为空'
					},
					lc_url:{
						required:'公司网址不能为空',
						url:'网址格式不正确'
					},
					lc_tel:{
						required:'电话格式不能为空',
						// mobile:'电话格式不正确',
					},
					lc_code:{
						required:'代码不能为空'
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