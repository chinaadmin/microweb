define(function(require , exports ,module){
	var $ = require("jquery"),tool = require('model/tool'),common = require('common'),main = {};
	require('jquery_validate');
	main.index = function(){
		common.date();//引入时间插件
		tool.check_all("#check_all",'input[name="id[]"]');
		tool.batch_del($('.js-del'),$('input[name="id[]"]'));
	}
	//生成并布置标签
	function labelOnPic(drag){
		$('#labelGroup :checked').each(function(i){
			checkBoxClick($(this),drag,true);
		});
	}
	//重设label
	function resetLabel(targetClass){
		//elp_id++elp_top++elp_left
		var resetLabelClass = targetClass,resetStr;
		resetStr = $('.'+resetLabelClass).val();
		resetStr = resetStr.split('++');
		resetStr[1] = "elp_top";
		resetStr[2] = "elp_left";
		resetStr = resetStr.join('++');
		$('.'+resetLabelClass).val(resetStr);
		//关闭选中状态
		$('.'+resetLabelClass).closest('label').find('span.checked').removeClass('checked');
		$('.'+resetLabelClass).closest('label').find(':checkbox').attr('checked',false);
	}
	/**
	 * 增加某个标签 给生成的标签绑定相应事件
	 * @param _self 触发生成标签的多选框对象
	 * @param drag 移动事件
	 * @param isSetPos 是否要设置位置
	 */
	function checkBoxClick(_self,drag,isSetPos){
		var label,html,ident,labelStyle = '';
		if(!_self.is(':checked')){ 
			//存在则去除该标签和相关信息...
			 $('#'+_self.data('ident')).remove();
			return;
		}
		label = _self.data('label');
		ident = _self.data('ident');
		if(isSetPos){
			labelStyle = $('.'+ident).val(); //elp_id++elp_top++elp_left+1+8
			labelStyle = labelStyle.split('++'); 
			if(labelStyle[1] != 'elp_top'){ //初始化时有位置信息
				labelStyle = "top:"+labelStyle[1]+"px;left:"+labelStyle[2]+"px;";
			}else{
				labelStyle = '';
			}
		}
		html = "<div style = '"+labelStyle+"' class = 'moveCard' id = '"+ident+"'>"+label+"<span class='pull-right hidden-print' aria-hidden='true'>×</span></div>";
		$('#labelContainer').append($(html));
		$('.moveCard span').unbind('click').click(function(){ //关闭已生成的标签 同时恢复数据
			$(this).parent().remove();
			resetLabel($(this).closest('.moveCard').attr('id'));
		});
	  $('.moveCard').unbind('mousedown').mousedown(function(event){ //移动标签
        	drag(event,this);
       });
	}
	//给标签checkbox增加单击事件
	function label(drag){
		$('#labelGroup :checkbox').click(function(e){
			checkBoxClick($(this),drag);
		});
	}
	main.edit_add = function(){
        $('#adit_add').validate($.extend(tool.validate_setting,{
            rules: {
            	ft_name:{
            		required:true
            	}
            },
            messages: {
            	ft_name:{
            		required:'模板名称不为空'
            	}    	
            },
            submitHandler: function (form) {
            	//生成字段值
            	var html;
            	html = encodeURIComponent($('#myHtml').html());
            	$('[name=et_content_html]').val(html);
                tool.formAjax(form,function(data){
                    require.async('base/jtDialog',function(jtDialog){
                        if(data.status != tool.success_code){
                            jtDialog.showTip(data.msg);
                        }else{
                            jtDialog.showTip(data.msg,1,function(){
                                location.href = document.referrer
                            });
                        }
                    });
                    return false;
                });
            }
        }));
        $('[name=et_width],[name=et_height]').keyup(function(){
        	var _self = $(this),name = _self.attr('name'),contentObj = $('#content img');
        	if(name == 'et_width'){
        		contentObj.width(_self.val()+'mm');
        		return;
        	}
        	//et_height
        	contentObj.height(_self.val()+'mm');
        });
        
        /********************************** 鼠标拖动 start***************************************/
        var oDrag = "";
        var ox,oy,nx,ny,dy,dx;
        function drag(e,o){ //纪录开始时的事件位置
            var e = e ? e : event;
            var mouseD = document.all ? 1 : 0;
            if(e.button == mouseD){
                oDrag = $(o);
                //alert(oDrag.id);
                ox = e.clientX;
                oy = e.clientY;        
            }
        }
        function dragPro(e){
            if(oDrag != "")
            {    
                var e = e ? e : event;
                dx = parseInt(oDrag.offset().left);
                dy = parseInt(oDrag.offset().top);
                nx = e.clientX;
                ny = e.clientY;
                var leftStr = dx + ( nx - ox );
                var topStr = dy + ( ny - oy );
                var recordStrArr = '';
                oDrag.offset({left:leftStr});
                oDrag.offset({top:topStr});
                //记录相对位置
                recordStrArr = $('.'+oDrag.attr('id')).val();
                recordStrArr = recordStrArr.split('++');
                recordStrArr[1] = oDrag.position().top;
                recordStrArr[2] = oDrag.position().left;
                $('.'+oDrag.attr('id')).val(recordStrArr.join('++'));
                ox = nx;
                oy = ny;
            }
        }
        $(document).mouseup(function(){
        		oDrag = "";
        	});
        $(document).mousemove(function(event){
        		dragPro(event);
        });
        /********************************** 鼠标拖动 end***************************************/
        label(drag);
        labelOnPic(drag);
        //调整偏移
        $('.printOffestY').keyup(function(){
        	var _self = $(this);
        	$('.moveCard').each(function(i){
        		var offsetTop;
        		offsetTop = parseInt($(this).offset().top) + parseInt(_self.val());
            	$(this).offset({top:offsetTop});	
        	});
        });
        
        $('.printOffestX').keyup(function(){
        	var _self = $(this);
        	$('.moveCard').each(function(i){
        		var offsetLeft;
        		offsetLeft = parseInt($(this).offset().left) + parseInt(_self.val());
        		$(this).offset({left:offsetLeft});
        	});
        });
        //显示已经上传的图片在内容选项
        var timeCircle = setInterval(function(){
        	if($('#content img').attr('src') == ''){
            	if(window.uploaderResult && window.uploaderResult.success){
            		$('#content img').attr('src',window.uploaderResult.path);
            	}
            }else{
            	clearInterval(timeCircle);
            }
        },3000);
	}
	main.print = function(){
	/*	 var positon = $.parseJSON(globalPositonJson);
		 var count = positon.length,html = '',labelStyle;
		 for(var i = 0;i < count;i++){
			 labelStyle = '';
			 labelStyle = 'top:'+positon[i]['elp_top']+'px;left:'+positon[i]['elp_left']+'px;';
			 html += "<div style = '"+labelStyle+"' class = 'moveCard' id = 'moveCard_"+positon[i]['label_code']+"'>"+positon[i]['label_name']+"</div>";
		 }
		$('#PrintArea').append(html);*/
		//设置网页打印的页眉页脚为默认值 
		var HKEY_Root,HKEY_Path,HKEY_Key;      
		HKEY_Root="HKEY_CURRENT_USER";      
		HKEY_Path="//Software//Microsoft//Internet Explorer//PageSetup//";      
		//设置网页打印的页眉页脚为空      
		function PageSetup_Null()     
		{     
		   try{      
		       var Wsh=new ActiveXObject("WScript.Shell");      
		       HKEY_Key="header";      
		       Wsh.RegWrite(HKEY_Root+HKEY_Path+HKEY_Key,"");      
		       HKEY_Key="footer";      
		       Wsh.RegWrite(HKEY_Root+HKEY_Path+HKEY_Key,"");      
		   }catch(e){}      
		}    
		PageSetup_Null();
		//生成有位置信息的隐藏域 记取位置信息生成标签
		$('#print').click(function(){
			require.async("pulgins/print/jquery.PrintArea",function(){
	            $("#PrintArea").printArea();   
	        })
		});
		
	}
	module.exports = main;
})