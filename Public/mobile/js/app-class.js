// JavaScript Document
$(document).ready(function(){
	//推荐左边的效果
	$(".list-title").each(function(index, element) {
		$(this).hover(function(){
			$(this).addClass("rem_left");
		},function(){
			$(this).removeClass("rem_left")
		}).click(function(){
			var it=$(this).attr('rel')
			$(".list-title").removeClass("rem_left1")
			$(this).addClass("rem_left1")
			if(it!='all'){
				//alert(it)
				$(".main_right li[rel="+it+"]").stop(false,true).show(1000);
				$('.main_right li[rel!='+it+']').stop(false,true).hide(1000);
				//$('.remmain_right li').animate({'opacity':1})
			}else{
				$('.main_right li').stop(false,true).show(1000);
			}
			
		})
	});
	//推荐右边的效果
	
	$(".list-title").each(function(index, element) {
		$(this).on("mouseover",function(){
			$(this).addClass("movie_scroll_hov")
		}).on("mouseout",function(){
			$(this).removeClass("movie_scroll_hov")
		})
	});
})

//控制左边侧栏的宽度
$(document).ready(function(){
    var olistheight= function(){
    var oheader=$('.header-box').height();
    var ofooter=$('.nav-box').height();
    var owindow=$(window).height();
    $('.swiper-container').height(owindow-oheader-ofooter);
    }
    olistheight();
    window.onresize=function(){
      olistheight();
    };
    //点击上跳的效果
    

    $(".list-title").click(function(){
    	var new_index =$(this).index();
    		$(".list-slide").css({
    			"transform":"translate3d(0px,"+new_index*-41+"px,0px)",
    			"transition":"all 0.5s ease-in-out 0s",
    		});
    	
    	//alert(-oindex*41);
    });
	
  });



