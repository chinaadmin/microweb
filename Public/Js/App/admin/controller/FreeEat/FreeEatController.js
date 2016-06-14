/**	
 * 免费试吃
 * @author : xiaohuakang
 * @date : 2016-05-20
 */
 define(function(require, exports, module) {
 	var $ = require('jquery');
 	require('jquery_validate');
 	var common = require('common');
 	var tool = require('model/tool');

 	// 选择时间
 	var chooseDate = function(){
 		// 加载时间选择器
 		require.async('pulgins/bootstrap/datetimepicker/locales/zh-CN', function(){
 			$('.form_datetime').datetimepicker({
 				language: 'zh-CN',	// 设置显示为中文
				format: "yyyy-MM-dd hh:mm:ss", // 设置时间格式
                startDate: new Date(),
				autoclose: true	// 自动关闭
 			});
 		});
 	}

 	// 表单验证
 	var edit_validate = function(){
        $('#form_edit').validate($.extend({
            rules: {
                ma_title: {
                    required: true
                }
            },
            messages: {
                ma_title: {
                    required: "活动名称不能空"
                }
            }
        },tool.validate_setting));
    };

 	var main = {
		index : function(){

		},
		edit: function() {
			chooseDate();
			edit_validate();
		}
	};

 	module.exports = main;
 });