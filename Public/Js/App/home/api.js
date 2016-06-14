/**
 * 测试用的
 */
var api = function(){};
api.prototype = {
    updateImage:function(){
        var goods =  {
            goods_id:'2',
            name:'多酚胶囊',
            photo:'http://test.jitujituan.com/Uploads/Image/20150423/5538aebe5768a/5538aebe5768a_700X700.jpg'
        };
        $('#goods_img').attr('src',goods.photo);
        $('#goods_name').html(goods.name);
    }
};

function updateImage(){
    var goods =  {
        goods_id:'2',
        name:'多酚胶囊',
        photo:'http://test.jitujituan.com/Uploads/Image/20150423/5538aebe5768a/5538aebe5768a_700X700.jpg'
    };
    $('#goods_img').attr('src',goods.photo);
    $('#goods_name').html(goods.name);
}
/**
 * 调用方法
 * var get_api = new api();
 * get_api.updateImage();
 */
