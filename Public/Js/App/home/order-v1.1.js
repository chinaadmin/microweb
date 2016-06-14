;(function () {
	function init ($) {
		
		//新增收货地址
		$(".address h3").on('click', 'a', function(event) {
			event.preventDefault();
			var h = $(".popBox").show().height();
			var selfh = $(".popBox .adderss-add").height()/2
			$(".popBox .adderss-add").show().css({
				"margin-top":(h/2-selfh)+"px"
			});
		});


		//配送方式
		$(".delivery").on('click', 'span', function(event) {
			event.preventDefault();
			if ($(this).find('input').size()) {
				$(this).find('input').get(0).checked = true;
			};
		});
		// 门店自提-修改
		$(".delivery .location").on('click', 'span a.adderss', function(event) {
			event.preventDefault();			
			var h = $(".popBox").show().height();
			var selfh = $(".popBox .adderss-delivery").height()/2
			$(".popBox .adderss-delivery").show().css({
				"margin-top":(h/2-selfh)+"px"
			});
		});
		$(".delivery .location").on('click', 'span a.time', function(event) {
			event.preventDefault();			
			var h = $(".popBox").show().height();
			var selfh = $(".popBox .time-delivery").height()/2
			$(".popBox .time-delivery").show().css({
				"margin-top":(h/2-selfh)+"px"
			});
		});

		$(".popBox h3").on('click', '.close-box', function(event) {
			event.preventDefault();
			$(".popBox").hide();
			$(this).parents(".box").hide();
		});

		//支付方式--在线支付方式选择
		$(".paybox p").on('click', 'span', function(event) {
			event.preventDefault();
			if ($(this).find('input').size()) {
				$(this).find('input').get(0).checked = true;
			};			
		});

		//修改发票-展开
		$(".billbox h3").on('click', 'a', function(event) {
			event.preventDefault();
			if ($(this).hasClass('shw')) {
				$(this).removeClass('shw');
				$(".billbox .default").show();
				$(".billbox .editing").hide();
			}else{
				$(this).addClass('shw');
				$(".billbox .editing").show();
				$(".billbox .default").hide();
			}
		});

		//修改发票--新增发票抬头
		$(".billbox .editing").on('click', '.addon span:last', function(event) {
			event.preventDefault();
			if ($(".billbox .editing .input").size() <= 5) {
				var title="删除该抬头";
				var html = "<p class=\"input\"><span></span><span><input type=\"text\" /><i class=\"checked\" title='选择使用'></i><i class=\"del\" title='"+title+"'>-</i></span></p>"
				$(".billbox .editing .input:last").after(html)
			} else {
				window.alert("最多可以添加5个发票抬头！")
			}
		});
		$(".billbox .editing").on('click', '.del', function(event) {
			event.preventDefault();
			var $p = $(this).parents(".input")
				if ($p.hasClass('active')) {
					$p.siblings('.input').eq(0).addClass('active');
				};
				$p.remove();
		});
		$(".billbox .editing").on('click', '.checked', function(event) {
			event.preventDefault();
			var $p = $(this).parents(".input");
				$p.siblings('.input').removeClass('active');
				$p.addClass('active');
		});

		//使用优惠券--选择输入优惠券号
		$(".coupon select").on("change",function (e) {
			if ($(this).val()=="input") {
				$(this).siblings('.cpni').show()
			}else{
				$(this).siblings('.cpni').hide()
			}
		});
		//补充说明--展开与闭合 +、-
		$(".catbox .coupon p").on('click', 'span', function(event) {
			event.preventDefault();
				var $em = $(this).find('em');
			if ($em.hasClass('div')) {
				$em.removeClass('div');
				$em.parents("span").siblings('a').hide();
			}else{
				$em.addClass('div');
				$em.parents("span").siblings('a').show();
			}
		});

	};
	if (window.define) {
		window.define(function(require, exports, module) {
			var $ = require('jquery');
			init($);
		});
	}else{
		window.jQuery(function() {
		  init(window.jQuery);
		});		
	}
})();