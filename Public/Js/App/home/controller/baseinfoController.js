define(function(require , exports ,module){
	var $ = require('jquery');
    var city = require("pulgins/area/area.js");
    var common = require("common");
    require('pulgins/birthday/birthday.js');
    //var dialog = require("artDialog"); 
    var diDialog = require('model/diDialog');
    var handel = {
    		userInfo:function(){
    			 require.async('jquery_validate',function(){
    				 $('#signupForm').validate({
    					 rules:{
    						 nickname:{
    							 required:true,
    							 remote:{
    								 url:common.U('baseinfoCheck')
    							 }
    						 },
    						 username:{
    							 required:true,
    							 remote:{
    								 url:common.U('baseinfoCheck'),
    								 data:{
    									 type:'username'
    								 }
    							 }
    						 },
    						 mepo_name:{
    							 required:true
    						 }
    					 },
    					 messages:{
    						 nickname:{
    							 required:"昵称不能为空",
    						     remote:'昵称已存在!'
    						 },
    						 username:{
    							 required:"登录名不能为空",
    							 remote:'登录名已存在!'
    						 },
    						 mepo_name:{
    							 required:"真实姓名不能为空"
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
                            		/* var d = dialog({
                         			    content: data.msg
                         			 });
                         			 d.show();*/
                         			 diDialog.Alert("个人资料修改成功！");
                            		 setTimeout(function () {
                            			  // d.close().remove();
                            			   window.location.reload();	
                            			}, 2000); 
                            	 }
                                 return false;
                             });
                         }
    				 });
    			 });
    		}
    };
    var main = {
    	init:function(){
    		city.init({
    			value:{
    				'provice_id':provice_id,
    				'city_id':city_id,
    				'county_id':county_id,
    				'town_id':town_id
    			}
    		});
    		handel.userInfo();
    		//生日picker
    		$.ms_DatePicker({
                YearSelector: ".sel_year",
                MonthSelector: ".sel_month",
                DaySelector: ".sel_day",
                FirstText: "请选择",
            });
    	   $.ms_DatePicker();
    	}	
    }
    module.exports = main;
});