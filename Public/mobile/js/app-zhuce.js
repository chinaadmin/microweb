$(document).ready(function  () {
	//获取短信验证码
	var validCode=true;
	$(".btn-massage").click (function  () {
		var time=30;
		var code=$(this);
		if (validCode) {
			validCode=false;
			code.addClass("msgs1");
		var t=setInterval(function  () {
			time--;
			code.html(time+"秒");
			if (time==0) {
				clearInterval(t);
			code.html("重新获取");
				validCode=true;
			code.removeClass("msgs1");

			}
		},1000)
		}
	})
	//点击错误提示
	$('.btn-zhuce').click(function(){
		$(this).css({"background":"#ff8c00","border":"none","box-shadow":"none"});
		$('.warring-box').show();
		setTimeout(
			function(){
				$('.warring-box').hide()
				}
		,2000);
	})
})