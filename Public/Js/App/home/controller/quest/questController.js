define(function(require , exports ,module){
    var $ = require('jquery');
    var common = require('common');
    var dialog = require("artDialog");
    var main ={
        user:function(){
            var validCode = true;
            $(".msgs").click(function(){
                var _self = $(this);
                var time = 60;
                var code = $(".msgs");
                if (validCode) {
                    if(_self.data('disabled') === true){
                        return ;
                    }
                    _self.data('disabled',true);
                    common.doAjax({
                        url:common.U('quest/mobCode',''),
                        data:{
                            mobile:$('input[name="mobile"]').val()
                        }
                    },function(data){
                        _self.data('disabled',false);
                        if(data.status != 'MESSAGE_CODE_SEND'){
                            main.tip(data.msg);
                            code.html("重新获取");
                        }else{
                            validCode = false;
                            code.addClass("msgs1");
                            var t = setInterval(function() {
                                time--;
                                code.html(time + "秒");
                                if (time == 0) {
                                    clearInterval(t);
                                    code.html("重新获取");
                                    validCode = true;
                                    code.removeClass("msgs1");
                                }
                            }, 1000)
                        }
                    });
                }
            });
            require.async('jquery_validate',function(){
                var validate = function(){
                    $('#user-from').validate({
                        errorElement: 'label',
                        rules: {

                        },
                        messages: {

                        },
                        submitHandler: function (form) {
                            common.formAjax(form,function(data){
                                if(data.status != common.success_code){
                                    main.tip(data.msg);
                                }else{
                                    common.U('quest/answer',{id:data.result},true);
                                }
                                return false;
                            });
                        }
                    });
                };
                validate();
            });
        },
        answer:function(){
            var question_data = {
                list: [],
                current:{},
                val:{}
            };

            /**
             * 初始化试题
             */
            var init_question = function(){
                common.doAjax({
                    url:common.U('quest/getQuestion')
                },function(data){
                    question_data.list = data.result;
                    $('#js-answer-con').data('current',1);
                    show_question();
                });
            }();

            /**
             * 显示试题
             */
            var show_question = function(){
                var current = $('#js-answer-con').data('current');
                var length = question_data.list.length;
                var is_finish = false;
                if(length == current){
                    is_finish = true;
                }

                $.each(question_data.list,function(key,val){
                    if(key + 1 == current){
                        question_data.current = val;
                    }
                });
                var templates = require('template');
                $('#js-answer-con').html(templates('tpl-answer',{info:question_data.current,is_finish:is_finish}));
            };

            /**
             * 验证选项
             * @returns {boolean}
             */
            var auth_option = function(){
                switch (question_data.current.type) {
                    case 1:
                    case 2:
                        var length = $('input[name="option"]:checked').length;
                        if (length <= 0) {
                            main.tip('必须选择其中一项');
                            return false;
                        }
                        break;
                }
                return true;
            }

            /**
             * 选择答案
             */
            var set_answer = function(){
                var id = question_data.current.id;
                switch (question_data.current.type) {
                    case 1:
                        var option_val = $('input[name="option"]:checked').val();
                        question_data.val[id] = option_val;
                        break;
                    case 2:
                        var option_val = [];
                        $('input[name="option"]:checked').each(function(){
                            option_val.push($(this).val());
                        });
                        option_val = option_val.join(',');
                        question_data.val[id] = option_val;
                        break;
                    case 3:
                        var option_val = $('textarea[name="option"]').val();
                        question_data.val[id] = option_val;
                        break;
                }
            }

            /**
             * 是否继续下一题
             */
            var next_confirm = function (ok,cancel) {
                var d = dialog({
                    id: 'Confirm',
                    fixed: true,
                    lock: true,
                    opacity: .1,
                    content: '是否继续最后5个问题呢？填写后中奖概率更大哦！',
                    okValue: '继续',
                    ok: ok,
                    cancelValue: '飘过',
                    cancel: cancel
                });
                d.showModal();
            }

            /**
             * 下一题
             * @returns {boolean}
             */
            var next_question = function(){
                if(auth_option()===false){
                    return false;
                }
                set_answer();
                var current = $('#js-answer-con').data('current');
                $('#js-answer-con').data('current',current + 1);
                show_question();
            }

            /**
             * 提交试题
             * @returns {boolean}
             */
            var finish_question = function(){
                if(auth_option()===false){
                    return false;
                }
                set_answer();
                var uid = $('#uid').val();
                common.doAjax({
                    url:common.U('quest/submitAnswer',''),
                    data:{
                        'uid':uid,
                        'answer':question_data.val
                    }
                },function(data){
                    if(data.status != common.success_code){
                        main.tip(data.msg);
                    }else{
                        common.U('quest/finish','',true);
                    }
                });
            }

            $('#js-answer-con').on('click','.js-next',function(){//下一题
                var current = $('#js-answer-con').data('current');
                if(current == 5){
                    next_confirm(next_question,function(){
                        finish_question();
                    });
                }else {
                    next_question();
                }
            }).on('click','.js-finish',function(){//完成
                finish_question();
            }).on('click','.js-radio',function(){//选中题目
                $('.js-radio').removeClass('sel');
                $(this).addClass('sel');
                $(this).find('input[type="radio"]').prop("checked",true);
            }).on('click','.js-checkbox',function(){//选中题目
                var checkbox = $(this).find('input[type="checkbox"]');
                if(checkbox.is(':checked')){
                    $(this).removeClass('sel');
                    checkbox.prop("checked",false);
                }else{
                    $(this).addClass('sel');
                    checkbox.prop("checked",true);
                }
            });
        },
        tip:function(msg){
            var d = dialog({
                content: msg
            });
            d.showModal();
            setTimeout(function () {
                d.close().remove();
            }, 2000);
        }
    };
    module.exports = main;
});