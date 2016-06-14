define(function(require , exports ,module){
	var $ = require('jquery');
    var common = require("common");
    require('jquery_validate');
    var diDialog = require('model/diDialog');

    var address_lists = [];

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

    /**
     * 收货地址列表
     */
    var show_address_lists = function(){
        common.doAjax({
            url:common.U('affiliated/getRecaddress')
        },function(data){
            address_lists = data;
            var templates = require('template');
            var html = templates('tpl-recaddress',{items:data});
            $('#js-recaddress-div').html(html);
            $('#js-recaddress-count').html(12-data.length);
            $('#js-recaddress-count-div').show();
        });
    }

    /**
     * 收货地址编辑
     * @param data
     */
    var show_address_add = function(data){
        data = data||{};
        var templates = require('template');
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
                            show_address_lists();
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

    var main = {
        suggestion:function(){
            require.async(['artDialog','jquery_validate'],function(dialog,jquery_validate){
             $('#suggestForm').validate({
                 errorElement: 'label',
                 errorPlacement:function(error,element){
                    error.appendTo(element.parent());
                 },
                 rules:{
                     'type[]':{
                         required:true,
                        minlength:1
                     },
                    content:{
                         required:true,
                     },
                    email:{
                         required:true,
                        email:true
                     }
                 },
                 messages:{
                     'type[]':{
                         required:'至少选择一种意见类型',
                        minlength:'至少应选择一个意见类型'
                     },
                    content:{
                         required:'内容不能为空',
                     },
                    email:{
                         required:'&nbsp;联系邮箱不能为空',
                         email:'&nbsp;邮箱格式有误'
                     }
                 },
                 submitHandler: function (form) {
                        common.formAjax(form,function(data){
                         if(data.status!='SUCCESS'){
                             var d = dialog({
                                    content: data.msg
                                });
                                d.show();
                         }else{
                             var d = dialog({
                                    content: data.msg
                                 });
                                 d.show();
                             setTimeout(function () {
                                   d.close().remove();
                                   window.location.reload();
                                }, 2000);
                         }
                            return false;
                        });
                    }
             });
            var form = document.getElementById('suggestForm');
            $('#a_submit').click(function(){
                $(form).submit()
            });
         });
        },
        recaddress:function(){

            show_address_lists();

            //打开添加收货地址
            var open_popbox = function(){
                var h = $(".popBox").show().height();
                var selfh = $(".popBox .adderss-add").height()/2
                $(".popBox .adderss-add").show().css({
                    "margin-top":(h/2-selfh)+"px"
                });
            }

            $('#js-recaddress').on('click','.js-recaddress-add-btn',function(event){ //添加收货地址
                event.preventDefault();
                if(address_lists.length>=12) {
                    diDialog.Alert('收货地址最多只能添加12个');
                    return;
                }
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
                            show_address_lists();
                        }
                    });
                });
                event.stopPropagation();

            }).on('click','.js-recaddress-item .js-update',function(event){//修改
                //event.preventDefault();
                var items = $(this).parents('.js-recaddress-item');
                var address_id = items.data('id');

                var stores_info = {};
                $.each(address_lists,function(i,val){
                    if(val.address_id == address_id){
                        stores_info = val;
                    }
                });
                show_address_add(stores_info);

                open_popbox();

                event.stopPropagation();
            }).on('click','.js-recaddress-item',function(){//设置默认收货地址
                if($(this).hasClass('active')){
                    return ;
                }
                var id = $(this).data('id');
                diDialog.Confirm('<span>设为默认收货地址</span><p>确定要将该收货地址设为默认吗？</p>',function() {
                    common.doAjax({
                        url: common.U('affiliated/setDefaultRecaddress', {id: id})
                    }, function (data) {
                        if (data.status != common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            show_address_lists();
                        }
                    });
                });
            });

            $(".popBox").on('click', '.js-cancel,.close-box', function(event) {//关闭和取消
                event.preventDefault();
                $(".popBox").hide();
                $(this).parents(".box").hide();
            }).on('click','.js-submit',function(){//提交表单
                $('#recaddressForm').submit();
            });

        }
    };
    module.exports = main;
})