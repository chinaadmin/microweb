define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    jQuery.validator.addMethod("photo_required", function(value, element, param) {
        var photo = $("input[name='photo']").val();
        if(typeof(photo) == 'undefined' || photo == ''){
            return false;
        }
        return true;
    }, "请上传图片");

    /**
     * 修改验证
     */
    var edit_validate = function(){
        $('#user_edit').validate($.extend({
            ignore:'',
            rules: {
                name: {
                    required: true
                },
                code:{
                    required: true
                },
                photo_vaild:{
                    photo_required: true
                }
            },
            messages: {
                name: {
                    required: "支付方式名称"
                },
                code:{
                    required: "支付方式编号"
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
		}
	};
	module.exports = main;
});