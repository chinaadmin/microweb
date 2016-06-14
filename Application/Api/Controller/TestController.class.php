<?php
/**
 * 测试用类
 */
namespace Api\Controller;
class TestController extends ApiBaseController {

    public function goods(){
        $this->ajaxReturn($this->_get_info());
    }

    private function _get_info(){
        return [
            'goods_id'=>1,
            'name'=>'减肥茶',
            'photo'=>'http://test.jitujituan.com/Uploads/Image/20150423/5538aa181890a/5538aa181890a_700X700.jpg'
        ];
    }

    public function index(){
        $info = $this->_get_info();
        $this->assign('info',$info);
        $this->display();
    }
    //微信支付test
    public function wechatPay(){
    	$this->display();
    }
    //模拟运单接口返回数据
    function testKuaiDiCreateOrder(){
    	$a =  [
			"logisticID" => "AL353453253", 
			"mailNo" => "1144fafa",
			"result" => true, 
			"resultCode" => "1000",
			"uniquerRequestNumber" => "3600275537833690"
		];
    	header('Content-Type:application/json; charset=utf-8');
    	exit(json_encode($a));
    }
    function testKuaiDi(){
    	$a =' 
    	{
    		"responseParam":
    		{
    		"logisticCompanyID":"DEPPON",
    		"orders":
	    		[
		    		{
		    		"mailNo":"92189788",
		    		"orderStatus":"SIGNSUCCESS",
		    		"traceCode":"1000",
		    		"steps":
			    		[
			    		{
			    				"acceptTime":" 2013-04-16T10:53:46.000+0800",
			    				"remark":"已收取货物，[盐城]营业网点库存中"
			    		},
			    		{
			    			"acceptTime":" 2013-04-17T09:13:25.000+0800",
			    			"remark":"离开 [盐城]营业网点 发往 [南通]运输中心"
			    			},
			    			{
			    			"acceptTime":" 2013-04-18T19:53:46.000+0800",
			    			"remark":"已到达 [南通]运输中心"
			    			},
			    			{
			    			"acceptTime":"2012-11-30 10:59:11",
			    				"remark":"已到达 目的地,[西安雁塔丁白路]"
			    			},
			    			{
			    			"acceptTime":" 2013-05-16 09:26:19",
			    			"remark":" 签收人：赵**"
			    			}
			    		]
		    		}
	    		]
    		},
		"result":"true",
		"resultCode":"1000",
    	"reason":"",
    	"uniquerRequestNumber":"839296208933240"
    	}
    	   ';
    	header('Content-Type:application/json; charset=utf-8');
    	exit($a);
    }
    
  	function createCoupon($id){
	  	 	 echo path_encrypt('zhouhuodong|'.$id); //wzMA2G9uZ3aHVvZ6aG911hbGxSNAI2aml0d
	  }
}