define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

   	/**
   	 * 添加或修改发送模板验证
   	 */
	var update_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
            	temp_name: {
                    required: true
                },
                temp_code:{
                    required: true
                },
                temp_content:{
                    required: true
                },
                temp_type:{
                	required: true
                },
            },
            messages: {
            	temp_name: {
                    required: "模板名称不能空"
                },
                temp_code:{
                    required: "模板代码不能空"
                },
                temp_content:{
                    required: "内容不能为空",
                },
                temp_type:{
                	required: "模板类型不为空",
                }
            }
        },tool.validate_setting));
	};
	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        update : function(){
            update_validate();
            $('a[name=insertCode]').click(function(){
    			UE.getEditor('editor').execCommand('insertHtml', $(this).attr('code'))
    		});
            $('#delEdit').click(function(){
            	UE.getEditor('editor').destroy();
            });
            try{
            	var ue = UE.getEditor('editor',{ 
                    'enterTag':'',
                    'indent':false
                }); 	
            }catch(e){
            	
            }
        },
	};
	module.exports = main;
});