/**
 * 行政地区联动插件 支持同一页面多处调用
 * use example:
 *  ---------example---------------
 *  <div id="area-select">
 *  <select name="provice" id="provice">
 *  </select>
 *  <select name="city" id="city">
 *  </select>
 *  <select name="county" id="county">
 *  </select>
 *  <select name="town" id="town">
 *  </select>
 *  </div>  
 *  -------------------------------
 */
define(function(require , exports, module){
	var $ = require('jquery');
	var common = require('common');
	var area = {
			//配置
			options : {
					url:common.U('Area/getAreaData'),
					provice:"provice",
					city:"city",
					county:"county",
					town:"town",
					selectId:"area-select",
					//value:{'provice_id':'110','city_id':'110100000000','county_id':'110101000000','town_id':'110101001000'},
					value:{},
					hasFirst:true,
					firstText:'请选择',
					fitstValue:'',
					namekey:{'provice_name':'provice_id','city_name':"city_id",'county_name':"county_id","town_name":"town_id"},
			},
			init:function(options){
				if(options){
					var options = $.extend(this.options,options);
				}else{
					var options = this.options;
				}
				$('#'+options.city).hide();
				$('#'+options.county).hide();
				$('#'+options.town).hide();
				var data = this.data(options);
				this.citys(options,data);
			},
			//城市数据
			citys : function(options,data){
				options.type=1;
				this.addData(options, data);
				this.defaultCity(options, data);
				this.doChange(options, data);
			},
			/**
			 * 定位或预处理数据
			 */
			defaultCity:function(options,data){
				if(options.value.provice_id){
					var result = this.getById(data, options,options.provice);
					this.addData(options, result);
					options.value.provice_id="";
				}
				if(options.value.city_id){
					var result = this.getById(data, options,options.city);
					this.addData(options, result);
					options.value.city_id="";
				}
				if(options.value.county_id){
					var result = this.getById(data, options,options.county);
					this.addData(options, result);
					options.value.county_id="";
					options.value.town_id="";
				}
			},
			//点击切换数据
			doChange:function(options,data){
				var _this = this;
				$('#'+options.selectId+" select").on('change',function(){
					var objId = $(this).attr('id');
					var id = $(this).find("option:selected").attr('data-id');
					var value = $(this).val() || options.firstText || "请选择";
					var result = _this.getById(data,options,objId);
					$(this).find("input[name='"+options.hiddenkey+"']").remove();
					var input="";
					if(id){
					 input = "<input type='hidden' name='"+options.hiddenkey+"' value='"+id+"'/>";
					}
					$(this).find("option:selected").html(value+input);
					if(result){
					 _this.addData(options, result);
					}
				})
			},
			//通过id获取值
			getById:function(data,options,objId){
				var datas;
				var pid = $('#'+options.provice).find("option:selected").attr('data-id') || options.value.provice_id;
				var cid = $('#'+options.city).find("option:selected").attr('data-id') || options.value.city_id;
				var county_id = $('#'+options.county).find("option:selected").attr('data-id') || options.value_county_id;
				this.doHide(pid, cid, county_id,options);
				switch(objId){
				//城市数据
				case options.provice :    
					                  options.type=2;
					                  options.hiddenkey = options.namekey.provice_name;   
					                  if(parseInt(pid)){
					                	  datas = this.data(options,"pid="+pid);				                    
					                  }
					                  this.removeHtml([options.city,options.county,options.town])
					                  break;
				case options.city :
					                 options.type=3;
					                 options.hiddenkey = options.namekey.city_name;        
					                 if(parseInt(pid && cid)){		           	                 
					                  //datas = data[pid]['child'][cid]['child'];
					                	 datas = this.data(options,"pid="+pid+"&cid="+cid);
					                 }
					                 this.removeHtml([options.county,options.town])
					                 break;
				case options.county:
					                options.type=4;
					                options.hiddenkey = options.namekey.county_name;
					                if(parseInt(pid && cid && county_id)){
					                	datas = this.data(options,"pid="+pid+"&cid="+cid+"&county_id="+county_id);
					                 //datas = data[pid]['child'][cid]['child'][county_id]['child'];
					                }			          
					                this.removeHtml([options.town])
					                break;
			    default:
			    	     options.hiddenkey = options.namekey.town_name;
			    	     return;
			    	     
				};
				return datas;
			},
			/**
			 * 不显示的html去除
			 */
			removeHtml:function(objs){
				for(i in objs){
					$('#'+objs[i]).html("");
					$('#'+objs[i]).hide();
				}
			},
			/**
			 * 编辑时无值隐藏
			 */
			doHide:function(pid,cid,county_id,options){
                 if(!pid){
					$('#'+options.city).hide();
					$('#'+options.county).hide();
					$('#'+options.town).hide();
                 }
                 if(!cid){
 					$('#'+options.county).hide();
 					$('#'+options.town).hide();
                 }
                 if(!county_id){
  					$('#'+options.town).hide();
                  }
			},
			//添加options数据
			addData:function(opt,data){
				var str = "<option value='"+opt.fitstValue+"'>"+opt.firstText+"</option>";
				if(!opt.hasFirst){
					str="";
				}
				var inputkey ="";
				switch(opt.type){
				   case 1:
					   var id = "provice_id";
					   var name = "provice_name";
					   var objId = $('#'+opt.provice);
					   var namekey = opt.namekey.provice_name;
				   break;
				   case 2:
					   var id = "city_id";
					   var name ="city_name";
					   var objId = $('#'+opt.city);
					   var namekey = opt.namekey.city_name;
				   break;
				   case 3:
					   var id = "county_id";
					   var name ="county_name";
					   var objId = $('#'+opt.county);
					   var namekey = opt.namekey.county_name;
				   break;
				   case 4:
					   var id = "town_id";
					   var name ="town_name";
					   var objId = $('#'+opt.town);
					   var namekey = opt.namekey.town_name;
				   break;
				}
				if(data){ 
				 for(i in data){
					var value = data[i][id];
					if((value==opt.value.provice_id) || (value==opt.value.city_id) || (value==opt.value.county_id) || (value==opt.value.town_id)){
						str += "<option selected='selected' data-id='"+value+"' value='"+data[i][name]+"'>"+data[i][name];
					    inputkey="<input type='hidden' name='"+namekey+"' value='"+value+"'/>";
					}else{
						str += "<option data-id='"+value+"' value='"+data[i][name]+"'>"+data[i][name];
					}
					str += " </option>";
				 }
				 objId.html(" ");
				 objId.html(str);
				 if(inputkey){
				  var select = objId.find("option:selected").html()+inputkey;
				  objId.find("option:selected").html(select);
				 }
				 objId.show();
				}
			},
			//获取行政地区数据
			data:function(options,param){
				param = param || "";
				var data;
				$.ajax({
				    url:options.url,
				    type:'post',
				    dataType:'json',
				    async:false,
				    data:param,
				    success:function(result){
				    	data = result;
				    },
				    error:function(XMLHttpRequest, textStatus, errorThrown){
				    	   alert(XMLHttpRequest.status);
				    }
				});
				return data;
			}
			
	};
	
	function clone3(obj){  
	    function Clone(){}  
	    Clone.prototype = obj;  
	    var o = new Clone();  
	    for(var a in o){  
	        if(typeof o[a] == "object") {  
	            o[a] = clone3(o[a]);  
	        }  
	    }  
	    return o;  
	}  
	yumArr = [];
	for(i = 0 ;i < 10 ;i++){
		yumArr['index'+i] = clone3(area); 
	}
//	yum = area;
	areaFactory = {
			yumArea:yumArr,
			set:function(key,param){
				 this.yumArea[key].init(param);
			},
			get:function(key){
				return this.yumArea[key];
			}
	}
	module.exports = areaFactory;
});	