// JavaScript Document


/*-------注册验证-----------*/
$(document).ready(function() {
	$("#signupForm").validate({
		rules: {
			userName: {
				required: true,
			},
			passWord: {
				required: true,
				rangelength: [6, 20]
			},
			confirm_password: {
				required: true,
				equalTo: "#passWord",
				rangelength: [6, 20]
			},
			email: {
				required: true,
				email: true,
			},
			telephone: {
				required: true,
				rangelength: [11, 11],
				digits: "只能输入整数"
			},
		},
		messages: {
			userName: {
				required: "请输入用户名",
				rangelength: jQuery.format("请输入正确的用户名"),
			},
			passWord: {
				required: "请输入密码",
				rangelength: jQuery.format("密码在6~20个字符之间"),
			},
			confirm_password: {
				required: "请输入确认密码",
				rangelength: jQuery.format("密码在6~20个字符之间"),
				equalTo: "两次输入密码不一致"
			},
			email: {
				required: "请输入Email地址",
				email: "请输入正确的email地址"
			},
			telephone: {
				required: "请输入手机号",
				rangelength: jQuery.format("请输入正确的手机号"),
			}
		},
		submitHandler: function(form) {
			$(".email-show").show();
			$(".send-succ").show();
			$(".em-suc").show();
		
			//获取短信验证码
			var validCode = true;

			var time = 30;
			var code = $(".msgs");
			if (validCode) {
				validCode = false;
				code.addClass("msgs1");
				var t = setInterval(function() {
					time--;
					code.html(time + "秒");
					if (time == 0) {
						clearInterval(t);
						code.html("重新获取");
						validCode = true;
						code.removeClass("msgs1");

					}
				}, 1000)
			}
		
		}
	});
});



$(document).ready(function() {
	$(".code_btn").click(function() {
		$(".code_error").show();
		$(this).val('重新获取验证码');
	})
})