<?php
/**
 * 众筹订单
 * @author xiongzw
 * @date 2015-12-10
 */
namespace Chips\Controller;
use Api\Controller\ApiBaseController;
class OrderController extends ApiBaseController{
	protected  $order_model;
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
		$this->order_model = D("Chips/Order");
	}
	
	/**
	 * 订单页面
	 *     输入参数
	 *       <code>
	 *         cdId 众筹方案id
	 *         cgId 众筹商品 多个用英文,号隔开
	 *         trem 第几期 (选传)
	 *       </code>    
	 */
	public function orderShow(){
		$trem = 1;
		$cdId = I("request.cdId",0,'intval');
		$cgId = I("request.cgId",'');
		$cgId = explode(",", $cgId);
		//获取方案详情
		$scheme = D("Chips/Chips")->schemeInfo($cdId,$trem,$this->user_id);
		//获取方案商品
		$goods = D("Chips/Chips")->getChipsGoods($cgId);
		D("Home/List")->getThumb($goods,false,'cg_att_id');
		$goods_data = array();
		foreach($goods as $key=>$v){
			$goods_data[$key] = array(
					'cgId'=>$v['cg_id'],
					'name'=>$v['cg_goods_name'],
					'price'=>$v['cg_market_price'],
					'photo'=>fullPath($v['thumb'])
			);
		}
		$order = array(
				'scheme'=>$scheme,
				'goods'=>$goods_data
		);
		$this->ajaxReturn($this->result->content(['order'=>$order])->success());
	}
	
	/**
	 * 提交订单
	 *     输入参数
	 *     <code>
	 *      cdId  方案id
	 *      cgIds 方案商品  [{'cgId':10,'num':2}]
	 *      refereeMobile 推荐人手机号
	 *      storeId 门店id
	 *      shippingType 配送方式
	 *      postscript 买家附言
	 *     </code>
	 */
	public function submitOrder(){
		$cdId = I("request.cdId",0,'intval');
		$cgIds = html_entity_decode(I("request.cgIds",''));
		$cgIds = json_decode($cgIds,true);
		$mark = I('request.postscript','');
		$refereeMobile = I('request.refereeMobile','');
		$store_id = I("request.storesId",0,'intval');
		$addressId = I("request.addressId",0,'intval');
		$recommendUid = "";
		if($refereeMobile){
			 $where = array(
			 		'mobile'=>$refereeMobile,
			 );
			 $recommendUid = D('User/User')->scope('normal,default')->where($where)->getField("uid");
			 if(empty($recommendUid)){
			 	$this->ajaxReturn($this->result->error("推荐人不存在！"));
			 }
			 if($recommendUid==$this->user_id){
			 	$this->ajaxReturn($this->result->error("推荐人不能是本人！"));
			 }
		}

		
		
		$data = array(
				'uid'=>$this->user_id,
				'recommendUid'=>$recommendUid,
				'mark'=>$mark,
				'store_id'=>$store_id,
				'addressId'=>$addressId,
				'shipping_type'=>I("request.shippingType",0,'intval'),
				'cor_shipping_time'=>strtotime(I("POST.cor_shipping_time",time()))
		);
		$result = $this->order_model->creatOrder($cdId,$cgIds,$data);
		$this->ajaxReturn($result);
	}
	
	/**
	 * 获取全部订单
	 */
	public function orders(){
		$where = array(
				'jt_com_uid'=>$this->user_id,
				'jt_com_delete_time'=>0
		);
		$data = array();
		//总订单
		$countOrder = $this->_lists(M("CrowdfundingOrderMakefile"),$where,'jt_com_add_time desc');
		$data = $this->order_model->formatOrder($countOrder['data']);
        $countOrder['data'] = $data;
        $this->ajaxReturn($this->result->content($countOrder)->success());
	}
	
	/**
	 * 退出众筹
	 *     <code>
	 *      countOrder 总订单号
	 *     </code>
	 */
	public function exitChips(){
		$countOrder = I("post.countOrder","");
		$result = $this->order_model->exitChips($countOrder);
		$this->ajaxReturn($result);
	}
	
	
	/**
	 * 订单详情
	 *     
	 */
	public function orderInfo(){
		$deliveryString = array('待发货','发货中','确认收货','已收货','退货');
		$where = array(
				'jt_com_uid'=>$this->user_id,
				'cor_order_sn'=>I('orderId'),
		);
		$order 		= M('crowdfunding_order')->where($where) -> find(); //订单信息
		$img 		= M('crowdfunding_goods')->where(array('fk_cd_id'=>$order['fk_cd_id'])) -> find();
		$img_src 	= M('attachment')->where(array('att_id'=>$img['cg_att_id'])) -> find();
		$goods 		= M('crowdfunding_order_goods')->where(array('fk_cor_order_id'=>$order['cor_order_id'])) -> find();
		$stores 	= M("stores") -> where(array('stores_id'=>$order['cor_store_id']))  -> find();//门店信息
		$info  		= M('crowdfunding_order_goods')-> where(array('fk_cor_order_id'=>$order['cor_order_id']))-> select();
		$orderInfo 	= M("CrowdfundingOrderMakefile")->where(['jt_com_ordersn'=>$order['fk_com_ordersn']])->find();
		$receipt 	= M("OrderReceipt")->where(['order_id'=>$orderInfo['jt_com_id']])->find();
		
		if($order['cor_delivery_type'] >0){
			$ads = M("order_receipt")->where(array('order_id'=>$order['cor_order_id'])) -> find();
			$order['username'] 	= $ads['name']; 
			$order['address'] 	= $ads['localtion']."&nbsp&nbsp&nbsp".$ads['address']; 
		
		}
		$order['cor_order_status'] 	= $deliveryString[$order['cor_order_status']];
		// $order['img'] 				= 'http://'.$_SERVER['HTTP_HOST'].$img_src['path'].'/'.$img_src['name'].'.'.$img_src['ext'];
		$oimg 				= 'http://'.$_SERVER['HTTP_HOST'].$img_src['path'].'/'.$img_src['name'].'.'.$img_src['ext'];
		$order['cor_pay_time'] 		= empty($order['cor_pay_time'])?'':date('Y-m-d H:i:s',$order['cor_pay_time']);
		$order['cor_shipping_time'] = empty($order['cor_shipping_time'])?"":date('Y-m-d H:i:s',$order['cor_shipping_time']);
		
		/*if($goods){
			$data = array_merge($order,$stores,$img,$goods);
		}else{
			$data = array_merge($order,$stores,$img);
		}*/
		$data['info'] = $info;
		$data['order'] = $order;
		$data['cog_shipping_status'] = $goods['cog_shipping_status'];
		$data['img'] = $oimg;
		if($stores){
			$data['stores'] = $stores;
		}else{
			$data['stores'] = array(
					'stores_id'=>'','name'=>'','provice'=>'','city'=>'','county'=>'','localtion'=>'','address'=>'','phone'=>'','status'=>'','sort'=>'','add_time'=>'','remark'=>'','address_list'=>'','lat_lon'=>'','am_start_time'=>'','am_end_time'=>'','pm_start_time'=>'','pm_end_time'=>'',
				);
		}
		if($receipt){
			$data['receipt'] = $receipt;
		}else{
			$data['receipt'] = array(
					'order_id'=>'','name'=>'','provice'=>'','city'=>'','county'=>'','localtion'=>'','address'=>'','zipcode'=>'','tel'=>'','mobile'=>'','email'=>'','name'=>'',
				);
		}
        $this->ajaxReturn($this->result->content($data)->success());
	}
}