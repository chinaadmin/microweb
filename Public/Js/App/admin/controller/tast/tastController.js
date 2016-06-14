define(function(require , exports ,module){
	var $ = require("jquery");
	var tool = require('model/tool');
	var common = require('common');
	require("jquery_validate");
	var method = {
		  status:function(){
			  $(".tast-status").on("click",function(){
				  var url = $(this).attr("href");
				  require.async('base/jtDialog',function(jtDialog){
					  tool.doAjax({
						  url:url
					  },function(result){
						  if(result.status != tool.success_code){
	                          jtDialog.showTip(result.msg);
	                      }else{
	                          jtDialog.showTip(result.msg,2,function(){
	                         	 window.location.href = common.U('tast/index');
	                          });
	                      } 
					  })
				  });
				  return false;
			  })
		  },
		  remark:function(){
			  $(".tast_remark").on("click",function(){
				  var id = $(this).attr('data-id');
				  require.async('base/jtDialog',function(jtDialog){
				     if(!id){
				    	 jtDialog.showTip("位置错误！"); 
				     }else{
				    	 $("#myModal_remark_"+id).modal("show");
				    	 $('#remark_'+id).validate(
				    			 $.extend(
						    	    		tool.validate_setting,
						    	    		{
						    	            	 submitHandler: function (form) {
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
						    	                 }
						    	            }
						    	    )
				    	 );
				     }
				  });
				  
				  return false;
			  })
		  }
	};
	
	var main = {
		index:function(){
			method.status();
			method.remark();
		}
	};
	module.exports = main;
})