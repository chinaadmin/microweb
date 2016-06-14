define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');

	require('jquery_validate');
    var tool = require('model/tool');

    jQuery.validator.addMethod("time_required", function(value, element, param) {
        if($('#start_time').val() == ''||$('#end_time').val() == ''){
            return false;
        }
        return true;
    }, "请选择投放时间");

    /**
     * 修改规则验证
     */
    var edit_validate = function(){
    	$('#user_edit').validate($.extend({
            ignore:'',
            rules: {
                name: {
                    required: true
                },
                sel_time:{
                    time_required:true
                },
                money: {
                	required: true
                }
            },
            messages: {
                name: {
                    required: "优惠劵名称不能为空"
                },
                money: {
                	required: "面值不能为空"
                }
            }
        },tool.validate_setting));
    };

    //过期时间
    function put_in_time(){
        require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true
            }).on('changeDate',function(ev){
                if(ev.date.valueOf() > new Date($('#end_time').val()).getTime()){
                    $('#end_time').val('');
                }
                $('.end_time').datepicker('setStartDate', new Date(ev.date));
            });
            $('.end_time').datepicker({
                autoclose:true,
                startDate:$('#start_time').val()
            });
            /*$('#sel_time').change(function(){
                $('#end_time').val($(this).val());
                $('.end_time').datepicker('update');
            });*/
        });
    }
 //商品弹框start
    //选择商品面板
    function goods_portlet(){
 	   $('#choose_goods').click(function(){
 		   $('#goods-modal').modal('show');
 	   });
 		$('#serach_choose').click(function(){
 			var data = {};
 			var cat_id = $('[name = cat_id]').val();
 			var name = $('[name = goods_name]').val();
 			if(cat_id > 0){
 				data.cat_id = cat_id;
 			}
 			if(name){
 				data.name = name;
 			}
 			ajaxList(data);
 		});
 		ajaxList();
 		$('#onlyChoosed').click(function(){
 			var selfCheck = $(this).is(':checked');
 			$('#goodsList [type = checkbox]').each(function(index){
 				var _self = $(this);
 					if(selfCheck){
 						_self.closest('tr')[_self.is(':checked') ? 'show' : 'hide']();
 					}else{
 						_self.closest('tr').show();
 					}
 			});
 		});
 		clearGoodsList();
 		$('#serach_goods').click(function(){
 			var search = $('[name = goods_key]').val();
 			if(!search){
 				$('#choosed_list_body :hidden').show();
 				return false;
 			}
 			$('#choosed_list_body td').each(function(index){
 				var _self = $(this),searchIndex,reg;
 				reg = new RegExp(search);
 				searchIndex = _self.text().search(reg);
 				if(searchIndex == '-1'){ //未找到
 					_self.closest('tr').hide();
 				}
 			});
 		});
 		$('.js-goods-confirm').click(function(){
 			dealGoodsId();
 			$('#goods-modal').modal('hide');
 		});
 		$('.boxClose').click(function(){
 			delGoods($(this));
 		});
    }

    function dealGoodsId(){
 	   this.getGoodsId = function(){
 		   //获取商品id
 		   var goodsId = [];
 		   $('#choosed_list_body tr').each(function(){
 			   var _self = $(this),thisClass;
 			   thisClass = _self.attr('class')
 			   goodsId.push(thisClass.match(/\d+/)[0]);
 		   });
 		   return  goodsId.join(',');
 	   }
 	   //写入dom
 	   $('[name=goods_ids]').val(this.getGoodsId());
    }
    var oldCheckAllDom = $('#checkAll input');
    //ajax获取商品列表
    function ajaxList(data){
 	   var data = data || {};
 	   var list = require('base/ajaxListComponent');
 	     var config = {
 	                    page_id : 'goodsListPage', //分页控件id
 	                    list_id : 'goodsList', //加载列表id
 	                    list_tpl_id : 'goodsListPageTpl', //模板id
 	                    url : common.U('user/goodsList',data), //请求路径
 	                    page_size:10,
 	                    async:false
 	                };
 	     list.init(config);
 	     list.before_events = function(){
 	    	 //重新删除并重新生成元素防止元素事件重复绑定
 	    	 $('#checkAll').html(oldCheckAllDom);
 	     };
 	     list.once_after_events = function(){
 	    	 var myTool = require('model/tool');
 	    		 myTool.check_all('.goodsListCheckAll','.goodsListCheckOne');
 	    		 //复选框事件 增加已选商品
 		    	 $('.goodsListCheckOne').click(function(){
 		    		 var value;
 		    		 value = $(this).val();
 		    		 if( $(this).prop("checked") == true ){
 		    			 addGoods({value:value,html:$(this).closest('td').next().html()});
 		    		 }else{
 		    			 $('.goodsRemoveLeft'+value).remove();
 		    		 }
 		    	 });
 	     };
 	     list.get_lists();
 	}
    function addGoods(data){
   	 	var str = "<tr class = 'goodsRemoveLeft"+data.value+"'><td>"+data.html+"<i class = 'close myClose'></i></td></tr>"
   	 	var obj = $(str);
   	 	removeGoods(obj.find('.myClose')); //绑定移除事件
   	 	$('#choosed_list_body').append(obj);
    }
    //删除已经有的商品同时取消选中状态
    function delGoods(obj){
    	var trObj,remarkClass;
			trObj = obj.closest('tr');
			remarkClass = trObj.attr('class');
			trObj.remove();//移除商品
			//关闭选中状态 goodsRemoveRight59
			var match = remarkClass.match(/\d+/)
			remarkClass = 'goodsRemoveRight' + match[0];
			$('.'+remarkClass).prop('checked',false);
    }
 //   绑定移除事件
    function removeGoods(obj){
 		obj.unbind('click').click(function(){
 			delGoods($(this));
 		});
   }
    //清空已选商品列表
    function clearGoodsList(){
 	   $('.clearGoodsList').click(function(){
 		   $('#choosed_list_body').find('.myClose').each(function(index){
 			   var _self = $(this);
 			   _self.trigger('click');
 		   });
 		   $('#choosed_list_body').html('');
 	   });
    }
//    商品弹框end
	var main ={
        //显示隐藏优惠劵详情
        hover_info : function(){
            $('.order-code').hover(function(e){
                e.stopPropagation();
                $(this).find('.goods-info').show();
            },function(){
                $(this).find('.goods-info').hide();
            })
        },
		index : function(){
            this.hover_info();
			tool.del($('.js-del'));
		},
        edit : function(){
            edit_validate();
            put_in_time();
            colorPicker();
            goods_portlet();
            $('select[name="type"]').change(function(){
                var type_val = $(this).val();
                $('.js-type').hide().each(function(){
                    var id = $(this).data('id');
                    if(id==type_val){
                        $(this).show();
                    }
                });
            });

            $('input[name="grant_rule"]').click(function(){
                var val = $(this).val();
                if(val == 2){
                    $('#js-user-receive').show();
                }else{
                    $('#js-user-receive').hide();
                }
            });

            //颜色选择器
            function colorPicker(){
                require("pulgins/color-picker/spectrum.css");
                require.async("pulgins/color-picker/spectrum.js",function(){
                    $("#custom").spectrum({
                        color: bd_color,
                        preferredFormat: "hex",
                        showInput: true,
                        chooseText:"选择",
                        cancelText: "取消",
                        /* change: function(color) {
                         color.toHexString(); // #ff0000
                         }*/
                    });
                })
            }

		},
        code:function(){
            tool.del($('.js-del'));
        }
	};
	module.exports = main;
});
