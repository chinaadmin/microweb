define(function(require , exports ,module){
      var $ = require("jquery");
      //require("highcharts");
      require("pulgins/Highcharts/highstock/highstock.js");
      var common = require("common");
      var timeX = [];
      var maxX = 20;
      var series=[{name:'下单笔数',data:[]},{name:'付款笔数',data:[],yAxis:1},{name:'付款金额',data:[],yAxis:2}];
      //报表
      var report = {
    		  report:function(){
    			  this.data();
    			  $('#container').highcharts({
  			        title: {
  			            text: null,
  			            x: -20 //center
  			        },
  			        subtitle: {
  			            text: '',
  			            x: -20
  			        },
  			        xAxis: {
  			        	tickmarkPlacement :'on',
  			            categories: timeX,
  			            tickInterval:tick,
  			            max:maxX,
  			            min:1
  			        },
  			        yAxis: [{  	
  			        	title:{text:'下单笔数'},
  			        	tickInterval:1,
  			        	min:0,
  			        	lineWidth:1
  			        },
  			        {  	
  			        	title:{text:'付款笔数'},
  			        	tickInterval:1,
  			        	min:0,
  			        	lineWidth:1
  			        },
  			        {  	
  			        	title:{text:'付款金额'},
  			        	//tickInterval:1,
  			        	min:0,
  			        	lineWidth:1,
  			        	opposite: true
  			        }
  			        ],
  			        
  			        tooltip: {
  			            valueSuffix: ''
  			        },
  			        legend: {
  			            layout: 'vertical',
  			            align: 'right',
  			            verticalAlign: 'middle',
  			            borderWidth: 0
  			        },
  			        series: series,
  			        scrollbar: {
   		               enabled: true
   		            }
  			    });
    		  },
    		  data:function(){
    			  var j=0;
    		      for(var i in report_data){
    		    	  timeX[i] =  report_data[i].time;
    		    	  series[0].data[i] = report_data[i].place;
    		    	  series[2].data[i] = report_data[i].pay_money;
    		    	  series[1].data[i] = report_data[i].pay_num;
    		    	  j++;
    		      }
    		      if(j<20){
    		    	  maxX=j-1;
    		      }
    		  },
    		  select:function(){
    			  $("select[name='source']").on("change",function(){
    				  var val = $(this).val();
    				  window.location.href = common.U('Report/sale',{'day':day,'source':val});
    			  })
    		  }
      }
      
    //投放时间
      function put_in_time(){
          require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
              $('.start_time').datepicker({
                  autoclose:true
              });
              $('.end_time').datepicker({
                  autoclose:true
              });
              $('#sel_time').change(function(){
                  $('#end_time').val($(this).val());
                  $('.end_time').datepicker('update');
              });
          });
      }
      var main = {
    		  index:function(){
    			 report.report(); 
    			 put_in_time();
    			 report.select();
    		  }
      };
      module.exports = main;
});