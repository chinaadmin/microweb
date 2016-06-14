/**
*	抽奖js控制器
*/
define(function(require,exports,module){
	var $ = require('jquery');
	var common = require('common');
	var tool = require('model/tool');
	require('jquery_validate');
	
	var main = {};
	main.index = function(){
		$(function () {
			$('[data-toggle="tooltip"]').tooltip();	//提示框
			require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
                $('.date-picker').datepicker({
                    autoclose:true
                }); //时间选择器
            });

			//打印快递订单
			$('.print_express_bill').click(function(){
				//执行快递单打印				
				var printObj = $(this).closest('td').find('.print-hidden');
			 	require.async("pulgins/print/jquery.PrintArea",function(){
			 		printObj.printArea();
    	     	});			
			});

		});
	}

	module.exports = main;
});