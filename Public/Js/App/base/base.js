define(function(require, exports, module) {
//自定义加载模块
    var $ = require('jquery');
    $.each(window.requireJs,function(i, item){
        if(item != ''){
            require.async(item);
        }
    });
    $('#home-search').on('submit',function(){
    	var value = $(this).find("input[name=searchKeywords]").val();
    	if(value=="" || value==null){
    		return false;	
    	}
    })
});