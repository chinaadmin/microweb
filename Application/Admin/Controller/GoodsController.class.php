<?php
/**
 * 商品管理
 * @author xiongzw	
 * @date 2014-04-14
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class GoodsController extends AdminbaseController{
	protected $curent_menu = 'Goods/index';
	protected $goods_model;
	public function _initialize() {
		parent::_initialize ();
		$this->goods_model = D ( 'Goods' );
	}
	/**
	 * 商品列表
	 */
	public function index() {
		$where = array(
				'delete_time'=>0
		);
		$where = $this->_goods_where($where);
		$data = $this->lists ( $this->goods_model, $where, "update_time desc,sort desc" );
        //$attr_feat = $this->goods_model->getAttrFeat();
		/* foreach ( $data as &$v ) {
			$featured = "";
			foreach ( $attr_feat as $k => $vo ) {
				if (intval ( $k ) & intval ( $v ['featured'] )) {
					$featured .= $vo . ",";
				}
			}
			$featured = rtrim ( $featured, "," );
			$v ['featured'] = $featured;
		} */
		if($data){
			$goods = array_column($data,"goods_id");
			$feat = D("Admin/Feat")->getByGoods($goods);
			foreach($data as &$v){
				$featured = "";
				if($feat){
					foreach ($feat as $vo){
						if($v['goods_id'] == $vo['goods_id']){
							$featured .= $vo['feat_name'] . ",";
						}
					}
				}
				$featured = rtrim ( $featured, "," );
				$v ['featured'] = $featured;
			}
		}
		$categorys = D ( 'Category' )->getTree ();
		$this->assign ( 'categorys', $categorys );
		$this->assign("brands",D ( 'Admin/Brand' )->getBrands ( array (), 'brand_id,name' ));
		$this->assign ( 'lists', $data );
		$this->assign("feats",$this->goods_model->getAttrFeat());
		$this->display ();
	}
	
	/**
	 * 商品查询条件
	 * @param  $where
	 */
	private function _goods_where($where=array()){
		$cat_id = I('request.cat_id',0,'intval');
		$keywords = I('request.goods_keywords','');
		$brand = I('request.brand',0,'intval');
		$sale = I('request.sale');
		$feat = I('request.feat',0,'intval');
		//分类
		if($cat_id){
			$cats = D('Admin/Category')->getChilds($cat_id);
			$cats[] = $cat_id;
			$where['cat_id'] = array('in',$cats);
			$this->assign('cat_id',$cat_id);
		}
		//关键字
		if($keywords){
			$where['name'] = array('like',"%{$keywords}%");
			$this->assign("keywords",$keywords);
		}
		//品牌
		if($brand){
			$where['brand_id'] = $brand;
			$this->assign("brand_id",$brand);
		}
		//上/下架
		if($sale<2 && $sale!=''){
			$where['is_sale'] = $sale;
			$this->curent_menu = 'Goods/index/sale/'.$sale;
			$this->assign("is_sale",$sale);
		}
		//展位
		if($feat>0){
			//$where['_string'] = $feat."&featured>0";
			$goods = array_column(D("Admin/Feat")->getGoodsFeat($feat,"goods_id"),"goods_id");
			if($goods){
			  $where['goods_id'] = array("in",$goods);
			}else{
			   $where['goods_id'] = 0;
			}
			$this->assign('feat',$feat);
		}
		$sold = I("request.sold",0,'intval');
		if($sold){
			$where['stock_number'] = 0;
			$this->curent_menu = 'Goods/index/sold/1';
		}
		$stock = I("request.stock",0,'intval');
		//库存报警商品
		if($stock==1){
			$num = C("JT_CONFIG_WEB_STOCK_WARNING_NUM");
			$num = $num>0?$num:10;
			$where['stock_number'] = array("LT",$num);
		}
		return $where;
	}

    /**
     * 推荐商品列表
     */
    public function get_ad_lists(){
        $page = I('get.page',1,'intval');
        $pageSize = I('get.pageSize',8,'intval');
        $goods_model = D('Admin/Goods');
        $where = [];

        //关键字
        $keyword = I('get.name');
        if(!empty($keyword)){
            $where['name'] = ['like','%'. $keyword .'%'];
        }

        $featured = I('get.featured');
        if(!empty($featured)){
            $where['featured'] = ['exp',' & '.$featured.'>0'];
        }

        $count = $goods_model->scope()->where($where)->count();
        $lists = $goods_model->scope()->field(true)->where($where)->page($page,$pageSize)->order('sort desc,update_time desc')->select();

        $data = array();
        $data['items'] = $lists;
        $data['count'] = $count;
        $this->ajaxReturn($this->result->content($data)->success()->toArray());
    }

	/**
	 * 添加/编辑商品
	 */
	public function edit() {
		$id = I ( 'request.id', 0, 'intval' );
		// 编辑商品
		if ($id) {
			$info = $this->goods_model->getById ( $id, true, 2 );
			$info ['attribute_id'] = json_decode ( $info ['attribute_id'], true );
			if ($info ['attribute_id']) {
				$info ['attribute_id'] = D ( 'Upload/AttachMent' )->getAttach ( $info ['attribute_id'] );
			}
			if($info ['attrs']){
			 $type_id = current(D("Admin/Category")->getById($info['cat_id'],"type_id"));
			 $info ['attrs'] = $this->goods_model->formatAttr ( $info ['attrs'] ,$type_id);
		 	 $info ['attrs'] = json_encode ( $info ['attrs'] );
			}
			//商品规格
			$norms = json_encode($this->goods_model->getNorms($id));
			$this->assign("norms",$norms);
			$this->assign ( "info", $info );
			$this->assign("subcats",M("GoodsSubcat")->where(['goods_id'=>$id])->getField('cat_id',true));
			$this->assign ( "feat", D("Admin/Feat")->getByGood($id) );
		}
		// 获取所有商品类型
		$types = D ( 'Type' )->getTypes ( "name,type_id" );
		$this->assign ( 'types', $types );
		// 获取分类
		$categorys = D ( 'Category' )->getTree ();
		$this->assign ( 'categorys', $categorys );
		// 获取品牌
		$brands = D ( 'Brand' )->getBrands ( array (), 'brand_id,name' );
		$this->assign ( 'brands', $brands );
		//商品标签
		$this->assign("tags",$this->goods_model->getTags($id));
		$this->assign('attFeat',$this->goods_model->getAttrFeat());
		$this->display ();
	}
	
	/**
	 * 添加更新商品
	 */
	public function update() {
		if (IS_POST) {
			$user = $this->user_instance->user ();
			$uid = $user ['uid'];
			$id = I ( 'post.id', 0, 'intval' );
			$result = $this->goods_model->editGood ( $uid );

			$res = $result->toArray();
			if($res['status']=='SUCCESS'){	//修改成功，先查出该商品的购物车数据
				$list = array();
				$where = array();
				
				$where['goods_id']=$id;
				
				$list = M('Cart')->where(['goods_id'=>$id])->select();
				foreach($list as $k=>$v){
					$data = array();
					$data['market_price'] = I ( 'post.old_price',"" );

					if($v['norms']){	//是否有规格id，有则查出规格价格
						$where['goods_norms_link']=$v['norms'];
						$goods_price = M('GoodsNormsAttr')->where($where)->getField('goods_norms_price');
						if($goods_price){	//存在规格价格，规格价格=商品价格
							$data = array(
								'goods_price' => $goods_price,
								'price' => $goods_price*$v['number']
							);
						}else{	//不存在规格价格（该规格已被删除），清空规格id和规格值	
							$data = array(
								'goods_price' => I('post.price',""),
								'price' => I('post.price',"")*$v['number'],
								'norms' => '',
								'norms_value'=>'[]'
							);
						}
					}else{	//规格id不存在，商品价格=传来的商品价格,清空规格值（可能存在）
						$data = array(
							'goods_price' => I('post.price',""),
							'price' => I('post.price',"")*$v['number'],
							'norms_value'=>'[]'
						);
					}
					//修改涉及该商品的购物车信息
					M("Cart")->where(['cart_id'=>$v['cart_id']])->save($data);
				}
			}
			
			$this->ajaxReturn($res);
		}
	}
	/**
	 * 删除
	 */
	public function del(){
		$goods_id = I('request.id',0,'intval');
		if($goods_id){
			$where = array(
					'goods_id'=>$goods_id
			);
			$result = $this->goods_model->tombstoneData($where);
			if($result->isSuccess()){
				$mess = '删除id为&nbsp;'.$goods_id.'&nbsp;商品&nbsp;'.$this->goods_model->getFieldByGoods_id($goods_id,'name');
				D('AdminLog')->addAdminLog($mess,3,$where);
				M("Cart")->where($where)->delete();
			}
			$this->ajaxReturn($result->toArray());
		}
	}
	/**
	 * 保存排序
	 */
	public function sort() {
		$sort = I ( 'request.sort' );
		$result = $this->goods_model->saveSort ( $sort, false, 'sort', 'goods_id' );
		$this->ajaxReturn ( $result->toArray());
	}
	
	/**
	 * 通过类型获取属性
	 */
	public function attrs() {
		$cat_id = I ( 'post.cat_id', 0, 'intval' );
		$type_id = current(D("Admin/Category")->getById($cat_id,"type_id"));
		$result ['success'] = false;
		if ($type_id) {
			$atts = D ( 'Attr' )->getByType ( $type_id );
			if ($atts) {
				foreach ( $atts as &$v ) {
					$v ['value'] = explode ( ",", $v ['value'] );
				}
				$result ['success'] = true;
				$result ['data'] = $atts;
			}
		}
		$this->ajaxReturn ( $result );
	}
	
	/**
	 * 添加商品时搜索商品
	 */
	public function searchGoods() {
		if (IS_AJAX) {
			$result ['success'] = false;
			$cat_id = I ( 'post.cat_id', 0, 'intval' );
			$brand_id = I ( 'post.brand_id', 0, 'intval' );
			$keyword = I ( 'post.keyword', '' );
			$type = I ( 'post.type', 0, 'intval' ); // 1:关联商品 2：配件 3：赠品
			$id = I ( 'post.id', 0, 'intval' );
			$goods = I ( 'post.goods', '' );
			$where = array (
					"delete_time"=>0,
					"is_sale"=>1
			);
			if ($cat_id) {
				$cats = D ( 'Category' )->getChilds ( $cat_id );
				$cats [] = $cat_id;
				$where ['cat_id'] = array (
						'in',
						$cats 
				);
			}
			if ($brand_id) {
				$where ['brand_id'] = $brand_id;
			}
			if ($keyword != "" && $keyword != null) {
				$where ['name'] = array (
						'like',
						"%{$keyword}%" 
				);
			}
			if ($type > 1) {
				$where ['is_goods'] = 0;
			}
			if ($goods) {
				if ($id)
					$goods .= "," . $id;
				$where ['goods_id'] = array (
						'not in',
						$goods 
				);
			} else {
				// 排除自己
				if ($id) {
					$where ['goods_id'] = array (
							'NEQ',
							$id 
					);
				}
			}
			$data = $this->goods_model->getGoods ( $where, "name,goods_id,price,stock_number" );
			if (! empty ( $data )) {
				$result ['success'] = true;
				$result ['data'] = $data;
			}
			$this->ajaxReturn ( $result );
		}
	}
	/**
	 * 通过分类id获取规格值
	 */
	public function getNormsValue(){
		$cat_id = I('request.cat_id',0,'intval');
		$data = D('Admin/Category')->getNormsValueById($cat_id);
		$this->ajaxReturn($data);
	}
	
	/**
	 * 获取商品从属分类
	 */
	public function getSubCats(){
		$categorys = D ( 'Category' )->getTree ();
		$this->ajaxReturn($categorys);
	}
	//展示商品评论
	function goodsComment(){
		$goods_id = I('goods_id',0,'int');
		if(!$goods_id){
			exit('传参有误!');
		}
		$this->goodsName = M('goods')->where(['goods_id' => $goods_id])->getField('name');
		$where = ['gc_goods_id' => $goods_id];
		if(($status = I('status','-1','int')) >= 0 ){
			$where['gc_status'] = $status;
		}
		if($keywords = I('keywords','','trim')){
			$where['username|aliasname'] = ['like','%'.$keywords.'%'];
		}
		$model = D('Admin/GoodsComment')->viewModel();
		$list = $this->lists($model,$where,'gc_add_time desc');
		$list = D('Admin/GoodsComment')->formatList($list);
		$this->assign('list',$list);
		$this->display('goodscomment');
	}
	function ajaxChangeCommentStatus(){
		$id = I('gc_id','0','int');
		$gc_status = I('gc_status',0,'int');
		if(!$id || (!$gc_status && $gc_status != 0)){
			$this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
		}
		$gc_failed_remark =  I('gc_failed_remark',0,'trim');
		if(D('Admin/GoodsComment')->switchStatus($id,$gc_status,$gc_failed_remark)){
			$this->ajaxReturn($this->result->success()->toArray());
		}
		$this->ajaxReturn($this->result->error()->toArray());
	}
	
}