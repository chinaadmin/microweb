define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    var main ={
        index:function(){

            var order_count = function(){
            	return;
                $('.js-order-count').each(function(){
                    var type = $(this).data('type');
                    var url = common.U('order/count',{type:type});
                    var opt = {
                        url:url
                    };
                    var _self = $(this);
                    var diDialog = require('model/diDialog');
                    common.doAjax(opt,function(data){
                        if (data.status !== common.success_code) {
                            //diDialog.Alert(data.msg);
                        } else {
                            _self.html('（'+data.result+'）');
                        }
                    });
                });
            }();

            $('#js-order').on('click','.js-cancel',function(){//取消
                var _self = $(this);
                var url = _self.data('url');
                var diDialog = require('model/diDialog');
                diDialog.Confirm('<span>取消订单</span><p>确定要取消该订单吗？</p>',function(){
                    var opt = {
                        url:url
                    };
                    common.doAjax(opt,function(data){
                        if (data.status !== common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            location.reload();
                        }
                    });
                });
            }).on('click','.js-del',function(){//删除
                var _self = $(this);
                var url = _self.data('url');
                var diDialog = require('model/diDialog');
                diDialog.Confirm('<span>删除订单</span><p>确定要删除该订单吗？</p>',function(){
                    var opt = {
                        url:url
                    };
                    common.doAjax(opt,function(data){
                        if (data.status !== common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            location.reload();
                        }
                    });
                });
            }).on('click','.js-receipt',function(){//确认收货
                var _self = $(this);
                var url = _self.data('url');
                var diDialog = require('model/diDialog');
                diDialog.Confirm('<span>确认收货</span><p>确定要确认收货吗？</p>',function(){
                    var opt = {
                        url:url
                    };
                    common.doAjax(opt,function(data){
                        if (data.status !== common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            location.reload();
                        }
                    });
                });
            });

        },
        view:function(){
            $('.js-cart').click(function(){
                var cart = require('model/cart');
                var goods_id = $(this).data('id');
                var num = 1;
                var norms = $(this).data('norms');
                cart.order_add(goods_id,num,norms);
            });
        },
        gopay:function(){
            $('.platform span').click(function(){
                $(this).find('input[name="payment"]').prop("checked",true);
            });

            $('#js-pay').click(function(){
                to_pay();
            });

            var to_pay = function(id){
                var form = $('#js-form');
                var pay_type = $('input[name="payment"]:checked').val();
                $('input[name="pay_type"]').val(pay_type);
                form.attr('action', common.U('order/pay'));
                form.submit();

                $('#js-pay-mask').show();
                $('#js-pay-content').data('id',id).show();
            }

            $('#js-pay-content').on('click','.close-box', function(){//付款退出框
                $('#js-pay-mask').hide();
                $('#js-pay-content').hide();
                //var id = $('#js-pay-content').data('id');
                var id = $('input[name="id"]').val();
                common.U('cart/paysuccess',{id:id},true);
            }).on('click','.js-pay-finish',function(){//已完成付款
                //var id = $('#js-pay-content').data('id');
                var id = $('input[name="id"]').val();
                common.U('cart/paysuccess',{id:id},true);
            }).on('click','.js-pay-problem',function(){//付款遇到问题
                $('#js-pay-content .btn-box').hide();
                $('#js-pay-content .btn-2').show();
            }).on('click','.js-pay-again',function(){//重新支付
                //var id = $('#js-pay-content').data('id');
                to_pay();
                $('#js-pay-content .btn-box').show();
                $('#js-pay-content .btn-2').hide();
            }).on('click','.js-pay-cancel',function(){//取消支付
                //var id = $('#js-pay-content').data('id');
                var id = $('input[name="id"]').val();
                common.U('cart/payfail',{id:id},true);
            })
        }
    };
    module.exports = main;
});