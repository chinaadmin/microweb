define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require("common");
	var diDialog = require('model/diDialog');
	var main = {
			//加入购物车
			addCart:function(){
				$('.btnCart').click(function(){
	                var cart = require('model/cart');
	                var goods_id = $(this).attr("data-id");
	                var num = 1;
	                var norms = $(this).attr("norms-data");
	                cart.add(goods_id,num,norms);
	            });
			},
			//取消收藏
			delCollect:function(){
				$(".del-collect").on("click",function(){
					var _this = $(this);
					var goods_id = $(this).attr("data-id");
					$.ajax({
	                	 url:common.U("Collect/del"),
	                	 type:"post",
	                	 dataType:"json",
	                	 data:"goods_id="+goods_id,
	                	 success:function(result){
	                		  if (result.status !== common.success_code) {
	                			  if(result.status === 'NOT_LOGGED_IN'){
	                				  $('#js-login-mask').show();
	                                  $('#js-login-box').show();
	                                  $('#js-login-box').center();
	                			  }else{
	                				  diDialog.Alert(result.msg);  
	                			  }
	                		  }else{
	                			  _this.parents("li").parent().remove();
	                		  }
	                	 }
	                 })
				})
			}
	};
	
	main.init=function(){
		main.addCart();
		main.delCollect();
	}
	module.exports = main;
})