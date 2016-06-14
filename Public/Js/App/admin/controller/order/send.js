/**
 * 发货
 */
define(function(require , exports ,module){
	var $ = require("jquery");
	var tool = require('model/tool');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
	//批量打印快递单
	$('.print-start').click(function(){
		var tipObj = $('.print_tip');
		if($('[name = "template"]').val() == 0){
			tipObj.show();
			return;
		}else{
			tipObj.hide();
		}
		 require.async("pulgins/print/jquery.PrintArea",function(){
			 $(".express-template").printArea();
	     })
	});
	var handle = {
			//选择默认快递
	      	checkDefault:function(){
	      		$("#add-logistics").on("click",".logistics",function(){
		      		if($(this).is(":checked")){
		      			$(this).parents(".checkbox").siblings().find(".logistics").attr("checked",false);
		      			$.uniform.update(".logistics");
		      			var val = $(this).val();
		      			$(".logistics-select").attr("readonly",true);
		      			$(".logistics-select").val(val);
		      		}else{
		      			$(".logistics-select").attr("readonly",false);
		      		}
		      	})
	      	},
	      	//添加运单号
	      	addSn:function(){
	      		/*$("#order-data").on("click",".add-sn",function(){
	      			var val = $(this).prev().val();
	      			$("#order-data .numbers").each(function(i,obj){
	      				if(i>0){
	      					val = parseInt(val)+1;
	      					$(obj).val(val);
	      				}
	      			})
	      		})*/
	      		$('#add-sender').on('click','#getSendCode',function(){
	      			var ware = $("#add-sender select[name='sender_people']").val();
	      			var orders = $('#order-data .order-id').serializeArray();
	      			tool.doAjax({
	      				url:common.U("SendGoods/getSendCode",{"ware_id":ware}),
	      				data:orders
	      			},function(result){
	      				if(result){
	      					for(i in result){
	      						var code_class = "n_"+result[i].order_id;
	      						$("."+code_class).val(result[i].code);
	      					}
	      				}
	      			})
	      		})
	      	},
	      	//文本验证
	      	validate:function(){
	      		//物流发货验证运单号
	      		$("#order-data").on("blur",".numbers",function(){
	      			if(!$(this).val()){
	      				$(this).parent().next().html("请填写运单号！");
	      			}else{
	      				$(this).parent().next().html("");
	      			}
	      		})
	      		//上门送货验证
	      		$("#door-order").on("change",".stores-user",function(){
	      			if($(this).val() && $(this).parents("td").next().find(".user-mobile").val()){
	      				$(this).parents("tr").find(".for-error").html("");
	      			}
	      		})
	      		$("#door-order").on("blur",".user-mobile",function(){
	      			if($(this).val()&&$(this).parents("td").prev().find(".stores-user").val()){
	      				$(this).parents("tr").find(".for-error").html("");
	      			}
	      		})
	      	},
	      	//订单商品alt显示
	      	altShow:function(){
	      		$(".table").on("mouseover",".goods-title",function(){
	      			$(this).parent().find(".goods-alt").show();
	      		})
	      		$(".table").on("mouseout",".goods-title",function(){
	      			$(this).parent().find(".goods-alt").hide();
	      		})
	      	},
	      	//根据门店获取配送员
	      	getUserByStores:function(){
	      		$(".table").on("change",".stores-name",function(){
	      			var stores_id = $(this).val();
	      			var _this = $(this);
	      			require.async('base/jtDialog',function(jtDialog){
		      			tool.doAjax({
		      				url:common.U('SendGoods/getStoresUser'),
		      				data:"stores_id="+stores_id
		      			},function(result){
		      				 if(result.status != tool.success_code){
                                 jtDialog.showTip(data.msg);
		      				 }else{
		      					 var data = result.result;
		      					 var str = "<option value=''>配送员</option>";
		      					 for(i in data){
		      						 str+="<option value='"+data[i].uid+"' data-mobile='"+data[i].mobile+"'>"+data[i].username+"</option>";
		      					 }
		      					_this.parent().next().find(".stores-user").html(str);
		      				 }
		      			})
	      			});
	      		})
	      	},
	      	//根据配送员获取手机号码
	      	getUserMobile:function(){
	      		$(".table").on("change",".stores-user",function(){
	      			var mobile = $(this).find("option:selected").attr('data-mobile');
	      			$(this).parent().next().find("input").val(mobile);
	      		})
	      	},
	      	//物流打印模版
	      	logisticsTemplate:function(){
	      		$("#add-template").on("change",".l-template",function(){
	      			var value = $(this).val();
	      			if(value == 0){
	      				return;
	      			}
	      			var sender = $("select[name='send_people']").val();
	      			var lc_id = $(this).find("option:selected").attr('data-logistic');
	      			if(lc_id){
//	      			  var data = $("#print .lc_"+lc_id).serializeArray();
	      			  var data = $("#print .lc_").serializeArray();
	      			}
		      		require.async('base/jtDialog',function(jtDialog){
		      				$("#print .for-error").each(function(i,obj){
		      					var object = $(obj).parent("tr").find(".print-order").find("input");
		      					if(!object.hasClass("lc_"+lc_id)){
		      						//$(obj).html("不符合快递模版！");
		      					}
		      				})
		      				//创建表单
		      				var str = "<input type='hidden' name='et_id' value='"+value+"'/>";
		      				str+= "<input type='hidden' name='sender' value='"+sender+"'/>";
		      				$("#print .lc_").each(function(i,obj){
		      					str+="<input type='hidden' name='print_order[]' value='"+$(obj).val()+"'/>";
		      				})
		      				$("#post-tmp").html(str);
		      				//通过ajax获取模板
			      			tool.doAjax({
			      				url:common.U("SendGoods/getTemplate",{"et_id":value,"sender":sender || 0}),
			      				data:data
			      			},function(result){
			      				if(result.status != tool.success_code){
	                                jtDialog.showTip(result.msg);
			      				 }else{
			      				    var data = result.result;
			      				    var template = "";
			      				    for(i in data){
			      				    	template += data[i];
			      				    }
			      				    $(".express-template").html(template);
			      				 }
			      			})
		      		})
	      		   
	      		})
	      	},
	      	//预览物流模版
	      	previewExpress:function(){
	      		$("#add-template").on('click',".preview-express",function(){
	      			if($('[name="template"]').val() == 0){
	      				$('.print_tip').show();
	      				return;
	      			}
	      			$('.print_tip').hide();
	      			$("#post-tmp").submit();
	      		})
	      	}
	      	
	};
	
	var send = {
			//发货弹出框
			send_stores : function(){
				$(".send-order").on('click',function(){
					var modal = $(this).attr("data-sn");
					var stores_id = "stores_id="+$(this).attr("data-stores");
					var url = common.U('Order/getStoresUser');
					$.post(url,stores_id,function(result){
						 require.async('base/jtDialog',function(jtDialog){
                             if(result.status != tool.success_code){
                                 jtDialog.showTip(result.msg);
                             }else{
                            	 var data = result.result;
                            	 var html = "";
                            	 for(i in data){
                            		 html+="<option value='"+data[i]['uid']+"' "+data[i]['selected']+">"+data[i]['username']+"</option>";
                            	 }
                            	 $("#myModal_"+modal).find("select").html(html);
                            	 $("#myModal_"+modal).modal('show');
                             }
                         });
					})
					return false;
				})
			},
			//执行发货
			send:function(){
				require.async("jquery_validate",function(){
					$(".modal_form").submit(function(){
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
			//发送订单提货码
			sendSort:function(){
				$(".send-sort").on("click",function(){
					var url = common.U("order/sendSort");
					var order_id = $(this).attr('data-id');
					require.async('base/jtDialog',function(jtDialog){
					tool.doAjax({
	    				  url:url,
	    				  data:"order_id="+order_id
	    			   },function(result){
	    				   if(result.status != tool.success_code){
	    					   jtDialog.showTip("更新失败！");
	    				   }else{
	    					   jtDialog.showTip(result.msg, 2, function () {
	                               location.reload();
	                            });
	    				   }
	    		       });
					})
				})
			},
			//物流发货ajax
			logisticsSend:function(callback){
				 $('#send-logistics').validate($.extend({
			        },$.extend(tool.validate_setting,
			          {
						   submitHandler: function (form) {
						 		 var str = "请填写运单号！";
								 var j = 0;
								 $("#order-data .numbers").each(function(i,obj){
									 if(!$(obj).val()){
										 $(obj).parent().next().html(str);
										j++;
									 }
								 })
								 if(j>0){
									 return false;
								 }
				                tool.formAjax(form,function(data){
				                    require.async('base/jtDialog',function(jtDialog){
				                        if(data.status != tool.success_code){
				                            jtDialog.showTip(data.msg);
				                        }else{
				                        	if(typeof(callback)=='function'){
				                        		callback();
				                        	}else{
				                        		jtDialog.showTip(data.msg,1,function(){
					                                location.href = document.referrer
					                            });   
				                        	}
				                        }
				                    });
				                    return false;
				                });
				          }	
			          }
			          )
			        )
			     );
			},
			//上门送货发货
			doorSend:function(callback){
				$('#send-doors').validate($.extend({
		        },$.extend(tool.validate_setting,
		          {
					   submitHandler: function (form) {
						     var str1 = "请选择配送员！";
					 		 var str2 = "请填写配送员联系方式！";
							 var j = 0;
							 $("#door-order .stores-user").each(function(i,obj){
								 if(!$(obj).val()){
									$(obj).parents("tr").find(".for-error").html(str1);
									j++;
								 }else{
									 if(!$(obj).parents("td").next().find(".user-mobile").val()){
										 $(obj).parents("tr").find(".for-error").html(str2); 
										 j++;
									 }
								 }
								 
							 })
							 if(j>0){
								 return false;
							 }
			                tool.formAjax(form,function(data){
			                    require.async('base/jtDialog',function(jtDialog){
			                        if(data.status != tool.success_code){
			                            jtDialog.showTip(data.msg);
			                        }else{
			                        	if(typeof(callback)=='function'){
			                        		callback();
			                        	}else{
			                        		 jtDialog.showTip(data.msg,1,function(){
					                                location.href = document.referrer
					                            });
			                        	}
			                           
			                        }
			                    });
			                    return false;
			                });
			          }	
		          }
		          )
		        )
		     );
			}
		
	};
	
	var modal = {
			//modal框尺寸设置
			modalSize:function(id){
				 $(id).modal('show').css({
	                    'margin-left': function () {
	                        return -($(this).width() / 2);
	                    }
	                });
			},
			//选中的值
			checkedValue:function(){
				var data = $(".order_check").serializeArray();
				require.async('base/jtDialog',function(jtDialog){
					if($.isEmptyObject(data)){
						jtDialog.showTip("请选择要操作的值");
						return false;
					}
				});
				return data;
			},
			//物流发货模版
			logisticModal:function(){
				$("#logistic-send").on("click",function(){
					var data  = modal.checkedValue();
					if(data == false){
						return false;
					}
					require.async('base/jtDialog',function(jtDialog){
					tool.doAjax({
						url:common.U("SendGoods/getLogOrder"),
						data:data
					},function(result){
						 if(result.status == tool.success_code){
							 var data = {
									 "logistics":result.result.logistics,
									 "data":result.result.order,
									 "sender":result.result.sender
							 };
						   var html = template("logistics",data);
						   $("#add-logistics").html(html);
						   var html2 = template("order",data);
						   $('#order-data').html(html2);
						   var html3 = template("sender",data);
						   $("#add-sender").html(html3);
						   base.initUniform(".logistics");
						   modal.modalSize("#logisticModel");
						 }else{
							 jtDialog.showTip(result.msg); 
						 }
					 });
					})
				})
			},
			//打印快递单
			printExpress:function(){
					var data  = modal.checkedValue();
					if(data == false){
						return false;
					}
					require.async('base/jtDialog',function(jtDialog){
						tool.doAjax({
							url:common.U("SendGoods/getPrintLogistics"),
							data:data
						},function(result){
							 if(result.status == tool.success_code){
								 var data = {
										 "data":result.result.order,
										 "express":result.result.express,
										// "sender":result.result.sender
								 };
								 var html = template("print-logistics",data);
								 $("#print").html(html);
								 var html2 = template("template",data);
								 $("#add-template").html(html2);
//								 var html3 = template("sender",data);
//								 $("#add-sender").html(html3);
								 modal.modalSize("#expressModel");
							 }else{
								 jtDialog.showTip(result.msg); 
							 }
						})
					})
			},
			//执行销售单打印
			doPrintSales:function(){
				 $('#all-print').click(function(){
		    		 require.async("pulgins/print/jquery.PrintArea",function(){
		    			 $(".print-sales").each(function(i,obj){
		    				 $(obj). printArea(); 
		    			 })   
		    	     })
				})
			},
			//执行快递单打印
			doPrintExpress:function(){
				$(".do-print-express").on("click",function(){
					 require.async("pulgins/print/jquery.PrintArea",function(){
		    			/* $("#express-view .content-print").each(function(i,obj){
		    				 $(obj). printArea(); 
		    			 })  */ 
						 $("#express-view").printArea();
		    	     })
				})
			},
			//打印发货单
			printSales:function(){
					var data = modal.checkedValue();
					if(data == false){
						return false;
					}
					var str = "";
					for(i in data){
					    str+="<input type='hidden' name='orders[]"+"' value='"+data[i].value+"'/>";
					}
					$('#order-sales').html(str);
					$("#order-sales").submit();
					return false;
			},
			//送货上门
			doorModal:function(){
				$("#door-send").on("click",function(){
					var data  = modal.checkedValue();
					if(data == false){
						return false;
					}
					require.async('base/jtDialog',function(jtDialog){
					tool.doAjax({
						url:common.U("SendGoods/delivery"),
						data:data
					},function(result){
						 if(result.status == tool.success_code){
							 var data = {
									 "stores":result.result.stores,
									 "data":result.result.order,
									 "user":result.result.StoresUser
							 };
							 var html = template("add-door",data);
							 $("#door-order").html(html);
							 modal.modalSize("#doorModel");
						 }else{
							 jtDialog.showTip(result.msg); 
						 }
					 });
					})
				})
			}	
	}
	
	//发货并打印操作
	var send_print = {
			//物流发货并打印发货单
			logisticsDevSale:function(){
				$(".l-send-print-sales").on("click",function(){
					send.logisticsSend(function(){
						$("#logisticModel").modal('hide');
						modal.printSales();
						require.async('base/jtDialog',function(jtDialog){
							 location.href = document.referrer
						})
					});
					 $('#send-logistics').submit();
				})
			},
			//物流发货并打印快递单
			logsticsDevExpress:function(){
				$(".l-send-print-express").on("click",function(){
					send.logisticsSend(function(){
						$("#logisticModel").modal('hide');
						modal.printExpress();
					});
					 $('#send-logistics').submit();
				})
			},
			//送货上门并打印发货单
			doorDevSale:function(){
				$(".d-send-print-sales").on("click",function(){
					send.doorSend(function(){
						modal.printSales();
						require.async('base/jtDialog',function(jtDialog){
							 location.href = document.referrer
						})
					});
					$('#send-doors').submit();
				})
			}
	};
	send.index=function(){
		this.send_stores();
    	this.send();
    	this.sendSort();
    	modal.logisticModal();
    	//打印快递单
    	$("#print-express").on("click",function(){
    		modal.printExpress();
    	})
    	//打印销售单
    	$("#print-sales").on("click",function(){
    		modal.printSales();
    	})
    	modal.doPrintSales();
    	modal.doPrintExpress();
    	modal.doorModal();
    	handle.checkDefault();
    	handle.addSn();
    	handle.validate();
    	handle.altShow();
    	handle.getUserByStores();
    	handle.getUserMobile();
    	handle.logisticsTemplate();
    	handle.previewExpress();
    	//物流发货
    	$(".send-logistics").on("click",function(){
    		send.logisticsSend();
    		$('#send-logistics').submit();
    	})
    	//上门送货
    	$(".do-send-door").on("click",function(){
    		send.doorSend();
    		$('#send-doors').submit();
    	})
    	//物流发货并打印发货单
    	send_print.logisticsDevSale();
    	//物流发货并打印快递单
    	send_print.logsticsDevExpress();
    	//送货上门并打印发货单
    	send_print.doorDevSale();
	}
	module.exports=send;
})