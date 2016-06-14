define(function(require , exports ,module){
var $ = require('jquery');	
var residency_hukou_flag=0;	// 居住地 / 户口所在地   开关
require('jquery');	
var drag = require('pulgins/city/drag');	
var boxAlpha = drag.boxAlpha;
var draglayer  = drag.draglayer;


var city_arr = require('pulgins/city/city_arr');
var getAreaIDs = city_arr.getAreaIDs;
var maincity = city_arr.maincity;
var allprov = city_arr.allprov;
var getName = city_arr.getName;
var ja = city_arr.ja;

	function in_array(needle, haystack) {
		if(typeof needle == 'string' || typeof needle == 'number') {
			for(var i in haystack) {
				if(haystack[i] == needle) {
						return true;
				}
			}
		}
		return false;
	}
var residency = {
	// 居住地输出
	Show : function(){
		var k=0;
		var Div=new Array('maincity','allProv');
		while(k<=1){
			var output='<h4>主要城市：</h4>';
			var arr=maincity,area;
			if(k==1){
				output='<h4>主要城市：</h4>';
				arr=allprov;
			}
			for (var i in arr){
				area=arr[i][0];
				output+='<dl><dt>'+area+'</dt><dd>';
				for (var j in arr[i][1] ){
					id=arr[i][1][j];
					if(k==0){
						output+='<li onclick="residency.Chk(\''+id+'\')">'+ja[id]+'</li>';
					}else{
						if(area=='其它') output+='<li onclick="residency.Chk(\''+id+'\')">'+ja[id]+'</li>';
						else output+='<li onclick="residency.SubLayer(\''+id+'\')">'+ja[id]+'</li>';
					}
				}
				output+='</dd></dl>';
			}
			$('#'+Div[k]).html(output);
			k++;
		}
		$('#drag').width('580px');
		// 鼠标悬停变色
		$('#residencyAlpha li').hover(function(){$(this).addClass('over')},function(){$(this).removeClass()});
		// 点击弹出子菜单
		$('#allProv li').click(function(e){$("#sublist").css({top:e.pageY-4,left:e.pageX-4}).hover(function(){$(this).show()},function(){$(this).hide()})})
	},
	// 所有省份 下拉 城市菜单
	SubLayer : function(id){
		var output='<div id="sub_city">',width,ischecked,key;
		var arr=getAreaIDs(id);
		width=Math.ceil(Math.sqrt(arr.length-1))*60;
		output+='<ul style="width:'+width+'px"><h4 onclick="residency.Chk(\''+id+'\')"><a href="javascript:">'+ja[id]+'</a></h4>';
		for (var i=1;i<arr.length;i++){
			key=arr[i];
			output+='<li><a href="javascript:" onclick="residency.Chk(\''+key+'\')">'+ja[key]+'</a></li>';
		}
		output=output+'</ul></div>';
		$("#sublist").html(output).show();
	},


	Chk : function(id){
		if(residency_hukou_flag==0){
			$('#btn_residency').val(ja[id]);
			$('#residency').val(id);
		}else{
			$('#btn_hukou').val(ja[id]);
			$('#hukou').val(id);
		}
		$("#sublist").hide().empty();
		boxAlpha();
	}
}
	
function residencySelect(){
	residency_hukou_flag=0;
	var dragHtml ='<div id="residencyAlpha">';		//居住地
		dragHtml+='		<div id="maincity"></div>';	//主要城市
		dragHtml+='		<div id="allProv"></div>';	//所有省市
		dragHtml+='</div>';
	$('#drag_h').html('<b>请选择居住地</b><span onclick="boxAlpha()">关闭</span>');
	$('#drag_con').html(dragHtml);
	residency.Show();
	boxAlpha();
	draglayer();
}
function hukouSelect(){
	residency_hukou_flag=1;
	var dragHtml ='<div id="residencyAlpha">';		//居住地
		dragHtml+='		<div id="maincity"></div>';	//主要城市
		dragHtml+='		<div id="allProv"></div>';	//所有省市
		dragHtml+='</div>';
	$('#drag_h').html('<b>请选择户口所在地</b><span onclick="boxAlpha()">关闭</span>');
	$('#drag_con').html(dragHtml);
	residency.Show();
	boxAlpha();
	draglayer();
}


/* **************************************************************************** */

var jobArea_Arr = new Array();
//var jobArea_Arr = new Array('0100','0200','2402');

var jobArea = {
	idsuffix:'',//得先初始化
	reset_jobArea_Arr:function(){
		var str = $('#jobAreaID'+this.idsuffix).val();
		if(str){
			jobArea_Arr = new String(str).split(',');
		}else{
			jobArea_Arr =  [];
		}
	},
	// 请选择地区
	init : function(){
		var _str='',_id='';
		if (jobArea_Arr.length>0){
			for (var i in jobArea_Arr){
				_str+=','+ja[jobArea_Arr[i]];
				_id+=','+jobArea_Arr[i];
			}
			$('#btn_jobArea'+this.idsuffix).html(_str.substring(1));
			$('#jobAreaID'+this.idsuffix).val(_id.substring(1));
		}
	},
	Show : function(){
		var k=0,output='',output2='',arr,area,select_ed;
		var Div		= new Array('maincity2'+this.idsuffix,'allProv2'+this.idsuffix);
		var Title	= new Array('<h4>主要城市：</h4>','<h4>所有省份：</h4>');
		var LoopArr	= new Array(maincity,allprov);
		this.reset_jobArea_Arr();
		for(var i in jobArea_Arr){
			output2+='<li class="jobArea'+jobArea_Arr[i]+' chkON" onclick="jobArea.Chk(\''+jobArea_Arr[i]+'\')">'+ja[jobArea_Arr[i]]+'</li>';
		}
		$('#jobAreSelected'+this.idsuffix+' dd').html(output2);

		while(k<=1){
			output	= Title[k];
			arr		= LoopArr[k]
			for (var i in arr){
				area=arr[i][0];
				output+='<dl><dt>'+area+'</dt><dd>';
				for (var j in arr[i][1] ){
					id=arr[i][1][j];
					if(k==0){
						select_ed=in_array(id,jobArea_Arr)?' chkON':'';
						output+='<li class="jobArea'+id+select_ed+'" onclick="jobArea.Chk(\''+id+'\')">'+ja[id]+'</li>';
					}else{
						if(area=='其它') output+='<li class="jobArea'+id+'" onclick="jobArea.Chk(\''+id+'\')">'+ja[id]+'</li>';
						else output+='<li onclick="jobArea.SubLayer(\''+id+'\')">'+ja[id]+'</li>';
					}
				}
				output+='</dd></dl>';
			}
			
			$('#'+Div[k]).html(output);
			k++;
		}
		$('#drag').width('580px');
		// 鼠标悬停变色
		$('#jobAreaAlpha'+this.idsuffix+' li').hover(function(){$(this).addClass('over')},function(){$(this).removeClass('over')});
		// 点击弹出子菜单
		$('#allProv2'+this.idsuffix+' li').click(function(e){$("#sublist").css({top:e.pageY-4,left:e.pageX-4}).hover(function(){$(this).show()},function(){$(this).hide()})})
	},
	// 所有省份 下拉 城市菜单
	SubLayer : function(id){
		var output='<div name="sub_jobArea">',width,select_ed,key;
		select_ed=in_array(id,jobArea_Arr)?' chkON':'';
		var arr=getAreaIDs(id);
		width=Math.ceil(Math.sqrt(arr.length-1))*60;
		output+='<ul style="width:'+width+'px"><h4 onclick="jobArea.Chk(\''+id+'\')">';
		output+='<a href="javascript:" class="jobArea' + id + select_ed +'" title = " '+ja[id]+' ">'+ja[id]+'</a></h4>';

		for (var i=1;i<arr.length;i++){
			key=arr[i];
			select_ed=in_array(key,jobArea_Arr)?' chkON':'';
			output+='<li><a href="javascript:" class="jobArea' + key + select_ed +'" onclick="jobArea.Chk(\''+key+'\')">'+ja[key]+'</a></li>';
		}
		output=output+'</ul></div>';
		$("#sublist").html(output).show();
	},
	Chk : function(id){
		if(!in_array(id,jobArea_Arr)){
			var subArea,myid;
			if(id.length=='3'){	// 选中父类清除子类
				subArea=getAreaIDs(id);
				for(var i in subArea){
					if(in_array(subArea[i],jobArea_Arr)) this.del(subArea[i]);
				}
			}else{	// 选中子类清除父类
				myid=id.substr(0,3);
				if(in_array(myid,jobArea_Arr)) this.del(myid);
			};
			if(jobArea_Arr.length<5 ||true){
				jobArea_Arr[jobArea_Arr.length]=id;
				var html='<li class="jobArea'+id+'" onclick="jobArea.Chk(\''+id+'\')">'+ja[id]+'</li>';
				$('#jobAreSelected'+this.idsuffix+' dd').append(html);
				$('.jobArea'+id).addClass('chkON');
				$('#jobAreSelected'+this.idsuffix+' li').hover(function(){$(this).addClass('over')},function(){$(this).removeClass('over')});
			}else{
				alert('您最多能选择5项');
				return false;
			}
		}else{
			this.del(id);
		}
	},
	del : function(id){
		for (var i in jobArea_Arr){
			if(jobArea_Arr[i]==id) jobArea_Arr.splice(i,1);
		}
		$('#jobAreSelected'+this.idsuffix+' .jobArea'+id).remove();
		$('.jobArea'+id).removeClass('chkON');
	},
	// 确定
	confirm : function(){
		$('#btn_jobArea'+this.idsuffix+' .summaryList').html(this.formateAreaStr());
		summaryListClick();
		$('#jobAreaID'+this.idsuffix).val(jobArea_Arr);
		boxAlpha();
		$('#jobAreSelected'+this.idsuffix+' dd').empty();
	},
	close:function(){
		boxAlpha();
		$('#jobAreSelected'+this.idsuffix+' dd').empty();
	},
	//add by wxb 2015/07/27
	formateAreaStr:function(){
		//jobArea_Arr [130102000000,130183000000,130223000000]
		var areaStr='',my_jobArea_arr = jobArea_Arr;
		//取出省份
		var provinceArr = [],provinceCountArr = [],countyArr = [];
		for(var i in my_jobArea_arr){
			var tmp = my_jobArea_arr[i].substr(0,3);
			if(!in_array(tmp,provinceArr)){
				provinceArr.push(tmp);
				 //选中某个省全部
				provinceCountArr[tmp] = (my_jobArea_arr[i].length == 3) ? (getAreaIDs(tmp).length - 1) : 1;
				var reg = new RegExp(tmp+',');
				countyArr[tmp] = (my_jobArea_arr[i].length == 3) ? getAreaIDs(tmp).join(',').replace(reg,'') : my_jobArea_arr[i];
			}else{
				provinceCountArr[tmp]++;
				countyArr[tmp] += ','+my_jobArea_arr[i]; 
			}
		}
		//拼接字窜
		for(var i in provinceCountArr){
			var countyidstr = '';
			areaStr += '<li data-countyidstr ="'+countyArr[i]+'" data-provinceid = '+i+'>'+getName(i)+'<span>（'+provinceCountArr[i]+'）</span>&nbsp;&nbsp;&nbsp;</li>';
		}
		areaStr= areaStr ? areaStr : '<li>请选择地区</li>'; 
		//'<li>江西省<span>（14）</span></li>';
		return areaStr;
	},
	subString:function(countyidstr,separate){
		separate = separate || ',';
		var subString = '',arr;
		arr = new String(countyidstr).split(',');
		for(var i in arr){
			if(arr[i].length == 3){
				continue;
			}
			subString += separate+getName(arr[i]);
		}
		subString = subString.substr(1);
		return subString;
	}
}
	function jobAreaSelect(idsuffix){
		var idsuffix = idsuffix || '';
		var dragHtml ='<div name="jobAreaAlpha" id="jobAreaAlpha'+idsuffix+'">';		//地区
			dragHtml+='		<dl name="jobAreSelected" id="jobAreSelected'+idsuffix+'"><dt>已选地点：</dt><dd></dd></dl>';
			dragHtml+='		<div  name="maincity2" id="maincity2'+idsuffix+'" style = "display:none;"></div>';//主要城市
			dragHtml+='		<div  name="allProv2" id="allProv2'+idsuffix+'"></div>';	//所有省市
			dragHtml+='</div>';
		$('#drag_h').html('<b>请选择地区</b><span onclick="jobArea.close()">&nbsp;&nbsp;&nbsp;取消</span><span onclick="jobArea.confirm()">确定</span>');
		$('#drag_con').html(dragHtml);
		jobArea.idsuffix = idsuffix;
		jobArea.Show();
		boxAlpha();
		draglayer();
	}

	//显示所选详情市
	function summaryListClick(){
		$('.summaryList li').unbind('click').click(function(e) {
			var countyidstr,html,width;
			countyidstr = $(this).data('countyidstr');
			e = e || event;
			html = '';
			html = jobArea.subString(countyidstr);
			width=Math.ceil(Math.sqrt(new String(countyidstr).split(',').length))*60;
			$("#summaryList").html(html).css({
				top : e.clientY,
				left : e.clientX,
				position:'fixed',
				width:width
			}).show().hover(function() {
				$(this).show()
			}, function() {
				$(this).hide()
			})
		})
	}
	summaryListClick();

var main = {
		jobAreaSelect:jobAreaSelect,
		jobArea:jobArea,
		residency:residency,
}
module.exports=main;
})