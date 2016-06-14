<?php
/**
 * 首页逻辑类
 * @author cwh
 * @date 2015-03-30
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class IndexController extends AdminbaseController {

    protected $no_auth_actions = ['index'];
    protected $index_model;
    public function _initialize(){
    	parent::_initialize();
    	$this->index_model = D("Admin/Index");
    }
    /**
     * 后台首页
     */
    public function index(){
        if(empty($this->user['uid'])){
            $this->redirect(C('USER_AUTH_GATEWAY'));
        }
       /*  $url = 'Goods/index';
        if($this->checkRule($url)){
            $this->redirect($url);
        }else{
            $menu = $this->getMenu();
            if(empty($menu)){
                $this->redirect(C('USER_AUTH_GATEWAY'));
            }else {
                redirect(reset($menu)['show_url']);
            }
        }  */
    	//所属门店
        $stores_id = I('stores_id');
    	$stores_id = $stores_id ? $stores_id : $this->stores_id;
    	if($stores_id){
    		//获取门店信息
    		$stores = D("Stores/Stores")->getStoresById($stores_id);
    		//获取店长信息
    		$stores['manager'] = D("Stores/StoresUser")->getUserView($stores_id)->where("manager=1")->getField("nickname");
    		$this->assign("stores",$stores);
    		$this->showStoresList();
    	}
    	$data = array(
    			"confirmed" => $this->index_model->ordersStatus(0,$stores_id),   //未确认订单
    			"send" => $this->index_model->ordersStatus(1,$stores_id,2,0),    //待发货
    			"refund_ped"=>$this->index_model->refundStatus(0,$stores_id),    //待审核
    			"refunded" => $this->index_model->refundStatus(1,$stores_id),    //待退款
    			"refund" => $this->index_model->refundStatus(2,$stores_id),    //退款中
    			"warning_num" => $this->index_model->stockWarning(), //库存警告
    			"today_order" => $this->index_model->orderDay(1,$stores_id),  //今日订单数
    			"yesterday_order" => $this->index_model->orderDay(2,$stores_id), //昨日订单数
    			"today_pay" => $this->index_model->orderDay(1,$stores_id,"pay_time"), //今日支付
    			"yesterday_pay" => $this->index_model->orderDay(2,$stores_id,"pay_time"), //昨日支付
    			"today_user" => $this->index_model->userCount(), //今日注册
    			"yesterday_user" => $this->index_model->userCount(2), //作日注册
    			"userTotal" => $this->index_model->userCount(3), //总会员数
    			"goods"=>$this->index_model->goodsCount(), //商品总数
    			"goods_sale"=>$this->index_model->goodsCount(1),//上架商品总数
    			"readyToSend"=>'test'//待送货
    	);	
    	$this->assign($data);
        $this->display();
    }
	private function showStoresList(){
		$this->storesList = D('Stores/Stores')->getStores();
	}
}