define(function(require , exports ,module){
	var $ = require('jquery');
    require('pulgins/bootstrap/datetimepicker/bootstrap-datetimepicker.min.css');
	var common = require('common');
	require('jquery_validate');
    var tool = require('model/tool');

    /**
     * 分类验证
     */
    var validate_setting = tool.validate_setting;
    validate_setting.errorClass = 'error help-inline';
    delete validate_setting.highlight;
    delete validate_setting.unhighlight;
    delete validate_setting.success;
    var tmp = validate_setting.submitHandler;
    validate_setting.submitHandler = function(form){
    	if(!submitCheck()){
    		return false;
    	}
    	tmp(form);
    }
    function submitCheck(){

        //判断奖品中奖率
        if(($("#rateCount").val() - 0) > 100){
            require.async('base/jtDialog',function(jtDialog){
                jtDialog.showTip("奖品中奖率总和不能超过 100% ！");
            });
            return false;
        }

        //判断好友帮抽奖品中奖率
        if(($("#bc_rateCount").val() - 0) > 100){
            require.async('base/jtDialog',function(jtDialog){
                jtDialog.showTip("好友帮抽奖品中奖率总和不能超过 100% ！");
            });
            return false;
        }

    	//return true;
    	var tag = true;
    	$('.control-group-goods').each(function(){
    		var fk_as_id = $(this).find('[name="fk_as_id[]"]').val();
    		var apd_award_total = $(this).find('[name="apd_award_total[]"]').val();
    		var apd_alias_name = $.trim($(this).find('[name="apd_alias_name[]"]').val());
    		var apd_probability = $(this).find('[name="apd_probability[]"]').val();
    		var apd_pic_id = $(this).find('[name="apd_pic_id[]"]').val();
    		if(!$.isNumeric(apd_award_total) ||apd_alias_name == '' || !$.isNumeric(apd_probability)|| !$.isNumeric(apd_pic_id)){
    			tag = false;
    			$(this).find('.controls').addClass('borderRed');
    			location.href = '#';
    			return false;
    		}
    	});
    	return tag;
    }
    var edit_validate = function(){
        $('#from_edit').validate($.extend({
            rules: {
            	ap_name: {
                    required: true,
                },          
                ap_start_time:{
                	required: true,
                },
                ap_end_time:{
                	required: true,
                },           
                'ar_order_type':{
                	required: true,
                },
                'ap_remark':{
                	required: true,
                },
                'ap_lose_message':{
                	required: true,
                }
            }
        },validate_setting));
    };

    /**
    *   日期时间转为Date对象
    */
    function stringTime2date(stringDateTime){
        return new Date(Date.parse(stringDateTime.replace(/-/g,   "/")));
    }

    function put_in_time(){
        //修改时间选择插件
        require.async('pulgins/bootstrap/datetimepicker/locales/zh-CN', function(dateTimer) {
            $('.form_datetime').datetimepicker({
                language: 'zh-CN',
                format: "yyyy-MM-dd hh:mm:ss",
                autoclose: true
            }).on('changeDate', function(event) {
                var endTime = $('#end_time');
                if (endTime.val()) {
                    // if (event.date > common.stringTime2date(endTime.val())) {
                    if (event.date > stringTime2date(endTime.val())) {
                        endTime.val('');
                    }
                }
            });
            $('.end_datetime').datetimepicker({
                language: 'zh-CN',
                format: "yyyy-MM-dd hh:mm:ss",
                // startDate: common.stringTime2date($('#start_time').val()),
                startDate: stringTime2date($('#start_time').val()),
                autoclose: true
            });
        });

    }
    function addUploadEvent(jqueryObj){ //给新增加的上传插件增加事件
    	  require.async("base/plugin.js",function(doeach){
    		  doeach.eachUpload(jqueryObj);
          });
      }
    function delGoods(obj){ //给删除按钮增加删除事件
    	obj.click(function(){
            // $(this).closest('.control-group-goods').remove();
    		$(this).closest('.control-group-goods, .bc_control-group-goods').remove(); // 修改为兼容删除好友帮抽奖品设置
    	});
    }
    /**
     * 增加上传控件
     * addObj 点击事件元素  uploadObj上传插件父元素 
     * delObjSelector  删除事件元素选择器
     */
    function addDelGoods(addObj,uploadObj,delObjSelector){
    	addObj.click(function(){
	      	  var 	yumSuffix = uploadObj.find('.plupload').attr('id'),suffix;
	    	  yumSuffix = yumSuffix.split('_')[1];
	    	  suffix = uploadObj.data('uploadid') + ($('.plupload').size() + 1);
	    	  var uploadHtml = uploadObj.html();
             // var reg = new RegExp(yumSuffix,"i");
	    	 var reg = new RegExp(yumSuffix,"g"); //修改为替换所有出现的字符
	    	 // while(uploadHtml.indexOf(yumSuffix) > 0) //重写上传html
	    	 //  {
	    		 uploadHtml = uploadHtml.replace(reg,suffix);
	    	  // }
	    	 var text = 'window.requireJs = ["base/plugin.js"];';
	    	 uploadHtml = uploadHtml.replace(text,'');
	    	 uploadHtml = uploadHtml.replace('<script type="text/javascript">','');
	    	 uploadHtml = uploadHtml.replace('</script>','');//新生成上传插件
	    	 var controlHtml = $(this).closest('.controls').find('.cloneHtml').html();


             //动态设置奖品类别id
             var ptype = $(".prizeType").attr("id"); // 获取奖品类别id
             ptSuffix = ptype + ($(".prizeType").size() + 1); // 动态修改奖品类别id，每增加一行id自动 + 1
             var ptReg = new RegExp(ptype,"i");
             controlHtml = controlHtml.replace(ptReg, ptSuffix);

             //动态设置奖品列表id
             var plist = $(".prizelist").attr("id"); // 获取奖品列表id
             plSuffix = plist + ($(".prizelist").size() + 1); // 动态修改奖品列表id，每增加一行id自动 + 1
             var plReg = new RegExp(plist,"i");
             controlHtml = controlHtml.replace(plReg, plSuffix);

             //动态增加奖品中奖率id
             var prolist = $(".zz_probability").attr("id"); // 获取奖品中奖率id
             prolSuffix = prolist + (($(".zz_probability").size()) + 1); // 动态修改奖品中奖率id，每增加一行id自动 + 1
             var prolReg = new RegExp(prolist,"i");
             controlHtml = controlHtml.replace(prolReg, prolSuffix);


	    	 var delButton = '<a class="btn green del_goods">删除奖品</a>';
	    	 var addHtml = '<div class="control-group control-group-goods">\
	             <label class="control-label"> </label>\
	             <div class="controls"><span class="cloneHtml">'+controlHtml+'</span>'+delButton+uploadHtml+'</div></div>';      
	    	 $('.control-group-goods').last().after(addHtml);
	    	 addEvents($('#plupload_'+suffix),$('.control-group-goods').last().find(delObjSelector));
    	})    	
    }
    //为新元素增加事件
    function addEvents(uploadObj,delObj){
    	addUploadEvent(uploadObj);
    	delGoods(delObj);
    }


    /**
     *  获取奖品列表
     *  @param type:奖品类型id
     *  @param pListId:奖品列表id（html id属性）
     */
    function getPrizelist(type, pListId){
        $.getJSON('/award/getPrizeByType', {type : type}, function(result){
            var prize = $(pListId);
            $("option", prize).remove(); // 清空原有选项
            if(result.code == 1){
                $.each(result.data,function(index,array){ 
                    var option = "<option value='"+array['as_id']+"'>"+array['as_name']+"</option>";
                    prize.append(option); 
                });
            }
        });
    }

    /**
     *  好友帮抽动态增加奖品
     *  addObj 点击事件元素  uploadObj上传插件父元素 
     *  delObjSelector  删除事件元素选择器
     */
    function bcAddDelGoods(addObj,uploadObj,delObjSelector){
        addObj.click(function(){
              var   yumSuffix = uploadObj.find('.plupload').attr('id'),suffix;
              yumSuffix = yumSuffix.split('_')[1];
              suffix = uploadObj.data('uploadid') + ($('.plupload').size() + 1);
              var uploadHtml = uploadObj.html();

              uploadHtml = uploadHtml.replace('apd_pic_id[]', 'bc_apd_pic_id[]'); // 将 data-name="apd_pic_id[]" 替换成 data-name="bc_apd_pic_id[]"

             var reg = new RegExp(yumSuffix,"g"); //修改为替换所有相同字符

             // while(uploadHtml.indexOf(yumSuffix) > 0) //重写上传html
             //  {
                 uploadHtml = uploadHtml.replace(reg,suffix);
              // }

             var text = 'window.requireJs = ["base/plugin.js"];';
             uploadHtml = uploadHtml.replace(text,'');
             uploadHtml = uploadHtml.replace('<script type="text/javascript">','');
             uploadHtml = uploadHtml.replace('</script>','');//新生成上传插件
             var controlHtml = $(this).closest('.controls').find('.bc_cloneHtml').html();

             //动态设置好友帮抽奖品类别id
             var ptype = $(".bc_prizeType").attr("id"); // 获取奖品类别id
             ptSuffix = ptype + ($(".bc_prizeType").size() + 1); // 动态修改奖品类别id，每增加一行id自动 + 1
             var ptReg = new RegExp(ptype,"i");
             controlHtml = controlHtml.replace(ptReg, ptSuffix);

             //动态设置好友帮抽奖品列表id
             var plist = $(".bc_prizelist").attr("id"); // 获取奖品列表id
             plSuffix = plist + ($(".bc_prizelist").size() + 1); // 动态修改奖品列表id，每增加一行id自动 + 1
             var plReg = new RegExp(plist,"i");
             controlHtml = controlHtml.replace(plReg, plSuffix);

             //动态设置好友帮抽奖品中奖率id
             var prolist = $(".bc_probability").attr("id"); // 获取奖品中奖率id
             proSuffix = prolist + ($(".bc_probability").size() + 1); // 动态修改奖品中奖率id，每增加一行id自动 + 1
             var proReg = new RegExp(prolist,"i");
             controlHtml = controlHtml.replace(proReg, proSuffix);


             var delButton = '<a class="btn green bc_del_goods">删除奖品</a>';
             var addHtml = '<div class="control-group bc_control-group-goods">\
                 <label class="control-label"> </label>\
                 <div class="controls"><span class="bc_cloneHtml">'+controlHtml+'</span>'+delButton+uploadHtml+'</div></div>';      
             $('.bc_control-group-goods').last().after(addHtml);
             addEvents($('#plupload_'+suffix),$('.bc_control-group-goods').last().find(delObjSelector));
        })      
    }

    /**
     * 显示/隐藏好友帮抽奖品设置
     * @param: state 单选按钮选中的值 （0 or 1）
     */
    function bangchouToggle(state){
        state == 1 ? $("#bangchou").show() : $("#bangchou").hide();
    }

    /**
     * 切换活动状态
     */
    function switchState(){
        $(".switchState").click(function(){
            var apid = $(this).data('apid');
            var val = $(this).data('val');
            $.get(common.U('Admin/Award/switchState'), {apid : apid, val : val}, function(data){
                if(data.code){
                    window.location.reload();
                }
            });
        });
    }

    /**
     * 计算中奖率的总和
     * @param: string rateClassName 中奖率类名
     * @param: string rateIdName 单个中奖率输入框id名
     * @param: string rateCountIdName 中奖率总计文本框id名
     */
    function setRateCount(rateClassName, rateIdName, rateCountIdName){
        var rateSize = $(rateClassName).size(); // 中奖率输入框数量
        var rateCount = ($(rateIdName).val()) - 0; // 第一个中奖率输入框的值
        // 遍历除第一个输入框外所有输入框的值并相加
        for(var i = 2; i <= rateSize; i++){
            rateCount += ($(rateIdName + i).val()) - 0;
        }
        $(rateCountIdName).val(Math.ceil(rateCount)); // 将中奖率总和设置给隐藏文本框
    }

	var main ={
		index : function(){
			tool.del($('.js-del'));
			put_in_time();

            $('[data-toggle="tooltip"]').tooltip(); //鼠标移动到 开启/关闭 按钮时显示提示框
            switchState(); // 切换活动状态

            $('[data-toggle="popover"]').popover(); //点击查看奖品

		},
		edit:function(){
			edit_validate();
			put_in_time();
			addDelGoods($('.add_goods'),$('#addGoods'),'.del_goods');
			delGoods($('.del_goods'));

            bcAddDelGoods($('.bc_add_goods'),$('#addGoods'),'.bc_del_goods'); //动态增加好友帮抽奖品设置
            delGoods($('.bc_del_goods')); //为动态增加的删除按钮添加删除事件

            //奖品设置列表
            // getPrizelist(); // 获取奖品
            $(document).on('change', ".prizeType", function(){
                pListId = "#" + $(this).next(".prizelist").attr("id");
                getPrizelist($(this).val(), pListId);
            });

            //获赠奖品列表
            $("#zp_prizeType").change(function(){
                getPrizelist($(this).val(), "#zp_prizeList");
            });

            //好友帮抽奖品设置列表
            $(document).on('change', ".bc_prizeType", function(){
                pListId = "#" + $(this).next(".bc_prizelist").attr("id");
                getPrizelist($(this).val(), pListId);
            });

            //好友帮抽获赠奖品列表
            $("#bc_hy_prizeType").change(function(){
                getPrizelist($(this).val(), "#bc_hy_prizeList");
            });

            // 显示/隐藏好友帮抽奖品设置
            bangchouToggle($("input[name='ap_haoyoubangchou_status']:checked").val());
            $("input[name='ap_haoyoubangchou_status']").change(function(){
                bangchouToggle($(this).val());
            });

            //  页面加载时计算中奖率总和
            setRateCount(".zz_probability", "#zz_probability", "#rateCount");
            //  中奖率输入框改变时计算中奖率中和
            $(document).on('change', ".zz_probability", function(){
                setRateCount(".zz_probability", "#zz_probability", "#rateCount");
            });
            
            //  页面加载时计算好友帮抽中奖率总和
            setRateCount(".bc_probability", "#bc_probability", "#bc_rateCount");
            //  好友帮抽中奖率输入框改变时计算中奖率中和
            $(document).on('change', ".bc_probability", function(){
                setRateCount(".bc_probability", "#bc_probability", "#bc_rateCount");
            });
            


		}
	};
	module.exports = main;
});