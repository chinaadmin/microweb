define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "广告位名称不能为空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
		},
        edit : function(){
            edit_validate();
            $(".checkbox-all input[type='checkbox']").click(function(){
                if($(this).is(':checked')){
                    $('.checkbox-other').hide();
                }else {
                    $('.checkbox-other').show();
                }
            });
		}
	};
	module.exports = main;
});