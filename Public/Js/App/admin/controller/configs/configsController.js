define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#form_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                },
                code:{
                    required: true
                }
            },
            messages: {
                name: {
                    required: "配置名称不能为空"
                },
                code:{
                    required: "配置代码不能为空"
                }
            }
        },tool.validate_setting));
    };

    var index_validate = function(){
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
    };

	var main ={
        index : function(){
            index_validate();
        },
        lists : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		},
        edit : function(){
            edit_validate();
		}
	};
	module.exports = main;
});