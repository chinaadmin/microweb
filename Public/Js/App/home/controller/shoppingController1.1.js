define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    require('jquery_validate');
    var diDialog = require('model/diDialog');
    var templates = require('template');

    jQuery.validator.addMethod("address_required", function(value, element, param) {
        var provice = $("#provice").val();
        if(typeof(provice) == 'undefined' || provice == '' || provice == '请选择' || provice == null){
            return false;
        }
        var city = $("#city").val();
        if(typeof(city) == 'undefined' || city == '' || city == '请选择' || city == null){
            return false;
        }
        var county = $("#county").val();
        if(typeof(county) == 'undefined' || county == '' || county == '请选择' || county == null){
            return false;
        }
        return true;
    }, "请选择所在地区");

    var cache_data = {
        address:{
            lists:[],//收货地址列表
            sel_id:'',//选中收货地址id
            show_all:false, //显示全部
            is_default:0
        },
        delivery:{
            sel_id:'',//选中配送方式
            visit_delivey:{//送货上门
                id:0,//门店id
                shipment_price:0//运费
            },
            stores:{
                lists:{},
                sel_id:{
                    county:0,
                    stores:0,
                    time:0
                },
                time_lists:[]
            }//门店列表
        },
        invoice:{
            status:false, //是否开启发票
            sel_id:''
        },
        money:{
            data:{},
            sel_id:{
                coupon:0,
                credits:0
            }
        }
    };

    /**
     * 门店距离
     */
    var stores_distance = function(){
        var address_id = cache_data.address.sel_id;
        var order_money = cache_data.money.data.money.price;
        common.doAjax({
            url:common.U('Affiliated/getStoresDistance'),
            data:{
                order_money:order_money,
                address_id:address_id
            }
        },function(data){
            if (data.status != common.success_code) {
                diDialog.Alert(data.msg);
            } else {
                var stores_id = data.result.stores_id;
                $('.js-delivery-div p').each(function(){
                    var id = $(this).data('id');
                    if(id=='visit_delivery'){
                        if(stores_id == 0){
                            $(this).hide();
                            if(cache_data.delivery.sel_id == 'visit_delivery'){//选中的是送货上门需要重置为普通快递方式
                                $('.js-delivery-div p span').find('input').each(function(){
                                    if($(this).val()=='express_delivery') {
                                        $(this).click();
                                    }
                                });
                            }
                        }else {
                            $(this).show();
                            cache_data.delivery.visit_delivey.id = stores_id;
                        }
                    }else if(id=='disabled_visit_delivery'){
                        if(stores_id == 0) {
                            $(this).show();
                        }else{
                            $(this).hide();
                        }
                    }
                });
            }
        });
    }

    /**
     * 收货地址列表
     * @param is_default_order 是否默认排序：默认否
     * @param is_defalut_sel 是否需要强制默认选择：默认否
     */
    var show_address_lists = function(is_default_order,is_defalut_sel){
        //第一次加载时选中普通快递
        var is_first = false;
        if(cache_data.address.lists.length == 0) {
            is_first = true;
        }

        is_default_order = is_default_order||0;
        cache_data.address.is_default = is_default_order;
        is_defalut_sel = is_defalut_sel||false;
        var order_money = cache_data.money.data.money.price;
        common.doAjax({
            url:common.U('affiliated/getRecaddress'),
            data:{
                is_default:is_default_order,
                order_money:order_money
            }
        },function(data){
            cache_data.address.lists = data;
            if (is_defalut_sel||cache_data.address.sel_id == '') {
                $.each(cache_data.address.lists, function (i, val) {
                    if (val.is_default == 1) {
                        cache_data.address.sel_id = val.address_id;
                    }
                });
            }
            var html = templates('tpl-recaddress',{items:data,sel_id:cache_data.address.sel_id,show_all:cache_data.address.show_all});
            $('#js-recaddress-div').html(html);
            if(!cache_data.address.show_all && data.length >4){
                $('.js-show-all-address').show();
            }else{
                $('.js-show-all-address').hide();
            }

            if(is_first) {
                //选中默认配送方式为普通快递
                $('input[name="delivery"][value="express_delivery"]').parents('span').click();
                cache_data.delivery.sel_id = $('input[name="delivery"]:checked').val();
            }

            stores_distance();
        });
    }

    var address_id = '';
    /**
     * 收货地址编辑
     * @param data
     */
    var show_address_add = function(data){
        data = data||{};
        var html = templates('tpl-recaddress-update',{items:data});
        $('#js-recaddress-update').html(html);

        //地区
        var area = require('pulgins/area/area.js');
        var area_config = {
            value: {
                provice_id: data.user_provice||0,
                city_id: data.user_city||0,
                county_id: data.user_county||0
            }
        };
        area.init(area_config);

        //收货地址id
        address_id = data.address_id;

        var validate = function(){
            $('#recaddressForm').validate({
                errorElement: 'label',
                ignore:'',
                rules: {
                    name: {
                        required: true
                    },
                    mobile: {
                        required: true,
                        rangelength: [11, 11],
                        digits: "只能输入整数"
                    },
                    address:{
                        address_required: true
                    },
                    user_detail_address:{
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "收货人姓名"
                    },
                    mobile: {
                        required: "请输入手机号",
                        rangelength: "请输入正确的手机号"
                    },
                    user_detail_address:{
                        required: "请输入详细地址"
                    }
                },
                submitHandler: function (form) {
                    common.formAjax(form,function(data){
                        if(data.status != common.success_code){
                            diDialog.Alert(data.msg);
                        }else{
                            if(address_id=='' || $.type(address_id)=='undefined'){
                                cache_data.address.sel_id = data.result;
                                show_address_lists(0);
                            }else {
                                cache_data.address.sel_id = address_id;
                                show_address_lists(cache_data.address.is_default);
                            }
                            diDialog.Alert(data.msg);
                            $(".popBox").hide();
                        }
                        return false;
                    });
                }
            });
        };
        validate();
    }

    /**
     * 收货地址事件
     */
    var address_event = function(){
        //show_address_lists(1,true);

        //打开添加收货地址
        var open_popbox = function(){
            var h = $(".popBox").show().height();
            var selfh = $(".popBox .adderss-add").height()/2
            $(".popBox .adderss-add").show().css({
                "margin-top":(h/2-selfh)+"px"
            });
        }

        //收货地址
        $('#js-recaddress').on('click','.js-recaddress-add-btn',function(event){ //添加收货地址
            event.preventDefault();
            show_address_add();
            open_popbox();
        }).on('click','.js-recaddress-item .js-del',function(event){//删除
            var items = $(this).parents('.js-recaddress-item');
            var id = items.data('id');

            diDialog.Confirm('<span>删除收货地址</span><p>确定要删除该收货地址吗？</p>',function() {
                common.doAjax({
                    url: common.U('affiliated/delRecaddress', {id: id})
                }, function (data) {
                    if (data.status != common.success_code) {
                        diDialog.Alert(data.msg);
                    } else {
                        show_address_lists(1,true);
                    }
                });
            });
            event.stopPropagation();

        }).on('click','.js-recaddress-item .js-update',function(event){//修改
            //event.preventDefault();
            var items = $(this).parents('.js-recaddress-item');
            var address_id = items.data('id');

            var stores_info = {};
            $.each(cache_data.address.lists,function(i,val){
                if(val.address_id == address_id){
                    stores_info = val;
                }
            });
            show_address_add(stores_info);

            open_popbox();

            event.stopPropagation();
        }).on('click','.js-recaddress-item',function(){//选择收货地址
            if($(this).hasClass('active')){
                return ;
            }
            var id = $(this).data('id');
            cache_data.address.sel_id = id;
            $('.js-recaddress-item').removeClass('active');
            $(this).addClass('active');
            stores_distance();
        }).on('click','.js-show-all-address',function(){
            $(this).hide();
            cache_data.address.show_all = true;
            $('.js-recaddress-item').show();
        });

        $(".js-address-modal").on('click','.js-submit',function(){//提交表单
            $('#recaddressForm').submit();
        });
    }

    /**
     * 发票列表
     */
    var show_invoice_lists = function(){
        common.doAjax({
            url:common.U('affiliated/getinvoice')
        },function(data){
            var sel_id = cache_data.invoice.sel_id;
            var html = templates('tpl-invoice-input',{items:data,is_default:true,sel_id:sel_id});
            $('.js-invoice-input').html(html);
        });
    }

    /**
     * 添加发票
     */
    var show_invoice_add = function(){
        var data = [{name:''}];
        var html = templates('tpl-invoice-input',{items:data,is_default:false});
        $('.js-invoice-input').append(html);
    }

    /**
     * 显示发票
     */
    var show_invoice = function(info){
        var html = templates('tpl-invoice',{info:info});
        $('.js-invoice-div').html(html);
        $('.js-update-invoice').show();
        $(".billbox .default").show();
        $(".billbox .editing").hide();
    }

    //发票事件
    var invoice_event = function(){
        var info = {
            status:0
        };
        show_invoice(info);

        $("#js-invoice").on('click', '.js-update-invoice', function(event) {//修改发票
            event.preventDefault();
            $(this).hide();
            $(".billbox .editing").show();
            $(".billbox .default").hide();
            show_invoice_lists();
        }).on('click', '.js-add-invoice', function(event) {//添加发票
            event.preventDefault();
            if ($(".js-invoice-input .input").size() < 5) {
                show_invoice_add();
            } else {
                diDialog.Alert("最多可以添加5个发票抬头！");
            }
        }).on('click', '.js-del', function(event) {//删除
            event.preventDefault();
            var $p = $(this).parents(".input")
            if ($p.hasClass('active')) {
                $p.siblings('.input').eq(0).addClass('active');
            };
            $p.remove();
        }).on('click', '.js-checked', function(event) {//选择
            event.preventDefault();
            var $p = $(this).parents(".input");
            $p.siblings('.input').removeClass('active');
            $p.addClass('active');
        }).on('click','.js-submit',function(){//保存发票
            var data = [];
            var sel_val = '';
            $('input[name="invoice"]').each(function(){
                var invoice_info = {};
                var val = $(this).val();
                if(val.length == 0){
                   diDialog.Alert("发票抬头不能为空！");
                   return false;
                }
                var id = $(this).data('id');
                if(id != '' && $.type(id) != 'undefined') {
                    invoice_info.id = id;
                }
                invoice_info.val = val;
                if($(this).parents('.input').hasClass('active')){
                    invoice_info.sel = 1;
                    sel_val = val;
                }
                data.push(invoice_info);
            });

            common.doAjax({
                url:common.U('Affiliated/batchupdateinvoice'),
                data:{
                    invoice:data
                }
            },function(data){
                if (data.status != common.success_code) {
                    diDialog.Alert(data.msg);
                } else {
                    cache_data.invoice.sel_id = data.result;
                    var info = {
                        status:1,
                        invoice_payee:sel_val
                    };
                    show_invoice(info);
                }
            });
        }).on('click','.js-cancel',function(){//不开发票
            cache_data.invoice.status = false;
            var info = {
                status:0
            };
            show_invoice(info);
        });
    }

    /**
     * 门店自提
     */
    var from_mentioning = {
        init_stores:function(){//初始化门店列表
            if($.type(cache_data.delivery.stores.lists.county)!=='undefined'){
                return ;
            }
            common.doAjax({
                url:common.U('Affiliated/getStores')
            },function(data){
                if (data.status != common.success_code) {
                    diDialog.Alert(data.msg);
                } else {
                    cache_data.delivery.stores.lists = data.result;
                    var sel_id = {
                        county:0,
                        stores:0
                    };
                    var result = data.result;
                    $('#js-county-id').empty();
                    $.each(result.county,function(i,val){
                        if(sel_id.county == 0 && i==0){
                            sel_id.county = val['county_id'];
                        }
                        $('#js-county-id').append("<option value='"+val['county_id']+"'>"+val['county_name']+"</option>");
                    });
                    $('#js-county-id').val(sel_id.county);

                    from_mentioning.chenge_county(sel_id);

                    cache_data.delivery.stores.sel_id.county = sel_id.county;
                    cache_data.delivery.stores.sel_id.stores = sel_id.stores;
                }
            });
        },
        chenge_county:function(sel_id){//选择地区
            var stores = [];
            var stores_i = 0;
            $.each(cache_data.delivery.stores.lists.stores,function(i,val){
                if(val.county == sel_id.county){
                    if(stores_i == 0 && sel_id.stores == 0){
                        sel_id.stores = val.stores_id;
                    }
                    stores.push(val);
                    stores_i++;
                }
            });
            var html = templates('tpl-stores-lists',{items:stores,sel_id:sel_id.stores});
            $('.js-stores-lists').html(html);
        },
        show_from_mentioning : function(){ //显示门店自提信息
            var data = {};

            //门店信息
            $.each(cache_data.delivery.stores.lists.stores,function(i,val){
                if(val.stores_id == cache_data.delivery.stores.sel_id.stores){
                    data.stores = val;
                    return false;
                }
            });

            //门店提货时间信息
            $.each(cache_data.delivery.stores.time_lists,function(i,val){
                if(val.strtotime == cache_data.delivery.stores.sel_id.time){
                    data.time = val;
                    return false;
                }
            });

            var html = templates('tpl-from-mentioning',data);
            $('.js-from-mentioning').html(html);
        },
        init_stores_time:function(){//初始化门店列表
            if(cache_data.delivery.stores.time_lists.length > 0){
                return ;
            }
            common.doAjax({
                url:common.U('Affiliated/getStoresTime')
            },function(data){
                if (data.status != common.success_code) {
                    diDialog.Alert(data.msg);
                } else {
                    cache_data.delivery.stores.time_lists = data.result;

                    var sel_id = cache_data.delivery.stores.sel_id.time;
                    if(sel_id == 0){
                        sel_id = data.result[0].strtotime;
                        cache_data.delivery.stores.sel_id.time = sel_id;
                    }

                    var html = templates('tpl-stores-time-lists',{items:data.result,sel_id:sel_id});
                    $('#js-stores-time-lists').html(html);
                }
            });
        }
    };

    //配送方式
    var delivery_way_event = function(){

        from_mentioning.init_stores();
        from_mentioning.init_stores_time();

        //打开添加门店自提
        var open_popbox = {
            stores:function() {
                var h = $(".popBox").show().height();
                var selfh = $(".popBox .adderss-delivery").height() / 2
                $(".popBox .adderss-delivery").show().css({
                    "margin-top": (h / 2 - selfh) + "px"
                });
            },
            stores_time:function(){
                var h = $(".popBox").show().height();
                var selfh = $(".popBox .time-delivery").height()/2
                $(".popBox .time-delivery").show().css({
                    "margin-top":(h/2-selfh)+"px"
                });
            }
        };

        $('#js-delivery').on('click', '.js-delivery-div p span', function(event) {
            //event.preventDefault();
            if ($(this).find('input').size()) {
                var input = $(this).find('input').get(0);
                //$(this).find('input').get(0).click();
                var delivery_way = $(input).val();
                cache_data.delivery.sel_id = delivery_way;
                var price = 0;
                switch(delivery_way){
                    case 'disabled_visit_delivery'://禁用的送货上门
                        return;
                        break;
                    case 'express_delivery'://普通快递
                        var address_sel_id = cache_data.address.sel_id;
                        $.each(cache_data.address.lists,function(i,val){
                            if(address_sel_id == val.address_id){
                                price = val.shipment_price;
                            }
                        });
                    case 'visit_delivery'://送货上门
                        shipment_money(cache_data.delivery.visit_delivey.shipment_price);
                        $('.js-from-mentioning').html('');
                        break;
                    case 'from_mentioning'://门店自提
                        price = 0;
                        from_mentioning.show_from_mentioning();
                        break;
                }
                input.checked = true;
                shipment_money(price);
            };
        }).on('click','.js-stores-update', function (event) {//修改门店自提
            event.preventDefault();

            open_popbox.stores();

        }).on('click', '.js-stores-time-update', function(event) {//修改门店自提时间
            event.preventDefault();

            open_popbox.stores_time();

        });

        //选择自提时间模态框
        $('.js-time-delivery-modal').on('click','.js-stores-time-item',function(){
            $('.js-stores-time-item').removeClass('active');
            $(this).addClass('active');
        }).on('click','.js-submit',function() {//提交自提时间
            $('.js-stores-time-item').each(function(){
                if($(this).hasClass('active')){
                    cache_data.delivery.stores.sel_id.time = $(this).data('id');
                    return false;
                }
            });

            from_mentioning.show_from_mentioning();

            $(".popBox").hide();
            $(this).parents(".box").hide();
        });

        //选择自提点模态框
        $('.js-adderss-delivery-modal').on('change','#js-county-id',function(){//选择地区

            var sel_id = {
                county:$(this).val(),
                stores:0
            }

            from_mentioning.chenge_county(sel_id);

        }).on('click','.js-stores-items',function(){//选择自提地点
            $('.js-stores-items').removeClass('active');
            $(this).addClass('active');
            $('#js-stores-id').val($(this).data('id'));
        }).on('click','.js-submit',function(){//提交自提点

            cache_data.delivery.stores.sel_id.county = $('#js-county-id').val();
            cache_data.delivery.stores.sel_id.stores = $('#js-stores-id').val();

            from_mentioning.show_from_mentioning();

            $(".popBox").hide();
            $(this).parents(".box").hide();
        });

    }

    var pay_type_sel = function (sel_id) {

        $('.js-pay-type').each(function(){
            var radio = $(this).find('input[type="radio"]');
            var val = radio.val();
            if(val == sel_id){
                radio.prop("checked",true);
                return false;
            }
        });

        $('.js-pay-type-con').each(function () {
            $('.js-pay-type-con').hide();
            var val = $(this).data('id');
            if(val == sel_id){
                $(this).show();
                $(this).find('input[type="radio"]').eq(0).prop("checked",true);
                return false;
            }
        });

    }

    /**
     * 支付事件
     */
    var pay_event = function(){
        pay_type_sel(0);

        $('#js-pay').on('click','.js-pay-type-span', function () {//选择支付类型
            pay_type_sel($(this).find('input[type="radio"]').val());
        }).on('click','.js-pay-type-con-radio',function(){//选择支付方式
            $(this).find('input[type="radio"]').prop("checked",true);
        });

    }

    /**
     * 运费
     */
    var shipment_money = function(price){
        var data = cache_data.money.data;
        data.money.shipment_price = parseFloat(price);
        var pay_price = data.money.price - data.money.discount_price - data.money.coupon_price - data.money.integral_price + data.money.shipment_price;
        pay_price = pay_price.toFixed(2);
        data.money.pay_price = pay_price>=0?pay_price:0;
        var html = templates('tpl-money-coupon',{
            coupon:data.coupon,
            sel_id:data.sel.coupon
        });
        $('.js-use-coupon-con').html(html);

        var html = templates('tpl-money-integral',{
            credits:data.credits,
            sel_id:data.sel.credits
        });
        $('.js-use-integral-con').html(html);

        var html = templates('tpl-money',{money:data.money});
        $('#js-money').html(html);
    }

    /**
     * 显示金额
     * @param sel_id
     */
    var show_money = function(sel_id){
        var coupon = 0;
        if($.type(sel_id)=='undefined' || $.type(sel_id.coupon)=='undefined'){
            coupon = cache_data.money.sel_id.coupon;
        }else{
            coupon = sel_id.coupon;
        }

        var credits = 0;
        if($.type(sel_id)=='undefined' || $.type(sel_id.credits)=='undefined'){
            credits = cache_data.money.sel_id.credits;
        }else{
            credits = sel_id.credits;
        }

        //收货地址
        var address_id = cache_data.address.sel_id;

        //配送方式
        var shipping_type = cache_data.delivery.sel_id;

        common.doAjax({
            url:common.U('Cart/getMoney'),
            data:{
                coupon:coupon,
                credits:credits,
                address_id:address_id,
                shipping_type:shipping_type
            }
        },function(data){
            if (data.status != common.success_code) {
                diDialog.Alert(data.msg);
            } else {
                cache_data.money.data = data.result;

                var html = templates('tpl-money-coupon',{
                    coupon:data.result.coupon,
                    sel_id:data.result.sel.coupon
                });
                $('.js-use-coupon-con').html(html);

                var html = templates('tpl-money-integral',{
                    credits:data.result.credits,
                    sel_id:data.result.sel.credits
                });
                $('.js-use-integral-con').html(html);

                var html = templates('tpl-money',{money:data.result.money});
                $('#js-money').html(html);

                cache_data.money.sel_id.coupon = data.result.sel.coupon;
                cache_data.money.sel_id.credits = data.result.sel.credits;

                //第一次加载时显示收货列表
                if(cache_data.address.lists.length == 0) {
                    show_address_lists(1, true);//显示收货地址列表
                }
            }
        });
    }

    /**
     * 优惠事件
     */
    var preferential_event = function(){

        show_money();

        $('#js-preferential').on('click','p span', function(event) {
            event.preventDefault();
            var $em = $(this).find('em');
            if ($em.hasClass('div')) {
                $em.removeClass('div');
                $em.parents("span").siblings('a').hide();
            }else{
                $em.addClass('div');
                $em.parents("span").siblings('a').show();
            }
        }).on('change',"select[name='cpnselect']",function(){
            show_money({
                coupon:$(this).val()
            });
        }).on('click',".js-credits-submit",function(){
            var credits = $('input[name="js-credits"]').val();
            if(credits > cache_data.money.data.credits.use){
                diDialog.Alert('超过本次可使用的好货币数量');
                return;
            }

            show_money({
                credits:credits
            });
        });

    }

    /**
     * 提交订单事件
     */
    var submit_order_event = function(){

        $('#js-money').on('click','#js-order-submit', function(){//去付款
            var order_data = {};

            //收货地址
            var address_id = cache_data.address.sel_id;
            if(address_id == '' || $.type(address_id) == 'undefined'){
                diDialog.Alert('请填写收货地址');
                return;
            }
            order_data.address_id = address_id;

            //配送方式
            var shipping_type = cache_data.delivery.sel_id;
            if(shipping_type == '' || $.type(shipping_type) == 'undefined'){
                diDialog.Alert('请选择配送方式');
                return;
            }
            order_data.shipping_type = shipping_type;

            switch(shipping_type){
                case 'express_delivery'://普通快递
                    break;
                case 'visit_delivery'://送货上门
                    var visit_delivery_id = cache_data.delivery.visit_delivey.id;
                    if(visit_delivery_id == '' || $.type(visit_delivery_id) == 'undefined'){
                        diDialog.Alert('当前区域找不到可送货上门的门店');
                        return;
                    }
                    order_data.stores_id = visit_delivery_id;
                    break;
                case 'from_mentioning'://门店自提
                    var stores_id = cache_data.delivery.stores.sel_id.stores;
                    if(stores_id == 0 || $.type(stores_id) == 'undefined'){
                        diDialog.Alert('请选择自提门店');
                        return;
                    }

                    var stores_time = cache_data.delivery.stores.sel_id.time;
                    if(stores_time == 0 || $.type(stores_time) == 'undefined'){
                        diDialog.Alert('请选择门店自提时间');
                        return;
                    }

                    order_data.stores_id = stores_id;
                    order_data.stores_time = stores_time;
                    break;
                default :
                    diDialog.Alert('暂时不支持这种配送方式');
                    return;
            }

            //支付方式
            var payment = $('input[name="payment"]:checked').val();
            if(payment == '' || $.type(payment) == 'undefined'){
                diDialog.Alert('请选择支付方式');
                return;
            }
            order_data.pay_type = payment;

            //发票
            if(cache_data.invoice.status){
                var invoice_id = cache_data.invoice.sel_id;
                if(invoice_id == '' || $.type(invoice_id) == 'undefined'){
                    diDialog.Alert('请选择发票');
                    return;
                }
                order_data.invoice_id = invoice_id;
            }

            //买家附言
            order_data.postscript = $('input[name="postscript"]').val();

            //优惠
            order_data.coupon_id = cache_data.money.sel_id.coupon;
            order_data.integral = cache_data.money.sel_id.credits;

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

                }
            });

            //打开新窗口支付
            if (result_data.status == common.success_code) {
                var order_id = result_data.result.order_id;
                if(result_data.result.order_amount>0) {
                    to_pay(order_id);
                }else {
                    common.U('cart/paysuccess', {id: order_id}, true);
                }
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

    var main = {
        shopping:function(){
            //模态框关闭和取消操作
            $(".popBox").on('click', '.js-cancel,.close-box', function(event) {//关闭和取消
                event.preventDefault();
                $(".popBox").hide();
                $(this).parents(".box").hide();
            });

            address_event();//收货地址

            delivery_way_event();//配送方式

            pay_event();//支付

            invoice_event();//发票

            preferential_event();//优惠

            submit_order_event();//提交订单
        }
    };
    module.exports = main;
});