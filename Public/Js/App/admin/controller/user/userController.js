define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

   	/**
   	 * 添加管理员验证
   	 */
	var add_validate = function(){
        $('#user_edit').validate($.extend({
            rules: {
                username: {
                    required: true,
                    rangelength:[5,30]
                },
                pass:{
                    required: true,
                    minlength:5
                },
                email:{
                    email: true
                },
                mobile:{
                	mobile: true
                }
            },
            messages: {
                username: {
                    required: "用户名不能空"
                },
                pass:{
                    required: "密码不能空",
                    minlength:"密码最少 {0} 位数"
                },
                email:{
                    email: "请输入正确的email地址"
                },
                mobile:{
                	mobile: "请输入正确的手机号"
                }
            }
        },tool.validate_setting));
	};

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#user_edit').validate($.extend({
            rules: {
                username: {
                    required: true,
                    rangelength:[5,30]
                },
                pass:{
                    minlength:5
                },
                email:{
                    email: true
                },
                mobile:{
                	mobile: true
                }
            },
            messages: {
                username: {
                    required: "用户名不能空"
                },
                pass:{
                    minlength:"密码最少 {0} 位数"
                },
                email:{
                    email: "请输入正确的email地址"
                },
                mobile:{
                	mobile: "请输入正确的手机号"
                }
            }
        },tool.validate_setting));
    };
    suggestManage = function(){
    	common.date();
    }
    function addBlackList(){
    	postData = $('.user_check').serializeArray();
    	$.post(common.U('addBlackList'),postData,function(data){
    		require.async('base/jtDialog',function(jtDialog){
    			if(data.status != tool.success_code){
					jtDialog.showTip(data.msg);
				}else{
					 jtDialog.showTip(data.msg, 2, function () {
                        location.reload();
                     });
				}
    		});
    	});
    }
    //向某一组添加用户 或 删除用户
    function operateGroup(){
    	//向某一组添加用户 start
    	$('#addGroup').click(function(){
    		require.async('base/jtDialog',function(jtDialog){
	    		var data = $('#list').serializeArray();
	    		if($.isEmptyObject(data)){
	    			jtDialog.showTip('请选择用户');
	    			return false;
	    		}
	    		$('#help-tip').hide();
				$('#chooseGroup').modal('show');
    		})		
		});
		$('.choose_group_confirm').click(function(){
			var data = $('#list').serializeArray(),groupNameListObj = $('[name=groupNameList]'); //用户
			//某一会员组
			var groupId = $('[name=groupNameList]').val();
			if(groupId == ''){ //为空
				$('#help-tip').show();
				return;
			}
			data.push({name:'groupId',value:groupId});
			$.post(common.U('addGroupUsers'),data,function(data){
			    require.async('base/jtDialog',function(jtDialog) {
	                if (data.status !== tool.success_code) {
	                     jtDialog.error(data.msg);
	                } else {
	                	 jtDialog.showTip(data.msg,1,function(){
                             location.reload();
                         });
	                }
	            });
			});
    	});
    	//向某一组添加用户 end
		
    	//向某一组删除用户 start
		$('#removeUser').click(function(){
				require.async('base/jtDialog',function(jtDialog){
		    		var data = $('#list').serializeArray();
		    		if($.isEmptyObject(data)){
		    			jtDialog.showTip('请选择用户');
		    			return false;
		    		}
		    		var groupId = $('[name=groupId]').val();
		    		data.push({name:'groupId',value:groupId});
					$.post(common.U('removeGroupUsers'),data,function(data){
					    require.async('base/jtDialog',function(jtDialog) {
			                if (data.status !== tool.success_code) {
			                     jtDialog.error(data.msg);
			                } else {
			                	 jtDialog.showTip(data.msg,1,function(){
		                             location.reload();
		                         });
			                }
			            });
					});
	    		})
		});
    }
    function detailEvent(){
    	$('.detail').each(function(index){
    		var _self = $(this);
    		_self.mouseenter(function(){
    			_self.find('div').show();
    		}).mouseleave(function(){
    			_self.find('div').hide();
    		});
    	});
    }
	var main ={
		index : function(){
			$('.js-addBlackList').click(function(){
				addBlackList();
			});
			operateGroup();
			detailEvent();
            var coupon_data = {
                user:[],  //用户
                coupon:[] //优惠劵
            };
			tool.del($('.js-del'));
			tool.batch_del('.remove','.user_check','移除');
			tool.batch_del('.del','.user_check');
            tool.check_all("#check_all",".user_check");
            var jtDialog = require('base/jtDialog');
            var coupon_div = $('#js-coupon-list');
            $('.js-issuing-coupons').click(function(){
                var sel_user = [];
                $('.user_check').each(function(i){
                    var obj = $(this);
                    var val = obj.val();
                    if(obj.is(':checked')){
                        sel_user.push(val);
                    }
                });
                if(sel_user.length == 0){
                    jtDialog.alert('请先选择发放用户');
                    return;
                }
                coupon_data.user = sel_user;
                $('.js-issuing-user-count').text(sel_user.length);

                if(coupon_div.html().length == 0) {
                    var opt = {
                        url: common.U('Coupon/getListsJson')
                    };
                    common.doAjax(opt, function (data) {
                        if (data.status !== common.success_code) {
                            jtDialog.alert(data.msg);
                        } else {
                            var templates = require('template');
                            coupon_div.html(templates('tpl-coupon-list', {items: data.result})).show();
                        }
                    });
                }

                $('#issuing-coupons-view').modal('show').css({
                    'margin-left': function () {
                        return -($(this).width() / 2);
                    }
                });
            });

            coupon_div.on('click','.div_coupon',function(){//选中优惠劵
                var _self = $(this);
                var coupon_id = _self.data('id');
                var is_exist_index = false;
                $.each(coupon_data.coupon, function (key, val) {
                    if(val == coupon_id){
                        is_exist_index = key;
                        return false;
                    }
                });

                if(is_exist_index === false){
                    coupon_data.coupon.push(coupon_id);
                    _self.addClass('selected');
                }else{
                    coupon_data.coupon.splice(is_exist_index,1);
                    _self.removeClass('selected');
                }
            });

            //确认发放优惠劵
            $('#issuing-coupons-view .js-confirm').click(function(){
                if(coupon_data.user.length == 0){
                    jtDialog.alert('请先选择需要发放的用户');
                    return;
                }

                if(coupon_data.coupon.length == 0){
                    jtDialog.alert('请先选择需要发放的优惠劵');
                    return;
                }

                var opt = {
                    url: common.U('Coupon/issuing'),
                    data:{
                        coupon_id:coupon_data.coupon.join(','),
                        uid:coupon_data.user.join(',')
                    }
                };
                common.doAjax(opt, function (data) {
                    if (data.status !== common.success_code) {
                        jtDialog.alert(data.msg);
                    } else {
                        $('#issuing-coupons-view').modal('hide');
                        var success = data.result.success;
                        var fail = data.result.fail;
                        jtDialog.showTip('发放优惠劵成功'+success+'张，失败'+fail+'张');
                    }
                });
            });

            //导出全部会员
            $(".user-allExport").on('click',function(){
                var mix_keywords = $('input[name="mix_keywords"]').val() ? $('input[name="mix_keywords"]').val() : ''; //用户列表关键字
                var groupId = $('input[name="groupId"]').val() ? $('input[name="groupId"]').val() : ''; //用户列表关键字
                var from2 = $('input[name="from"]').val() ? $('input[name="from"]').val() : ''; //用户列表关键字
                var url = $(this).attr('url')+"?mix_keywords=" + mix_keywords+ "&grade_id=" + groupId + "&from" + from2;
                window.location.href=url;
           });

            tool.ajaxWithTip($('.remakInside'),'标记',1,$('[name="uid[]"]'));
		},
        add : function(){
            add_validate();
        },
        edit : function(){
            edit_validate();
            var area = require('pulgins/area/area.js');
            var area_config = {
                value: {
                    provice_id: $('#provice').data('id'),
                    city_id: $('#city').data('id'),
                    county_id: $('#county').data('id')
                }
            };
            area.init(area_config);
            setStatisticsTableCss();
            require.async('base/ajaxListComponent',function(list){
            	get_buy_goods(list);
            });
            require.async('controller/user/ajaxListComponent1',function(list){
              	getHistoryOrder(list);
            });
			require.async('controller/user/ajaxListComponent6',function(list){
              	get_crowd_goods(list);
            });
            require.async('controller/user/ajaxListComponent2',function(list){
            	get_shipping_address_list(list);
            });
            require.async('controller/user/ajaxListComponent3',function(list){
            	get_credits_list(list);
            });
            require.async('controller/user/ajaxListComponent4',function(list){
            	get_recharge_list(list);
            });
            require.async('controller/user/ajaxListComponent5',function(list){
            	get_recommend_list(list);
            });
		},
		suggestManage:function(){
			suggestManage();
		}
	};
	/**
     * 获取买过的普通商品列表
     */
	function get_buy_goods(list){
	    var config = {
	    		 	async:true,
	                page_id : 'buy_goods_page',
	                list_id : 'buy_goods_list',
	                list_tpl_id : 'tpl_buy_goods_list',
	                url : common.U('buyGoods',{'uid':$('#buy_goods_list').data('uid')}),
	                page_size:$('#buy_goods_list').data('pagesize')
	            };
	       list.init(config);
	       list.get_lists();
	}
	
	/**
     * 获取买过的众筹商品列表
     */
	function get_crowd_goods(list){
		var config = {
	    		 	async:true,
	                page_id : 'buy_crowd_page',
	                list_id : 'buy_crowd_list',
	                list_tpl_id : 'tpl_buy_crowd_list',
	                url : common.U('getCrowdGoods',{'uid':$('#buy_crowd_list').data('uid')}),
	                page_size:$('#buy_crowd_list').data('pagesize')
	            };
	   list.init(config);
	   list.get_lists();
	}
	
	
	/**
	 * 获取推荐会员列表
	 */
	function get_recommend_list(list){
		var config = {
				async:true,
				page_id : 'recommend_page',
				list_id : 'recommend_list',
				list_tpl_id : 'tpl_recommend_list',
				url : common.U('getRecommendList',{'uid':$('#recommend_list').data('uid')}),
				page_size:$('#recommend_list').data('pagesize')
		};
		list.init(config);
		list.get_lists();
	}
	/**
     * 获取充值日志
     */
	function get_recharge_list(list){
	     var config = {
	    		 	async:true,
	                page_id : 'recharge_page',
	                list_id : 'recharge_list',
	                list_tpl_id : 'tpl_recharge_list',
	                url : common.U('getRechargeLog',{'uid':$('#recharge_list').data('uid')}),
	                page_size:$('#recharge_list').data('pagesize')
	            };
	       list.init(config);
	       list.get_lists();
	}
	 /**
     * 获取积分日志
     */
	function get_credits_list(list){
	     var config = {
	    		 	async:true,
	                page_id : 'credits_page',
	                list_id : 'credits_list',
	                list_tpl_id : 'tpl_credits_list',
	                url : common.U('getAccountLog',{'uid':$('#credits_list').data('uid')}),
	                page_size:$('#credits_list').data('pagesize')
	            };
	       list.init(config);
	       list.get_lists();
	}
	 /**
     * 获取历史订单
     */
	function getHistoryOrder(list){
            var config = {
            	async:true,
                page_id : 'historyOrderPage',
                list_id : 'historyOrder',
                list_tpl_id : 'tpl_history_order',
                url : common.U('historyorderlist',{'uid':$('#historyOrder').data('uid')}),
                page_size:$('#historyOrder').data('pagesize')
            };
            list.init(config);
            list.get_lists();
	}
	function get_shipping_address_list(list){
		  var config = {
				  	async:true,
	                page_id : 'shipping_address_page',
	                list_id : 'shipping_address_list',
	                list_tpl_id : 'tpl_shipping_address_list',
	                url : common.U('getShippingAddressList',{'uid':$('#historyOrder').data('uid')}),
	                page_size:$('#shipping_address_list').data('pagesize')
	            };
	            list.init(config);
	            list.get_lists();
	}
	//设置会员信息统计表样式
	function setStatisticsTableCss(){
		$('.userStatisticsTable').find('tr:even').css({
			'background-color':'#F9F9F9'
		});
	}
	module.exports = main;
});