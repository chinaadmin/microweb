define(function(require , exports ,module) {
    var $ = require('jquery');
    var main = {
        add:function(goods_id,num,norms){
            var common = require('common');
            var opt = {
                url:common.U('cart/add'),
                data:{
                    id:goods_id,
                    num:num,
                    norms:norms
                }
            };

            common.doAjax(opt,function(data){
                var diDialog = require('model/diDialog');
                if (data.status === common.success_code) {
                    common.U('cart/success',{'id':goods_id},true);
                } else if (data.status === 'NOT_LOGGED_IN'){//未登录的弹出登录框
                    $('#js-login-mask').show();
                    $('#js-login-box').show();
                    $('#js-login-box').center();
                }else {
                    diDialog.Alert(data.msg);
                }
            });

            //关闭按钮
            $(".close-box").click(function(){
                $(".pop-box").hide();
                $(".comfirm-box").hide();
            });

            var passport = require('controller/passportController');
            passport.poplogin();
        },
        buy_now:function(goods_id,num,norms){
            var common = require('common');
            var opt = {
                url:common.U('cart/buynow'),
                data:{
                    id:goods_id,
                    num:num,
                    norms:norms
                }
            };

            common.doAjax(opt,function(data){
                var diDialog = require('model/diDialog');
                if (data.status === common.success_code) {
                    common.U('cart/shopping',{},true);
                } else if (data.status === 'NOT_LOGGED_IN'){//未登录的弹出登录框
                    $('#js-login-mask').show();
                    $('#js-login-box').show();
                    $('#js-login-box').center();
                }else {
                    diDialog.Alert(data.msg);
                }
            });

            //关闭按钮
            $(".close-box").click(function(){
                $(".pop-box").hide();
                $(".comfirm-box").hide();
            });

            var passport = require('controller/passportController');
            passport.poplogin();
        },
        order_add:function(goods_id,num,norms){
            var common = require('common');
            var opt = {
                url:common.U('cart/add'),
                data:{
                    id:goods_id,
                    num:num,
                    norms:norms
                }
            };
            common.doAjax(opt,function(data){
                var diDialog = require('model/diDialog');
                if (data.status === common.success_code) {
                    common.U('cart/index',{},true);
                }else {
                    diDialog.Alert(data.msg);
                }
            });
        }
    };
    module.exports = main;
});