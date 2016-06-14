define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');
    var main = {};
    var edit_validate = function(){
        $('#saveGroup').validate($.extend({
            rules: {
                name: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "分组名称不能空"
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
                                location.href = common.U('group_operate');
                            });
                        }
                    });
                    return false;
                });
            }
        }));
    };
    //添加窗口 验证及事件
    var addDialog_validate = function(){
    	var addDialogValidate = $('#addDialog').validate($.extend({
            rules: {
            	group_name: {
                    required: true
                }
            },
            messages: {
            	group_name: {
                    required: "分组名称不能空"
                }
            }
        },tool.validate_setting));
    	$('.add').click(function(){
    		$('#choosed_list_body tr').each(function(index){
    			//start
    		});
         	$('#member-modal').modal('show');
         });
    	$('.js-confirm').click(function(){
    		if(addDialogValidate.form()){
    			$('#addDialog').submit();
    			$('#member-modal').modal('hide');
    		}
    	});
    }
   function myClose(_selfJquery){
	   var obj = _selfJquery.closest('.portlet');
	   //关闭属性块
	  var classremark = obj.data('triggerbock');
	   if(obj.css('display') == 'none'){
		  obj.data('showSwitch','on');
		  obj.fadeIn('slow');
		  $("[classremark = "+classremark+"]").addClass('active');
	  }else{
		  obj.data('showSwitch','off');
		  obj.fadeOut('slow');
		  $("[classremark = "+classremark+"]").removeClass('active');
	  }
   }
   function addShowHide(condition){
	   if(condition){
		   condition = $.parseJSON(condition); 
	   }else{
		   condition = {};
	   }
	   //给X图标绑定关闭事件
	   $('.myClose').each(function(i){
		   var _self = $(this); 
		   _self.bind('click',function(){
			   myClose(_self)
		   });
	   });
	   //给属性块绑定切换事件
	   $('.attribute-block').each(function(index){
		   var _self = $(this),classremark = _self.attr('classremark')
		   _self.click(function(){
			   myClose($('.'+classremark));
			   _self[$('.'+classremark).data('showSwitch') == 'off' ? 'removeClass' : 'addClass']('active');
		   });
	   });
   }
   //纪录可用字段
   function saveFields($return){
	   var obj = $('.attrRmark .portlet:visible input, .attrRmark .portlet:visible select').serializeArray(),arrToStr = [],str;
	   	$.each( obj, function(i, thisDom){
	   		arrToStr.push(thisDom.name);
	   	});
	   	str = arrToStr.join(',');
	   	if($return){
	   		return str;
	   	}
	   	$('[name = availableNameStr]').val(str);
   }
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
	                    url : common.U('goodsList',data), //请求路径
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
   function addGoods(data){
  	 	var str = "<tr class = 'goodsRemoveLeft"+data.value+"'><td>"+data.html+"<i class = 'close boxClose'></i></td></tr>"
  	 	var obj = $(str);
  	 	removeGoods(obj.find('.boxClose')); //绑定移除事件
  	 	$('#choosed_list_body').append(obj);
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
   function addLocation(areaFactory,num,type){
	   var yumNum,newNum;
	   yumNum = $('.area .control-group').size() ;
	   for(var i = 0 ; i < num ; i++){
		   newNum = yumNum + i;
		   if(type != 2){
			   //生成dom
			   str = '<div class="control-group">\
					<label class="control-label">\
				<i style="color:green" class="icon-plus-sign addLocation"></i>&nbsp;&nbsp;\
				<i style="color:red" class="icon-minus-sign delLocation"></i>&nbsp;&nbsp;\
				省:\
				</label>\
				<div class="controls">';
			   str += '<div id="area-select-'+newNum+'">\
	           <select id="provice-'+newNum+'" data-id="" class = "span4">\
	           </select>\
	           <select id="city-'+newNum+'" data-id="" class = "span4">\
	           </select>\
	           <select id="county-'+newNum+'" data-id="" class = "span4">\
	           </select></div>\
	       </div>';
			  var htmlObj = $(str); 
			  htmlObj.find('.addLocation').click(function(){
				  addLocation(areaFactory,1);
			  });
			  htmlObj.find('.delLocation').click(function(){
				  $(this).closest('.control-group').remove();
			  });
			$('.area .portlet-body').append(htmlObj);   
		   }else{
			   newNum = i;
		   }
		   //设置js事件
	       areaFactory.set('index'+newNum,{
	   		provice: 'provice-'+newNum,
	   		city: 'city-'+newNum,
	   		county: 'county-'+newNum,
				selectId:"area-select-"+newNum,
				namekey:{
					'provice_name':'provice_id[]','city_name':"city_id[]",
					'county_name':"county_id[]","town_name":"town_id[]"},
	           value: {
	               provice_id: $('#provice-'+newNum).data('id'),
	               city_id: $('#city-'+newNum).data('id'),
	               county_id: $('#county-'+newNum).data('id')
	           }
	       }); 
	       
	   }
   }
   function locationEvent(){
	   $('.addLocation').click(function(){
		   addLocation(areaFactory,1);
	   });
	   $('.delLocation').click(function(){
		   $(this).closest('.control-group').remove();
	   });
   }
    main.init = function(condition,num){
    	num = num || 1;
    	addDialog_validate();
    	addShowHide(condition);
    	common.date({'startSelector':'.startTime','endSelector':'.endTime'});
    	edit_validate();
        var areaFactory = require('pulgins/area/areaMany.js');
        addLocation(areaFactory,num,2);	
        $('.calCount').click(function(){
        	saveFields();
        	var data = $('#saveGroup').serializeArray();
        	data.ifCount = true;
        	$.post(common.U('groupUidData'),data,function(rtn){
        		if(rtn.status == 'SUCCESS'){
        			$('#infoCount').html(rtn.result);
        		}
        	});
        });
        $('#saveGroup [type=submit]').click(function(){//提交前检查有效字段
        	saveFields();
        	return true;
        });
        tool.del($('.del'));
        goods_portlet();
        locationEvent();
    }
	module.exports = main;
});