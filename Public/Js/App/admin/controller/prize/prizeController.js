/**
 * 商品管理
 * @author ： liuwh
 * @return {[type]}                  [description]
 */
define(function(require, exports, module) {
	var $ = require('jquery');
	require('jquery_validate');
	require('pulgins/bootstrap/datetimepicker/bootstrap-datetimepicker.min.css');
	var common = require('common');
	var tool = require('model/tool');
	//增加非负数验证
	jQuery.validator.addMethod('isMyNumeric' , function(value, element, params){
		 var regex = /^[0-9]+(.[0-9]{1,2})?$/;
		 return regex.test(value);
	});

	/**
    *   日期时间转为Date对象
    */
    function stringTime2date(stringDateTime){
        return new Date(Date.parse(stringDateTime.replace(/-/g,   "/")));
    }
    
	//日期时间
	var validTime = function() {
		require.async(["pulgins/bootstrap/datetimepicker/bootstrap-datetimepicker.min", "pulgins/bootstrap/datetimepicker/locales/zh-CN"], function(dateTimer, zhCN) {
			$('.form_datetime').datetimepicker({
				language: 'zh-CN',
				format: "yyyy-MM-dd hh:mm:ss",
				autoclose: true
			}).on('changeDate', function(event) {
				var endTime = $('#end_time');
				if (endTime.val()) {
					// if (event.date > common.stringTime2date(endTime.val())) {
					if (event.date > stringTime2date(endTime.val())) {
						endTime.val('');
					}
				}
			});
			$('.end_datetime').datetimepicker({
				language: 'zh-CN',
				format: "yyyy-MM-dd hh:mm:ss",
				// startDate: common.stringTime2date($('#start_time').val()),
				startDate: stringTime2date($('#start_time').val()),
				autoclose: true
			});
		});
	};
	//选择奖品
	var selectPrize = function() {
		require.async('template', function(template) {
			var prizeContainer = $('#prize-item-show');
			var ajaxGetOption = function(url , selector) {
				var optionC = $('#as_coupon_id');
				$.getJSON(url, function(data) {
					var html = template('option_tpl', {
						data: data,
						select : selector || ''
					});
					optionC.html(html);
				})
			};
			var validRule = $.extend({
				rules: {
					as_name: {
						required: true
					},
					as_type: {
						min: 1
					}
				},
				messages: {
					as_name: {
						required: "名称不能空"
					},
					as_type: {
						min: "请选择奖品类型"
					},
					as_coupon_id: {
						min: "请选择奖品"
					},
					as_hongbao_amount: {
						required: '奖品不能空',
						isMyNumeric : '奖品设置错误'
					}
				}
			}, tool.validate_setting);
			var runValidForm = function(settings) {
				$('#form1').validate(settings);
			}
			$(document).on('change', '#select-prize-type', function() {
				var typeId = $(this).val();
				var _validRule = {};
				var html = sUrl = '';
				var data = {};
				if (typeId > 0) {
					sUrl = typeId == 2 ? common.U('coupon/edit') : '';
					html = template('prize_item_tpl', {
						type: typeId,
						addUrl: sUrl,
						data: data
					});
				}
				prizeContainer.html(html);
				switch (typeId) {
					case '2':
						$('#as_coupon_id').rules("add" , {min :1});
						ajaxGetOption(common.U('prize/getUseCoupon'));
						break;
					default:
						$('#as_hongbao_amount').rules("add" , {required : true , isMyNumeric : true});

				}
				runValidForm(validRule);
			}).on('click', '#refesh_option', function() {
				var typeId = $('#select-prize-type').val();
				if (typeId == 2) {
					ajaxGetOption(common.U('prize/getUseCoupon'));
				}
			});
			runValidForm(validRule);
		});
	}
	var main = {
		index : function(){
			tool.del($('.js-del'));
		},
		edit: function() {
			validTime();
			selectPrize();
		}
	};
	module.exports = main;
});
