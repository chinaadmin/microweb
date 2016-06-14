/**
 * 会员等级
 */
define(function(require , exports ,module){
	var $ = require("jquery");
    var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');
	var main = {
			/**
			 * 表达式切换
			 */
			change:function(obj,key){
				var _this = this;
				$(obj).on("change",function(){
					var val = $(this).val();
					_this.express(val,obj,key);
				})
			},
			express:function(value,obj,key){
				var val= value || $(obj).val();
				var span_obj = $(obj).parent(".ex-money").find("span");
				if(val==4){
					var str = '~<input type="text" name="'+key+'" class="m-wrap span1" value=""/>';
                    span_obj.html(str);
				}else{
					span_obj.html("");
				}
			},
			/**
			 * 编辑
			 */
			edit_validate : function(){
		        $('#from_edit').validate($.extend({
		            rules: {
		                name: {
		                    required: true
		                },
		                express:{
		                	min:1
		                },
		                start_money:{
		                	required:true
		                },
		                end_money:{
		                	required:true
		                },
		                discount:{
		                	required:true,
		                	min:1,
		                	max:10
		                }
		            },
		            messages: {
		                name: {
		                    required: "等级名称不能为空"
		                },
		                express:{
		                	min:"请选择消费金额"
		                },
		                start_money:{
		                	required:"请填写消费金额"
		                },
		                end_money:{
		                	required:"请填写消费金额"
		                },
		                discount:{
		                	required:"优惠折扣不能为空",
		                	min:"优惠折扣为1~10",
		                	max:"优惠折扣为1~10"
		                }
		            }
		        },tool.validate_setting));
		    },
		    /**
		     * 等级升降
		     */
		    createGrade:function(){
		    	$(".grade-sort").on("click",function(){
		    		var level = $(this).attr('data-level');
		    		var id = $(this).attr('data-id');
		    		var type = $(this).attr('data-sort');
		    		tool.doAjax({
		    			"url":common.U("UserGrade/changeGrade"),
		    			"data":"level="+level+"&type="+type+"&gid="+id
		    		});
		    	})
		    }
	};
	
	/**
	 * 编辑
	 */
	main.edit = function(){
		main.express();
		main.change("select[name='express']","end_money");
		main.change("select[name='recharge']","recharge_ex_money");
		main.edit_validate();
	}
	/**
	 * 列表
	 */
	main.index = function(){
		tool.check_all("#check_all",".grade_check");
		tool.batch_del($('.grade-del'),$('.grade_check'));
		main.createGrade();
	}
	module.exports=main;
})