/**
 * 试吃活动
 */
define(function(require , exports ,module){
	var $ = require("jquery");
	var tool = require('model/tool');
	var common = require('common');
	var main = {
			//图片预览
			fancyapps:function(options){
				 require.async('fancyapps',function(){
					 $(".lightbox").fancybox(options);
				 });
			},
			//审核
			status:function(){
				require.async("jquery_validate",function(){
					$(".modal_verify").submit(function(){
						var id_dom = $(this).attr('id');
						var form = $("#"+id_dom);
						tool.formAjax(form,function(data){
	                             require.async('base/jtDialog',function(jtDialog){
	                                 if(data.status != tool.success_code){
	                                     jtDialog.showTip(data.msg);
	                                 }else{
	                                     jtDialog.showTip(data.msg,1,function(){
	                                    	 location.reload();
	                                     });
	                                 }
	                             });
	                             return false;
	                         });
						return false;
					})
	        	})
			},
			//批量审核
			batchStatus:function(){
				$(".photo-status").on('click',function(){
					var status = $(this).attr('status');
					var data = $('.check').serializeArray();
					var url = common.U("ActivityEat/batchStatus")+"?status="+status;
	        		require.async('base/jtDialog',function(jtDialog){
	            		if($.isEmptyObject(data)){
	            			jtDialog.showTip("请选择要更改的值！");
	            			return false;
	            		}
	        			
	        				$.post(url,data,function(data){
	        					if(data.status != tool.success_code){
	        						jtDialog.showTip(data.msg);
	        					}else{
	        						 jtDialog.showTip(data.msg, 2, function () {
	                                    location.reload();
	                                 });
	        					}
	        				})
	        			
	        		})
				})
			}
	}
	main.index=function(){
		//预览图片
		main.fancyapps({
			 'width'                : '100%',  
			 'height'               : '100%',  
			 'autoScale'            : false,  
			 'transitionIn'         : 'none',  
			 'transitionOut'        : 'none',  
		});
		//全选
		tool.check_all("#check_all",".check");
		main.status();
		main.batchStatus();
	}
	module.exports = main;
})