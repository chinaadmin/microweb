$(document).ready(function(){
	//第一个添加框的弹出
	$(".add-btn").click(function(){
		$(".pop-box").show();
		$(".address-choice").show();
		
	});
	
	//新增后的按钮
	$(".btn-new").click(function(){
		if($(".add-address-new").css("display") == "none"){
		$(".add-address").hide();
		$(".add-address-new").show();
		
		$(".add-btn-2").show();
		}else{
			append("")
		}
	})
	//选择地址
	$(".btn-radio").click(function(){
		$(".add-address-new").removeClass("add-address-active");
		$(this).parents('.add-address-new').addClass("add-address-active");
		$(".way-box-new").removeClass("add-address-active");
		$(this).parents('.way-box-new').addClass("add-address-active");
		$(".change-box").hide();
		$(".change-way").hide();
		$(this).parents('.add-address-new').find(".change-box").show();
		$(this).parents('.way-box-new').find(".change-way").show();
		
	})
	$(".add-address-new").mouseover(function(){
		$(this).find('.change-box').show();
	})
	$(".add-address-new").mouseout(function(){
		if($(this).hasClass("add-address-active")){$(this).find('.change-box').show();}
		else{
		$(this).find('.change-box').hide();
		}
	})
	
	//银行卡选择
	$('.change-bank').click(function(){
		$(".bank-img").removeClass('bank-active');
		$(this).parent().find(".bank-img").addClass('bank-active');
		$(".i-img").hide();
		$(this).parent().find(".i-img").show();
	
	})
	//发票修改
	$(".change-btn").click(function(){
		$(".bill-change").show();
	})
	$(".closed-box").click(function(){
		$(".bill-change").hide();
	})
	$(".bill-inpt").click(function(){
		if($(this).is(":checked")==true){
		$(".bill-hd").show();
		}else{
			$(".bill-hd").hide();
		}
	})
	//提货地址
  $(".field").change(function(){
  	$(".show-address").show();
  })
  $(".change-btn-new").click(function(){
	$(".way-box-new").hide();
	$(".de-box").show();
	$(".change-show").show();
	$(".add-way").hide();
	})
 //去给钱
 $(".pay-go").click(function(){
 	$(".pop-box").show();
 	$(".pay-wid").show();
 })
  $(".pay-fail").click(function(){
  	$(".btn-box").hide();
  	$(".btn-2").show();
  })
});

$(document).ready(function() {
	 $("#signupForm").validate({
		rules: {
            userName:{
				required: true,
				},
            passWord: {
				required: true,
				rangelength:[6,20]
				},
			confirm_password: {
				required: true,
				equalTo: "#passWord",   
				rangelength:[6,20]
				},
			email:{
    			required: true,
    			email: true,
   				},
   			telephone:{
				required: true,
				rangelength:[11,11],
				digits: "只能输入整数"
				},
			},
			messages: {
                userName:{
					required: "请输入您的姓名",
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
   			telephone:{
					required: "请输入手机号",
					rangelength: jQuery.format("请输入正确的手机号"),
				},

	},submitHandler:function(form){
		$(".pop-box").hide();
		$(".address-choice").hide();
		$(".de-box").hide();
		$(".way-box-new").show();
	
			$(".add-way").show();

			
	}
	});
});
