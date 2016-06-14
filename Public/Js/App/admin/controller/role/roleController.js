define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    var edit_validate = function(){
        $('#user_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "角色名称不能空"
                }
            }
        },tool.validate_setting));
    };

	var main = {
		
		index : function(){
			var id;
			tool.del($('.js-del'));
			 require.async('fancyapps',function(){
				 $(".lightbox").fancybox({
					 'width'                : '100%',  
					 'height'               : '100%',  
					 'autoScale'            : false,  
					 'transitionIn'         : 'none',  
					 'transitionOut'        : 'none',  
				});
			 });	
            //选择权限
            $('#permission-content-view').on('click','.js_cbx_access',function(){
                var obj = $(this);
                //一级
                if(obj.hasClass('js_level_1')){
                    $('.js_cbx_access').each(function(){
                        //选中和取消二级
                        if($(this).data('level1') == obj.val()){
                            if(obj.is(':checked')){
                                $(this).prop("checked",true);
                            }else{
                                $(this).prop("checked",false);
                            }
                        }
                    });
                }

                //二级
                if(obj.hasClass('js_level_2')){
                    $('.js_cbx_access').each(function(){
                        //选中和取消三级
                        if($(this).data('level2') == obj.val()){
                            if(obj.is(':checked')){
                                $(this).prop("checked",true);
                            }else{
                                $(this).prop("checked",false);
                            }
                        }
                    });

                    //选中和取消一级
                    cheng_higher_check(obj,'level1');
                }

                //三级
                if(obj.hasClass('js_level_3')){
                    //选中和取消二级
                    cheng_higher_check(obj,'level2');

                    //选中和取消一级
                    cheng_higher_check(obj,'level1');
                }

                $.uniform.update('.js_cbx_access');
            });

            /**
             * 修改上级复选框状态
             * @param obj 当前复选框
             * @param level 上级编号
             */
            function cheng_higher_check(obj,level){
                var obj_level = '';
                var obj_checked_level = false;
                $('.js_cbx_access').each(function(){
                    if(obj.data(level) == $(this).val()){
                        obj_level = $(this);
                    }
                    if($(this).data(level) == obj.data(level)){
                        if($(this).is(':checked')){
                            obj_checked_level = true;
                        }
                    }
                });
                if(obj_checked_level){
                    obj_level.prop("checked",true);
                }else{
                    obj_level.prop("checked",false);
                }
            };

            //打开权限模态框
            $('.js-setting-permission').click(function(){
                id = $(this).data('id');
				//role_ids = id;
                $('#role_id').val(id);
                var options = {
                    url:common.U('getPermissionJson',{id:id}),
                    method: 'GET'
                };
                tool.doAjax(options,function(data){
                    tool.successCallback(data,function(){
                        var artTemplate = require('template');
                        $('#permission-content-view').html(artTemplate('tpl-permission-content', {items: data.result}));
                        var base = require('base/controller/adminbaseController');
                        base.initUniform();

                        $('#setting-permission-view').modal('show').css({
                            'margin-left': function () {
                                return -($(this).width() / 2);
                            }
                        });
                    });
                });
            });
			
			$(".js_level").click(function(){
				var pid = $(this).val();
				if(pid == 0){
					$(".pid").show();
				}else{
					$(".pid").hide();
					$(".pid"+pid).show();
				}
				
			})

            //提交权限
            $('.js-confirm').click(function(){
                var form = $('#js-set-permission');
                tool.formAjax(form,function(data){
                    $('#setting-permission-view').modal('hide')
                    require.async('base/jtDialog',function(jtDialog){
                        if(data.status != tool.success_code){
                            jtDialog.error(data.msg);
                        }else{
                            jtDialog.showTip(data.msg,1);
                        }
                    });
                    return false;
                });
            });
		},
        edit : function(){
            edit_validate();
		}
	};
	module.exports = main;
});