define(function(require, exports, module) {
	var $ = require("jquery");
	var Common = require("common");
	var jtDialog = require('base/jtDialog');
	var common = {
	   //百度编辑器
	  "ueditor" : function(opt){
		  require.async(["pulgins/ueditor/1.4.3/ueditor.config.js","ueditor"],function(o){
			  if(opt.options){
				  var tollbars = opt.options;
			  }else{
				  var tollbars = window.UEDITOR_CONFIG.toolbars;  
			  }
			  var ue = UE.getEditor(opt.id,{
				  toolbars:tollbars,
				  initialFrameWidth:opt.width,  
			      initialFrameHeight:opt.height,
			      autoHeightEnabled:true,
			      allowDivTransToP:false,
			      enableAutoSave:false,
			      enterTag:'br',
			      zIndex:0,
			      retainOnlyLabelPasted:true,
			      //pasteplain :true,
			      //imagePopup:false,
			      saveInterval:50000000000000000
			  });
		  });
		  
	  },
	  //百度编辑器前台版
	  "umeditor" : function(opt){
		  require.async("pulgins/umeditor/1.2.2/themes/default/css/umeditor.min.css");
		  require.async(["pulgins/umeditor/1.2.2/umeditor.config.js","umeditor"],function(o){
			  if(opt.options){
				  var tollbars = opt.options;
			  }else{
				  var tollbars = window.UMEDITOR_CONFIG.toolbars;  
			  }
			  var um = UM.getEditor(opt.id,{
				  toolbars:tollbars,
				  initialFrameWidth:opt.width,  
			      initialFrameHeight:opt.height,
			  });
		  });
		 
	  },
	  //上传插件plupload
	  'upload' : function(opt){
		  var url =window.JTCONFIG.jtUpload+"/Image/imageOne";
		  require.async("pulgins/plupload/2.1.2/js/upload.css");
		  var option = {
			            browse_button : 'browse', // 触发文件选择对话框的按钮，为那个元素id
			            url : url, // 服务器端的上传页面地址
			            flash_swf_url : 'js/Moxie.swf', // swf文件，当需要使用swf方式进行上传时需要配置该参数
			            silverlight_xap_url : 'js/Moxie.xap', // silverlight文件，当需要使用silverlight方式进行上传时需要配置该参数
			            multipart:true,
			            file_data_name:'file',
			            multi_selection:true,        // 允许选择多图
			            multipart_params:{
			            	"session_hao":window.JTCONFIG.SESSION
			            }
			           };
		  if(opt){
			  option = $.extend(option,opt);
		  }
		  require.async("plupload",function(o){
			    var uploader = new plupload.Uploader(option);    
			    uploader.init();
                var attrId = option.objupload;
			    // 绑定各种事件，并在事件监听函数中做你想做的事
			    uploader.bind('FilesAdded',function(uploader,files){
			    	//var type = $('#plupload').attr('type');
			    	//if(type=='one'){
			    	   var inputObj = attrId.find('.images img');
					   var imagePath = inputObj.attr('src');
					   var id = inputObj.attr('att_id');
					   var login = $(".plupload").attr('login');
					   var model = $(".plupload").attr('model');
					   var posts = {
							   "session_hao":window.JTCONFIG.SESSION,
							   'oldPath':imagePath || "",
							   'id' : id || 0,
							   'thumb':attrId.attr('thumb') ,
							   'thumbwidth':attrId.attr('thumbwidth'),
							   'thumbheight':attrId.attr('thumbheight'),
							   'thumbtype':attrId.attr('thumbtype'),
							   'login': login || "",
							   "model":model
					   };
					   var new_url = inputObj.attr('url');
					   if(new_url){
						   uploader.setOption('url',new_url);  
					   }
					   if(posts){
						 uploader.setOption('multipart_params',posts);
					   }	
			    	//}
			        uploader.start(); // 调用实例对象的start()方法开始上传文件，当然你也可以在其他地方调用该方法
			    });
			    uploader.bind('UploadProgress',function(uploader,file){
			    	var size = file.percent+"%";
			    	var str = "<div class='progress-info'>";
			    	str+= "<p style='width:"+size+";'></p><span>"+size+"</span></div>";
                    var progress = $('#plupload_'+option.browse_button +' .browse_progress');
                    progress.html(str);
			    	if(parseInt(file.percent)== 100){
			    		setTimeout(function(){
                            progress.html('');
			    		},400)
			    	}
			    });
			    uploader.bind('FileUploaded',function(uploader,file,response){
			    	var result = response.response;
			    	//全局结果
			    	result = JSON.parse(result);
			    	window.uploaderResult = result || {};
			    	if(result.success){
			    		common.callback(result,attrId);
			    	}else{
			    		if( $(".plupload").attr('model')==1){
			    			require.async('model/diDialog',function(diDialog){
			    				diDialog.Alert(result.error);
			    			});
			    		}else{
			    			jtDialog.alert(result.error);	
			    		}
			    	}
			    })
			
		  })
	  },
	  //图片上传成功回调
	  callback:function(result,attrId){
		  var str = "<div class='img-box'>";
			str+= "<img width='75' height='75' att_id ='"+result.id+"' src='"+result.path+"'/>";
			str+="<input type='hidden' value='"+result.id+"' name='"+attrId.data('name')+"'/>";//附件id
			str+="<span><s class='del' att_id='"+result.id+"' data='"+result.path+"'></s></span>";
			var type = attrId.attr('type');
			if(type=='one'){
				str += "<a href='"+result.path+"' class='lightbox'>预览</a></div>";
                attrId.find('.images').html(str);
			}	
			if(type=='many'){
				str += "<a href='"+result.path+"' class='lightbox' rel='group'>预览</a></div>";
                attrId.find('.images').append(str);
                uploadAtt.imgHover();
                uploadAtt.delFile();
			}
	  },
	}
	
	//上传文件操作
	var uploadAtt ={
			//删除文件
			delFile : function(){
				var anum= 0;
				$('.plupload .del').on('click',function(e){
					e.stopPropagation();
					e.preventDefault();
					var _this = $(this);
					var data = _this.attr('data');
					var id = _this.attr('att_id');
					var url =window.JTCONFIG.jtUpload+Common.U("/Image/del");
					anum += 1;

					if(anum != 1){
						return false;
					}
					
					if(confirm("删除后数据不可恢复，确定继续？")){
						$.post(url,{'filename':data,"id":id,"session_hao":window.JTCONFIG.SESSION},function(result){
							_this.parents('.img-box').remove();
						});
						anum=0;
					}else{
						anum=0;
					}
					
					
				})
			},
			//设置封面
			setDefault:function(){
				 if($('.plupload').attr('clickdefault')){
					   $('.plupload').on('click','.img-box',function(e){
						 str = "<P class='cover'>";
						 var id = $(this).children('img').attr('att_id');
						 str+= "<input type='hidden' name='attachId[default]' value='"+id+"'/></p>";
						 $(this).parents('.plupload').find(".cover").remove();
						 $(this).parents('.plupload').find("img").removeClass('img-border');
						 $(this).append(str);
						 $(this).children('img').addClass('img-border');
					  })
				}
			},
			//显示删除
			imgHover:function(){
				var type = $('.plupload').attr('type');
				if(type == "many"){
					$('.plupload .img-box').hover(function(){
						$(this).find("span").animate({
							"height":"20px"
						},300)
					},function(){
						$(this).find("span").animate({
							"height":"0px"
						},300)
					})
				}
			},
			//lightbox 图片展示/预览
			fancyapps:function(options){
			 require.async('fancyapps',function(){
				 $(".img-box .lightbox").fancybox(options);
			 });
			}
	}
	var doeach = function(){};
	/**
	 * mod 模式：<1:full模式 2：精简模式  3：自定义模式>
	 * use  编辑器：<editor:ue编辑器  umeditor:um编辑器>
	 */
	doeach.prototype.ueditor = function(){
		$('textarea').each(function(i){
			var _this = $('textarea').eq(i);
			var mod = _this.attr('mod');
			var use = _this.attr('use');
			var opt = {
					'width':_this.attr('width') || "100%",
					'height':_this.attr('height') ||"320",
					"id" : _this.attr('id') ||"container"
				};
			mod = parseInt(mod);
			var options ="" ;
			//编辑器
			if(use == 'editor'){
					switch(mod){
					  case 1:
						  options = "";
						  break;
					  case 2:
						  options = [['fullscreen', 'source', 'undo', 'redo', 'bold']];
						  break;
					  case 3 :
						  if(typeof tollbars == "object"){
						   options = tollbars;
						  }
					      break;
						  
					}
					opt.options = options;
					common.ueditor(opt);
			}
			//um编辑器
			if(use == 'umeditor'){
				switch(mod){
				case 1:
					options="";
					break;
				case 2:
					if(typeof tollbars == "object"){
						var options = tollbars;	
					}
					break;
				}
				opt.options = options;
				common.umeditor(opt);
			}
		})
	};
	
	/**
	 * 上传插件
	 * selector:jquery选择器
	 */
    doeach.prototype.eachUpload=function(selector){
    	var selector = selector || '.plupload';
    	$(selector).each(function(i){
           doeach.upload($(this));
        });
    };
    doeach.prototype.upload =function(obj){
    	 if((typeof obj=='object') && obj.attr('use')=='upload'){
             var type = obj.attr('type');
             var opt = {};
             opt.objupload = obj;
             opt.browse_button = obj.find(".browse").attr('id');
             //单图上传
             if(type=='one'){
                 opt.multi_selection=false
             }
             uploadAtt.imgHover();
             uploadAtt.setDefault();
             uploadAtt.delFile();
             uploadAtt.fancyapps({
//             	zoomSpeedChange:500,
//             	speedIn:500,
                 nextSpeed  : 550,
             	loop:false,
             	prevEffect		: 'none',
         		nextEffect		: 'fade',
         		helpers		: {
         			title	: { type : 'inside' },
         			buttons	: {}
         		},
         		afterLoad : function() {
 					this.title = 'Image ' + (this.index + 1) + ' of ' + this.group.length + (this.title ? ' - ' + this.title : '');
 				}
             });
             common.upload(opt);
         }
    };
    var doeach = new doeach();
    doeach.ueditor();
    doeach.eachUpload();
    module. exports=doeach;
});