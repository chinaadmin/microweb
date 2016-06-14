define(function(require , exports ,module){
    var $ = require('jquery');
    /**
     * 弹出层
     * @param {array} config 配置
     * 				id ID
     * 				title 标题
     * 				okValue 确认按钮值
     * 				cancelValue 取消按钮值
     * 				ok 确认执行
     * 				cancel 取消执行
     * @author cwh
     **/
    var Dialog =  function (config ,ok , cancel){
        var defaults = {
            id : 'jt-dialog' ,
            title : '提示',
            content : '系统提示',
            ok : false,
            okValue : '确定',
            cancel : false,
            cancelValue : '取消'
        };
        var callbackList = {};
        config = $.extend(defaults , config);
        // 按钮组
        if (!$.isArray(config.button)) {
            config.button = [];
        }
        // 取消按钮
        if (cancel !== undefined) {
            config.cancel = cancel;
        }

        // 确定按钮
        if (ok !== undefined) {
            config.ok = ok;
        }
        if (config.ok) {
            config.button.push({
                id: 'ok',
                value: config.okValue,
                callback :  config.ok
            });
        }
        if (config.cancel) {
            config.button.push({
                id: 'cancel',
                value: config.cancelValue,
                callback :  config.cancel
            });
        }
        //按钮
        buttonHtml = '';
        if(config.button){
            if (config.button.length != 0) {
                buttonHtml += '<div class="modal-footer modal-center">';
                $.each(config.button, function (i, item) {
                    item.id = item.id || item.value;
                    callbackList[item.id] = item.callback;
                    var className = item.id == 'cancel' ? 'btn-default' :'btn-success' ;
                    buttonHtml += '<button data-id="' + item.id + '" type="button" class="btn '+className+'">' + item.value + '</button>';
                });
                buttonHtml += '</div>';
            }
        }

        //弹出层
        var html = '<div class="modal fade" tabIndex="-1" id="'+config.id+'">'
            + '<div class="modal-header ">'
            + '<button data-dismiss="modal" class="close" type="button"></button>'
            + '<h3>'+config.title+'</h3>'
            + '</div>'
            + '<div class="modal-body modal-center">'+config.content+'</div>'
            + buttonHtml
            + '</div>';
        $('body').append(html);
        var targetList = $('#' + config.id);
        //modal回调
        targetList.on('hide.bs.modal' , function(e){
            setTimeout(
                function(){
                    targetList.remove();
                    callbackList = {};
                },
                500
            );
        });
        targetList.on('click' , 'button[data-id]' , function(event){
            var id = $(this).attr('data-id');
            typeof callbackList[id] == 'function' && callbackList[id]();
            targetList.modal('hide');
            event.preventDefault();
        }).modal('show');
    };

    var dialogZh = {
        title : '系统提示',
        error_title : '错误提示',
        content : '',
        ok : '确认',
        cancel : '取消'
    };
    var jtDialog = function(){
        this.language = dialogZh;
    };
    jtDialog.prototype = {
        //设置语言
        seti18n : function(lang){
            this.language = lang;
        },
        confirm : function(callbackFun , content , title){
            var settings = {
                id : 'jt-confirm' ,
                title : title ? title : this.language.title,
                content : content ? content : this.language.content,
                button : [
                    {
                        id : 'ok',
                        value:  this.language.ok,
                        callback : function(){
                            (typeof callbackFun == 'function') && callbackFun();
                        }
                    },
                    {
                        id : 'cancel',
                        value : this.language.cancel,
                        callback : function(){}
                    }
                ]
            };
            Dialog(settings);
        },
        error : function(content , title){
            var settings = {
                id : 'jt-error' ,
                title : title ? title : this.language.error_title,
                content : content ? content : this.language.content
            }
            Dialog(settings);
        },
        alert : function(content , title){
            var settings = {
                id : 'jt-alert' ,
                title : title ? title : this.language.title,
                content : content ? content : this.language.content
            };
            Dialog(settings);
        },
        //操作提示
        showTip : function(msg , timer , callBack){
            timer = timer ? timer *1000 : 3000;
            var html = $('<div class="alert alert-warning" role="alert"><button type="button" class="close" data-dismiss="alert"></button><strong>'+msg+'</strong></div>');
            $('.js-breadcrumb').after(html);
            var width = $('.js-breadcrumb').parents(".container-fluid").width();
            $('.show-msg').width(width-2);
            setTimeout(
                function(){
                    html.remove();
                    typeof callBack == 'function' && callBack();
                },
                timer
            );
        }
    };
    module.exports = new jtDialog();
});
