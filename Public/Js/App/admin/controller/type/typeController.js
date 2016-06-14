define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                name: {
                    required: true,
                    maxlength:10
                }
            },
            messages: {
                name: {
                    required: "分类名称不能空",
                    maxlength:"分类名称不能超过10个字"
                }
            }
        },tool.validate_setting));
    }();
    
    $('.add-in').on('click',function(){
    	var html = '<div class="edit-del">';
		html+= '<input type="text" class="span2 m-wrap" name="attk[]" value=""/>';
		html+= '<i class="icon-remove remove-del"></i></div>';
		$('.edit-del:last').before(html);
    })
    
    var doDel = {
    	//移除节点
    	"remove":function(){
    		$('.controls').on('click','.remove-del',function(e){
    			e.preventDefault()
    			var parent = $(this).parents('.edit-del');
    			var aid = parent
    			$(this).parents('.edit-del').remove();
    		})	
    	}
    }

	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));
            doDel.remove();
		}
	};
	module.exports = main;
});