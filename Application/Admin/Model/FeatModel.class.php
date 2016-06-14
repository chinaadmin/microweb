<?php
/**
 * 商品展位模型
 * @author xiongzw
 * @date 2015-08-28
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class FeatModel extends AdminbaseModel{
	protected $tableName = "feat";
	//命名范围
	protected $_scope = array (
			'default' => array (
					'where' => array (
							'feat_status' => 1 
					) 
			) 
	);
	//自动验证
	protected $_validate = array (
			array (
					'feat_name',
					'require',
					'展位名称不能为空！' 
			),
			array (
					'feat_name',
					'',
					'展位名称已经存在！',
					self::EXISTS_VALIDATE,
					'unique'
			)
	);
	
	/**
	 * 通过id获取
	 * @param $feat_id
	 * @param string $field
	 */
	public function getById($feat_id,$field=true){
		return $this->field($field)->where(['feat_id'=>$feat_id])->find();
	}
	
	/**
	 * 通过展位id获取展位商品id 
	 * @param unknown $feat
	 * @param string $field
	 */
	public function getGoodsFeat($feat,$field=true){
		$where = array(
				"feat_id"=>array('in',(array)$feat)
		);
		return M("GoodsFeat")->field($field)->where($where)->select();
	}
	
	/**
	 * 获取展位
	 * @param 展位位置 1：展位列表 2：首页推荐
	 */
	public function getFeats($position=2,$field=true){
		$where = array();
		 if($position){
			$where['feat_position'] = $position;
		} 
		return $this->field($field)->scope()->where($where)->order("feat_sort DESC")->select();
	}
	
	/**
	 * 手机端获取展位
	 * @param 展位位置 1：展位列表 2：首页推荐
	 * @param　Integer $type 是否过滤无图片展位 0：不过滤 1：过滤
	 */
	public function getMobilelFeats($position=2,$type=0){
		$feat = $this->getFeats ($position);
		foreach ( $feat as $key => &$v ) {
			if ($v ["attr_id"]) {
				$photo = current ( D ( 'Upload/AttachMent' )->getAttach ( $v ['attr_id'] ) );
				if (is_file ( "." . trim ( $photo ['path'], "." ) )) {
					$v ['photo'] = $photo ['path'];
				}
				if ($type) {
					if (empty ( $v ['photo'] )) {
						unset ( $feat [$key] );
					}
				}
			}
		}
		return $feat;
	}
	
	/**
	 * 通过商品id获取展位id
	 * @param Integer $goods_id 商品id
 	 */
	public function getByGood($goods_id){
		return M("GoodsFeat")->where(['goods_id'=>$goods_id])->getField("feat_id",true);
	}
	
	/**
	 * 通过商品id批量获取展位详情
	 * @param array $goods 商品id
	 */
	public function getByGoods($goods){
		$where = array(
				"goods_id"=>array('in',(array)$goods)
		);
		$viewFields = array(
				"GoodsFeat" => array(
				      "*",
					  "_type"=>"LEFT"
				),
				"Feat" => array(
				    "feat_name",
					"_on"=>"GoodsFeat.feat_id = Feat.feat_id"
				)
		);
		return $this->dynamicView($viewFields)->where($where)->select();
	}
	
	/**
	 * 删除展位
	 * @param Integer $feat_id 展位id
	 */
	public function del($feat_id){
		$where = [
		'feat_id' => $feat_id
		];
		$this->startTrans();
		// $result = $this->feat_model->delData ( $where ); //代码报错，找不到delData
		$result = $this->delData ( $where );
		//删除展位商品
		$return = M("GoodsFeat")->where($where)->delete();
		if($result->isSuccess() && $return !== false){
			$this->commit();
			return $result;
		}else{
			$this->rollback();
			return $this->result()->error();
		}
	}
}