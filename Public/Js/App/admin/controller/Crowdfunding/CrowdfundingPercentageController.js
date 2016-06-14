define(function(require,exports,module){
	var $ = require('jquery');
	var common = require('common');
	main = {};
	main.index = function(){
		common.date({startSelector:'[name="start_time"]',endSelector:'[name="end_time"]'});
		var exportExcelObj = $('[name = "exportExcel"]');
		$('.search').click(function(){
			exportExcelObj.val(0);
		});
		$('.export').click(function(){
			exportExcelObj.val(1);
		});
	}
	main.percentageStatistics = function(){
		
	}
	module.exports = main;
});