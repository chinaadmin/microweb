define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#user_edit').validate($.extend({
            rules: {
                subject: {
                    required: true
                }
            },
            messages: {
                subject: {
                    required: "任务标题不能空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));

            //更新缓存
            $('.js-enable').click(function(){
                var _self = $(this);
                require.async('base/jtDialog',function(jtDialog){
                    var url = _self.attr('url');
                    $.ajax({
                        type: 'GET',
                        url: url,
                        data: {},
                        dataType: "json",
                        cache: false,
                        success: function(data){
                            if(data.status != tool.success_code){
                                jtDialog.error(data.msg);
                            }else{
                                jtDialog.showTip('更新缓存成功',1,function(){
                                    location.reload();
                                });
                            }
                            return false;
                        },
                        error:function(){
                            jtDialog.error('更新缓存失败');
                        }
                    });
                });
            });

            //清空缓存成功
            $('.js-del-cache').click(function(){
                var _self = $(this);
                require.async('base/jtDialog',function(jtDialog){
                    jtDialog.confirm(function(){
                        var url = _self.attr('url');
                        $.ajax({
                            type: 'GET',
                            url: url,
                            data: {},
                            dataType: "json",
                            cache: false,
                            success: function(data){
                                if(data.status != tool.success_code){
                                    jtDialog.error(data.msg);
                                }else{
                                    jtDialog.showTip('清空缓存成功',1,function(){
                                        location.reload();
                                    });
                                }
                                return false;
                            }
                        });

                    },'确认清空缓存?');
                });
            });
		},
        edit : function(){
            edit_validate();
		}
	};
	module.exports = main;
});