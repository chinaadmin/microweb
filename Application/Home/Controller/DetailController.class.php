<?php
/**
 * 产品详情
 * @author xiongzw
 * @date 2015-4-23
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Common\Model\SharedModel;

class DetailController extends HomeBaseController{
	protected $detail_model;
	protected $goods_id;
	public function _initialize(){
		parent::_initialize();
		$this->detail_model = D('Home/Detail');
		$this->goods_id = I('request.id',0,'intval');
	}
	/**
	 * 产品详情
	 */
	public function index(){
		$goods_id = $this->goods_id;
		if ($goods_id) {
			//start*****add by wxb 2015/7/14
			if(!D('Admin/goods')->scope('sale')->where(['goods_id' => $goods_id])->count()){//商品已删除或下架则跳转到错误页
				$this->_404();
				exit;
			}
			//end*****
			$data = $this->detail_model->getById ( $goods_id );
			if ($data ['content']) {
				$data ['content'] = html_entity_decode ( $data ['content'] );
				$url = "http://".C('JT_CONFIG_WEB_DOMAIN_NAME');
				$preg = "/(<img\s+src=\"|\'\s*)((?!http|https)[\w+\/]+\.[jpg|png|gif])(.*?)(\"|\'\/>)/";
				$data['content'] = preg_replace($preg,'${1}'.$url.'${2}${3}${4}', $data['content']);
			}
			$links = $this->detail_model->links ( $goods_id ); // 关联商品
			$attr = $this->detail_model->attrs ( $goods_id ); // 商品属性

			//点击量、浏览历史
			$this->detail_model->clickAmount($goods_id,$this->user['uid']);

            //商品优惠信息
            $promtions = D('User/Promotions')->verifyGoods([$goods_id],SharedModel::SOURCE_PC);
            $promtions_goods = $promtions[$goods_id];
            if(!empty($promtions_goods)){
                $data['price'] = discountAmount($data['price'],$promtions_goods['discount']);
                $data['promtions_discount'] = $promtions_goods['discount'];
                $data['promtions_limit'] = $promtions_goods['limit'];
            }
			//商品规格
			$this->_norms($goods_id,$promtions_goods['discount']);
			$this->assign ( 'thumbSize', C ( 'THUMB_SIZE' ) );
			$this->assign ( "links", $links );
			$this->assign ( "info", $data );
			$this->assign ( "attr", $attr );
			$this->_dealNav($data['name']);
		}
        $this->assign('need_verify', A('Passport')->_need_verify());
        $stores_lists = D('Stores/Stores')->getStores();
        $this->assign('stores_lists',$stores_lists);
        $this->assign("afterSale",D("Admin/Article")->getByCode("2015061147442554","content"));
		$this->display();
	}
	/**
	 * 规格数据处理
	 */
	private function _norms($goods_id,$discount){
		$model = D('Admin/Goods');
		$norms = $model->getNorms($goods_id);
        if(!empty($discount)) {
            $norms['norms_attr'] = array_map(function ($info) use ($discount) {
                $info['goods_norms_price'] = discountAmount($info['goods_norms_price'], $discount);
                return $info;
            }, $norms['norms_attr']);
        }

		if(array_filter($norms)){
		 $norms = $model->formatNorms($norms);
		 $norms['norms_attr'] = json_encode($norms['norms_attr']);
		 $this->assign("norms",$norms);
		}
	}
	/**
	 * 来源导航
	 */
	private function _dealNav($goodsName){
		$cat_id = I('request.catId',0,'intval');
		if($cat_id){
			$name = current(D('Admin/Category')->getById($cat_id,"name"));
			$this->assign("cat_id",$cat_id);
			$this->assign('name',$name);
		}
		$this->assign('goodsName',$goodsName);
		$this->assign('goods_id',$this->goods_id);
	}
}