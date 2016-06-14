define(function(require , exports ,module){
    var $ = require('jquery');
    var main ={
        index:function(){
            $(".obtn").click(function(){
                var i = $(".obtn").index($(this));
                $(".list_detail").eq(i).toggle();
                $(".side_title").eq(i).toggleClass("side_normal");
            });
        }
    };
    module.exports = main;
});