define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	
	require('jquery_validate');
    var tool = require('model/tool');

    tool.validate_setting = $.extend(tool.validate_setting,{
        submitHandler: function (form) {
            checknode();
            tool.formAjax(form,function(data){
                require.async('base/jtDialog',function(jtDialog){
                    if(data.status != tool.success_code){
                        jtDialog.showTip(data.msg);
                    }else{
                        jtDialog.showTip(data.msg,1,function(){
                            //common.U('Menubar/index','',true);
                            location.href = document.referrer;
                        });
                    }
                });
                return false;
            });
        }
    });

    require('pulgins/jquery-ztree/3.5/js/jquery.ztree.all-3.5.min.js');
    require('pulgins/jquery-ztree/3.5/css/zTreeStyle/zTreeStyle.css');
    var checknode = function(){
        var zTree = $.fn.zTree.getZTreeObj("relationTree"),
            nodes = zTree.getCheckedNodes(true);
        var purview = '';
        for (var i = 0; i < nodes.length; i++) {
            purview +=  nodes[i].id+",";
        }
        $('#relation').val(purview);
        return true;
    }

    var get_relation_tree = function(id,category){
        var relationTree = $("#relationTree");
        var param = {id:id};
        if(typeof category != 'undefined'){
            param.category = category;
        }
        var options = {
            url:common.U('getRelationJson',param),
            method: 'GET'
        };
        tool.doAjax(options,function(data){
            var setting = {
                check: {
                    enable: true,
                    chkboxType: { "Y": "s", "N": "ps" }
                },
                data: {
                    simpleData: {
                        enable: true,
                        pIdKey:'pid'
                    }
                }
            };
            $.fn.zTree.init(relationTree, setting, data);
            checknode();
        });
    }

    /**
     * 修改管理员验证
     */
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
                name: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "菜单名称不能空"
                }
            }
        },tool.validate_setting));
    };

	var main ={
		index : function(){
			tool.del($('.js-del'));
            tool.saveSort($('#save-sort'));

            $('.js-relation').click(function(){
                var id = $(this).data('id');
                $('#menu_id').val(id);
                get_relation_tree(id);
                $('#relation-view').modal('show').css({
                    'margin-left': function () {
                        return -($(this).width() / 2);
                    }
                });
            });

            $('.js-confirm').click(function(){
                checknode();
                var id = $('#menu_id').val();
                var relation = $('#relation').val();
                var options = {
                    url:common.U('updataRelation',{id:id,relation:relation}),
                    method: 'GET'
                };
                tool.doAjax(options,function(data){
                    require.async('base/jtDialog',function(jtDialog) {
                        if (data.status != tool.success_code) {
                            jtDialog.showTip('关联菜单失败');
                        } else {
                            $('#relation-view').modal('hide');
                            jtDialog.showTip('关联菜单成功');
                        }
                    });
                });
            });
		},
        edit : function(){
            edit_validate();
            //图标显示
            $('#icon').on('keyup change',function(){
                $('#group-icon').html('<i class="'+ $(this).val() +'"></i>');
            });

            var id = $('#menu_id').val();
            var category = $('#category').val();
            get_relation_tree(id,category);
		}
	};
	module.exports = main;
});