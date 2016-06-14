<?php
/**
 * 商品模型
 * @author xiongzw
 * @date 2014-04-14
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class GoodsModel extends AdminbaseModel{
	protected $tableName = "goods";
	protected $edit_where;

    public $attr_feat = array (
        '1' => '推荐精品',
        /* '2' => '推荐',
        '4' => '新品',
        '8' => '爆款' */
    );

	public function _initialize(){
		parent::_initialize();
		$id = I('post.id',0,'intval');
		if($id){
			$this->edit_where = array(
					'goods_id' => $id
			);
		}
	}
	public $_validate = [ 
			[ 
					'name',
					'require',
					'商品名称不能为空' 
			],
			[ 
					'name',
					'ifUniqueName',
					'商品名称已存在',
					0,
					'callback',
					self::MODEL_BOTH 
			],
			[ 
					'code',
					'ifUniqueCode',
					'商品编号已存在',
					0,
					'callback',
					self::MODEL_BOTH 
			],
			[ 
					'goods_description',
					'0,255',
					'商品简述不能超过255个字符',
					self::EXISTS_VALIDATE,
					'length'
			],
			[ 
					'cat_id',
					'/[1-9]+/',
					'请选择分类' 
			],
			[ 
					'stock_number',
					'/[1-9]+/',
					'库存至少大于一件' 
			],
			[ 
					'price',
					'require',
					'商品价格不能为空' 
			],
			[ 
					'old_price',
					'require',
					'商品市场价不能为空' 
			],
			[ 
					'old_price',
					'checkOldPrice',
					'市场价必须大于商品价格！',
					0,
					'callback' 
			],
			[ 
					'attribute_id',
					'require',
					'商品图片不能为空' 
			],
			[ 
					'content',
					'require',
					'商品内容不能为空' 
			],	
	];
	
	//命名范围
	protected $_scope = [
			'default' => [  // 获取没有被删除状态
					'where' => [ 
							'delete_time' => [ 
									'eq',
									0 
							] 
					] 
			],
			'sale' => [  // 获取上架的商品
					'where' => [ 
							'delete_time' => [ 
									'eq',
									0 
							],
							'is_sale' => 1 
					] 
			] 
	];
	
	function ifUniqueName($goods_name){
		$where = [ 'name' =>$goods_name];
		$id = I ( 'post.id', 0, 'intval' );
		if($id){
			$where[$this->getPk()] = ['neq',$id]; //排除自身
		}
		$flag = $this->scope()->where($where)->count();
		return $flag ? false : true;  
	}
	function ifUniqueCode($goods_code){
		$id = I ( 'post.id', 0, 'intval' );
		$where = [ 'code' =>$goods_code];
		if($id){
			$where[$this->getPk()] = ['neq',$id]; //排除自身
		}
		$flag = $this->scope()->where($where)->count();
		return $flag ? false : true;
	} 
    public function checkOldPrice(){
    	$price = I("post.price",'','floatval');
    	$old_price = I("post.old_price",'','floatval');
    	if($old_price<=$price){
    		return false;
    	}else{
    		return true;
    	}
    }
    
    /**
     * 获取商品展位
     * @param int|null $attr_feat 展位id
     * @return array
     */
    public function getAttrFeat($attr_feat = null){
    	$feat = D("Admin/Feat")->getFeats(0);
    	$this->attr_feat = array_column($feat, "feat_name","feat_id");
        if(is_null($attr_feat)){
            return $this->attr_feat;
        }
        return $this->attr_feat[$attr_feat];
    }

	/**
	 * 添加基础数据
	 * @param $uid 用户id
	 */
	public function editGood($uid) {
		$feat = I ( 'post.feat' );
		$data = array (
                'yzid' => I ( 'post.yzid', '' ),
				'name' => I ( 'post.name', '' ),
				'code' => I ( 'post.code' ) ? I ( 'post.code' ) : $this->code (),
				'cat_id' => I ( 'post.cat_id', 0, 'intval' ),
				'brand_id' => I ( 'post.brand_id', 0, 'intval' ),
				'stock_number' => I ( 'post.number', 0, 'intval' ),
				'weight' => I ( 'post.weight', 0, 'floatval' ),
				'price' => I ( 'post.price',"" ),
				'old_price' => I ( 'post.old_price',"" ),
				'is_sale' => I ( 'post.is_sale', 1, 'intval' ),
				//'featured' => $this->feat ( $feat ),
				'is_goods' => I ( 'post.is_goods', 0, 'intval' ),
				'attribute_id' => I ( 'post.attachId')?json_encode(I ( 'post.attachId')):"",
				'type_id' => I ( 'post.type')?I('post.type','','intval'):null,
				'uid' => $uid,
				'add_time' => NOW_TIME,
				'update_time'=>NOW_TIME,
				'delete_time'=>0,
				'sort'=>I('post.sort',0,'intval'),
				'content' => I ( 'post.content' ),
				'goods_id' => I('post.id',0,'intval'),
				'integral' => I('post.integral',''),
				// 'is_postage' => I('post.is_postage','0'), // 去掉是否包邮字段

				'is_express' => I('post.is_express','1'), // 增加是否支持快递字段

				'goods_description'=>I('post.goods_description','')
		);
		if (! $this->create ( $data, self::MODEL_INSERT )) {
			return $this->getResultError ();
		}
		unset ( $data ['content'] );
		if(!empty($this->edit_where)){
			unset($data['add_time']);
			$result = $this->setData ( $this->editWhere,$data );
		}else{
			$result = $this->addData ( $data );
			$addGoods = $this->getLastInsID ();
			if($addGoods){
				M('GoodsStatistics')->add(array('goods_id'=>$addGoods));
			}
		}
		$goods_id = $addGoods | $data['goods_id'];
		if ($goods_id>0) {
			//商品规格
			$this->editNorms($goods_id);
			// 添加详情数据
			$this->editAttached ( $goods_id );
			// 商品属性
			$this->editAttrs ( $goods_id );
			// 关联商品
			$this->editLink ( $goods_id );
			// 附件
			$this->editFitt ( $goods_id );
			//商品标签
			$this->editTags($goods_id);
			//商品从属分类
			$this->editSubCat($goods_id);
			//商品展位
			$this->editFeat($goods_id);
		}
		if(!$data['goods_id']){//新增
			$massage = '新增商品';
			$type = 1;
		}else{//修改
			$massage = '修改id为&nbsp;'.$data['goods_id'].'&nbsp;的商品';
			$type = 2;
		}
		$massage .= '&nbsp;'.$data['name'];
		D('AdminLog')->addAdminLog($massage,$type);
		return $result;
	}
	
	/**
	 * 编辑展位
	 */
	public function editFeat($goods_id){
		$feat = I ( 'post.feat' );
		M("GoodsFeat")->where(['goods_id'=>$goods_id])->delete();
		if($feat){
			$data = array();
			foreach ($feat as $key=>$v){
				$data[$key] = array(
						'feat_id'=>$v,
						'goods_id'=>$goods_id
				);
			}
			M("GoodsFeat")->addAll($data);
		}
	}
	
	/**
	 * 更新详情数据
	 * 
	 * @param $goods_id 商品id        	
	 */
	private function editAttached($goods_id) {
		$data = array (
				'keywords' => I ( 'post.keywords', '' ),
				'description' => I ( 'post.description', '' ),
				'content' => I ( 'post.content', '' ),
				'goods_id' => $goods_id 
		);
		if(!empty($this->edit_where)){
			return M ( 'GoodsAttached' )->where($this->edit_where)->save($data);
		}else{
			return M ( 'GoodsAttached' )->add ( $data );
		}
	}
	
	/**
	 * 更新从属分类
	 */
	public function editSubCat($goods_id){
		$addSub = I("post.addsub",'0','intval');
		$sub_model = M("GoodsSubcat");
		$sub_model->where(['goods_id'=>$goods_id])->delete();
		$subcats  = I("post.subcat",'');
		if($subcats && $addSub){
			$subcats = array_unique(array_filter($subcats));
			$data = array();
			foreach ($subcats as $key=>$v){
				$data[$key] = array(
						'goods_id'=>$goods_id,
						'cat_id' => $v
				);
			}
			$sub_model->addAll($data,'',true);
		}
	}
	/**
	 * 更新商品属性
	 * 
	 * @param $goods_id 商品id        	
	 * @return Ambigous <\Think\mixed, boolean, unknown, string>
	 */
	private function editAttrs($goods_id) {
		$attrs = I ( 'post.attrs' );
		if (! empty ( $attrs )) {
			$data = array ();
			array_walk ( $attrs, function ($v, $key) use(&$data, $goods_id) {
				if (is_array ( $v )) {
					foreach ( $v as $k => $vs ) {
						if ((strtolower ( $k ) == "input") && (strpos ( $vs ['value'], "," ) !== false)) {
							$value = explode ( ",", rtrim($vs ['value'],",") );
							$price = explode ( ",", rtrim($vs ['price'],",") );
							foreach ( $value as $ks => $vo ) {
								$data [] = array (
										'attr_id' => $key,
										'goods_id' => $goods_id,
										'value' => $vo,
										'price' => isset ( $price [$ks] ) ? floatval ( $price [$ks] ) : 0 
								);
							}
						} else {
							if ($vs ['value']) {
								$data [] = array (
										'attr_id' => $key,
										'goods_id' => $goods_id,
										'value' => $vs ['value'],
										'price' => isset ( $vs ['price'] ) ? floatval ( $vs ['price'] ) : 0 
								);
							}
						}
					}
				} else {
					if ($v) {
						$data [] = array (
								'attr_id' => $key,
								'goods_id' => $goods_id,
								'value' => $v,
								'price' => isset ( $v ['price'] ) ? floatval ( $v ['price'] ) : 0 
						);
					}
				}
			} );
			$model = M('GoodsAttr');
			if(!empty($this->edit_where)){
				$model -> where($this->edit_where)->delete();
			}
			return M ( 'GoodsAttr' )->addAll ( $data );
		}
	}
	
	/**
	 * 更新关联商品
	 * @param $goods_id 商品id
	 */
	private function editLink($goods_id) {
		$model = M('GoodsLink');
		$linkeds = I('post.goods_linked','');
		$linkeds = array_unique($linkeds);
		if($this->edit_where){
			//双向删除
			if($linkeds){
				$link_where["goods_id"] = array('in',$linkeds);
				$link_where['link_id'] = $goods_id;
				$model->where($link_where)->delete();
			}
			$model->where($this->edit_where)->delete();
		}
		$goodLink = I ( 'post.goods' );
		if (! empty ( $goodLink )) {
			$data = array ();
			array_walk ( $goodLink, function (&$v, $key) use($goods_id, &$data) {
				$data [] = array (
						'goods_id' => $goods_id,
						'link_id' => $key 
				);
			} );
			$model->addAll ( $data,array(),true );
			//双向关联
			if($linkeds){
				foreach($linkeds as $key=>$v){
					$links[$key]['goods_id'] = $v;
					$links[$key]['link_id'] = $goods_id;
				}
				$model->addAll ( $links,array(),true );
			}
		}
	}
	
	/**
	 * 更新配件、赠品
	 * 
	 * @param
	 *        	$goods_id
	 */
	public function editFitt($goods_id) {
		$fitt = I ( 'post.fitt' );
		$gift = I ( 'post.gift' );
		$data_fitt = array ();
		$data_gift = array ();
		$model = M ( 'GoodsFitting' );
		if ($fitt) {
			$data_fitt = $this->formatFitt ( $fitt,$goods_id, 0 );
			$model->addAll ( $data_fitt );
		}
		if ($gift) {
			$data_gift = $this->formatFitt ( $gift,$goods_id, 1 );
			$model->addAll ( $data_gift );
		}
	}
	
	/**
	 * 更新商品规格
	 * @param $goods_id 商品id
	 */
	public function editNorms($goods_id){
		$value_model = M('GoodsNormsValue');
		$attr_model = M('GoodsNormsAttr');
		//规格值
		$norms = I('post.norms','');
		$norms_value = I('post.norms_value','');
		//规格附件
		$norms_attr = I('post.norms_attr','');
		//规格价格
		$norms_price = I('post.norms_price','');
		//规格数量
		$norms_number = I('post.norms_number','');
		//规格编码
		$norms_code = I('post.norms_code','');
		$norms_data = array();
		$norms_data_attr = array();
		foreach($norms as $key=>$v){
			$norms_data[$key]['norms_value_id'] = $v;
			$norms_data[$key]['goods_norms_attr']=$norms_attr[$v]?$norms_attr[$v]:"";
			$norms_data[$key]['norms_value']=$norms_value[$v];
			$norms_data[$key]['goods_id'] = $goods_id;
			if($norms_code){
				foreach($norms_code as $k=>&$vo){
					$ks = rtrim($k,"_");
					if(empty($vo)){
						$vo = $k."_".rand_string(6,1);
					}
					$norms_data_attr[$ks]['goods_norms_no'] = $vo;
					$norms_data_attr[$ks]['goods_norms_price'] = $norms_price[$k];
					$norms_data_attr[$ks]['goods_norms_number'] = $norms_number[$k];
					$key_v = explode("_", $ks);
					/* if(in_array($v,$key_v)){
					 $norms_data[$key]['goods_norms_no'] = $vo;
					} */
				}
			}
		}
		$data = array();
		$i=0;
		if(!empty($norms_data_attr)){
			foreach($norms_data_attr as $key=>$v){
				$data[$i]['goods_norms_no'] = $v['goods_norms_no'];
				$data[$i]['goods_norms_price'] = $v['goods_norms_price'];
				$data[$i]['goods_norms_number'] = $v['goods_norms_number'];
				$data[$i]['goods_id'] = $goods_id;
				$data[$i]['goods_norms_link'] = $key;
				$i++;
			}
		}
		$time = date('Y-m-d H:i:s',time());
		if($this->edit_where){
			$re  = $value_model->where($this->edit_where)->delete();
			$res = $attr_model->where($this->edit_where)->delete();
			//检测规格自动掉数据之迷，可删除  开始
			if($re){
				file_put_contents('./value.txt',$goods_id.'---'.$time."---value.删除成功".PHP_EOL,FILE_APPEND);
			}else{
				file_put_contents('./value.txt',$goods_id.'---'.$time."---value.删除失败".PHP_EOL,FILE_APPEND);
			}
			if($res){
				file_put_contents('./value.txt',$goods_id.'---'.$time."---attr.删除成功".PHP_EOL,FILE_APPEND);
			}else{
				file_put_contents('./value.txt',$goods_id.'---'.$time."---attr.删除成功".PHP_EOL,FILE_APPEND);
			}
			//检测规格自动掉数据之迷，可删除  结束
		}
		if($norms_data){
			$re = $value_model->addAll($norms_data);
		}
		if($data){
			$res = $attr_model->addAll($data);
		}
		//检测规格自动掉数据之迷，可删除  开始
		if($re){
			file_put_contents('./value.txt',$goods_id.'---'.$time."---value.增加成功".PHP_EOL,FILE_APPEND);
		}else{
			file_put_contents('./value.txt',$goods_id.'---'.$time."---value.增加失败".PHP_EOL,FILE_APPEND);
		}
		if($res){
			file_put_contents('./value.txt',$goods_id.'---'.$time."---attr.增加成功".PHP_EOL,FILE_APPEND);
		}else{
			file_put_contents('./value.txt',$goods_id.'---'.$time."---attr.增加失败".PHP_EOL,FILE_APPEND);
		}
		//检测规格自动掉数据之迷，可删除  结束
	}
	
	/**
	 * 更新商品标签
	 * @param Int $goods_id
	 * @return boolean
	 */
	public function editTags($goods_id){
		 $model = M("GoodsTagLink");
		 $model->where(['goods_id'=>$goods_id])->delete();
		 $tag_ids = I("post.tag_id",'');
		 if($tag_ids){
		 	$data = array();
		 	foreach($tag_ids as $key=>$v){
		 		$data[$key] = array(
		 				'goods_id'=>$goods_id,
		 				'tag_id' => $v
		 		);
		 		return $model->addAll($data,'',true);
		 	}
		 }
	}
	
	/**
	 * 格式化配件数据
	 * 
	 * @param
	 *        	$goods_id
	 * @param
	 *        	$type 0：配件 1：赠品
	 * @return multitype:unknown Ambigous <number, unknown>
	 */
	private function formatFitt($fitt,$goods_id, $type) {
		$data = array ();
		array_walk ( $fitt, function ($v, $key) use(&$data, $goods_id, $type) {
			$data[] = array (
					'goods_id' => $goods_id,
					'fitting_id' => $key,
					'price' => $v ? $v : 0,
					'type' => $type 
			);
		} );
		return $data;
	}
	
	/**
	 * 生成货品编号
	 */
	public function code() {
		$code = $this->max ( 'goods_id' );
		$code = "JTS0000" . ($code + 1);
		return $code;
	}
	
	/**
	 * 获取展示位属性
	 * @param 属性值数组
	 * @return number 属性值 
	 */
	private function feat(Array $feat) {
		$featured = 0;
		if ($feat) {
			foreach ( $feat as $v ) {
				$featured = $featured | $v;
			}
		}
		return $featured;
	}
	
	/**
	 * 查询商品
	 * @param $where 
	 * @return array
	 */
	public function getGoods($where=array(),$field=true){
		return $this->field($field)->where($where)->select();
	}
	
	/**
	 * 
	 * @param  $id 商品id
	 * @param  $field
	 * @param number $type 1:获取基础数据 2:获取详情数据
 	 */
	public function getById($id,$field,$type=1){
		$where = array(
				'goods_id'=>$id
		);
		$data = $this->where($where)->field($field)->find();
		if($type==1){
			return $data;
		}
		if($type==2){
			//获取附表数据
			$attacheds = M ( 'GoodsAttached' )->where ( $where )->find ();
			$data = array_merge ( $data, $attacheds );
			// 获取商品属性
			$data ['attrs'] = M ( 'GoodsAttr' )->where ( $where )->select ();
			// 获取关联商品
			$data ['link'] = M ( 'GoodsLink' )->where ( $where )->select ();
			// 获取关联商品
			if ($data ['link']) {
				$goods_link = array_column ( $data ['link'], "link_id" );
				$field = "name,price,goods_id,stock_number";
				$data ['link'] = $this->where ( array (
						'goods_id' => array (
								'in',
								$goods_link 
						) 
				) )->field ( $field )->select ();
			}
			// 获取配件
			$where ['type'] = 0;
			$data ['fitt'] = M ( 'GoodsFitting' )->where ( $where )->select ();
			if ($data ['fitt']) {
				$goods_fitt = array_column ( $data ['fitt'], "fitting_id" );
				if ($goods_fitt) {
					$data ['fitt'] = $this->where ( array (
							'goods_id' => array (
									'in',
									$goods_fitt 
							) 
					) )->field ( $field )->select ();
				} else {
					$data ['fitt'] = "";
				}
			}
			// 赠品
			$where ['type'] = 1;
			$data ['gift'] = M ( 'GoodsFitting' )->where ( $where )->select ();
			if ($data ['gift']) {
				$goods_gift = array_column ( $data ['gift'], "fitting_id" );
				if ($goods_gift) {
					$data ['gift'] = $this->where ( array (
							'goods_id' => array (
									'in',
									$goods_gift 
							) 
					) )->field ( $field )->select ();
				} else {
					$data ['gift'] = "";
				}
			}
		    return $data;
		}
	}
	
	
	/**
	 * 编辑时处理类型属性
	 * @param $attrs 商品属性
	 * @param $type_id 商品类型
	 * @return array
	 */
	public function formatAttr($attrs,$type_id){
	    $ids = array_column($attrs, 'attr_id');
	    $ids = array_unique($ids);
	    $where = array(
	    		'attr_id'=>array('in',$ids)
	    );
	    $data = M('Attribute')->where("type_id={$type_id}")->select();
		if (! empty ( $data )) {
			foreach ( $data as $key => &$v ) {
				$price = "";
				$value = "";
				foreach ( $attrs as $vo ) {
					if ($v ['attr_id'] == $vo ['attr_id']) {
						if ($v ['attr_type'] == 0) {
							$v ['att_value'] = $vo ['value'];
						} else {
							// 单选多选值
							if ($v ['input_type'] == 1) { // 手动输入选择的值
								if (! empty ( $v ['value'] )) {
									if (! is_array ( $v ['value'] )) {
										$v ['value'] = explode ( ",", $v ['value'] );
									}
									foreach ( $v ['value'] as $vs ) {
										if ($vs == $vo ['value']) {
											$f [$vs] ['att_value'] = $vo ['value'];
											$f [$vs] ['price'] = $vo ['price'];
											$v ['check_value'] = $f;
										}
									}
								}
							}
							if ($v ['input_type'] == 2 || $v ['input_type'] == 0) { // 手动的值
								$v ['value'] ['price'] .= $vo ['price'] . ",";
								$v ['value'] ['att_value'] .= $vo ['value'] . ",";
							}
						}
					} else {
						if ($v ['input_type'] == 1) {
							if (! is_array ( $v ['value'] )) {
								$v ['value'] = explode ( ",", $v ['value'] );
							}
						}
					}
				}
			}
		}
		return $data;
	}
	
	/**
	 * 通过id获取商品规格
	 * @param $goods_id 商品id
	 */
	public function getNorms($goods_id){
		$where = array(
				"goods_id"=>$goods_id,
		);
		$data['norms_value'] = M('GoodsNormsValue')->where($where)->select();
		foreach($data['norms_value'] as &$v){
			$v['norms_attr'] = D('Upload/AttachMent')->getAttach($v['goods_norms_attr']);
		}
		$data['norms_attr'] = M('GoodsNormsAttr')->where($where)->select();
		return $data;
	}
	
	/**
	 * 格式化norms
	 */
	public function formatNorms($norms){
		if (! $norms ['norms_value']) {
			$norms ['norms_value'] = $norms;
		}
		$norms_list = array ();
		$norms_values = array_column ( $norms ['norms_value'], "norms_value_id" );
		if ($norms_values) {
			$where = array (
					'norms_value_id' => array (
							'in',
							$norms_values 
					) 
			);
			$data = M ( 'NormsValue' )->where ( $where )->select ();
			if (! empty ( $data )) {
				$arr = array ();
				foreach ( $norms ['norms_value'] as &$v ) {
					if ($v ['norms_attr'] [0] ['path']) {
						$v ['pic'] = $v ['norms_attr'] [0] ['path'];
					}
					foreach ( $data as $vo ) {
						if ($v ['norms_value_id'] == $vo ['norms_value_id']) {
							$arr [$vo ['norms_id']] [] = $v;
						}
					}
				}
				$norms_id = array_unique ( array_column ( $data, 'norms_id' ) );
				$where = array (
						'norms_id' => array (
								'in',
								$norms_id 
						) 
				);
				$norms_data = M ( 'Norms' )->where ( $where )->select ();
				$norms_datas = array ();
				foreach ( $arr as $k => $v ) {
					foreach ( $norms_data as $vo ) {
						if ($k == $vo ['norms_id']) {
							$norms_datas [$vo ['norms_name']] = $v;
						}
					}
				}
				if ($norms ['norms_attr']) {
					$norms_list ['norms_value'] = $norms_datas;
					$norms_list ['norms_attr'] = $norms ['norms_attr'];
				} else {
					$norms_list = $norms_datas;
				}
			}
		}
		return $norms_list;
	}

    /**
     * 通过id获取特定商品规格
     * @param int $goods_id 商品id
     * @param null|string $norms 规格id
     * @return \Common\Org\util\Results
     */
    public function getSpecificNorms($goods_id,$norms = null){
        $where = [
            "goods_id"=>$goods_id,
        ];
        $norms_arr = empty($norms)?null:explode('_',$norms);
        $queryView = [
            'goods_norms_value' => [
                'goods_id',
                'norms_value_id',
                'norms_value',
                'goods_norms_attr'
            ],
            'norms_value' => [
                'norms_id',
                '_on' => 'norms_value.norms_value_id = goods_norms_value.norms_value_id',
            ],
            'norms' => [
                'norms_name',
                'norms_type',
                '_on' => 'norms.norms_id = norms_value.norms_id',
            ]
        ];
        if(empty($norms_arr)){
            $norms_lists = $this->dynamicView($queryView)->where($where)->group('norms_id')->select();
        }else{
            $where['norms_value_id'] = ['in',$norms_arr];
            $norms_lists = $this->dynamicView($queryView)->where($where)->select();
            if(count($norms_lists) != count($norms_arr)){
                return $this->result()->error('规格参数有误');
            }
        }
        $return_lists = [];
        if(!empty($norms_lists)) {
            $return_lists['norms'] = $this->formatBuyNorms($norms_lists);
            $norms_ids = array_column($return_lists['norms'],'id');
            $norms = implode('_',$norms_ids);
            $return_lists['norms_arr'] = M('GoodsNormsAttr')->where([
                'goods_id' => $goods_id,
                'goods_norms_link' => $norms
            ])->field(['goods_norms_price' => 'price', 'goods_norms_number' => 'number'])->find();
            //$return_lists['norms_arr']['norms_id'] = $norms;
            if(empty($return_lists['norms_arr'])){
                return $this->result()->error('规格参数有误');
            }
        }
        return $this->result()->content($return_lists)->success();
    }
    
    /**
     * 添加编辑时获取商品标签
     * @param $goods_id 商品id
     * @return array 
     */
    public function getTags($goods_id = 0){
    	$tags = D("Admin/Tag")->getTags();
    	if(!empty($goods_id)){
    		$linkTags = M("GoodsTagLink")->where(['goods_id'=>$goods_id])->getField("tag_id",true);
    		if($linkTags && $tags){
    			foreach($tags as &$v){
    				if(in_array($v['tag_id'], $linkTags)){
    					$v['checked'] = "checked";
    				}
    			}
    		}
    	}
    	return $tags;
    }
    
    /**
     * 库存变更
     * @param int $goods_id 商品id
     * @param int $norms_id 规格id
     * @param int $number 改变数量，负数为减
     * @return \Common\Org\util\Results
     */
    public function changeStock($goods_id,$norms_id,$number){
        $result = $this->result();
        if(empty($number)){
            return $result->success();
        }
        if(!empty($norms_id)) {
            $norms_model = M('GoodsNormsAttr');
            $where = [
                'goods_id'=>$goods_id,
                'goods_norms_link'=>$norms_id
            ];
            if($number<0){
                $norms_info = $norms_model->where($where)->field(true)->find();
                if($norms_info['goods_norms_number'] + $number < 0){
                    return $result->error('商品规格库存不足');
                }
            }
            $chenge_norms_stock =$norms_model->where($where)->setInc('goods_norms_number',$number);
            if($chenge_norms_stock===false){
                return $result->error('规格库存变更失败');
            }
        }

        $goods_model = M('Goods');
        $where = [
            'goods_id'=>$goods_id
        ];
        if($number<0){
            $goods_info = $goods_model->where($where)->field(true)->find();
            if($goods_info['stock_number'] + $number < 0){
                return $result->error('商品库存不足');
            }
        }
        $chenge_stock =$goods_model->where($where)->setInc('stock_number',$number);
        if($chenge_stock===false){
            return $result->error('规格库存变更失败');
        }
        return $result->success();
    }

    /**
     * 格式规格（购物流程）
     * @param array $norms_lists 规格
     * @return array
     */
    public function formatBuyNorms(array $norms_lists){
        return array_map(function($info){
            return [
                'id'=>$info['norms_value_id'],
                'name'=>$info['norms_name'],
                'value'=>$info['norms_value'],
                'photo'=>$info['goods_norms_attr'],
            ];
        },$norms_lists);
    }
    /**
     * 获取商品重量 返回kg为单位的重量
     * @param array $goodsIds 商品id数组 或者 以,隔开的字窜
     */
    function getGoodsWeight($goodsIds){
    	$orderWeight = $this->where(['goods_id' => ['in',$goodsIds]])->sum('weight');
    	$orderWeight = $orderWeight?$orderWeight/1000:0;
    	return $orderWeight;
    }
}