define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool'),global_edit_store_validate,global_change_store_validate;
    var main = {};
    main.index = function(){
    	tool.check_all("#check_all",".check_single");
    	tool.batch_del($('.check-del'),$('.check_single'));
    	require.async('base/ajaxListComponent',function(list){
    		 show_member_lists(list);
    		 modalClick('.js-manager','#set-manager-modal','.js-confirm');
    		 modalClick('.js-changeStore','#change-modal','.js-confirm',function(){
    			 return global_change_store_validate.form();
    		 });
    	 });
	   	 modalClick('.js-store','#js-store','.js-confirm',function(){
	   		return global_edit_store_validate.form();
		 });
    }
    
	/**
     * 修改门店验证
     */
    var edit_store_validate = function(){
    	global_edit_store_validate = $('#store_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                },
                provice: {
                	required: true
                },
                city: {
                	required: true
                },
                county: {
                	required: true
                },
                phone: {
                	required: true,
                    mobilephone:true
                }
            },
            messages: {
                name: {
                    required: "门店名称不能空"
                },
                provice: {
                	required: '请保证地址的完整性'
                },
                city: {
                	required: '请保证地址的完整性'
                },
                county: {
                	required: '请保证地址的完整性'
                },
                phone: {
                	required: '请输入电话号码',
                    mobilephone:'电话号码格式有误'
                }
            }
        },tool.validate_setting));
    };
    /**
     * 转门店验证
     */
    change_store_validate = function(){
    	global_change_store_validate = $('#change_store').validate($.extend({
            rules: {
            	new_stores_id: {
                    required: true
                }
            },
            messages: {
            	new_stores_id: {
                    required: "请选择门店"
                }
            }
        },tool.validate_setting));
    } 
    //给某个元素绑定click事件弹出模态
    function modalClick(clickSelector,modalSelector,confirmSelector,confirmCallBack){
        $(clickSelector).click(function(){
        	var ifCanClose = true;
        	if($(this).data('type') == 1){
        		ifCanClose = false;
        		$('#title').html('设置店长');
        		$('#tips').html('一个店只允许有一个店长');
        		$('#operate').html('设为店长');
        		//设置回调
        		confirmCallBack = '';
        		confirmCallBack = function(){
        			var url,data,options;
        			url = common.U('setManager');
        			data = {'stores_id':$('#stores_id').val()};
        			data.userid = $('input[name=managerBox]:checked').data('userid');
        			options = {
               			 url:url,
               			 type:'POST',
	                    data:data,
	                    dataType:"json",
	                    cache: false,
	                    success: function(data){
	                  	  require.async('base/jtDialog',function(jtDialog) {
	                  		if (data.status != tool.success_code) {
	                  			 jtDialog.showTip(data.msg);
                              }else{
                            	 location.reload();
                              }
                          });	
	                    }
        			};
        			$.ajax(options);	
        		}
        	}else if($(this).data('type') == 2){
        		ifCanClose = false;
        		//设置回调        		
        		confirmCallBack = '';
        		confirmCallBack = function(){
        			var url,data,options;
        			url = common.U('setAssistant');
        			data = {'stores_id':$('#stores_id').val()};
        			data.userid = $('input[name=managerBox]:checked').data('userid');
        			options = {
               			 url:url,
               			 type:'POST',
	                    data:data,
	                    dataType:"json",
	                    cache: false,
	                    success: function(data){
	                  	  require.async('base/jtDialog',function(jtDialog) {
	                  		if (data.status != tool.success_code) {
	                  			 jtDialog.showTip(data.msg);
                              }else{
                            	 location.reload();
                              }
                          });	
	                    }
        			};
        			$.ajax(options);
        		}
        		$('#title').html('设置店助');
        		$('#tips').html('一个店只允许有一个店助');
        		$('#operate').html('设为店助');
        	}
        	if(clickSelector == '.js-store'){
        		  $(modalSelector).width(947);
        		  var area = require('pulgins/area/area.js');
                  var area_config = {
                      value: {
                          provice_id: $('#provice').data('id'),
                          city_id: $('#city').data('id'),
                          county_id: $('#county').data('id')
                      }
                  };
                  area.init(area_config);
                  edit_store_validate();
        	}else if(clickSelector == '.js-changeStore'){
        		//检查是否有选择店员
        		if($('input[name="uid[]"]:checked').size() == 0){
        			 require.async('base/jtDialog',function(jtDialog) {
        				 jtDialog.showTip('请先选择转店成员');
                     });
        			return;
        		}
        		var uidStr = [];
        		$('input[name="uid[]"]:checked').each(function(i){
        			uidStr.push($(this).data('uid'));
        		});
        		$('input[name=uidStr]').val(uidStr.join(','));
        		change_store_validate();
        	}
        	
        	$(modalSelector).modal('show').css({
                'margin-left': function () {
                    return -($(this).width() / 2);
                }
            })
            $(modalSelector+' '+confirmSelector).unbind('click').click(function(){
            	var confirmCallBackResult = true;
            	if(typeof(confirmCallBack) == 'function'){ //有设置回调
            		confirmCallBackResult = confirmCallBack();
            	}
            	if(confirmCallBackResult === true && ifCanClose == true){
            		$(modalSelector).modal('hide');
            	}
            })
        });
    }
    
    /**
     * 获取成员列表
     */
    var show_member_lists = function(jsListObj){
    	var stores_id = $('#stores_id').val();
        var config = {
            page_id : 'pagebar',
            list_id : 'member-list',
            list_tpl_id : 'tpl-member-list',
            url : common.U('Stores/get_member_lists',{'stores_id':stores_id}),
            page_size:8
        };
        jsListObj.init(config);
        jsListObj.get_lists();
        jsListObj.after_events = function(data,params,listSheet){
        	  //绑定方法
            $('input[name=managerBox]').click(function(){
            	var _self,checked,$attrObj;
            	 _self = $(this);
            	 checked = _self.is(':checked');
            	 if(checked){
            		 $attrObj = {'disabled':true,'checked':false};
            	 }else{
            		 $attrObj = {'disabled':false,'checked':false};
            	 }
            	 $('input[name=managerBox]').not(_self).attr($attrObj);
            });	
            return true;
        };
    }
    
    main.add_mod = function(){
            $('#user_edit').validate($.extend({
                rules: {
                	username: {
                        required: true
                    },
                    nickname: {
                    	required: true
                    },
                    mobile: {
                    	mobile:true,
    		        	required: true
    		        },
    		        email: {
                    	required: true
                    },             
                    confirmPassword: {
                    	equalTo: '[name="password"]'
                    }
                },
                messages: {
                	username: {
                        required: '门店名称不能为空'
                    },
                    nickname: {
                    	required: '真实姓名不能为空'
                    },
                    mobile: {
    		        	required: '手机号不能为空'
    		        },
    		        email: {
                    	required: '邮箱不能为空'
                    },                
                    confirmPassword: {
                    	equalTo: '两次密码不一致'
                    }
                }
            },tool.validate_setting));
    }
    
	module.exports = main;
});