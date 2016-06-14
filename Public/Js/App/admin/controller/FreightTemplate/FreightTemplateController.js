define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	var template = require("template");
	require('jquery_validate');
   	/**
   	 * 添加管理员验证
   	 */
	var add_validate = function(){
        $('#form_validate').validate($.extend(tool.validate_setting,{
            rules: {
            	ft_name:{
            		required:true
            	}
            },
            messages: {
            	ft_name:{
            		required:'模板名称不为空'
            	}    	
            }
        }));
	};
	function toggle_show(selectorArr){
		$.each(selectorArr,function(k,v){
				$(v).click(function(){
					//根据状态显示或者隐藏
					var _self = $(this);
					toggle_click(_self);
				}).each(function(){
					toggle_show_hidden($(this));
				});
		});
		function toggle_click(check_box){
			check_box.closest('.control-group').next().toggle();
		}
		function toggle_show_hidden(check_box){
			check_box.closest('.control-group').next()[check_box.attr('checked') == 'checked' ? 'fadeIn' : 'fadeOut']();
		}
	};
	(function(){
		$('input:checkbox').click(function(){
			var id,val,_self = $(this);
			id = _self.data('id');
			if(_self.closest('span').hasClass('checked')){
				val = 1;
			}else{
				val = 0;
			}	
			$('#'+id).val(val);
		});
	})()
	function addClick(city_func){
		$('.jobAreaSelect').unbind('click').click(function(){
  		  var idsuffix = $(this).data('idsuffix');
  		  idsuffix = idsuffix || '';
  		  city_func.jobAreaSelect(idsuffix);
       });
	}
	//动态删除dom
	function removeClick(){
		$('.js-remove').unbind('click').click(function(){
        	$(this).closest('tr').remove();
        });	
	}
	var main ={
		index : function(){
			require("pulgins/city/city_func");
			tool.del($('.js-del'));
		},
        add : function(){
        	tool.del($('.js-del'));
        	toggle_show(['.js_toggle_show']);
            add_validate();
            var city_func = require("pulgins/city/city_func");
	    	window.jobArea = city_func.jobArea;
	    	
	    	$('.jobAreaSelect').click(function(){
	    		  var idsuffix = $(this).data('idsuffix');
	    		  idsuffix = idsuffix || '';
	    		  city_func.jobAreaSelect(idsuffix);
	        });
            //增加纪录
            $('#addFree').click(function(){
            	var count;
            	count = $('#addFreeTbody').children('tr').length;
            	var htmlFree = template("free",{"count":++count});	
            	$('#addFreeTbody').append($(htmlFree));
	        	addClick(city_func);
	        	removeClick();
            });
            $('#addFreight').click(function(){
            	var count;
            	count = $('#addFreightTbody').children('tr').length;
            	var htmlFreight = template("freight",{"count":++count});
            	$('#addFreightTbody').append(htmlFreight);
            	addClick(city_func);
            	removeClick();
            });
        }
	};
	module.exports = main;
});