define(function(require,exports,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	require('jquery_validate');
	var main = {};
	main.index = function(){
		tool.check_all("#check_all",".order_check"); // 全选/反选
		$('.plusMinus').click(function(){
			var nextTrObj = $(this).closest('tr').next().find('table');
			if(nextTrObj.css('display') == 'none'){
				nextTrObj.fadeIn();
				$(this).removeClass('icon-plus').addClass('icon-minus');
			}else{
				nextTrObj.fadeOut();
				$(this).removeClass('icon-minus').addClass('icon-plus');
			}
		});
		tool.del($('.js-del-order'));
		tool.ajaxWithTip($('.check'),'审核通过');
		tool.ajaxWithTip($('.cancelCheck'),'审核不通过');
		$('[name = "jt_offline_check"]').change(function(){
			var _selfVal = $(this).val();
			var typeObj = $('[name = "jt_com_type"]');
			if(_selfVal == '' || typeObj.val() == 2){
				return;
			}
			typeObj.val(2);
		});
		$('[name = "jt_com_type"]').change(function(){
			var _self = $(this);
			if(_self != 2){
				$('[name = "jt_offline_check"]').val('');
			}
		});
		//打印快递单
		var express_print_obj = $('#express_print');
		$('.express_print').click(function(){
			express_print_obj.modal('show');
		});
		$('.js-confirm').click(function(){
			$('#js-express-print').trigger('submit');
		});
		common.date({startSelector:'[name = "express_print_start"]',endSelector:'[name = "express_print_end"]'});
		express_print_validate();
	
		$('.edit_form').validate($.extend({
			rules : {	
				receipt_name:{
					required:true
				},
				receipt_mobile:{
                    required:true,
                    mobilephone:true
                }
			},
			messages : {
				receipt_name:{
					required:'<font color = "red">收货人名称不能为空</font>'
				},
				receipt_mobile:{
					required:'<font color = "red">手机不能为空</font>',
					mobilephone:'<font color = "red">手机号格式有误</font>',
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
		$(function(){
			tool.ajaxWithTip($('.changeStauts'),'退款');
			tool.ajaxWithTip($('.adminPay'),'现金支付');
			$('.deliveryGood').click(function(){
				$('.errorMessage').html('');
				$('.godds_name').html($(this).data('name'));
				$('.order_sn a').html($(this).data('sn'));
				$('[name = "goodId"]').val($(this).data('goodid'));
				$('[name = "orderGoodId"]').val($(this).data('ordergoodid'));
				$('#devilvery').modal('show');
				if($(this).data('type') != 1){
					$('.logistics').hide();
				}
			});
			$('.js-confirms').click(function(){
				//检查字段合法性
				var html = '',type = $('[name="cor_delivery_types"]').val();
				var logisticsId = $('[name="logistics"]').val();
				var send_num = $('[name="send_num"]').val();
				var errorMessageObj = $('.errorMessage'),realName = $('[name="nickname"]').val();
				if(type == 1){ //普通快递
					if(!logisticsId){
						html = '物流公司不能为空';
					}else	if(!send_num){
						html = '快递单号不能为空';
					}
				}else{
					if(realName == ''){
						html = '发货人姓名不能为空';
					}
				}
				if(html != ''){
					errorMessageObj.html(html);
					return ;
				}
				var opt = {
						url:common.U('deliveryGood'),
						data:$('#devilvery_form').serializeArray()
				};
				tool.doAjax(opt,function(data){
		            require.async('base/jtDialog',function(jtDialog){
                        if(data.status != tool.success_code){
                            jtDialog.showTip(data.msg);
                        }else{
                        	location.reload();
                        }
                    });
				});
			});
		});

		/**
	    *   日期时间转为Date对象
	    */
	    function stringTime2date(stringDateTime){
	        return new Date(Date.parse(stringDateTime.replace(/-/g,   "/")));
	    }

	    /**
		 * 检验起始时间是否大于结束时间
		 * @param: string timeType 检验时间类型 sTime：开始时间 eTime：结束时间
	    */
	    function checkTime(timeType){
	    	var $startTime = $('#start-time');
			var $endTime = $('#end-time');
			if ($endTime.val()) {
				if (stringTime2date($startTime.val()) > stringTime2date($endTime.val())) {
					if(timeType == "sTime"){
						$endTime.val('');
					}else if(timeType == "eTime"){
						$startTime.val('');
					}
				}
			}
	    }

		// 下单时间 
		require.async('pulgins/bootstrap/datetimepicker/locales/zh-CN', function(dateTimer) {
			var now = new Date(); // 当前时间
			$('.form_datetime').datetimepicker({
				language: 'zh-CN', // 语言
				format: "yyyy-MM-dd hh:mm:ss", // 时间格式
				endDate: now, // 可选择的结束时间
			}).on('changeDate', function(event) {
				checkTime('sTime');
			});
			$('.end_datetime').datetimepicker({
				language: 'zh-CN',
				format: "yyyy-MM-dd hh:mm:ss",
				endDate: now,
			}).on('changeDate', function(event){
				checkTime('eTime');
			});
		});


		/**
	     *  获取子栏目
	     *  @param int parentId 父栏目id
	     *  @param string type 父栏目类型
	     *  @param string subNode 子栏目节点
	     */
	    function getSubSection(parentId, type, subNode){
	    	if('cr-name' === type){
	    		var url = '/CrowdfundingOrder/getPlanByCrowdId';
	    	}else if('delivery-type' === type){
	    		var url = '/CrowdfundingOrder/getStore';
	    	}

	        $.getJSON(url, {parentId : parentId}, function(result){
	            var $subNode = $(subNode);
	            $("option", $subNode).remove(); // 清空原有选项
	            if(result.code == 1){
	                $.each(result.data,function(index,array){ 
	                    var option = "<option value='"+array['id']+"'>"+array['name']+"</option>";
	                    $subNode.append(option); 
	                });
	            }
	        });
	    }

	    // 选择众筹项目时关联众筹方案
	    $("#cr-name").change(function(){
	    	getSubSection($(this).val(), 'cr-name', '#cd-id');
	    });

	    // 选择配送方式时关联自提门店
	    $("#delivery-type").change(function(){
	    	getSubSection($(this).val(), 'delivery-type', '#store-id');	
	    });

	    // 导出订单
	    $(".order-export").on('click',function(){
	    	var startTime = $('#start-time').val();
	    	var endTime = $('#end-time').val();
	    	var payStatus = $('#pay-status').val();
	    	var shippingStatus = $('#shipping-status').val();
	    	var crId = $('#cr-name').val();
	    	var cdId = $('#cd-id').val();
	    	var comType = $('#com-type').val();
	    	var deliveryType = $('#delivery-type').val();
	    	var storeId = $('#store-id').val();
	    	var insideUser = $('#inside-user').val();
	    	var uName = $('#user-name').val();
	    	var reName = $('#receipt-name').val();
	    	var reMobile = $('#receipt-mobile').val();
	    	var offlineCheck = $('#offline-check').val();
	    	var order_sn = $('#order-sn').val();
	    	// alert(insideUser);return false;

	    	var url1="?startTime="+startTime+"&endTime="+endTime+"&payStatus="+payStatus+"&shippingStatus="+shippingStatus
	    	 +"&crId="+crId+"&cdId="+cdId+"&comType="+comType+"&deliveryType="+deliveryType+"&storeId="+storeId+"&insideUser="
	    	 +insideUser+"&uName="+uName+"&reName="+reName+"&reMobile="+reMobile+"&offlineCheck="+offlineCheck+"&order_sn="+order_sn;

    		var data = "";
    		$(".order_check").each(function(i,v){
    			if($(v).is(":checked")){
    				data+=$(v).val()+",";
    			}
    		})
    		if(data){
	    		var url = $(this).attr('url')+"?ordersn="+data+url1;
	    		window.location.href=url;
    		}else{
    			/*require.async('base/jtDialog',function(jtDialog){
    				 jtDialog.showTip("请选择要导出的订单");
    			});*/
			    var url2 = $(this).attr('url')+url1;

    			$.ajax({
    				type:"post",
    				url:'getExportNum',
    				data:{'startTime':startTime,'endTime':endTime,'payStatus':payStatus,'shippingStatus':shippingStatus,
    					'crId':crId,'cdId':cdId,'comType':comType,'deliveryType':deliveryType,'storeId':storeId,
    					'insideUser':insideUser,'uName':uName,'reName':reName,'reMobile':reMobile,'offlineCheck':offlineCheck,
    					'order_sn':order_sn
    				},
    				success:function(data){
    					if(data>1000){
    						if(confirm('导出数量最大为1000条，请筛选后导出！')){
			    		 		window.location.href=url2;
			    			}
    					}else{
			    		 	window.location.href=url2;
    					}
    				}
    			}); 			
    		}
    	})

	}
	express_print_validate = function(){
		$('#js-express-print').validate($.extend({
			rules : {	
				express_print_start:{
					 required: '#express_print_type:checked',
					 dateISO:true					 
				},
				express_print_end:{
					required: '#express_print_type:checked',
					dateISO:true
				},
				logisticsCompanyId:{
					required: true,
                },
			},
			messages : {
				express_print_start:{
				   required:'<font color = "red">必填项</font>',			
				   dateISO:'<font color = "red">必填项</font>',			
				},
				express_print_end:{
					required:'<font color = "red">必填项</font>',			
				    dateISO:'<font color = "red">必填项</font>',			
                },
                logisticsCompanyId:{
                	required:'<font color = "red">请选择模板</font>'
                }
			}
		},tool.validate_setting,{
			 submitHandler: function (form) {
	                tool.formAjax(form,function(data){
	                    require.async('base/jtDialog',function(jtDialog){
	                        if(data.status != tool.success_code){
	                            jtDialog.showTip(data.msg);
	                        }else{
                            	var printObj = $('.print-hidden');
                            	printObj.html(data.result.print_content);
                            	//写入打印区开启打印
                            	//执行快递单打印				
                    			 require.async("pulgins/print/jquery.PrintArea",function(){
                    				 printObj.printArea();
                        	     })		
	                        }
	                    });
	                    return false;
	                });
	            }
		}));
	}
	main.edit = function(){
		$('#edit_form').validate($.extend({
			rules : {	
				receipt_name:{
					required:true
				},
				receipt_mobile:{
                    required:true,
                    mobilephone:true
                }
			},
			messages : {
				receipt_name:{
					required:'<font color = "red">收货人名称不能为空</font>'
				},
				receipt_mobile:{
					required:'<font color = "red">手机不能为空</font>',
					mobilephone:'<font color = "red">手机号格式有误</font>',
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
		$(function(){
			tool.ajaxWithTip($('.changeStauts'),'退款');
			tool.ajaxWithTip($('.adminPay'),'现金支付');
			$('.deliveryGood').click(function(){
				$('.errorMessage').html('');
				$('[name = "goodId"]').val($(this).data('goodid'));
				$('[name = "orderGoodId"]').val($(this).data('ordergoodid'));
				$('#devilvery').modal('show');
			});
			$('.js-confirm').click(function(){
				//检查字段合法性
				var html = '',type = $('[name="cor_delivery_type"]').val();
				var logisticsId = $('[name="logistics"]').val();
				var send_num = $('[name="send_num"]').val();
				var errorMessageObj = $('.errorMessage'),realName = $('[name="nickname"]').val();
				if(type == 1){ //普通快递
					if(!logisticsId){
						html = '物流公司不能为空';
					}else	if(!send_num){
						html = '快递单号不能为空';
					}
				}else{
					if(realName == ''){
						html = '发货人姓名不能为空';
					}
				}
				if(html != ''){
					errorMessageObj.html(html);
					return ;
				}
				var opt = {
						url:common.U('deliveryGood'),
						data:$('#devilvery_form').serializeArray()
				};
				tool.doAjax(opt,function(data){
		            require.async('base/jtDialog',function(jtDialog){
                        if(data.status != tool.success_code){
                            jtDialog.showTip(data.msg);
                        }else{
                        	location.reload();
                        }
                    });
				});
			});
		});
	}
	
	main.addOrder = function(){
		require("./addOrder.js");
	}
	main.print = function(){
		$("#print").on("click",function(){
			require.async("pulgins/print/jquery.PrintArea",function(){
				 $("#print-body"). printArea();  
	        })
		})
	}
	main.detail = function(){
		$('.fatherPosition').hover(function(){
			$(this).find('.childPosition').fadeIn();
		},function(){
			$(this).find('.childPosition').fadeOut();
		});
		$('.print_express_bill').click(function(){
			//执行快递单打印				
			var printObj = $(this).closest('td').find('.print-hidden');
			 require.async("pulgins/print/jquery.PrintArea",function(){
				 printObj.printArea();
    	     })				
		});
	}
	module.exports = main;
});