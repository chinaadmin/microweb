define(function(require , exports ,module){
	var $ = require("jquery");
	var tool = require('model/tool');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
	var max = $("input[name='number']").attr("max");
	var moneyMax = $('.maxRefundMoney').val();
	var refund = {
			
			//申请退款退货
			doRefund:function(){
				 $('#refund_form').validate($.extend({
			            rules: {
			                number: {
			                    required: true,
			                    min:1,
			                    max:max
			                },
			                refund_money:{
			                	required:true,
			                	number:true,
			                	// range:[0.001,moneyMax]
			                	range:[0,moneyMax]
			                },
			                reasons:{
			                	min:1
			                }
			            },
			            messages: {
			                number: {
			                    required: "退款数量不能为空",
			                    min:"退货数至少是1",
			                    max:"退款数量不能大于最大退款数"
			                },
			                refund_money:{
			                	required:'退款金额不能为空',
			                	number:'退款金额不数字',
			                	range:'退款金额范围有误',
			                },
			                reasons:{
			                	min:"请选择退款原因"
			                }
			            }
			        },tool.validate_setting,{
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
			},
		 //退款金额计算
		 refundMoney:function(){
			 $("input[name='number']").on("blur",function(){
				 var val = $(this).val();
				 if(val<=max && val>0){
					 $("input[name='refund_money']").val(val*goods_price) 
				 }
			 }) 
		 }
	};
	
	refund.index = function(){
		this.doRefund();
		this.refundMoney();
	}
	module.exports=refund;
})