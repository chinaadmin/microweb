define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改规则验证
     */
    var edit_validate = function(){
    	$('#user_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                },
                code: {
                	required: true
                }
            },
            messages: {
                name: {
                    required: "规则名称不能空"
                },
                code: {
                	required: "规则编号不能空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        edit : function(){
            edit_validate();
            $('select[name="type"]').change(function(){
                var type_val = $(this).val();
                $('.js-type').hide().each(function(){
                    var id = $(this).data('id');
                    if(id==type_val){
                        $(this).show();
                    }
                });
            });
		},
        config:function(){
            $('#form_edit').validate($.extend(tool.validate_setting,{
                submitHandler: function (form) {
                    tool.formAjax(form,function(data){
                        require.async('base/jtDialog',function(jtDialog){
                            if(data.status != tool.success_code){
                                jtDialog.showTip(data.msg);
                            }else{
                                jtDialog.showTip('保存配置成功',1,function(){
                                    location.reload();
                                });
                            }
                        });
                        return false;
                    });
                }
            }));
        }
	};
	module.exports = main;
});