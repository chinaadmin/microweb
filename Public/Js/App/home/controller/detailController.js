define(function(require , exports ,module){
	var $ = require('jquery');
	require('model/jquery.spinner.js');
	require("ueditorPath/ueditor.parse.min.js");
	var common = require("common");
	 var diDialog = require('model/diDialog');
	$('.spinnerExample').spinner({value:1, min:1});
    //pane_img
    uParse('.pane_img', {
        rootPath: window.UEDITOR_HOME_URL
    })
    /*详情页选项卡*/
    $(function(){	
    	$('.tab_btn').click(function(){
    		$(this).addClass('hit').siblings().removeClass('hit');
    		$('.panes>div:eq('+$(this).index()+')').show().siblings().hide();	
    	})
    })
    
    var main = {
    	checkedNorms:function(){
    		var _this = this;
    		$('.norms_group .norms_value').on('click',function(){
    			$(this).addClass('spec-active').siblings('.norms_value').removeClass('spec-active');
    			_this.selectedNorms();
    			var path = $(this).attr('data-img');
    			if(path){
    				$('#vertical img').eq(0).attr('src',path);
    				$('#vertical img').eq(0).attr('data-big',path);
    			}
    		})
    	},
    	selectedNorms:function(){
    		var att_id =[];
    		$(".norms_group").each(function(i,dom){
    			var obj = $(dom).find('.spec-active');
    			if(obj.length<1){
                 obj = $(dom).find('.norms_value').eq(0);
    			}
                obj.addClass('spec-active');
                var id = obj.attr('data-id');
                if(id){
                    att_id.push(id);
                }
    		})

            att_id = att_id.join("_");

    		if(norms_attr){
    			for(i in norms_attr){
    				if(att_id == (norms_attr[i]['goods_norms_link'])){
    					$('.detail_price').html("<i>￥</i>"+norms_attr[i]['goods_norms_price']);
    					$('.goods_number').html("库存:"+norms_attr[i]['goods_norms_number']+"件");
                        $('#norms').data('price',norms_attr[i]['goods_norms_price']).data('number',norms_attr[i]['goods_norms_number']).val(att_id);
    				}
    			}
    		}
    	},
        bindEvent:function(){
            $('.btnCart').click(function(){
                var cart = require('model/cart');
                var goods_id = $('#goods_id').val();
                var num = $('#num').val();
                var norms = $('#norms').val();
                cart.add(goods_id,num,norms);
            });

            $('.btn-buy').click(function(){
                var cart = require('model/cart');
                var goods_id = $('#goods_id').val();
                var num = $('#num').val();
                var norms = $('#norms').val();
                cart.buy_now(goods_id,num,norms);
            });
        },
    	/**
    	 * alt显示
    	 */
    	show_alt:function(){
    		$(".norms_alt").hover(function(){
    			$(this).find(".alt").show();
    		},function(){
    			$(this).find(".alt").hide();
    		})
    	},
    	/**
    	 * 收藏
    	 */
    	collect:function(){
    		 $('.btnCollect').click(function(){
                 var goods_id = $('#goods_id').val();
                 var norms = $('#norms').val();
                 $.ajax({
                	 url:common.U("Collect/addCollect"),
                	 type:"post",
                	 dataType:"json",
                	 data:"goods_id="+goods_id+"&norms="+norms,
                	 success:function(result){
                		  if (result.status !== common.success_code) {
                			  if(result.status === 'NOT_LOGGED_IN'){
                				  $('#js-login-mask').show();
                                  $('#js-login-box').show();
                                  $('#js-login-box').center();
                                  var passport = require('controller/passportController');
                                  passport.poplogin();
                			  }else{
                				  diDialog.Alert(result.msg);  
                			  }
                		  }else{
                			  var val = parseInt($("#collect").text())+1;
                			  $("#collect").html(val);
                			  diDialog.Alert("收藏成功！");
                		  }
                	 }
                 })
             });
    	}
    };
    //百度分享
	window._bd_share_config = {
		"common" : {
			"bdSnsKey" : {},
			"bdText" : $("#goods_title").text(),
			"bdMini" : "2",
			"bdPic" : window.JTCONFIG.urlHost+$("#onlickImg img").attr("data-big"),
			"bdStyle" : "0",
			"bdSize" : "16"
		},
		"share" : {}
		/*"image" : {
			"viewList" : [ "qzone", "tsina", "tqq", "renren", "weixin" ],
			"viewText" : "分享到：",
			"viewSize" : "16"
		},*/
		/*"selectShare" : {
			"bdContainerClass" : null,
			"bdSelectMiniList" : [ "qzone", "tsina", "tqq", "renren", "weixin" ]
		}*/
	};
	with (document)
		0[(getElementsByTagName('head')[0] || body)
				.appendChild(createElement('script')).src = 'http://bdimg.share.baidu.com/static/api/js/share.js'];
	 //关闭按钮
    $(".close-box").click(function(){
        $(".pop-box").hide();
        $(".comfirm-box").hide();
    });
    main.index = function(){
    	 main.selectedNorms();
    	 main.checkedNorms();
    	 main.bindEvent();
    	 main.show_alt();
    	 main.collect();
    }
    module.exports=main;
});