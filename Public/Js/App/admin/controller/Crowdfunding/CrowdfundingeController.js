function addGoods(e){
	e = e || window.event;
	var t = e.target || e.srcElement;
	if(t.tagName == 'TD'){
		t = t.parentNode;
	}else if(t.tagName == 'IMG'){
		t = t.parentNode;
		t = t.parentNode;
	}
	t.outerHTML = '';
}
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	require('jquery_validate');
	jtDialog = require('base/jtDialog');
	main = {
			crowdfunding_pay_goods:''
	};
	main.index = function(){
		tool.del($('.js-del'));
	}
    function addUploadEvent(jqueryObj){ //给新增加的上传插件增加事件
  	  require.async("base/plugin.js",function(doeach){
  		  doeach.eachUpload(jqueryObj);
        });
    }
	main.planEvents = function(parentJquery){//给方案添加事件
  	  var period_unit = 0; //1 2 3 年季月
  	  var period_count = 0;//单位个数
  	  var pay_type = 0,period_unit_obj = parentJquery.find('[name="cd_period_unit[]"]');
  	  var period_count_obj = parentJquery.find('[name="cd_period_count[]"]');
  	  var unitName = parentJquery.find("[name='cd_period_unit[]'] :selected").text();addGoodsObj = parentJquery.find('.addGoods'),delPlanObj = parentJquery.find('.delPlan'),pay_type_obj = parentJquery.find('[name="cd_pay_type[]"]');
  	  unitName = unitName.substr(0,1);
  	  delPlanObj.click(function(event){
  		  if($('.planDiv').size() == 1){
  			  return;
  		  }
  		  parentJquery.fadeOut("normal",function(){
  			   $(this).remove();
  		 });
  	  });
  	  //isNumeric
  	  period_unit_obj.change(function(){
	    	  period_unit = parseInt($(this).val());
	    	  var str = '<option >单位个数</option>',total,changeObj = $(this).closest('.control-group').find('[name="cd_period_count[]"]');
	    	  unitName = $(this).find(":selected").text();
	    	  unitName = unitName.substr(0,1);
	    	  if(!$.isNumeric(period_unit)){
	    		  period_unit = 0;
	    		  changeObj.html(str).next().html('');
	    		  pay_type_obj.hide();
	    		  $(this).closest('.control-group').next('.pay-type-group').hide();
	    		  return;
	    	  }
	    	  if(period_unit == 1){
	    		  total = 5;
	    	  }else if(period_unit == 2 ){
	    		  total = 4;
	    	  }else{
	    		  total = 12;
	    	  }
	    	  for(var i=1;i<=total;i++){
	    		  str += "<option value = '"+i+"'>"+i+"</option>";
	    	  }
	    	  changeObj.html(str).next().html(unitName);
	      });
	      period_count_obj.change(function(){
	    	  pay_type_obj = parentJquery.find('[name="cd_pay_type[]"]');
	    	  period_count = parseInt($(this).val());
	    	  if(!$.isNumeric(period_count)){
	    		  period_count = 0;
	    		  pay_type_obj.hide();
	    		  $(this).closest('.control-group').next('.pay-type-group').hide();
	    		  return;
	    	  }
	    		  pay_type_obj.show();
	    	 
	      });
	      
	      pay_type_obj.change(function(){
	    	  period_count = parentJquery.find('[name="cd_period_count[]"] :selected').val();
	    	  pay_type = parseInt($(this).val()),inputStr = '<table>';
	    	  if(!$.isNumeric(pay_type)){
	    		  pay_type = 0;
	    		  $(this).closest('.control-group').next('.pay-type-group').hide();
	    		  return;
	    	  }
		      var group_control,tmpNum = 0;
		   	  if(pay_type == 1 || pay_type == 3){
		   		tmpNum = 1;
	    	  }else if(pay_type == 2){	
			   	tmpNum = period_count;
	    	  }
		   	  var tmpArr = [];
		   	for(var i = 1;i <= tmpNum;i++){
		   		tmpArr.push( '第'+i+unitName+'&nbsp;<input type="text" class = "span2 perPay">&nbsp;元&nbsp;&nbsp;&nbsp;');
		   		if(tmpNum >= 3){
		   			if(tmpArr.length == 3){
			   			inputStr += '<tr><td>' +tmpArr.join('')+ '</td></tr>';
			   			tmpArr = [];
		   			}else if( i == tmpNum){
		   				inputStr += '<tr><td>' +tmpArr.join('')+ '</td></tr>';
		   			}
		   		}else if(tmpNum < 3 && i == tmpNum){
		   			inputStr += '<tr><td>' +tmpArr.join('')+ '</td></tr>';
		   		}
		   	}
		   	inputStr += '</table>';
	 	    group_control = "<div  class = 'control-group pay-type-group'>\
				<label class = 'control-label'></label>\
				<div class = 'controls'>\
					"+inputStr+"</div>\
			</div>";
	 	    $(this).closest('.control-group').next('.pay-type-group').remove();
	 	   	$(this).closest('.control-group').after(group_control);
	      });
	      addGoodsObj.click(function(){
	    	  var html = '<tr  ondblclick = "javascript:addGoods(event);">',goodIndex,myTable = $(this).closest('.control-group').next().find('table');
	    	  var uploadObj = $('#uploadTmp');
	    	  var 	yumSuffix = uploadObj.find('.plupload').attr('id'),suffix;
	    	  yumSuffix = yumSuffix.split('_')[1]+'\"';
	    	  suffix = 'browseAdd' + ($('.plupload').size() + 1)+'\"';
	    	  var uploadHtml = uploadObj.html();
	    	 var reg = new RegExp(yumSuffix,"i");
	    	 while(uploadHtml.indexOf(yumSuffix) > 0) //重写上传html
	    	  {
	    		 uploadHtml = uploadHtml.replace(reg,suffix);
	    	  }
	    	  goodIndex = myTable.find('tr').size();
	    	  html += '<td class = "goodsPic">'+uploadHtml+'</td>';
	    	  html += '<td>商品' + goodIndex + ':&nbsp;&nbsp;</td>';
	    	  html += '<td class = "goodsName" contenteditable = "true"></td>';
	    	  html += '<td class = "goodsSubhead"  contenteditable = "true">&nbsp;&nbsp;&nbsp;</td>';
	    	  html += '</tr>';
	    	  var tableDiv = $(this).closest('.control-group').next();
	    	  tableDiv.find('tr').first().after(html);
	    	  tableDiv.show();
	    	  addUploadEvent($('#plupload_'+suffix.replace('"','')));
	      });
	}
	main.checkPlan = function(){
		var allPlan = $('.planDiv'),returnVal = true,crowdfunding_pay_goods = [];
		allPlan.each(function(i){
			crowdfunding_pay_goods.push({'perPayId':[],'perPay':[],'goodsId':[],'goodsList':[],'goodsPic':[],'goodsSubhead':[]});
			var _self = $(this);
			var perPayObj = _self.find('.perPay');
			var perPayIdObj = _self.find('.perPayId');
			
			perPayObj = perPayObj.filter(function(index) {
				// var val = parseInt($(this).val());

				var val = $(this).val(); // 将用户方案金额修改为不限制必须为整数
				
				if(!val || val == 0){
					return false;
				}else{
					crowdfunding_pay_goods[i]['perPay'].push(val);
					return true;
				}
			});
			perPaySize = perPayObj.size();
			if(perPaySize == 0){
  				  jtDialog.showTip('缴费方案有误');
  				   _self.addClass('addTip');
  				 return returnVal = false;
			}
			perPayIdObj.each(function(index,value){
				var tmp = $.trim($(this).val());
				crowdfunding_pay_goods[i]['perPayId'].push(tmp);
			});
			var cd_name = _self.find('[name = "cd_name[]"]').val();
			var cd_subhead = _self.find('[name = "cd_subhead[]"]').val();//子标题
			var cd_percentage = parseFloat(_self.find('[name = "cd_percentage[]"]').val());//员工提成
			var cd_period_unit = parseInt(_self.find('[name = "cd_period_unit[]"]').val());
			var cd_period_count = parseInt(_self.find('[name = "cd_period_count[]"]').val());
			var cd_pay_type = parseInt(_self.find('[name = "cd_pay_type[]"]').val());
			if(!cd_name || !cd_period_unit || !cd_period_count || !cd_pay_type || !cd_percentage || (cd_percentage > 100)){
  				   jtDialog.showTip('数据有误');
  				   _self.addClass('addTip');
  				 return returnVal = false;
			}
			var pay_type_tip = false;//缴费方案有误
			if((cd_pay_type == 1 || cd_pay_type == 3)){ 
				if(perPaySize != 1){
					pay_type_tip = true;
				}
			}else if(perPaySize != cd_period_count){
				pay_type_tip = true;
			}
			if(pay_type_tip){				
  				   jtDialog.showTip('缴费方案有误');
  				   _self.addClass('addTip');
  				 return returnVal = false;
			}
			//检查是否有添加商品
			var goodsListObj = _self.find('.goodsName');
			var goodsListPic = _self.find('.goodsPic');
			var goodsSubhead = _self.find('.goodsSubhead');
			var goodsIdObj = _self.find('.goodsId');
			
			if(goodsListObj.size() == 0){
				jtDialog.showTip('未增加商品');
				   _self.addClass('addTip');
				 return returnVal = false;
			}
			goodsListObj.each(function(){
				var tmp = $.trim($(this).text());
				if(!tmp){
					jtDialog.showTip('商品数据不全');
					_self.addClass('addTip');
					 return returnVal = false;
				}
				crowdfunding_pay_goods[i]['goodsList'].push(tmp);
			});
			if(!returnVal){
				return false;	
			}
			
			goodsListPic.each(function(){
				var tmp = $(this).find('[name="cg_att_id"]').val();
				if(!tmp){
					jtDialog.showTip('商品数据不全');
					_self.addClass('addTip');
					 return returnVal = false;
				}
				crowdfunding_pay_goods[i]['goodsPic'].push(tmp);
			});
			if(!returnVal){
				return false;	
			}
			
			goodsSubhead.each(function(){
				var tmp = $.trim($(this).text());
				if(!tmp){
					jtDialog.showTip('商品数据不全');
					_self.addClass('addTip');
					return returnVal = false;
				}
				crowdfunding_pay_goods[i]['goodsSubhead'].push(tmp);
			});
			goodsIdObj.each(function(){
				var tmp = $.trim($(this).val());
				crowdfunding_pay_goods[i]['goodsId'].push(tmp);
			});
			if(!returnVal){
				return false;	
			}
			
			_self.removeClass('addTip');
			return returnVal;
		});
		main.crowdfunding_pay_goods = crowdfunding_pay_goods;
		return returnVal;
	}
	main.edit = function(){
	      function put_in_time(){
	          require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
	              $('.start_time').datepicker({
	                  autoclose:true
	              });
	              $('.end_time').datepicker({
	                  autoclose:true
	              });
	          });
	      }
	      put_in_time();
	      //给当前方案添加事件
	      $('.planDiv').each(function(){
	    	  main.planEvents($(this));
	      });
	      $('#addPlan').click(function(){
	    	  var clone = $('.planDiv').last().clone();
	    	  var tableObj = clone.find('.control-group').last().find('table');
	    	  var tmp = "<tr>\
											<th>图片</th>\
											<th></th>\
											<th>主标题</th>\
											<th>子标题</th>\
										</tr>"; 
	    	  tableObj.html('').html(tmp);//清空原有商品
	    	  clone.find('[type=hidden]').val('');//清空原有隐藏控件值
	    	  $('.planDiv').last().after(clone);
	    	  main.planEvents(clone);
	      });
	      //增加经证
	      var from_edit_validate = $('#from_edit').validate($.extend({
	            rules: {
	            	cr_name: {
	                    required: true
	                },
	                cr_start_time:{
	                    required: true,
	                },
	                cr_end_time:{
	                	required: true
	                },
	                cr_count:{
	                	required: true,
	                	digits:true
	                },
	                cr_staff_discount:{
	                	required: true,
	                	digits:true
	                },
	                cr_content:{
	                	required: true
	                },
	            },
	            messages: {
	            	cr_name: {
	                    required: '众筹项目名不为空'
	                },
	                cr_start_time:{
	                    required: '众筹开始时间不能为空',
	                },
	                cr_end_time:{
	                	required: '众筹结束时间不能为空',
	                },
	                cr_count:{
	                	required: '众筹数量不为空',
	                	digits:'众筹数量必须为整数',
	                },
	                cr_staff_discount:{
	                	required: '内部员工折扣不为空',
	                	digits:'内部员工折扣必须为整数'
	                },
	                cr_content:{
	                	required: '项目内容不能为空'
	                }
	            }
	        },tool.validate_setting,{
	        	   submitHandler: function (form) {
	        		   if(!main.checkPlan()){
	        			   return false;
	        		   }
	        		   var data;
	        		   data = $(form).serializeArray();
	        		   data.push({'name':'crowdfunding_pay_goods','value':JSON.stringify(main.crowdfunding_pay_goods)});
	        		   $.ajax({
	        	            type: $(form).method || 'POST',
	        	            url: $(form).attr("action"),
	        	            data: data,
	        	            dataType: "json",
	        	            cache: false,
	        	            success: function(data){
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
		                   }
	        	        });
	               }
	        }));
	      $('[name="cr_travel_status"]').click(function(){
	    	  var isChecked = $(this).val();
	    	  if(!$(this).is(':checked')){
	    		  return ;
	    	  }
	    	  if(isChecked == 1)
	    		  $('[name="cr_travel_name"],[name="cr_travel_content"]').addClass('required');
	    	  else
	    		  $('[name="cr_travel_name"],[name="cr_travel_content"]').removeClass('required');
	      });
	      $('[name="cr_travel_status"]').each(function(){
	    	  $(this).trigger('click');
	      });
	}
	module.exports = main;
});	