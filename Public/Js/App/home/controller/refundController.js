define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    var diDialog = require('model/diDialog');
    var tool = require('tool');
    var max = $('#number').attr('max');
    var main ={
    	/**
    	 * 价格计算
    	 */
    	number:function(){
    		$('#number').on('blur',function(){
    			var val=$(this).val();
    			var price = $(this).attr('price');
    			var max = $(this).attr("max");
    			if(val>max){
    				//diDialog.Alert("不能超过最大退货数！");
    				return false;
    			}
    			if(val==0 || val==""){
    				$('#money').val("");
    				//diDialog.Alert("至少退货一件！");
    				return false;
    			}
    			var money = val*price;
    			money = money.toFixed(2); 
    			$('#money').val(money);
    		})
    	},
    	/**
    	 * 退款提交验证
    	 */
    	refund:function(){
    		require.async('jquery_validate',function(){
				 $('#refund-form').validate({
					  errorElement: "label",
					 rules:{
						 number:{
							 required:true,
							 min:1,
							 max:max
						 },
						 refund_rea:{
							  min:1
						 }
					 },
					 messages:{
						 number:{
							 required:"请填写退货的数量",
							 min:"退货数不能少于1件",
							 max:"退货数量不能大于最大退货数"
						 },
						 refund_rea:{
							 min:"请选择退款原因"
						 }
					 },
					 submitHandler: function (form) {
	                     tool.formAjax(form,function(data){
	                             if(data.status != tool.success_code){
	                            	 diDialog.Alert(data.msg);
	                             }else{
	                            	 location.href=data.msg;
	                             }
	                         return false;
	                     });
	                 }
				 })
			 });
    	},
        index:function(){
        	this.number();
        	this.refund();
        },
        info:function(){ //退款/退货详情页
        	$('.js_cancel_refund').click(function(e){
        		common.stopDefault(e);
        		var _self = $(this);
        		diDialog.Confirm('确认要取消该退款退货申请吗？',function(){
        			var url = _self.attr('href');
            		$.post(url,function(data){
            			 if(data.status != tool.success_code){
                        	 diDialog.Alert(data.msg);
                         }else{
                        	 location.reload();
                         }
            		});
        		});
        	});
        }
    };
    module.exports = main;
});