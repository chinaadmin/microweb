/**
 * 行政地区联动插件
 * use example:
 *  ---------example---------------
 *  <div id="area-select">
 *  <select name="provice" id="provice">
 *  </select>
 *  <select name="city" id="city">
 *  </select>
 *  <select name="county" id="county">
 *  </select>
 *  <select name="stores" id="stores">
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
					stores:"stores",
					selectId:"area-select",
					//value:{'provice_id':'110','city_id':'110100000000','county_id':'110101000000','stores_id':'110101001000'},
					value:{},
					hasFirst:true,
					firstText:'请选择',
					fitstValue:'请选择',
					namekey:{'provice_name':'provice_id','city_name':"city_id",'county_name':"county_id","stores_name":"stores_id"},
			},
			init:function(options){
				if(options){
					var options = $.extend(this.options,options);
				}else{
					var options = this.options;
				}
				$('#'+options.city).hide();
				$('#'+options.county).hide();
				$('#'+options.stores).hide();
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
				}
				if(options.value.city_id){
					var result = this.getById(data, options,options.city);
					this.addData(options, result);
				}
				if(options.value.county_id){
					var result = this.getById(data, options,options.county);
					this.addData(options, result);
				}
			},
			//点击切换数据
			doChange:function(options,data){
				var _this = this;
				$('#'+options.selectId+" select").on('change',function(){
					var objId = $(this).attr('id');
					var id = $(this).find("option:selected").attr('data-id');
					var value = $(this).val();
					var result = _this.getById(data,options,objId);
					$(this).find("input[name="+options.hiddenkey+"]").remove();
					var input="";
					if(id){
					 input = "<input type='hidden' name='"+options.hiddenkey+"' value='"+id+"'/>";
					}
					$(this).find("option:selected").html(value+input);
					if(result){
					 _this.addData(options, result);
					}

                    if(options.stores == objId && typeof (id) != 'undefined'){
                        var stores =_this.getCurrentId(data,options,objId);
                        var localtion = stores.localtion + ' ' + stores.address;
                        $('#js-address .address').html(localtion);
                        $('#js-address').show();
                    }else{
                        $('#js-address').hide();
                    }
				})
			},
            getCurrentId:function(data,options,objId){
                var datas;
                var pid = $('#'+options.provice).find("option:selected").attr('data-id') || options.value.provice_id;
                var cid = $('#'+options.city).find("option:selected").attr('data-id') || options.value.city_id;
                var county_id = $('#'+options.county).find("option:selected").attr('data-id') || options.value.county_id;
                var stores_id = $('#'+options.stores).find("option:selected").attr('data-id') || options.value.stores_id;
                switch(objId){
                    //城市数据
                    case options.provice :
                        if(parseInt(pid)){
                            datas = data[pid];
                        }
                        break;
                    case options.city :
                        if(parseInt(pid && cid)){
                            datas = data[pid]['child'][cid];
                        }
                        break;
                    case options.county:
                        if(parseInt(pid && cid && county_id)){
                            datas = data[pid]['child'][cid]['child'][county_id];
                        }
                        break;
                    case options.stores:
                        if(parseInt(pid && cid && county_id && stores_id)){
                            datas = data[pid]['child'][cid]['child'][county_id]['child'][stores_id];
                        }
                        break;
                    default:
                        return;
                };
                return datas;
            },
			//通过id获取值
			getById:function(data,options,objId){
				var datas;
				var pid = $('#'+options.provice).find("option:selected").attr('data-id') || options.value.provice_id;
				var cid = $('#'+options.city).find("option:selected").attr('data-id') || options.value.city_id;
				var county_id = $('#'+options.county).find("option:selected").attr('data-id') || options.value.county_id;
				this.doHide(pid, cid, county_id,options);
				switch(objId){
				//城市数据
				case options.provice :    
					                  options.type=2;
					                  options.hiddenkey = options.namekey.provice_name;   
					                  if(parseInt(pid)){
					                   datas = data[pid]['child'];
					                  }
					                  $('#'+options.city).hide();
					                  $('#'+options.county).hide();
					  			      $('#'+options.stores).hide();
					                  break;
				case options.city :
					                 options.type=3;
					                 options.hiddenkey = options.namekey.city_name;
					                 if(parseInt(pid && cid)){
					                  datas = data[pid]['child'][cid]['child'];
					                 }
					                 $('#'+options.county).hide();
					                 $('#'+options.stores).hide();
					                 break;
				case options.county:
					                options.type=4;
					                options.hiddenkey = options.namekey.county_name;
					                if(parseInt(pid && cid && county_id)){
					                 datas = data[pid]['child'][cid]['child'][county_id]['child'];
					                }
					                $('#'+options.stores).hide();
					                break;
			    default:
			    	     options.hiddenkey = options.namekey.stores_name;
			    	     return;
			    	     
				};
				return datas;
			},
			/**
			 * 无值隐藏
			 */
			doHide:function(pid,cid,county_id,options){
                 if(!pid){
					$('#'+options.city).hide();
					$('#'+options.county).hide();
					$('#'+options.stores).hide();
                 }
                 if(!cid){
 					$('#'+options.county).hide();
 					$('#'+options.stores).hide();
                 }
                 if(!county_id){
  					$('#'+options.stores).hide();
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
					   var id = "stores_id";
					   var name ="name";
					   var objId = $('#'+opt.stores);
					   var namekey = opt.namekey.stores_name;
				   break;
				}
				if(data){ 
				 for(i in data){
					var value = data[i][id];
					if((value==opt.value.provice_id) || (value==opt.value.city_id) || (value==opt.value.county_id) || (value==opt.value.stores_id)){

						str += "<option selected='selected' data-id='"+value+"' value='"+data[i][name]+"'>"+data[i][name];
					    inputkey="<input type='hidden' name='"+namekey+"' value='"+value+"'/>"
					}else{
						str += "<option data-id='"+value+"' value='"+data[i][name]+"'>"+data[i][name];
					}
					str += " </option>";
				 }
				 objId.html(str);
				 if(inputkey){
				  var select = objId.find("option:selected").html()+inputkey;
				  objId.find("option:selected").html(select);
				 }
				 objId.show();
				}
			},
			//获取行政地区数据
			data:function(options){
				var data;
				$.ajax({
				    url:options.url,
				    type:'post',
				    dataType:'json',
				    async:false,
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
	module.exports = area;
});