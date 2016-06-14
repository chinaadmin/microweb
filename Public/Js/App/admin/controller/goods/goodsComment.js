/**
 * 从属分类
 */
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	main = {
		init:function(){
			$('#pass').click(function(){
				if($(this).data('status') > 0){ //已经通过
					return;
				}
				//没通过
				main.switchStatus($(this));
			});
			$('#noPass').click(function(){
				if($(this).data('status') > 0){ //已经通过
					//切换成不通过
					$('#tips').hide();
					$('#myModal').modal().show();
					$('.modal-footer [type=submit]').click(function(){
						main.switchStatus($('#noPass'),function(){
							if(!$('#myModal textarea').val()){
								$('#myModal textarea').focus();
								$('#tips').show();
								return false;
							}
							return true;
						});
					});
				}
			});
		},
		switchStatus:function(self,callBefore){
			 require.async('base/jtDialog',function(jtDialog){
		    	  var gc_id = self.data('gc_id'),gc_status = self.data('status'),data = {'gc_id':gc_id,'gc_status':gc_status}; 
		    	  if(callBefore){
		    		  if(!callBefore()){
		    			  return;
		    		  }
		    		  data.gc_failed_remark = $('#myModal textarea').val();
		    	  }
		    	  $.post(common.U('ajaxChangeCommentStatus'),data,function(data){
		    		  if(data.status != tool.success_code){
                         jtDialog.showTip(data.msg);
                     }else{
                         jtDialog.showTip(data.msg,1,function(){
                             location.reload();
                         });
                     }
		    	  });
             });
		}
	};
	module.exports = main;
	
});