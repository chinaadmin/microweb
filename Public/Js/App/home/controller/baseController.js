define(function(require , exports ,module){
    var $ = require('jquery');

    var main ={
        menu:function(){
            $('.sort_show').mouseover(function () {
                $('#sort_hover').show();
                $('.pro_hover').addClass("pro_hovered").removeClass("pro_hover");
            });
            $('.sort_show').mouseleave(function () {
                $('#sort_hover').hide();
                $('.pro_hovered').removeClass("pro_hovered").addClass("pro_hover");
            });
        },
        cart:function(number){
            require.async('pulgins/ui/parabola.js',function(parabola) {
                /*抛物线JS*/
                window.onload = function () {
                    // 元素以及其他一些变量
                    var eleFlyElement = document.querySelector("#flyItem"),
                        eleShopCart = document.querySelector("#shopCart");
                    //购物车数量
                    var numberItem = number || 0;
                    eleShopCart.querySelector("span").innerHTML = numberItem;

                    // 抛物线运动
                    var myParabola = parabola.funParabola(eleFlyElement, eleShopCart, {
                        speed: 400, //抛物线速度
                        curvature: 0.0008, //控制抛物线弧度
                        complete: function () {
                            eleFlyElement.style.visibility = "hidden";
                            eleShopCart.querySelector("span").innerHTML = ++numberItem;
                        }
                    });
                    // 绑定点击事件
                    if (eleFlyElement && eleShopCart) {
                        $(".btnCart").each(function(i,button) {
                            $(button).click(function(event) {
                                // 滚动大小
                                var scrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft || 0,
                                    scrollTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
                                eleFlyElement.style.left = event.clientX + scrollLeft + "px";
                                eleFlyElement.style.top = event.clientY + scrollTop + "px";
                                eleFlyElement.style.visibility = "visible";
                                // 需要重定位
                                myParabola.position().move();
                            });
                        });
                    }
                }
            });
        },
        nav:function(){
            $(".logo_tow_nav").hide();
            $(".logo_box").mouseover(function(){
                $(".logo_box").addClass("logo_box_hover")
                $(".logo_tow_nav").show();
            })
            $(".logo_tow_nav").mouseover(function(){
                $(".logo_tow_nav").show();
            })
            $(".logo_tow_nav").mouseout(function(){
                $(".logo_tow_nav").hide();
                $(".logo_box").removeClass("logo_box_hover")

            })
            $(".logo_box").mouseout(function(){
                $(".logo_tow_nav").hide();

            })

            /*“我的订单”部分下来菜单--指向*/
            $(".logo_nav_tow").hide()
            $(".logo_index").mouseover(function(){
                var x=$(".logo_index").index(this)
                $(".logo_nav_tow").eq(x).show();
            })
            /*“我的订单”部分下来菜单--移出*/
            $(".logo_index").mouseout(function(){
                $(".logo_nav_tow").hide();
            })
            $('.list-item').mouseover(function(){
                var this_a=$(this).attr('data-a');
                $('.subView').hide();
                $('[data-b='+this_a+']').show();
            })
        },
        banner:function() {
            $(".thumbnail_img").eq(0).addClass("border_red")
            var n = 0;
            function imgChange(){
                if(n<$("#banner img").length-1){
                    n=n+1;
                }else{
                    n=0;
                }

                $("#banner img").hide();
                $("#banner img:eq("+n+")").fadeIn(1000);
                $(".thumbnail_img").removeClass("border_red")
                $(".thumbnail_img").eq(n).addClass("border_red")
            }

            var clock = setInterval(imgChange,5000);
            $("#banner_box").mouseover(function(){
                clearInterval(clock);
                $('#btn_left').animate({left:"19%"});
                $('#btn_right').animate({right:"18%"});;
            }).mouseleave(function(){
                clock=setInterval(imgChange,5000);
                $('#btn_left').stop().animate({left:"-3%"});
                $('#btn_right').stop().animate({right:"-3%"});
            });
            $("#btn_right").click(function(){
                if(n<$("#banner img").length-1){
                    n=n+1;
                }else{
                    n=0;
                }
                $("#banner img").hide();
                $("#banner img").eq(n).fadeIn(1000);
                $(".thumbnail_img").removeClass("border_red");
                $(".thumbnail_img").eq(n).addClass("border_red");
            })

            $("#btn_left").click(function(){
                if(n>0){
                    n=n-1;
                }else{
                    n=$("#banner img").length-1;
                }
                $("#banner img").hide();
                $("#banner img").eq(n).fadeIn(1000);
                $(".thumbnail_img").removeClass("border_red");
                $(".thumbnail_img").eq(n).addClass("border_red");
            })

            $(".thumbnail li").click(function(){
                n=$(".thumbnail li").index(this)
                $("#banner img").hide();
                $("#banner img").eq(n).fadeIn(1000);
                $(".thumbnail_img").removeClass("border_red");
                $(".thumbnail_img").eq(n).addClass("border_red");
            })
        },
        top_cart:function(){//顶部购物车
            var top_cart = null;

            /**
             * 显示购物车
             */
            var show_cart = function(){
                require.async(['common','template'], function (common,templates) {
                    if(top_cart == null) {
                        common.doAjax({
                            url: common.U('cart/listJson')
                        }, function (data) {
                            if(data.status !== common.success_code) {
                                top_cart = {
                                    lists:[],
                                    total:{
                                        number:0
                                    }
                                };
                            }else{
                                top_cart = data.result;
                            }
                            var html = templates('tpl-top-cart-lists',{items:top_cart});
                            $(".cats-show").html(html).show();
                            $(".buy_car").addClass("buy-img-bottom");
                            $('#js-cart-count').html(top_cart.total.number);
                        });
                    }else {
                        var html = templates('tpl-top-cart-lists', {items:top_cart});
                        $(".cats-show").html(html).show();
                        $(".buy_car").addClass("buy-img-bottom");
                        $('#js-cart-count').html(top_cart.total.number);
                    }
                });
            };

            /**
             * 隐藏购物车
             */
            var hide_cart = function(){
                $(".buy_car").removeClass("buy-img-bottom");
                $(".cats-show").hide();
            };

            var topcart_timeout = ''
            $('#header .cats-hover').on('mouseenter',function(){
                clearTimeout(topcart_timeout);
                show_cart();
            }).on('mouseleave',function(){
                topcart_timeout = setTimeout(function(){
                    hide_cart();
                },500);
            }).on('click','.js-top-cart-del',function(event){
                var parents_tr = $(this).parents('.show-list');
                require.async(['common','model/diDialog'], function (common,diDialog) {
                    var opt = {
                        url:common.U('cart/del'),
                        data:{
                            id:parents_tr.data('id')
                        }
                    };
                    common.doAjax(opt,function(data){
                        if (data.status !== common.success_code) {
                            diDialog.Alert(data.msg);
                        } else {
                            top_cart = null;
                            show_cart();
                        }
                    });
                });
                event.stopPropagation();
            });
        }
    };

    //顶部
    if(typeof window.home.nav != "undefined" && window.home.nav) {
        //右边导航
        require.async('model/quick_links',function(){

        });
        main.nav();
    }

    //导航菜单
    if(typeof window.home.nav_default_menu != "undefined" && window.home.nav_default_menu) {
        main.menu();
    }

    //购物车
    if(typeof window.home.cart != "undefined" && window.home.cart) {
        main.cart();
    }

    //头部购物车
    if(typeof window.home.top_cart != "undefined" && window.home.top_cart) {
        main.top_cart();
    }

    //banner
    if(typeof window.home.banner != "undefined" && window.home.banner) {
        main.banner();
    }

    module.exports = main;
});