define(function(require , exports ,module){
	var $ = require("jquery");
	main = {};
	main.init = function(){
		require.async("pulgins/bootstrap/datepicker/bootstrap-datepicker",function(){
            $('.start_time').datepicker({
                autoclose:true
            });
            $('.end_time').datepicker({
                autoclose:true
            });
        });
	};
	module.exports = main;
});