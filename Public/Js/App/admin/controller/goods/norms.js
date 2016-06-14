/**
 * 商品规格
 */
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	var template = require("template");
	var base = require('base/controller/adminbaseController');
	var plugin = require("base/plugin.js");
	var methods = function(){
		this.hasup=[];
		this.norms_value={};
		this.addNorms = function(){
			var postData = "cat_id="+$("select[name='cat_id']").val();
			var _this =this;
			tool.doAjax({
				url:common.U('goods/getNormsValue'),
				type:'post',
				data:postData
			},function(result){
				if(!$.isEmptyObject(result)){
					if(norms){
						result = _this.dochecked(norms.norms_value,result);
					}
					var data = {"data":result};
					var html = template("add-norms",data);
					$('#norms').show();
					$('#norms').html(html);
					if(result.checkId){
						for(i in result.checkId){
							_this.up(result['checkId'][i]);
						}
					}
					if(norms && norms.norms_attr){
						_this.valueGroup(norms.norms_attr);
					}
					_this.each_parent();
					base.initUniform(".add-norms");
					//$('.norms_group').show();
				}else{
					$("input[name='number']").removeAttr("readonly");
					$('#norms').hide();
					$('#norms').html("");
					//$('.norms_group').hide();
				}
				_this.norms_group()
			})
		};
		/**
		 * 编辑时被选中的值
		 */
		this.dochecked = function(norms,data){
			data.checkId =[];
			for(i in data){
			  for(k in data[i]['norms_values']){
				for(j in norms){
					if(data[i]['norms_values'][k]['norms_value_id'] == norms[j]['norms_value_id']){
						data[i]['norms_values'][k]['checked'] = "checked";
						data[i]['norms_values'][k]['attr'] = norms[j]['norms_attr'][0];
						if(norms[j]['norms_value']){
						  //data[i]['norms_values'][k]['norms_value'] = norms[j]['norms_value'];
						  data[i]['norms_values'][k]['norms_edit'] = norms[j]['norms_value'];
						}
						data.checkId.push(norms[j]['norms_value_id']);
					}
				}
			  }
			}
			return data;
		};
		/**
		 * 选择分类
		 */
		this.changeValue = function(){
			var _this = this;
			$("select[name='cat_id']").on('change',function(){
				_this.addNorms();
			})
		};
		/**
		 * 选择值做的处理
		 */
		this.clickVlaue = function(){
			var _this = this;
			$('#norms').on('click','.add-norms',function(){
				var type = $(this).attr('data-type');
				var value = $(this).val();
				if($(this).is(":checked")){
				 if(type == 2){
					_this.up(value);
				 }
				}else{
					$('.norms-table .norms_'+value).hide();  
					_this.each_parent();
				}
				_this.valueGroup();
			});
		};
		/**
		 * 隐藏上传框
		 */
		this.each_parent = function(){
			var j=0;
			$("#norms .add-norms[data-type='2']").each(function(i,v){
				if($(v).is(':checked')){
					j++;
				}
			})
			if(j<=0){
				$("#norms .norms-table").hide();
			}
		};
		/**
		 * 处理组合值
		 */
		this.valueGroup = function(norms_attr){
			var length = $('#norms .add-norms').length;
			var data=new Array();
			var datas = {};
			//组合值
			var parent = [];
		    for(var i=0;i<length;i++){
		    	var i_dom = $('#norms .add-norms').eq(i);
		    	var i_parent = i_dom.attr('data-parent');
		    	if(i_dom.is(':checked')){
		    		if($.inArray(i_parent,parent)<0){
		    			parent.push(i_parent);
		    		}
		    		i_parent = i_parent.toString();
		    		if(!datas[i_parent]){
		    		 datas[i_parent] = [];
		    		}
		    		datas[i_parent].push(i);
		    	}
		    };
		    var result = [];
		    if(datas){
		    	for(i in datas){
		    		data.push(datas[i]);
		    	}
		       if(parent.length>1){
		        result = this.impData(data);
		        result = result[0];
		       }else{
		    	 for(i in data[0]){
		    		 result.push(data[0][i]+"_");
		    	 }
		       }
		    }
		    if(result){
		      this.addHtml(result,norms_attr);
		    }
		 };
		/**
		 * 递归计算数组组合
		 */
		this.impData = function(data){
			var datas = [];
			var arr = [];
			for(j in data[0]){
				for(k in data[1]){
					var str = data[0][j]+"_"+data[1][k];
					if($.inArray(str,arr)<0){
					 arr.push(str);
					}
				}
			}
			datas.push(arr);
			for(i in data){
				if(i>=2){
					datas.push(data[i]);
				}
			}
			if(datas.length>=2){
				return this.impData(datas);
			}else{
				return datas;
			}
		};
		/**
		 * 去除重复数据
		 */
		this.unqiue = function(data1,data2){
			for(i in data1){
				for(j in data2){
					var index = data2.indexOf(data1[i]);
					if(index>-1){
						data2.splice(index,1);
					}
				}
			}
			return data2;
		};
		/**
		 * 图片类型上传处理
		 */
		this.up = function(value){
			$('.norms-table').show();
			$('.norms-table .norms_'+value).show();
			var obt = $('#plupload_browse_'+value);
			var upId = obt.attr('id');
			if(this.hasup.indexOf(upId)<0){
			 plugin.upload(obt);
			 this.hasup.push(obt.attr('id'));
			}
		};
		//处理组合数据
		this.addHtml = function(data,norms_attr){
			var str="";
			var parents = [];
			var price = $("input[name='price']").val();
			for(var i=0;i<data.length;i++){
				str += "<tr class='"+data[i]+"'>";
				var arr = data[i].split("_");
				var v_key ="";
				for(j in arr){
				 if(arr[j]=="" || arr[j]==null){
					 continue;
				 }
				 var dom = $("#norms .add-norms").eq(arr[j]);
				 var type= dom.attr('data-type');
				 if(type==2){
					var path = dom.parents('.checkbox').find('img').attr('src');
				 }else{
					 path='';
				 }
				 var text_val = dom.attr('data-edit') || dom.attr('data-value');
				 var val = dom.val();
				 v_key+=val+"_";
				 str+="<td>";
				 if(path){
					str+="<img src='"+path+"' width='20' height='20'/>"; 
				 }
				 str+="<input type='text' class='span8' name='norms_value["+val+"]' value='"+text_val+"'/></td>";
				 var parent_value = dom.attr('parent-value');
				 if(parents.indexOf(parent_value)<=-1){
					 parents.push(parent_value);
				 }
				}
				var price_name = 'norms_price['+v_key+']';
				var number_name = 'norms_number['+v_key+']';
				var code_name = 'norms_code['+v_key+']';
				var norms_price =this.norms_value[price_name] || price;
				var norms_number=this.norms_value[number_name] || "";
				var norms_code = this.norms_value[code_name] || "";
				if(norms_attr){
					for(k in norms_attr){
						if((v_key) == (norms_attr[k]['goods_norms_link']+"_")){
							this.norms_value[price_name] = norms_attr[k]['goods_norms_price'];
							this.norms_value[number_name] = norms_attr[k]['goods_norms_number'];
							this.norms_value[code_name] = norms_attr[k]['goods_norms_no'];	
							norms_price = norms_attr[k]['goods_norms_price'];
							norms_number = norms_attr[k]['goods_norms_number'];
							norms_code = norms_attr[k]['goods_norms_no'];
						}
					}
				}
				str+="<td><input type='text' class='span8 norms_attr norms_price  norms_check' name='norms_price["+v_key+"]' value='"+norms_price+"' /></td>";
				str+="<td><input type='text' class='span8 norms_attr norms_number norms_check' name='norms_number["+v_key+"]' value='"+norms_number+"' /></td>";
				str+="<td><input type='text' class='span8 norms_attr' name='norms_code["+v_key+"]' value='"+norms_code+"' /></td>";
				str+="</tr>"
			}
			$('#add-norms-group').html(str);
			this.del_head(parents);
			this.norms_group();
		};
		/**
		 * 显示隐藏属性组
		 */
		this.norms_group = function(){
			var j=0;
			$("#norms .add-norms").each(function(i,v){
				if($(v).is(":checked")){
					j++;
				}
			})
			if(j>0){
				$('#norms .norms_group').show();
				$("input[name='number']").attr("readonly",true);
			}else{
				$("input[name='number']").removeAttr("readonly");
				$('#norms .norms_group').hide();
			}
		}
		/**
		 * th的值
		 */
		this.del_head = function(parents){
			$('#norms .norms_th_value').each(function(i){
				var obj = $('#norms .norms_th_value').eq(i);
				var val = obj.html();
				for(j in parents){
					if(parents.indexOf(val)<=-1){
						obj.hide();
					}else{
						obj.show();
					}
				}
			})
		}
		/**
		 * 记录输入的数据
		 */
		this.saveValue = function(){
			var _this = this;
			$('#norms').on('blur','.norms_attr',function(){
				var name = $(this).attr('name');
				var val = $(this).val();
				if(val){
					$(this).removeClass("norms_red");
				}
				_this.norms_value[name] = val;
			})
		};
	}
	/**
	 * 检查规格值是否为空
	 */
	methods.prototype.checkValue=function(){
		var price = $("input[name='price']").val();
		var j=0;
		var k=0;
		var number=0;
		if($('#norms').html() && $('#norms .norms_check').length){
			$('#norms .norms_check').each(function(i,dom){
				var val = $(dom).val();
				//val = parseInt(val);
				if(val<=0){
					j++;
					$(dom).addClass("norms_red");
				}
				//商品价格判断
				if($(dom).hasClass("norms_price")){
					var price_val = $(dom).val();
					if(price && val>=price){
						k++;
					}
				}
				//规格库存
				if($(dom).hasClass("norms_number")){
					var number_val = $(dom).val();
					if(number_val){
					 number += parseInt(number_val);
					}
				}
			})
			if(j>0){
				require.async('base/jtDialog',function(jtDialog){
					jtDialog.showTip("商品规格值不能为0或者空！");
				})
				return false;	
			}else{
				if(k<=0){
					require.async('base/jtDialog',function(jtDialog){
						jtDialog.showTip("规格价格至少有一个大于等于销售价");
					})
					return false;
				}
				$("input[name='number']").val(number);
				return true;
			}
		}
		return true;
	}
	methods.prototype.editNorms = function(){
		methods.changeValue();
		methods.clickVlaue();
		methods.addNorms();
		methods.saveValue();	
	}
	var methods = new methods();
	module.exports = methods;
})