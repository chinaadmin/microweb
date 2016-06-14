define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    require('jquery_validate');
    var diDialog = require('model/diDialog');

    jQuery.validator.addMethod("stores_required", function(value, element, param) {
        var stores = $("#stores").val();
        if(typeof(stores) == 'undefined' || stores == '' || stores == '请选择' || stores == null){
            return false;
        }
        return true;
    }, "请选择门店");

    var main = {
        shopping:function() {
            $('#js-needs-invoice').prop("checked",false);
            var cache_data = {
                stores:[],
                invoice:[],
                status_invoice:0
            };

            /**
             * 提货地址列表
             * @param id 选中提货地址
             */
            var show_stores_lists = function(id){
                common.doAjax({
                    url:common.U('affiliated/getdelivery')
                },function(data){
                    cache_data.stores = data;
                    var templates = require('template');
                    $('#js-stores-address').html(templates('tpl-stores',{items:data}));

                    //$('.js-stores-address-lists').removeClass('add-address-active');
                    id = id||0;
                    var is_sel = false;
                    $('.js-stores-address-lists').each(function(){
                        if($(this).data('id')==id){
                            is_sel = true;
                            $(this).click();
                        }
                    });

                    if(!is_sel){
                        $('.js-stores-address-lists').eq(0).click();
                    }
                });
            }

            show_stores_lists();

            /**
             * 编辑提货地址
             * @param data
             */
            var show_stores_add = function(data){
                data = data||{};
                var templates = require('template');
                $('#js-stores-add').html(templates('tpl-stores-add',{info:data})).show();
                var validate = function(){
                    $('#signupForm').validate({
                        errorElement: 'label',
                        ignore:'',
                        rules: {
                            name: {
                                required: true
                            },
                            telephone: {
                                required: true,
                                rangelength: [11, 11],
                                digits: "只能输入整数"
                            },
                            stores:{
                                stores_required: true
                            }
                        },
                        messages: {
                            name: {
                                required: "收货人姓名"
                            },
                            telephone: {
                                required: "请输入手机号",
                                rangelength: jQuery.format("请输入正确的手机号")
                            }
                        },
                        submitHandler: function (form) {
                            common.formAjax(form,function(data){
                                if(data.status != common.success_code){
                                    diDialog.Alert(data.msg);
                                }else{
                                    show_stores_lists(data.result);
                                }
                                return false;
                            });
                        }
                    });
                };
                validate();

                var area = require('model/stores_area.js');
                var area_config = {
                    url:common.U('Area/getStoresData'),
                    value: {
                        provice_id: data.provice||0,
                        city_id: data.city||0,
                        county_id: data.county||0,
                        stores_id: data.stores_id||0
                    }
                };
                area.init(area_config);
            }

            /**
             * 发票列表
             * @param id 选中发票
             */
            var show_invoice_lists = function(id){
                common.doAjax({
                    url:common.U('affiliated/getinvoice')
                },function(data){
                    cache_data.invoice = data;
                    var templates = require('template');
                    var html = templates('tpl-invoice',{items:data});
                    $('#js-invoice-div').html(html);
                    cache_data.status_invoice = 1;
                    if(data.length < 1){
                        cache_data.status_invoice = 0
                        show_invoice_add();
                    }

                    id = id||0;
                    var is_sel = false;
                    $('.js-invoice-lists').each(function(){
                        if($(this).data('id')==id){
                            is_sel = true;
                            $(this).click();
                        }
                    });

                    if(!is_sel){
                        $('.js-invoice-lists').eq(0).click();
                    }
                });
            }

            /**
             * 发票编辑
             * @param data
             */
            var show_invoice_add = function(data){
                data = data||{};
                var templates = require('template');
                $('#js-invoice-add-div').html(templates('tpl-invoice-add',{info:data})).show();
                var validate = function(){
                    $('#invoice_form').validate({
                        errorElement: 'label',
                        ignore:'',
                        rules: {
                            invoice_payee: {
                                required: true
                            }
                        },
                        messages: {
                            invoice_payee: {
                                required: "发票抬头不能为空"
                            }
                        },
                        submitHandler: function (form) {
                            common.formAjax(form,function(data){
                                if(data.status != common.success_code){
                                    diDialog.Alert(data.msg);
                                }else{
                                    show_invoice_lists(data.result);
                                }
                                return false;
                            });
                        }
                    });
                };
                validate();
            }

            $('#js-shopping-content').on('click','.pay-item',function(){//选中支付方式
                $(this).find('.change-bank').click();
            }).on('click','.change-bank',function(event){
                $('.pay-item').removeClass('active');
                $(this).parents('.pay-item').addClass('active');
                event.stopPropagation();
            }).on('click','.js-stores-add-address',function(){//添加提货地址
                show_stores_add();
                //$('.js-stores-add-address-div').hide();
            }).on('click','.js-stores-address-lists',function(event){//选中提货信息
                $('.js-stores-address-lists').removeClass('add-address-active');
                $(this).addClass('add-address-active');
                $(this).find('input[type="radio"]').prop("checked",true);
            }).on('click','.js-stores-address-lists .js-update', function () {//更新提货信息
                var id = $(this).parents('.js-stores-address-lists').data('id');
                var stores_info = {};
                $.each(cache_data.stores,function(i,val){
                    if(val.delivery_id == id){
                        stores_info = val;
                    }
                });
                show_stores_add(stores_info);
            }).on('click','.js-stores-address-lists .js-del', function (event) { //删除提货信息
                var id = $(this).parents('.js-stores-address-lists').data('id');
                diDialog.Confirm('<span>删除提货地址</span><p>确定要删除该提货地址吗？</p>',function() {
                    common.doAjax({
                        url: common.U('affiliated/deldelivery', {id: id})
                    }, function (data) {
                        if (data.status != common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            show_stores_lists(id);
                        }
                    });
                });
                event.stopPropagation();
            }).on('click','#js-stores-add .js-cancel-but',function(){//取消添加
                $('#js-stores-add').html('');
            }).on('click','#js-invoice',function(){//需要发票
                $('#js-needs-invoice').click();
            }).on('click','#js-needs-invoice',function(event){
                if($(this).is(':checked')) {
                    $('#js-invoice-div').show();
                    show_invoice_lists();
                }else{
                    $('#js-invoice-div').hide();
                }
                event.stopPropagation();
            }).on('click','.js-invoice-lists',function(){//选中发票信息
                $(this).find('input[type="radio"]').prop("checked",true);
            }).on('click','.js-invoice-lists .js-update',function(){//修改信息
                var id = $(this).parents('.js-invoice-lists').data('id');
                var invoice_info = {};
                $.each(cache_data.invoice,function(i,val){
                    if(val.invoice_id == id){
                        invoice_info = val;
                    }
                });
                show_invoice_add(invoice_info);
            }).on('click','.js-invoice-lists .js-del',function(){//删除发票信息
                var id = $(this).parents('.js-invoice-lists').data('id');
                diDialog.Confirm('<span>删除发票信息</span><p>确定要删除该发票信息吗？</p>',function() {
                    common.doAjax({
                        url: common.U('affiliated/delinvoice', {id: id})
                    }, function (data) {
                        if (data.status != common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            show_invoice_lists(id);
                        }
                    });
                });
                event.stopPropagation();
            }).on('click','#js-invoice-add-div .js-cancel-but',function(){//取消添加发票
                $('#js-invoice-add-div').html('');
                if(cache_data.status_invoice != 1){
                    $('#js-needs-invoice').prop("checked",false);
                }
            }).on('click','#js-pay', function(){//去付款
                var order_data = {};
                //提货地址
                var delivery_id = $('input[name="delivery_id"]:checked').val();
                if(delivery_id == '' || $.type(delivery_id) == 'undefined'){
                    diDialog.Alert('请填写提货地址');
                    return;
                }
                order_data.delivery_id = delivery_id;

                //支付方式
                var payment = $('input[name="payment"]:checked').val();
                if(payment == '' || $.type(payment) == 'undefined'){
                    diDialog.Alert('请选择支付方式');
                    return;
                }
                order_data.pay_type = payment;

                //发票
                var needs_invoice = $('input[name="needs_invoice"]').is(':checked');
                if(needs_invoice){
                    order_data.invoice_id = $('input[name="invoice_id"]:checked').val();
                    order_data.invoice_content = $('input[name="invoice_content"]').val();
                }

                order_data.postscript = $('input[name="postscript"]').val();

                var result_data = {};
                common.doAjax({
                    url:common.U('order/single'),
                    data:order_data,
                    async:false
                },function(data){
                    result_data = data;
                    if (data.status != common.success_code) {
                        diDialog.Alert(data.msg);
                    } else {
                        /*var id = data.result.order_id;
                        common.doAjax({
                            url:common.U('order/pay'),
                            data:{id:id}
                        },function(data){
                            if (data.status != common.success_code) {
                                common.U('cart/payfail',{id:id},true);
                            }else{
                                common.U('cart/paysuccess',{id:id},true);
                            }
                        });*/
                        //$('#js-pay-mask').show();
                        //$('#js-pay-content').show();
                    }
                });

                //打开新窗口支付
                if (result_data.status == common.success_code) {
                    to_pay(result_data.result.order_id);
                }
            });

            var to_pay = function(id){
                var form = $('#js-form');
                form.attr('action', common.U('order/pay', {id: id}));
                form.submit();

                $('#js-pay-mask').show();
                $('#js-pay-content').data('id',id).show();
            }


            $('#js-pay-content').on('click','.close-box', function(){//付款退出框
                $('#js-pay-mask').hide();
                $('#js-pay-content').hide();
                var id = $('#js-pay-content').data('id');
                common.U('cart/paysuccess',{id:id},true);
            }).on('click','.js-pay-finish',function(){//已完成付款
                var id = $('#js-pay-content').data('id');
                common.U('cart/paysuccess',{id:id},true);
            }).on('click','.js-pay-problem',function(){//付款遇到问题
                $('#js-pay-content .btn-box').hide();
                $('#js-pay-content .btn-2').show();
            }).on('click','.js-pay-again',function(){//重新支付
                var id = $('#js-pay-content').data('id');
                to_pay(id);
                $('#js-pay-content .btn-box').show();
                $('#js-pay-content .btn-2').hide();
            }).on('click','.js-pay-cancel',function(){//取消支付
                var id = $('#js-pay-content').data('id');
                common.U('cart/payfail',{id:id},true);
            })

        }
    };

    module.exports = main;
});