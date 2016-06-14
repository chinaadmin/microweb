define(function(require, exports, module) {
	var $ = require('jquery');
	$(document).ready(function() {
		$('.btn_page').click(function() {
			$('.btn_page').removeClass('click_num');
			$(this).addClass('click_num');
		});
		$("#plist li").mouseover(function() {
			$(this).find(".add_cart_large").show();
		})
		$("#plist li").mouseout(function() {
			$(".add_cart_large").hide();
		})
		// Store variables
		var accordion_head = $('.accordion > li > a'), accordion_body = $('.accordion li > .sub-menu');
		// Open the first tab on load
		var selectOne = selectOne || '';
		if(selectOne == ''){//没有选中子类
			$('.accordion > li > .active').next().slideDown( //默认进入时打开相关分类
			'normal')
		}
		// Click function
/*		accordion_head.on('click', function(event) {
			event.preventDefault();
			if (!$(this).hasClass('active')) {
				accordion_body.slideUp('normal');//关闭所有分类菜单
				$(this).next().stop(true, true).slideToggle('normal');
				accordion_head.parent().find(".active").removeClass('active');//移除所有其它active类
				$(this).addClass('active').siblings('.sort_bg').addClass("active");
			}
		});*/
	})
});
