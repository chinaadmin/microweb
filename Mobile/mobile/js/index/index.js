//补全轮播图
(function (){
	$.post(interfaceURL.home.banner,function(rtn) {
		var tmp = [],content = '',res = rtn.bannerList,bannerPoint = '',active,activeBody;
		for (var i = 0; i < res.length; i++) {
			if (res[i].photo) {//有图片才显示
				tmp.push(res[i])
			};
			if(i == 0){
				active = 'active';
			}else{
				active = '';
			}
			bannerPoint += '<li  style = "margin-right:5px;" data-target="#carousel-example-captions" data-slide-to="'+i+'" class="'+active+'" ></li>';
		};
		$('.carousel-indicators').html(bannerPoint);
		
		for (var i = 0; i < tmp.length; i++) {
			tmp[i].photo = (tmp[i].photo).replace(/\s+/img, '');
			//拼接href
			if(tmp[i].url){
				tmp[i].href = tmp[i].url;		
			}else if(tmp[i].linkPoint == 1){ //linkId 商品id
				tmp[i].href = '#';
				// tmp[i].href = '/html/commodity/detail.html?gid='+tmp[i].linkId;
			}else if(tmp[i].linkPoint == 2){ //linkId 商品分类
				tmp[i].href = '/html/list.html?cid='+tmp[i].linkId;
			}else if(tmp[i].linkPoint == 3){ //linkId 商品类型
				//tmp[i].href = '/html/commodity/detail.html?gid='+tmp[i].linkId;
				tmp[i].href = "#"			
			}else{
				tmp[i].href = "#"
			}
			if(i == 0){
				activeBody = 'active';
			}else{
				activeBody = '';
			}
			content += '\
				<div class="swiper-slide"><a href = "'+tmp[i]["href"]+'"><img style="width:100%;"  src = "'+tmp[i]["photo"]+'" /></a></div>\
				';
		};
		$('.swiper-wrapper').html(content);
		$('.swiper-wrapper img').bind('error',function(){
			$(this).attr('src','../images/banner.jpg');
		});
		
		$(document).ready(function(){
			var swiper = new Swiper('.swiper-container', {
				pagination: '.swiper-pagination',
			    paginationClickable: true,
			    centeredSlides: true,
			    autoplay: 4000,
			    autoplayDisableOnInteraction: false,
				loop: true,
				freeMode : false,
				speed:1000
			});
		});
		
	});
})()

