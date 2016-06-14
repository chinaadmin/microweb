define(function(require , exports ,module){
var common = require('common');
var $ = require('jquery');	
var ja=[];
//所有省份数据字典
var allprov = [];
var ja = [];
$.post(common.U('FreightTemplate/getFormatArea'),function(rtn){
	//areaname provicestr
	var provice = rtn.provice;
	$.each(provice,function(k,v){
		allprov.push([v.areaname,v.provicestr.split(',')]);
	});
	$.each(rtn.data,function(k,v){
		ja[k] = v;
	});
});
var allprovArray=new Array('1600','2100','2800','2300','2400','2200','0700','0800','1500','1100','1300','1200','0300','1400','1000','1700','1800','1900','2000','2700','3200','2900','3100','0900','2600','2500','3000');
//所有省+直辖市
var allProvDuchy=new Array('0100','0200','0300','0400','0500','0600','1600','2100','2800','2300','2400','2200','0700','0800','1500','1100','1300','1200','0300','1400','1000','1700','1800','1900','2000','2700','3200','2900','3100','0900','2600','2500','3000','3300','3400','3500','3600');

function getAreaIDs(idx){
	var idx =  new Number(idx).toString();
	var newArr = new Array();
	for (var i in ja){
		if(i.substr(0,3)==idx.substr(0,3)){
			newArr[newArr.length]=i;
		}
	}
	return newArr;
}

	var main = {
			allprov:allprov,
			ja:ja,
			getAreaIDs:getAreaIDs,
			getName:function(idx){
				for (var i in ja){
					if(i == idx){
						return ja[i];
					}
				}
			}
	}
	module.exports=main;
})