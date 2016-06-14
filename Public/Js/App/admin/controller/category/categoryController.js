define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var template = require('template');
	require('jquery_validate');
	var base = require('base/controller/adminbaseController');
    var tool = require('model/tool');

    /**
     * 分类验证
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
    
    var attGroup = function(){
		$('select[name=type]').on('change',function(){
			var value = $(this).val();
			var typeObj = $('#type-attr');
			var url = common.U('category/attrs');
			if(value){
				$.post(url,{"type_id":value},function(data){
					if(data.success){
						if(checkAttr){
							for(i in data.data){
								for(j in checkAttr){
								   if(data.data[i].attr_id == checkAttr[j]){
									   data.data[i].check = true;
									   continue;
								   }	
								}
							}
						}
						var result = {"data":data.data};
						result['checkAttr'] = checkAttr;
						var html = template("attr",result);
						typeObj.html(html);
						typeObj.show();
						base.initUniform(".attr-check");
					}else{
						typeObj.html("");
						typeObj.hide();
					}
				})
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