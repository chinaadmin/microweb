define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
	var template = require("template");
    var tool = require('model/tool');

   	/**
   	 * 验证
   	 */
	var add_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                },
                type:{
                	min:1
                },
                value:{
                	required:true
                }
            },
            messages: {
                name: {
                    required: "属性名不能为空"
                },
                type:{
                	min:"请选择所属类型"
                },
                value:{
                	required:"请输入可选值"
                }
            }
        },tool.validate_setting));
	}();
    
	var attGroup = function(){
		$('#type').on('change',function(){
			var value = $(this).val();
			var url = common.U('attribute/attGroup');
			if(value){
				$.post(url,{"type_id":value},function(data){
					if(data.success){
						var html = template("att_group",{"data":data.data,"att_group":att_group});
						$('#group').html(html);
						$('#group').show();
					}else{
						$('#group').html("");
						$('#group').hide();
					}
				})
			}
		})
	}();
	
	var select = function(){
		$('input[name=input_type]').on('click',function(){
			 var v = $(this).val();
			 if(v == 1){
				 $('textarea[name=value]').removeAttr('disabled');
			 }else{
				 $('textarea[name=value]').attr('disabled',true); 
			 }
		})
	}();
	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		}
	};
	module.exports = main;
});