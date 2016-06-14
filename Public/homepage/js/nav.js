// JavaScript Document
$(document).ready(function(){
  $('#nav-menu .menu > li').hover(function(){
	$(this).find('.children').animate({ opacity:'show', height:'show' },300);
	$(this).find('.xialaguang').addClass('navhover');
}, function() {
	$('.children').stop(true,true).hide();
	$('.xialaguang').removeClass('navhover');
});
  $('#flip .dede_pages ul li').hover(function(){
	  var _this = $(this);
	  var href = _this.children('a').attr('href');
	  var thisClass = _this.attr('class');
	  if(!href && !thisClass){
		  _this.css({'background':'#fff'});
	  }
	  
  })
	
});
$(document).ready(function(){
    $("#nav-menu .menu .stmenu :eq(40)").css({"width":"122px","left":"0","top":"37px",});
    $(".qr").mouseover(function(){
    	$(".qr_img").show();
    })
    $(".qr").mouseleave(function(){
    	$(".qr_img").hide();
    })
});