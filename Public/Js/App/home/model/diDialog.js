define(function(require , exports ,module){
    var $ = require('jquery');

    /**
     * 弹出层居中
     */
    jQuery.fn.center = function(loaded) {
        var obj = this;
        body_width = parseInt($(window).width());
        body_height = parseInt($(window).height());
        block_width = parseInt(obj.width());
        block_height = parseInt(obj.height());

        left_position = parseInt((body_width/2) - (block_width/2)  + $(window).scrollLeft());
        if (body_width<block_width) { left_position = 0 + $(window).scrollLeft(); };

        top_position = parseInt((body_height/2) - (block_height/2) + $(window).scrollTop());
        if (body_height<block_height) { top_position = 0 + $(window).scrollTop(); };

        if(!loaded) {

            obj.css({'position': 'absolute'});
            obj.css({ 'top': top_position, 'left': left_position });
            $(window).bind('resize', function() {
                obj.center(!loaded);
            });
            $(window).bind('scroll', function() {
                obj.center(!loaded);
            });

        } else {
            obj.stop();
            obj.css({'position': 'absolute'});
            obj.animate({ 'top': top_position , 'left': left_position }, 200, 'linear');
        }
    }

    var common = {};

    /**
     * 弹出层
     * @param {array} config 配置
     * 				id ID
     * 				title 标题
     * 				mask 是否遮罩默认是
     * 				content 提示内容
     * 				ok 确认执行
     * 				cancel 取消执行
     * 				colse 关闭执行
     * @author cwh
     **/
    function DIDialog(config){
        config.id =  config.id?config.id:'dig_confirm';
        config.title = config.title?config.title:'提示';

        //遮罩层
        var html_mask = config.mask===false?'':'<div id="dig_mask" class="pop-box" style="display: block"></div>';
        //按钮
        //确认按钮
        var btn = (config.ok?' <input type="button" class="dig_confirm_btn true-box" value="确定"> ':'');

        //其他按钮
        if(config.btn){
            $.each(config.btn,function(i,item){
                btn += ' <input type="button" id="'+item.name+'" class="fail-box" value="'+item.title+'"> ';
            });
        }

        //取消按钮
        btn += (config.cancel?' <input type="button" class="dig_cancel_btn fail-box" value="取消"> ':'');

        //弹出层
        var html = html_mask +
            '<div id="' + config.id + '" class="comfirm-box" style="display: block">'+
            '<h2>' + config.title + '<i class="close-box close-btn"></i></h2>'+
            '<div>'+
            '<div class="delete-content">'+
            '<div class="delete-text">'+
            '<i class="delete-bg"></i><div class="delete-write">'+
            config.content +'</div></div>'+
            '<div class="btn-box"> '+btn+' </div>'+
            '</div>'+
            '</div>';
        $('body').append(html);
        $('#'+config.id).center();

        //确认按钮
        if(config.ok){
            $('.dig_confirm_btn').on("click",function(here){
                if(typeof config.ok==='function'){
                    config.ok(here);
                }
                colse();
            });
        }

        //其他按钮
        if(config.btn){
            $.each(config.btn,function(i,item){
                //btn += ' <input type="button" id="'+item.name+'" class="fail-box" value="'+item.title+'"> ';
                $('#'+item.name).on("click",function(here){
                    if(typeof item.callback==='function'){
                        item.callback(here);
                    }
                    colse();
                });
            });
        }

        //取消按钮
        if(config.cancel){
            $('.dig_cancel_btn').on("click",function(here){
                if(typeof config.cancel==='function'){
                    config.cancel(here);
                }
                colse();
            });
        }

        //关闭按钮
        $('.close-btn').on("click",function(here){
            colse();
        });

        //关闭方法
        function colse(){
            config.colse && config.colse();//关闭执行
            config.mask===false?'':$('#dig_mask').remove();
            $('#'+config.id).remove();
        }
    }
    common.DIDialog = DIDialog;

    /**
     * 弹出确认框
     * @param {String} message 内容
     * @param {function} truefun 确定执行方法
     * @param {function} falsefun 取消执行方法
     * @author cwh
     **/
    common.Confirm = function(message,truefun,falsefun){
        var config = {
            id:'DIConfirm',
            content:message,
            ok:truefun,
            cancel:falsefun?falsefun:true,
            mask:true
        }
        return DIDialog(config);
    }

    /**
     * 弹出提示
     * @param {String} message 内容
     * @author cwh
     **/
    common.Alert = function(message,title){
        var config = {
            id:'DIAlert',
            content:message,
            ok:true,
            cancel:false,
            mask:true
        }
        if(typeof(title) != 'undefined'){
            config.title = title;
        }
        return DIDialog(config);
    }

    module.exports = common;
});
