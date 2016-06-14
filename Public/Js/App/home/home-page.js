 $(function(){
	$(".logo_tow_nav").hide();
	$(".logo_box").mouseover(function(){
				$(".logo_box").addClass("logo_box_hover")
				$(".logo_tow_nav").show();
			})
	$(".logo_tow_nav").mouseover(function(){
				$(".logo_tow_nav").show();
			})
	$(".logo_tow_nav").mouseout(function(){
				$(".logo_tow_nav").hide();
				$(".logo_box").removeClass("logo_box_hover")
				
		})		
	$(".logo_box").mouseout(function(){
				$(".logo_tow_nav").hide();
				
		})
	
	/*“我的订单”部分下来菜单--指向*/
	$(".logo_nav_tow").hide()	
	$(".logo_index").mouseover(function(){
			x=$(".logo_index").index(this)
			$(".logo_nav_tow").eq(x).show();
		})
	/*“我的订单”部分下来菜单--移出*/	
	$(".logo_index").mouseout(function(){
			$(".logo_nav_tow").hide();
		})
	$('.list-item').mouseover(function(){
		this_a=$(this).attr('data-a');
		$('.subView').hide();
		$('[data-b='+this_a+']').show();
	})
})

<!--首页海报轮播-->
$(document).ready(function(){
$(".thumbnail_img").eq(0).addClass("border_red")
	var n = 0;
	function imgChange(){
		if(n<$("#banner img").length-1){
			n=n+1;
			}else{
			n=0;	
		}
			 
		$("#banner img").hide();
		$("#banner img:eq("+n+")").fadeIn(1000);
		$(".thumbnail_img").removeClass("border_red")
		$(".thumbnail_img").eq(n).addClass("border_red")
		}
	
	var clock = setInterval(imgChange,3000);
	$("#banner_box").mouseover(function(){
		clearInterval(clock);
		$('#btn_left').animate({left:"19%"});
		$('#btn_right').animate({right:"18%"});;
			}).mouseleave(function(){
			clock=setInterval(imgChange,3000);
			$('#btn_left').stop().animate({left:"-4%"});
			$('#btn_right').stop().animate({right:"-4%"});	
	});
			$("#btn_right").click(function(){
		if(n<$("#banner img").length-1){
			n=n+1;
			}else{
			n=0;	
		}
		$("#banner img").hide();
		$("#banner img").eq(n).fadeIn(1000);	
		$(".thumbnail_img").removeClass("border_red");
		$(".thumbnail_img").eq(n).addClass("border_red");	
	})
	
	$("#btn_left").click(function(){
		if(n>0){
			n=n-1;
			}else{
			n=$("#banner img").length-1;	
		}
		$("#banner img").hide();
		$("#banner img").eq(n).fadeIn(1000);
		$(".thumbnail_img").removeClass("border_red");
		$(".thumbnail_img").eq(n).addClass("border_red");		
	})
	});

	/*--点击切换图片--*/
	$(document).ready(function(){
	$(".thumbnail li").click(function(){
		n=$(".thumbnail li").index(this)
		$("#banner img").hide();
		$("#banner img").eq(n).fadeIn(1000);
		$(".thumbnail_img").removeClass("border_red");
		$(".thumbnail_img").eq(n).addClass("border_red");
	})
	});

	
/*导航商品分类*/
$(document).ready(function(){
	$('.sort_show').mouseover(function(){
		$('#sort_hover').show();
		$('.pro_hover').addClass("pro_hovered").removeClass("pro_hover");
	});
	$('.sort_show').mouseleave(function(){
		$('#sort_hover').hide();
		$('.pro_hovered').removeClass("pro_hovered").addClass("pro_hover");
	});
	//删除按钮
	$(".delete-btn").click(function(){
		$(".pop-box").show();
		$(".comfirm-box").show();
	})
	//关闭按钮
	$(".close-box").click(function(){
		$(".pop-box").hide();
		//$(".address-choice").hide();
		$(".comfirm-box").hide();
		$(".pay-wid").hide();
	})
//删除按钮
		$(".delete").click(function(){
				/*var conf = confirm('确定删除此商品吗？');
                    if (conf) {
                        $(this).parents("tr").remove();
                       }*/
                      $(".pop-box").show();
					  $(".comfirm-box").show();
					  $(this).parents(".cats-show").show();
                    });
//购物车弹出
$(".buy_img").mouseover(function(){
	$(".cats-show").show();
	$(".buy_car").addClass("buy-img-bottom");
});
$(".buy_img").mouseleave(function(){
	$(".buy_car").removeClass("buy-img-bottom");
	$(".cats-show").hide();
});
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
