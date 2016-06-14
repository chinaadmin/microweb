define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');
    var template = require('template');
    //颜色选择器
    function colorPicker(){
    	require("pulgins/color-picker/spectrum.css");
    	 require.async("pulgins/color-picker/spectrum.js",function(){
    		 $("#custom").spectrum({
     	        color: bd_color,
     	        preferredFormat: "hex",
     	        showInput: true,
     	        chooseText:"选择",
     	        cancelText: "取消",
     	       /* change: function(color) {
     	           color.toHexString(); // #ff0000
     	        }*/
     	    });	 
    	 })
    }
    jQuery.validator.addMethod("ad_time_required", function(value, element, param) {
        if($('#start_time').val() == ''||$('#end_time').val() == ''){
            return false;
        }
        return true;
    }, "请选择投放时间");

    jQuery.validator.addMethod("photo_required", function(value, element, param) {
        var photo = $("input[name='photo']").val();
        if(typeof(photo) == 'undefined' || photo == ''){
            return false;
        }
        return true;
    }, "请上传图片");

    /**
     * 验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            ignore:'',
            rules: {
                name: {
                    required: true
                },
                sel_time:{
                    ad_time_required:true
                },
                photo_vaild:{
                    photo_required: true
                }
            },
            messages: {
                name: {
                    required: "广告名称不能为空"
                }
            }
        },tool.validate_setting));
    };

    /**
     * 验证
     */
    var edit_goods_validate = function(){
        $('#from_edit').validate($.extend({
            ignore:'',
            rules: {
                sel_time:{
                    ad_time_required:true
                },
                photo_vaild:{
                    photo_required: true
                },
//                goods_id:{
//                    required: true,
//                    min:1
//                }
            },
            messages: {
//                goods_id: {
//                    required: "请选择商品",
//                    min: "请选择商品"
//                }
            }
        },tool.validate_setting));
    };

    //投放时间
    function put_in_time(){
        require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true
            });
            $('.end_time').datepicker({
                autoclose:true
            });
            $('#sel_time').change(function(){
                $('#end_time').val($(this).val());
                $('.end_time').datepicker('update');
            });
        });
    }
    //图片预览
    require.async('fancyapps',function(){
		 $(".lightbox").fancybox({
			 'width'                : '100%',  
			 'height'               : '100%',  
			 'autoScale'            : false,  
			 'transitionIn'         : 'none',  
			 'transitionOut'        : 'none', 
		 });
	 });
    //图片裁剪
    var cropper = function(){
    	require.async("pulgins/cropper/cropper.js",function(){
	    	$(".cropper").on("click",function(){
	    		var src = $(".img-box img").attr("src");
	    		$('.cropper-img > img').attr("src",src);
	    		//模泰框
	    		$("#add-cropper").modal('show').css({
	                'margin-left': function () {
	                    return -($(this).width() / 2);
	                }
	            });
	    		var data = {'path':src};
	    		var $image = $('.cropper-img > img'),cropBoxData,canvasData;
	    		$("#add-cropper").on("shown",function(){
		    		//裁剪
		    		$image.cropper({
		    			  aspectRatio:3/1,
		    			  //minCropBoxWidth:650,
		    			  minCropBoxHeight:325,
		    			  autoCropArea: 0.65,
		    			  dragCrop:false,
		    			  strict: true,
		    			  guides: true,
		    			  highlight: true,
		    			  center:true,
		    			  cropBoxMovable: true,
		    			  cropBoxResizable: false,
		    			  crop: function(e) {
		    				  data.x = e.x;
		    				  data.y = e.y;
		    				  data.width = e.width;
		    				  data.height = e.height;
		    				  data.rotate = e.rotate;
		    				  data.scaleX = e.scaleX;
		    				  data.scaleY = e.scaleY;
		    			  }
		    			});
	    		})
	    		
	    	 //执行裁剪
    		$("#cut-btn").on("click",function(){
	    		require.async('base/jtDialog',function(jtDialog) {
	    			tool.doAjax({
		    			url:common.U("ad/cropper"),
		    			data:data
		    		},function(result){
		    			if(result.status!=tool.success_code){
		    				jtDialog.error(result.msg);
		    			}else{
		    				$("#mobile-photo").show();
		    				$("#mobile-photo img").attr("src",result.result+"?"+Math.random()*100);
		    				$("#mobile-photo").attr("href",result.result+"?"+Math.random()*100);
		    				$("#add-cropper").modal('hide');
		    			}
		    		})
	    		});
    		})
	    	})
    	});
    }
    
    //链接类型
    var url_type = function(){
    	judeType();
    	$("input[name='url_type']").on('click',function(){
    		judeType();
    	})
    	function judeType(){
    		var val = $("input[name='url_type']:checked").val();
    		if(val==1){
    			$(".do-check").fadeIn('slow');
    			$("#link_url").fadeOut('slow');
                $("#link_point").fadeIn('slow');

                $("#link_point_out").fadeOut('slow'); // 去掉外部链接活动类型选择框
    			$("#link_id_out").fadeOut('slow');   //  去掉外部链接链接id选择框

    			check_goods_cat();
    		}
    		if(val==2){
    			$(".do-check").fadeOut('slow');
    			$("#link_url").fadeIn('slow');

                $("#link_point_out").fadeIn('slow'); // 显示外部链接活动类型选择框
                $("#link_id_out").fadeIn('slow');   //  显示外部链接链接id选择框

    			$("#link_point").fadeOut('slow');

                
    		}
    	}
    }
    //选择商品
    var check_goods = function(){
    	 require.async('base/ajaxListComponent',function(list){
             //打开添加试卷模态框
             $('#sel-goods').click(function(){
                 var config = {
                     page_id : 'pagebar',
                     list_id : 'paper-list',
                     list_tpl_id : 'tpl-paper-list',
                     url : common.U('Goods/get_ad_lists',{'featured':$('#paper-list').data('featured')}),
                     page_size:8
                 };
                 list.init(config);
                 list.get_lists();
                 $("#goods-modal").modal('show').css({
                     //width: '960',
                     'margin-left': function () {
                         return -($(this).width() / 2);
                     }
                 });
             });
         });

         //选择商品
         $('#goods-modal').on('click' ,'.js-sel-goods',function(){
             var goods_id = $(this).parents('tr').children("td:eq(0)").html();
             var name = $(this).parents('tr').children("td:eq(1)").html();
             var goods_sn = $(this).parents('tr').children("td:eq(2)").html();
             var html = '<p>商品编号：'+goods_id+'</p>'+
                 '<p>商品货号：'+goods_sn+'</p>'+
                 '<p>商品名称：'+name+'</p>';
             $('#goods-info').html(html);
             $('#goods_id').val(goods_id);
             $("#goods-modal").modal('hide');
         });
    }
    
    //切换分类、商品
    var check_goods_cat = function(){
    	docheck();
    	$("input[name='link_point']").on("click",function(){
    		docheck();
    	})
    	function docheck(){
    		var val = $("input[name='link_point']:checked").val();
    		var url_type = $("input[name='url_type']:checked").val();
    		if(url_type==1){
	    		if(val==1){
	    			$("#check_good").fadeIn('slow');
	    			$("#check_cat").fadeOut('slow');
	    		}
	    		if(val==2){
	    			$("#check_good").fadeOut('slow');
	    			$("#check_cat").fadeIn('slow');
	    		}
    		}
    	}
    }
    //选择分类
    var check_cat = function(){
    	$("#sel-cat").on('click',function(){
    		 tool.doAjax({
    			 url:common.U("Ad/getCategory")
    		 },function(result){
    			if(result.result){
    			  var data = result.result;
    			  var str ="";
    			   for(i in data){
    				   str+= "<tr>"
    				   str+="<td>"+data[i].cat_id+"</td>";
    				   str+="<td>"+data[i].fullname+"</td>";
    				   str+="<td><bttton class='btn blue-stripe js-sel-cat'>选择</bttton></td>"
    				   str+="</tr>";
    			   }
    			  $("#paper-cat-list").html(str);
    			}
    		 })
    		 $("#cat-modal").modal('show').css({
                 'margin-left': function () {
                     return -($(this).width() / 2);
                 }
             });
    	})
    	$("#cat-modal").on('click',".js-sel-cat",function(){
    		 var cat_id = $(this).parents('tr').children("td:eq(0)").html();
             var name = $(this).parents('tr').children("td:eq(1)").html();
             var html = '<p>分类名称：'+name+'</p>';
             $('#cat-info').html(html);
             $('#cat_id').val(cat_id);
             $("#cat-modal").modal('hide');
    	})
    }
	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        edit : function(){
            edit_validate();
            put_in_time();
            colorPicker();
            cropper();
            check_goods();
            url_type();
            check_goods_cat();
            check_cat();
        },
        edit_3:function(){
            edit_goods_validate();
            put_in_time();
            check_goods();
            url_type();
            check_cat();
            check_goods_cat();
        }
	};
	module.exports = main;
});