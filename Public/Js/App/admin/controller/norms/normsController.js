/**
 * 产品规格
 */
define(function(require , exports ,module){
	var $ = require('jquery');
	var plugin = require("base/plugin.js");
	 var tool = require('model/tool');
	var methods = function(){
		//规格值排序默认值
		this.sort_value = function(){
			var val = 1;
			$('#norms_edit .norms').each(function(i,dom){
				var value = parseInt($(dom).find("input[name='value_sort[]']").val());
				if(value && value>=val){
					val = value+1;
				}
			});
			return val;
		};
		//获取节点代码html
		this.getHtml = function(){
			$('#mytag script').remove();
			var obj = $('#mytag .plupload').eq(0);
			var browseObj = $('#mytag .browse').eq(0);
			var progressObj = $('#mytag .browse_progress').eq(0);
			var arr = [obj,browseObj,progressObj];
			var rand = Math.random()*1000;
			var id=[];
			var nextId=[];
			var imagesObj = $('#mytag .plupload .images').eq(0);
			for(i in arr){
				id[i] = arr[i].attr('id');
				nextId[i] = id[i]+"_"+rand;
				arr[i].attr('id',nextId[i]);
				var uploadHtml = $('#mytag').html() || $('#mytagCopy').html();
			}
			for(i in arr){
				arr[i].attr('id',id[i]);
			}
			var str = "<tr class='norms'><td><input type='text' name='value[]'/></td>";
			str+="<td>"+uploadHtml+"</td>"
			var sort_value = this.sort_value();
			str+="<td><input type='text' name='value_sort[]' class='span3' value='"+sort_value+"'/></td>";
			var remove = "<a class='btn mini black del-norms' href='#'><i class='icon-trash'></i>移除</a>";
			str+="<td>"+remove+"</td></tr>";
			return str;
		};
		this.number = $('.norms').length;
		/**
		 * 添加规格值节点
		 */
		this.addnorms = function() {
			var _this = this;
			$('#add-norms').on('click', function() {
				var html = _this.getHtml();
				if (html) {
					$('#append-data').append(html);
					_this.number++;
					$('.plupload').eq(_this.number).find('.images').html("");
					plugin.upload($('.plupload').eq(_this.number));
					//_this.delnorms();
				}
			})
		};
		/**
		 * 删除规格值节点
		 */
		this.delnorms = function(){
			var _this = this;
			$("#append-data").on('click','.del-norms',function(){
				var index = $(this).parents('.norms').index();
				 $(this).parents('.norms').remove();
				 if(_this.number>0){
					 _this.number--;
				 }
			})
		};
		//类型切换
		this.changeType = function(){
			var _this = this;
			$('#change-type').on('change',function(){
				var value = $(this).val();
				_this.ableup(value);
			})
		};
		//禁用启用上传
		this.ableup = function(value){
			$('#norms_edit .norms').each(function(i){
				if(value==1){
				 $('.norms').eq(i).find('.browse').css({'background':"#ddd"});
				 $('.norms').eq(i).find('.up-hide').show();
				}
				if(value == 2){
					$('.norms').eq(i).find('.browse').css({'background':"green"});
					$('.norms').eq(i).find('.up-hide').hide();
				}
			})
			if(value==1){ //add by wxb 2015/07/16
				$('#mytagCopy').find('.browse').css({'background':"#ddd"});
				$('#mytagCopy').find('.up-hide').show();
			}else{
				$('#mytagCopy').find('.browse').css({'background':"green"});
				$('#mytagCopy').find('.up-hide').hide();
			}
		};
	};
	//验证
	methods.prototype.verify = function(){
		require.async('jquery_validate',function(){
			 $('#norms_edit').validate($.extend({
		            rules: {
		                name: {
		                    required: true,
		                }
		            },
		            messages: {
		                name: {
		                    required: "规格名称不能为空"
		                }
		            }
		        },tool.validate_setting));
		})
	};
	var main = {
			init:function(){
				return new methods();
			},
			edit:function(){
				var methods = this.init();
				methods.addnorms();
				methods.delnorms();
				methods.verify();
				methods.changeType();
				var value = $('#change-type').val();
				methods.ableup(value);
			},
			index:function(){
				tool.del($('.js-del'));
	            tool.saveSort($('#save-sort'));
			}
	};
	module.exports = main;
});