/**
 * 订单中心
 */
define(function(require , exports ,module){
	var $ = require("jquery");
	var tool = require('model/tool');
	var common = require('common');
	var send = require("./send");
	var refund = require("./refund");
    var main = function(){
		
    	/**
    	 * 订单查询时间段
    	 */
    	this.time_sole = function(){
    		require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
                $('.start_time').datepicker({
                    autoclose:true
                });
                $('.end_time').datepicker({
                    autoclose:true
                });
            });
    	};
    	//显示隐藏商品详情
    	this.hover_info = function(){
    		$('.order-code').hover(function(e){
    			e.stopPropagation();
    			var index = $(this).index();
    			$(this).find('.goods-info').show();
    		},function(){
    			$(this).find('.goods-info').hide();
    		})
    	};
    	//点击切换发票
    	this.invoice_click=function(){
    		var _this = this;
    		$('#need_invoice,#norml_invoice,#add_invoice').on('click',function(){
    			_this.tab_invoice();
    		})
    	};
    	/**
    	 * 发票类型切换
    	 */
    	this.tab_invoice=function(){
    		if($('#need_invoice').is(":checked")){
    			$('.invoce_type').show();
    			if($('#norml_invoice').is(":checked")){
    				$(".general-invoice").show();
    				$('.add-invoice').hide();
    			}
    			if($('#add_invoice').is(":checked")){
    				$(".general-invoice").hide();
    				$('.add-invoice').show();
    			}
    		}else{
    			$('.invoce_type').hide();
    			$(".general-invoice").hide();
				$('.add-invoice').hide();
    		}
    	};
    	/**
    	 * 编辑表单提交
    	 */
    	this.editSubmit = function(){
    		require.async("jquery_validate",function(){
        		$('#edit-invoice').validate($.extend(tool.validate_setting,{
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
                }));
        	})
    	};
    	/**
    	 * 批量确认
    	 */
    	this.all_confirm = function(){
    		$(".order_confirm").on('click',function(){
    			var _this = $(this);
    	require.async('base/jtDialog',function(jtDialog){
    		var order_id = _this.attr('order_id');
			var j=0;
			if(order_id){
				var data = "order_id="+order_id;
				j++;
			}else{
				var data = {};
    			data['order_id'] ={};
    			$('.order_check').each(function(i){
    				var obj = $('.order_check').eq(i);
    				var status = obj.attr('data-status');
    				var val = obj.val();
    				if(obj.is(':checked') && status==0){
    					data['order_id'][i] = val;
    				}
    				if(obj.is(':checked')){
    					j++;
    				}
    			});
			}
    			if(j){
    		     jtDialog.confirm(function(){
    			   tool.doAjax({
    				  url:common.U('Order/order_confirm'),
    				  data:data
    			   },function(result){
    				   if(result.status != tool.success_code){
    					   jtDialog.showTip("更新失败！");
    				   }else{
    					   jtDialog.showTip(result.msg, 2, function () {
                               location.reload();
                            });
    				   }
    		       });
    		     },"订单确认后无法再进行编辑，确认吗？");
    			 }else{
    				 jtDialog.showTip("请选择要确认的订单!");
    			 }
    		});
    		});	
    	};
		
		/*
		* 修改运单号
		*/
		
		$('#up_send_num').click(function(){
			$('#up_number').show();
		});
		
		$('.cancel').click(function(){
			$('#up_number').hide();
		});
		
		$("#submit_num").click(function(){
			
			var data = {};
			data['order_id'] = $("input[name='order_id']").val();
			data['number'] = $("input[name='num']").val();
			data['send_num'] = $("input[name='send_num']").val();
			require.async('base/jtDialog',function(jtDialog){
				if(!data['number']){
					jtDialog.showTip("请正确填写运单号");
					return false;
				}
				
				if(data['number'] == data['send_num']){
					jtDialog.showTip("请正确填写运单号");
					return false;
				}
				
				if(!data['send_num']){
					jtDialog.showTip("该订单不支持运单号修改");
					return false;
				}
				tool.doAjax({
					url:common.U('Order/up_number'),
					data:data,
					
				},function(result){
					
					jtDialog.showTip(result.msg);
					if(result.status == 'SUCCESS'){
						$("input[name='num']").val('');
						$('#up_number').hide();
					}
				});
			})
				
			
		});
    };
    /**
     * 订单导出
     */
    main.prototype.export_order = function(){
    	$(".order-export").on('click',function(){
    		var data = "";
    		$(".order_check").each(function(i,v){
    			if($(v).is(":checked")){
    				data+=$(v).val()+",";
    			}
    		})
    		if(data){
    		 var url = $(this).attr('url')+"?order_id="+data;
    		 window.location.href=url;
    		}else{
    			require.async('base/jtDialog',function(jtDialog){
    				 jtDialog.showTip("请选择要导出的订单");
    			});
    		}
    	})
    }

    //导出全部订单
    main.prototype.allExport_order = function(){
        $(".order-allExport").on('click',function(){
            var order_status = $('select[name="order_status"]').val(); //订单状态
            var pay_status = $('select[name="pay_status"]').val(); // 支付状态
            var receipt_status = $('select[name="receipt_status"]').val(); // 发货状态
            var order_keywords = $('input[name="order_keywords"]').val(); // 关键字查询
            var start_time = $('input[name="start_time"]').val(); // 下单起始时间
            var end_time = $('input[name="end_time"]').val(); // 下单结束时间
            var start_money = $('input[name="start_money"]').val(); // 订单起始金额
            var end_money = $('input[name="end_money"]').val(); // 订单最大金额
            var send_type = $('select[name="send_type"]').val(); //配送方式
            var order_source = $('select[name="order_source"]').val(); // 订单来源
            var pay_type = $('select[name="pay_type"]').val(); // 支付方式
            var stores = $('select[name="stores"]').val(); // 门店
            var url = $(this).attr('url')+"?order_status=" + order_status + "&pay_status=" + pay_status +"&receipt_status=" + receipt_status +"&order_keywords=" + order_keywords +"&start_time=" + start_time +"&end_time=" + end_time +"&start_money=" + start_money  +"&end_money=" + end_money +"&send_type=" + send_type +"&order_source=" + order_source +"&pay_type=" + pay_type +"&stores=" + stores;
             window.location.href=url;
        })
    }

    /**
     * 订单列表
     */
    main.prototype.index = function(){
    	this.time_sole();
    	this.hover_info();
    	this.all_confirm();
    	this.export_order();

        this.allExport_order(); //导出全部订单

    	tool.check_all("#check_all",".order_check");
    	tool.batch_del($('.order-del'),$('.order_check'));
    	send.index();
    }
    /**
     * 编辑订单
     */
    main.prototype.edit = function(){
    	this.tab_invoice();
    	this.invoice_click();
    	this.editSubmit();
    }
    /**
     * 订单备注
     */
    main.prototype.mark = function(){
    	require.async("jquery_validate",function(){
    		$('#seller_postscript').validate($.extend({
                /*rules: {
                	seller_postscript: {
                        required: true,
                    }
                },
                messages: {
                	seller_postscript: {
                        required: "订单备注不能为空",
                    }
                }*/
            },$.extend(tool.validate_setting,{
            	 submitHandler: function (form) {
                     tool.formAjax(form,function(data){
                         require.async('base/jtDialog',function(jtDialog){
                             if(data.status != tool.success_code){
                                 jtDialog.showTip(data.msg);
                             }else{
                            	 $("#myModal_remark").modal('hide');
//                                 jtDialog.showTip(data.msg,1,function(){
//                                	 //location.reload();
//                                	 
//                                 });
                             }
                         });
                         return false;
                     });
                 }
            })));
    	})
      };
      main.prototype.info = function(){
    	  this.mark();
    	  this.all_confirm();
    	  refund.index();
      }
      //订单打印
      main.prototype.printList = function(){
    	 $('button[name="confirmPrint"]').click(function(){
    		 require.async("pulgins/print/jquery.PrintArea",function(){
    			 $("#print").printArea();   
    		 })
    	 });
      }
    module.exports = new main();
});