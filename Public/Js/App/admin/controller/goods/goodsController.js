define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
    var tool = require('model/tool');
    var selects = require("common/checkselect/checkselect");
    require("common/checkselect/checkselect.css");
    var methods = require("./norms.js");
    var cats = require("./cats.js");
	/**
	 * 商品验证
	 */
	var edit_validate = function() {
		$('#form_edit').validate($.extend({
			rules : {	
			},
			messages : {
			}
		}, $.extend(tool.validate_setting,{
			 submitHandler: function (form) {
				    if(!methods.checkValue()){
				    	return false;
				    }
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
		   }
		)
	));
	}();
	var getAttrs = function(val){
		var value = val || $('select[name=cat_id]').val();
		var typeObj = $('#type-attr');
		var url = common.U('goods/attrs');
		//编辑时
		if(type_id && type_id==value && attrs){
			edit.addHtml(attrs);
		}else{
			if(value){
				$.post(url,{"cat_id":value},function(data){
					if(data.success){
						edit.addHtml(data.data);
					}else{
						typeObj.html("");
						typeObj.hide();
					}
				})
			}	
		}
	}
	var attGroup = function(){
		$('select[name=cat_id]').on('change',function(){
			var value = $(this).val();
			getAttrs(value);
			cats.optDisable();
		})
	}();
	/**添加编辑**/
	var edit = {
			//类型属性表单添加
			addHtml : function(data) {
			var html = "";
			for (i in data) {
				var name = data[i].name;
				var head = '<div class="control-group"><label class="control-label">'
						+ name + '</label><div class="controls">';
				var footer = '</div></div>';
				// 手工录入
				if (data[i].input_type == 0) {
					str = this.addText(data, i, 1);
					html += head + str + footer;
				}
				// 选择输入
				if (data[i].input_type == 1) {
					str = this.addCheckBox(data, i);
					html += head + str + footer;
				}
				// 多行文本输入
				if (data[i].input_type == 2) {
					str = this.addText(data, i, 2);
					html += head + str + footer;
				}
			}
			$('#type-attr').html(html);
			base.initUniform();
			$('#type-attr').show();
		},
		addText : function(data, i, type) {
			var str = "";
			var attr_id = data[i].attr_id;
			var namekey = "name = attrs[" + attr_id + "]";
			// 不可选
			if (data[i].attr_type == 0 ) {
				//编辑时属性值
				var att_value = data[i].att_value || "";
				if(type==1){
				 str += '<input type="text" ' + namekey
						+ ' class="m-wrap span4" value="'+att_value+'"/>';
			    }
				if(type==2){
					str += '<textarea '
						+ namekey
						+ ' class="m-wrap span4" row="3">'+att_value+'</textarea>';
				}
			} else {
				// 单选、复选
				var key = "name = attrs[" + attr_id + "][input]";
				namekey = key + "[value]";
				nameprice = key + "[price]";
				//编辑时属性值
				var att_value = data[i]['value'].att_value || "";
				var att_price = data[i]['value'].price || ""
				if (type == 1) {
					var str = '<input type="text"'
							+ namekey
							+ ' class=\"m-wrap span4\"  value="'+att_value+'" placeholder=\'多个值用英文","隔开，与属性价格一一对应。\' />';
					str += '<span style="margin-left:3px;line-height:30px;">属性价格</span><input type="text" '
							+ nameprice + ' class="m-wrap span3" value="'+att_price+'"/>';
				}
				if (type == 2) {
					str += '<textarea '
							+ namekey
							+ ' class="m-wrap span4" row="3" placeholder=\'多个值用英文","隔开，与属性价格一一对应。\'>'+att_value+'</textarea>';
					str += '<span style="margin-left:3px;line-height:30px;">属性价格</span><textarea '+ nameprice+' class="m-wrap span4" row="3">'+att_price+'</textarea>';
				}
			}
			return str;
		},
		addCheckBox : function(data, i) {
			var value = data[i].value;
			var attr_id = data[i].attr_id;
			var namekey = "name = attrs[" + attr_id + "]";
			var str = "";
			var select = '<select class="m-wrap" '+namekey+'><option value="">请选择</option>';
			var option = "";
			//编辑时被选中的值
			var att_value = data[i].check_value;
			for (j in value) {
				var checked = "";
				var att_price = "";
				for(k in att_value){
					if(att_value[k].att_value == value[j]){
						checked ="checked";
						att_price = att_value[k].price;
					}
				}
				if (data[i].attr_type == 0) {
					/*str += '<input type="radio" ' + namekey +" "+checked+ ' value="'
							+ value[j] + '"/>' + value[j] + '</label>';*/
					if(data[i].att_value == value[j]){
						 option+= '<option selected="selected" value="'+value[j]+'">'+value[j]+'</option>';
					}else{
						 option+= '<option value="'+value[j]+'">'+value[j]+'</option>';	
					}
				} else {
					str += '<p><label class="checkbox line" style="display:inline-block;margin-right:12px;">';
					var key = "name = attrs[" + attr_id + "][" + j + "]";
					namekey = key + "[value]";
					nameprice = key + "[price]";
					str += '<input type="checkbox" ' + namekey +" "+checked+' value="'
							+ value[j] + '"/>' + value[j] + "</label>";
					str += '<span style="line-height:30px;">属性价格</span><input style="margin-right:3px;" type="text" '
							+ nameprice + ' class="m-wrap span1" value="'+att_price+'"/>';
				}
				str += "</p>";
			}
			if(option){
				str = select+option+"</select>";
			}
			return str;
		},
		//关联表单查询
		addLink:function(searchName,linkId,checkedId){
			var the_this = this;
			$('.'+searchName).on('click',function(){
				var _this = $(this);
				var data = the_this.postData(_this,checkedId);
				tool.doAjax({
					url:common.U('goods/searchGoods'),
					data:data
				},function(result){
					the_this.addNode(result, linkId);
				})
			})
		},
		//添加节点
		addNode:function(data,linkId){
			if(data['success']){
				var result = data.data;
				var html ="" ;
				for(i in result){
					html+='<li class="selected"><span ids="'+result[i].goods_id+'" price="'+result[i].price+'">'+result[i].name+'&nbsp;&nbsp;（库存：'+result[i].stock_number+'）</span></li>';
				}
				$('#'+linkId+" ul").html(html);
			}else{
				$('#'+linkId+" ul").html('');
			}
		},
		//post值
		postData:function(obj,checkedId){
			var type = obj.attr('type');
			var cat_id = obj.siblings('.category_id').val();
			var brand_id = obj.siblings('.brand_id').val();
			var keyword = obj.siblings('.keyword').val();
			var id = $('input[name=id]').val();
			var data = "cat_id="+cat_id+"&brand_id="+brand_id+"&keyword="+keyword+"&type="+type+"&id="+id;
			var goods = [];
			var checkObj = $("#"+checkedId+" ul li");
			checkObj.each(function(i){
				var ids = checkObj.eq(i).children('span').attr('ids');
				if(ids){
					goods[i] = ids;
				}
			})
			if(goods){
				data+="&goods="+goods;
			}
			return data;
		}
		
	};
	
	/**
     * 关联选择
     */
    var select = function(){
    	//关联商品
    	edit.addLink("link-search","link-select","checked-select");
    	selects.selected();
    	//配件
    	edit.addLink("fitt-search","fittlink-select","fittchecked-select");
    	selects.selected({
    		leftClass : "fittlink-select",
    		rightClass : "fittchecked-select",
    		name:"fitt",
    		input:true
    	});
    	//赠品
    	edit.addLink("gift-search","giftlink-select","giftchecked-select");
    	selects.selected({
    		leftClass : "giftlink-select",
    		rightClass : "giftchecked-select",
    		name:"gift",
    		input:false
    	});
    };
    
    var main = {
    		init:function(){
    			tool.del($('.js-del'));
                tool.saveSort($('#save-sort'));
    		},
    		edit:function(){
    			//编辑类型属性
    			if(attrs){
    				 edit.addHtml(attrs);
    			}else{
    				getAttrs();
    			}
    			select();
    			//规格
    			methods.editNorms();
    			//从属分类
    			cats.edit();
    		}
    }
   module.exports=main;
})