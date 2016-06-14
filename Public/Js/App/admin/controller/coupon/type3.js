//显示活动
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
    var templates = require('template');
	require('jquery_validate');
    var tool = require('model/tool');
   //选择商品
    function put_in_time(){
        require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true,
            }).on('changeDate',function(ev){
                if(ev.date.valueOf() > new Date($('.end_time').val()).getTime()){
                    $('.end_time').val('');
                }
                $('.end_time').datepicker('setStartDate', new Date(ev.date));
            });
            $('.end_time').datepicker({
                autoclose:true,
                startDate:$('.start_time').val()
            });
        });
    }
    
/*****************************商品弹框start*******************************************/
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
 		    			addGoodsShow({value:value,html:$(this).closest('td').next().html()});
 		    		 }else{
// 		    			$('.goodsRemoveLeft'+value).remove();
 		    			 delGoods($('.goodsRemoveLeft'+value).find('.myClose'));
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
			//删除表单的显示
			delGoodsShow(match);
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
    //表单内添加已选商品展示
    function  addGoodsShow(data){
    	var str = "<tr class = 'checkedGoods"+data.value+"'><td>"+data.html+"</td>";
    	if(pro_type==3){
    	   //商品计时
			str+='<td class="valid_time"><div class="input-append date date-picker start_time">';
	        str+='<input  name="goods_start_time['+data.value+']" type="text" class="span9 start_time m-wrap m-ctrl-medium date-picker" readonly="" size="16" value="">';
	        str+='<span class="add-on"><i class="icon-calendar"></i></span></div>~';
	        str+='<div class="input-append date date-picker end_time">';       
	        str+='<input name="goods_end_time['+data.value+']" type="text" class="span9 end_time m-wrap m-ctrl-medium date-picker" readonly="" size="16" value="">';      
	        str+='<span class="add-on"><i class="icon-calendar"></i></span></div><input type="hidden" name="sel_time"></td>';                   
	    	//商品价格
	        str+='<td><input type="text" class="m-wrap span6" name="goods_price['+data.value+']"/></td>';
    	}
    	str+='<td class="delGoods" data='+data.value+' style="cursor:pointer">删除</td>';
        if(pro_type==3){
        	str+='<td class="valid_status"></td>';
        }
        str+='<input type="hidden" name="goods_cheked_id[]" value="'+data.value+'"/>';
    	str+='</tr>'
    	$('#goods-info tbody').append(str);
    	put_in_time();
    }
    //删除表格里的数据
    function delGoodsShow(value){
    	$('#goods-info tbody .checkedGoods'+value).remove();
    }
    $('#goods-info tbody').on('click','td.delGoods',function(){
    	var value = $(this).attr('data');
    	delGoods($('#choosed_list_body .goodsRemoveLeft'+value).find('.close'));
    })
/*****************************商品弹框end*******************************************/
    
    
    
    
    
    var type = {
    	   goods:function(){  //全部商品或部分商品
    		   function isAllGoods(){
    			   var val =  $("input[name='all_goods']:checked").val();
    			   if(val=='1'){
    				   $("#check_good").hide();
    			   }else{
    				   $("#check_good").show();
    			   } 
    			   if(pro_type==3){
    				   if(val=='1'){
        				   $("#goods_discount").show();
        			   }else{
        				   $("#goods_discount").hide();
        			   }  
    			   }
    		   }
    		   $("input[name='all_goods']").on('click',function(){
    			   isAllGoods(); 
    		   })
    		   isAllGoods();
    	   }
    };
    var vaild = function(){
    	
    }
    var time_limit = {
    		index:function(){
    			 goods_portlet();
    			 type.goods();
    		}
    };
    module.exports = time_limit; 
});