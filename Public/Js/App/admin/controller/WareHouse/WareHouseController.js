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
                    required: "发货点不能空",
                    maxlength:"分类名称不能超过10个字"
                }
            }
        },tool.validate_setting));
    }();
	
	var main ={
		index : function(){
			//tool.del($('.js-del'));
			tool.check_all("#check_all",".ware_check");
	    	tool.batch_del($('.ware-del'),$('.ware_check'));
		},
		edit:function(){
			    var area = require('pulgins/area/area.js');
	            var area_config = {
	                value: {
	                    provice_id: $('#provice').data('id'),
	                    city_id: $('#city').data('id'),
	                    county_id: $('#county').data('id')
	                }
	            };
	            area.init(area_config);
		}
	};
	module.exports = main;
});