/**
 * 从属分类
 */
define(function(require , exports ,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	var template = require("template");
	require("chosen");
	var cats = {
		addsub:function(){
			if($(".addsub").is(":checked")){
				$('.subcat').show();
			}else{{
				$('.subcat').hide();
			}}
		},
		optDisable:function(){
			var disval = $("select[name='cat_id']").val();
			if(disval){
			 $(".subcat option[value="+disval+"]").attr("disabled",true);
			 $(".subcat option[value="+disval+"]").siblings("option").removeAttr("disabled");
			}
			$(".chosen-select").chosen({width: "220px"});
			$(".chosen-select").trigger("chosen:updated");
		}
	}
	cats.edit = function(){
		//$(".chosen-select").chosen({width: "220px"});
		cats.addsub();
		cats.optDisable();
		$(".addsub").on('click',function(){
			cats.addsub();
			cats.optDisable();
		})
	}
	module.exports = cats;
});