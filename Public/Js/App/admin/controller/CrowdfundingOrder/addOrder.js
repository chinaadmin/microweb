define(function(require,exports,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	require('jquery_validate');
	var template = require('template');
	//第一步检查用户
	var step1 =function(){
		$('#from_edit1').validate($.extend({
			rules : {	
				username:{
					required:true
				}
			},
			messages : {
				username:{
					required:'请填写用户名！'
				}
			}
		},tool.validate_setting,{
			 submitHandler: function (form) {
	                tool.formAjax(form,function(data){
	                    require.async('base/jtDialog',function(jtDialog){
	                        if(data.status != tool.success_code){
	                            jtDialog.showTip(data.msg);
	                        }else{
	                            form.submit();	                      
	                        }
	                    });
	                    return false;
	                });
	            }
		}));
	}();
	
	$(".recaddress").on("click",".js-recaddress-item",function(){
		$(this).addClass("active").siblings(".js-recaddress-item").removeClass("active");

		var addressId = $(this).data('id');
		$("input[name='addressId']").val(addressId); //确认收货地址选择地址时更新隐藏域addressId的值
	})
	
	//添加编辑收获地址
	var add_address = function(){
		$(".recaddress").on("click",".edit-address",function(){
			var data = data || {};
			var  id = $(this).parents(".js-recaddress-item").attr("data-id");
			var index = $(this).parents(".js-recaddress-item").attr("data-key");
			if(index>-1){
				data = recaddress[index];
				$("input[name='address_id']").val(data.id);
				$("input[name='name']").val(data.name);
				$("input[name='mobile']").val(data.mobile);
				$("input[name='user_detail_address']").val(data.address);
			}else{
				$("input[name='name']").val("");
				$("input[name='mobile']").val("");
				$("input[name='user_detail_address']").val("");
				$("input[name='address_id']").val(0);
			}
			var area = require('pulgins/area/area.js');
	        var area_config = {
	            value: {
	                provice_id: data.provice||0,
	                city_id: data.city||0,
	                county_id: data.county||0,
	                stores_id: data.stores_id||0
	            }
	        };
	        area.init(area_config);
			$("#address-modal").modal('show').css({
	            'margin-left': function () {
	                return -($(this).width() / 2);
	            }
	        });
		})
	}();
	//更新收货地址
	var update_address = function(){
		$(".submit-address").on("click",function(){
			$('#form-edit-address').submit();
		})
		$('#form-edit-address').validate($.extend({
			rules : {	
				name:{
					required:true
				},
				mobile:{
					required:true,
					mobile:true
				},
				provice:{
					required:true
				},
				city:{
					required:true
				},
				county:{
					required:true
				},
				user_detail_address:{
					required:true
				}
			},
			messages : {
				name:{
					required:'请填写收货人姓名！'
				},
				mobile:{
					required:"请填写收货人手机号"
				},
				provice:{
					required:"请选择所在省份"
				},
				city:{
					required:"请选择所在城市"
				},
				county:{
					required:"请选择所在县区"
				},
				user_detail_address:{
					required:"请填写详细地址"
				}
			}
		},tool.validate_setting,{
			 submitHandler: function (form) {
	                tool.formAjax(form,function(data){
	                    require.async('base/jtDialog',function(jtDialog){
	                        if(data.status != tool.success_code){
	                            jtDialog.showTip(data.msg);
	                        }else{
	                        	recaddress = data.result;
	                        	var result = {"data":data.result};
	    						var html = template("js-address",result);
	    						$(".address-content").html(html);
	                        	$("#address-modal").modal('hide');
	                        }
	                    });
	                    return false;
	                });
	            }
		}));
	}();
	
	//删除收货地址
	var del_address = function(){
		$(".recaddress").on("click",".js-del",function(){
			var obj = $(this).parents(".js-recaddress-item");
			var id = obj.attr("data-id");
			tool.doAjax({
				url:common.U("CrowdfundingOrder/delrecaddress",{'address_id':id})
			},function(data){
				 require.async('base/jtDialog',function(jtDialog){
	                 if(data.status != tool.success_code){
	                     jtDialog.showTip(data.msg);
	                 }else{
	                 	 obj.remove();
	                 }
	             });
			})
		});
	}();
	//第二步添加收获
	$("#from_edit2").submit(function(){ 
		var uid = $("input[name='uid']").val();
		var address_id = $(".address-content .active").attr("data-id");
		var re = '';
		 require.async('base/jtDialog',function(jtDialog){
			 if(!uid){
				 jtDialog.showTip("请选择用户！");
				 re=1;
				 return false;
			 }
			 if(!address_id){
				 jtDialog.showTip("请选择收货地址！");
				 re=1;
				 return false;
			 }else{
				 $("input[name='addressId']").val(address_id);
			 }
         });
		 if(re){
	     	 return false;
	     }
	});
	
	//方案
	var scheme = {
			init:function(){
				this.getScheme();
				this.changeChips();
				this.checkScheme();
				this.checkGoods();
			},
			//切换方案
			changeChips:function(){
				var _this = this;
				$("select[name='chips']").change(function(){
					_this.getScheme();
				})
			},
			//获取方案
			getScheme:function(){
				var id = $("select[name='chips']").val();
				tool.doAjax({
					url:common.U('CrowdfundingOrder/getScheme',{'crId':id})
				},function (result){
					var data = result.result;
					if(data){
						var str = "";
						for(i in data){
							str+="<a href='#' data-id="+data[i].cd_id+" class='btn blue'>"+data[i].cd_name+"</a>";
						}
						$("#schemes").html(str);
					}else{
						$("#schemes").html("");
					}
					$("#add-scheme-goods").html("");
				})
			},
			//选择方案
			checkScheme:function(){
				$("#schemes").on("click","a",function(){
					$(this).addClass("green").siblings("a").removeClass("green");
					var id = $(this).attr('data-id');
					if(id != $("#add-scheme-goods input[name='scheme']").val()){
						$("#add-scheme-goods").html("");
					}
					$("#scheme-table").attr('data-id',id);
					tool.doAjax({
						url:common.U("CrowdfundingOrder/chipsGoods",{"cdId":id})
					},function(result){
						var data = result.result.goods;
						var html = "";
						for(i in data){
							html+="<tr><td><input class='goods_id' type='checkbox' value='"+data[i].cgId+"'/></td>";
							html+="<td>"+data[i].name+"</td>";
							html+="<td><input type='text' class='span3' name='num' value='1'/></td>";
							/*html+="<td>"+data[i].price+"</td></tr>";*/

							//添加备选方案
							html += "<td><select name='backUpGoods'><option>请选择备选商品</option>";
							for(j in data[i].alternativegoods){
							 html += "<option value=" + data[i].alternativegoods[j].cg_id + ">" + data[i].alternativegoods[j].cg_goods_name + "</option>";
							}
							html += "</select></td>";


						}
						$("#scheme-goods").html(html);
						$("#scheme-modal").modal('show').css({
				            'margin-left': function () {
				                return -($(this).width() / 2);
				            }
				        });
					})
				})
			},
			//选择方案商品
			checkGoods:function(){
				$(".check-scheme-goods").on('click',function(){
					var str = "";
					$("#scheme-goods .goods_id:checked").each(function(i,dom){
						var id = $(dom).val();
						if(id){
							var name = $(dom).parent().next().text();
							var num = $(dom).parent().next().next().find('input').val();
							str+="<input type='hidden' name='cgIds["+id+"]' value='"+num+"'/>";
							str+="<p>商品名称："+name+" 数量："+num+"</p>";

							//备选商品
							var backUpGoods = $(dom).parent().next().next().next().find("option:selected").text();
							var bgid = $(dom).parent().next().next().next().find("select").val();
							str+="<input type='hidden' name='bgIds["+ bgid + "_" + id +"]' value='"+num+"'/>";
							str+="<p>备选："+ backUpGoods;
							
						}
					});
					if(str){
						var scheme = $("#scheme-table").attr('data-id');
						str+="<input type='hidden' name='scheme' value='"+scheme+"'/>";
						$("#add-scheme-goods").html(str);
						$("#scheme-modal").modal('hide');
					}else{
						 require.async('base/jtDialog',function(jtDialog){
							 jtDialog.showTip("请选择商品!");
				         });
					}
				})
			}
	}
	scheme.init();

	//地推
	var staff = {
		init:function(){
			this.staffBox();
			this.checkStaff();
			this.searchStaff();
		},

		//弹出选择地推框
		staffBox:function(){
			$("#spread").on('click', function(){
				tool.doAjax({
					url:common.U("CrowdfundingOrder/getStaff")
				},function(result){
					var data = result.result.staff;
					var html = "";
					for(i in data){
						html+="<tr><td><input type='radio' name='chooseStaff' value='" + data[i].uid +  "' data-username='" + data[i].username + "'  data-mobile='" + data[i].mobile + "' /></td>";
						html+="<td style='text-align:center'>" + data[i].username + "</td>";
						html+="<td style='text-align:center'>" + data[i].mobile + "</td></tr>";
					}
					$("#staff_tb").html(html);
					$("#mix_keywords").val("");
					$("#spread-modal").modal('show').css({
				       	'margin-left': function () {
				       		return -($(this).width() / 2);
				       	}
				    });
				})
			})
		},

		//选择地推
		checkStaff:function(){
			$("#closeSpread").on('click',function(){
				var uid = $("input[type='radio']:checked").val();
				var username = $("input[type='radio']:checked").data("username");
				var mobile = $("input[type='radio']:checked").data("mobile");
				$("#staff_uid").val(uid);
				$("#staff_name").html(username);
				$("#staff_mobile").html(mobile);
				$("#spread-modal").modal('hide');
			})
		},

		//查询地推人员
		searchStaff:function(){
			$("#search_staff").click(function(){
				var mix_keywords = $("#mix_keywords").val();
				$.get("/CrowdfundingOrder/searchStaff", {mix_keywords : mix_keywords}, function(data){
					$("#staff_tb").empty();
					var html = '';
					$.each(data.staff, function(k, v){
						html +='<tr><td><label><input type="radio" name="chooseStaff" value="' + v.uid + '" data-username="' + v.username + '" data-mobile="' + v.mobile + '" /></label></td><td style="text-align:center">' + v.username + '</td><td style="text-align:center">' + v.mobile + '</td></tr>';
						$("#staff_tb").html(html);
					});
				}, "json");
			})
		}
	}
	staff.init();

	//选择合同签订时间
	var times = {
		init:function(){
			this.chooseTime();
		},
		chooseTime:function(){
			require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
                $('.cor_sign_time').datepicker({
                    autoclose:true
                });
            });
		}
	}
	times.init();

	//检测推荐人是否已存在
	var referee = {
		init:function(){
			this.checkReferee();
		},
		checkReferee:function(){
			$('input[name="refereeMobile"]').on('change', function(){
				var refereeMobile = $(this).val();
				$.get('/CrowdfundingOrder/checkRefereeMobile', {refereeMobile:refereeMobile}, function(result){
					if(result.code == 2){
						var html = "<a class='btn green' href='/user/edit?refereeMobile="+ refereeMobile +"' target='_blank'>添加 + </a>";
						$(".controls #addReferee").html(html);
					}else{
						$(".controls #addReferee").html("");
					}
				});
			});
		}
	}
	referee.init();

	//检测用户是否已存在
	var userObj = {
		init:function(){
			this.checkUser();
		},
		checkUser:function(){
			$('input[name="username"]').on('change', function(){
				var username = $(this).val();
				$.get('/CrowdfundingOrder/checkUser', {username:username}, function(result){
					if(result.code == 2){
						$("label.ok").remove();
						require.async('base/jtDialog',function(jtDialog){
							jtDialog.showTip(result.msg);
	                    });
						var html = "<a class='btn green' href='/user/edit?username=" + username + "' target='_blank'>添加用户 + </a>";
						$(".controls #addUser").html(html);
					}else{
						$(".controls #addUser").html("");
					}
				});
			});
		}
	}
	userObj.init();

	//刷新 检测用户是否存在
	$("#refreshUser").click(function(){
		var username = $('input[name="username"]').val();
		$.get('/CrowdfundingOrder/checkUser', {username:username}, function(result){
			if(result.code == 2){
				$("label.ok").remove();
				require.async('base/jtDialog',function(jtDialog){
					jtDialog.showTip(result.msg);
	            });
				var html = "<a class='btn green' href='/user/edit?username=" + username + "' target='_blank'>添加用户 + </a>";
				$(".controls #addUser").html(html);
			}else{
				$(".controls #addUser").html("");
			}
		});
	});

	$("select[name='shipping_type']").change(function(){
		var value = $(this).val();
		if((value == 0) && value != ''){
			$("#stores").show();
		}else{
			$("#stores").hide();
		}
	})
	//第三步 提交订单
	var submitOrder = function(){
		$('#from_edit3').validate($.extend({
			rules : {	
				chips:{
					required:true
				},
				scheme:{
					required:true
				},
				// refereeMobile:{
				// 	mobile:true
				// },
				pay_type:{
					required:true
				},
				shipping_type:{
					required:true
				}
			},
			messages : {
				chips:{
					required:'请选择项目！'
				},
				scheme:{
					required:"请选择方案"
				},
				pay_type:{
					required:"请选择请选择支付方式"
				},
				// refereeMobile:{
				// 	mobile:"请填写正确的手机号！"
				// },
				shipping_type:{
					required:"请选择配送方式！"
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
	                        		location.href = common.U("CrowdfundingOrder/index")
	                            });
	                        }
	                    });
	                    return false;
	                });
	            }
		}));
	}();
	//module.exports = addOrder;
});