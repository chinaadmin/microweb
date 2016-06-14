define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改门店验证
     */
    var edit_validate = function(permission_delivery){
    	user_edit = $('#user_edit').validate($.extend({
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
                },
                lat_lon: {
                	required: true
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
                },
                lat_lon: {
                	required: '请输入经纬度'
                }
            }
        },tool.validate_setting));
    	permission_delivery && require.async("pulgins/datetimepicker/jquery.datetimepicker",function(){
    		var set = {
        			datepicker:false,
        			format:'H:i',
        			step:5
        		};
    		$('[name = "am_start_time"]').datetimepicker(set);
    		$('[name = "am_end_time"]').datetimepicker(set);
    		$('[name = "pm_start_time"]').datetimepicker(set);
    		$('[name = "pm_end_time"]').datetimepicker(set);
        });
    };
    var global_address_list = [],global_address_str,circle = '';
	var main ={
		index : function(){
			tool.del($('.js-del'));
		},
        edit : function(addressList,permission_delivery){
        	if(addressList){
        		global_address_list = addressList.split('<br>');  
        	}
            $('#beforeSubmit').click(function(){
            	$("[name=address_list]").val($('#address_list').html());
            	return true;
            });
            $('#enterSearch').click(function(){
            	var key = $('.addressSearch').val();
            	if(key == ''){
            		$('#address_list').html(global_address_list.join('<br>')).attr('contenteditable',true);
            	}else if(global_address_list.length > 1){
            		var res = [];
            		for(var i = 0;i < global_address_list.length; i++){
            			if(global_address_list[i].indexOf(key) != -1){
            				res.push(global_address_list[i]);
            			}
            		}
            		//不可编辑
            		$('#address_list').html(res.join('<br>')).attr('contenteditable',false);
            	}
            	return false;
            });
            $('#address_list').keyup(function(event){
            	if(circle){
            		clearTimeout(circle);
            	}
            	circle = setTimeout(function(){
            		var html = $('#address_list').html();
                	if(global_address_str != html){ //内容有变化	 更新数组	 更新字符窜
                		global_address_list = html.split('<br>');
                		global_address_str = html;
                	}
            	},300);
            });
            
            //加载地区
            edit_validate(permission_delivery);
            var area = require('pulgins/area/area.js');
            var area_config = {
                value: {
                    provice_id: $('#provice').data('id'),
                    city_id: $('#city').data('id'),
                    county_id: $('#county').data('id')
                }
            };
            area.init(area_config);
            $('#queryLatLong').click(function(){
            	var address;
            	//检查地址是否已经填写
            	if(!user_edit.element($('#provice')) || !user_edit.element($('#city')) || !user_edit.element($('#county'))){
            		return;
            	};
            	address = $('#provice :selected').text()+ ' '+$('#city  :selected').text()+' '+$('#county  :selected').text()+' '+$('[name=address]').val();
            	$.post(common.U('getLatLonByAddress'),{'address':address},function(rtn){
            		if(rtn.status != 'SUCCESS'){
            			   require.async('base/jtDialog',function(jtDialog) {
                               jtDialog.error('获取经纬失败','错误');
                           });
            		}else{
            			$('[name=lat_lon]').val(rtn.result);
            		}
            	});
            });
		},
        member : function(){

            require.async('base/ajaxListComponent',function(list){
                var member_model = "#member-modal";
                var stores_id = $('#member-list').data('stores_id');
                //打开添加成员模态框
                $('#sel-member').click(function(){
                    show_member_lists();
                    $(member_model).modal('show').css({
                        'margin-left': function () {
                            return -($(this).width() / 2);
                        }
                    });
                });

                /**
                 * 获取成员列表
                 */
                var show_member_lists = function(){
                    var config = {
                        page_id : 'pagebar',
                        list_id : 'member-list',
                        list_tpl_id : 'tpl-member-list',
                        url : common.U('Stores/get_member_lists',{'stores_id':stores_id}),
                        page_size:8
                    };
                    list.init(config);
                    list.get_lists();
                }

                /**
                 * 门店店员列表
                 */
                var show_stores_user_lists = function(){
                    var config = {
                        has_page : false,
                        list_id : 'stores-user-list',
                        list_tpl_id : 'tpl-stores-user-list',
                        url : common.U('Stores/get_stores_user_lists',{'stores_id':stores_id})
                    };
                    list.init(config);
                    list.get_lists();
                };

                show_stores_user_lists();

                $(member_model).on('click' ,'.js-sel-member',function(){//选择成员
                    var _self = $(this);
                    var uid = $(this).data('uid');
                    var url = common.U('Stores/sel_stores_user',{'stores_id':stores_id,'uid':uid});
                    $.get(url,'',function(data){
                        if(data.status==common.success_code){
                            show_stores_user_lists();
                            _self.parent('td').find('.js-del-member').show();
                            _self.parent('td').find('.js-sel-member').hide();
                        }
                    },'json');
                }).on('click' ,'.js-del-member',function(){//删除成员
                    var _self = $(this);
                    var uid = $(this).data('uid');
                    var url = common.U('Stores/del_stores_user',{'stores_id':stores_id,'uid':uid});
                    $.get(url,'',function(data){
                        if(data.status==common.success_code){
                            show_stores_user_lists();
                            _self.parent('td').find('.js-del-member').hide();
                            _self.parent('td').find('.js-sel-member').show();
                        }
                    },'json');
                });

                $('#stores-user-list').on('click' ,'.js-del-member',function() {//删除成员
                    var uid = $(this).data('uid');
                    var url = common.U('Stores/del_stores_user',{'stores_id':stores_id,'uid':uid});
                    $.get(url,'',function(data){
                        if(data.status==common.success_code){
                            show_stores_user_lists();
                        }
                    },'json');
                }).on('click','.js-set-manager', function () {//设为店长
                    var _self = $(this);
                    var uid = $(this).data('uid');
                    var url = common.U('Stores/set_manager',{stores_id:stores_id,uid:uid,manager:1});
                    $.get(url,'',function(data){
                        if(data.status==common.success_code){
                            //show_stores_user_lists();
                            _self.parent('td').find('.js-del-manager').show();
                            _self.parent('td').find('.js-set-manager').hide();
                            _self.parents('tr').find('td').eq(2).html('是');
                        }
                    },'json');
                }).on('click','.js-del-manager', function () {//取消店长
                    var _self = $(this);
                    var uid = $(this).data('uid');
                    var url = common.U('Stores/set_manager',{stores_id:stores_id,uid:uid,manager:0});
                    $.get(url,'',function(data){
                        if(data.status==common.success_code){
                            //show_stores_user_lists();
                            _self.parent('td').find('.js-del-manager').hide();
                            _self.parent('td').find('.js-set-manager').show();
                            _self.parents('tr').find('td').eq(2).html('否');
                        }
                    },'json');
                }).on('change','.js-role-lists',function(){
                    var _self = $(this);
                    var uid = _self.data('uid');
                    var stores_id = _self.data('stores_id');
                    var url = common.U('Stores/set_role',{uid:uid,role_id:_self.val(),stores_id:stores_id});
                    $.get(url,'',function(data){
                        if(data.status==common.success_code){
                            require.async('base/jtDialog',function(jtDialog) {
                                jtDialog.showTip('修改角色成功', 1);
                            });
                        }else{
                            require.async('base/jtDialog',function(jtDialog) {
                                jtDialog.showTip(data.msg, 2);
                            });
                        }
                        show_stores_user_lists();
                    },'json');
                });
            });
        }
	};
	module.exports = main;
});