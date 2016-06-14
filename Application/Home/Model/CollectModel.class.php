<?php
/**
 * 收藏模型
 * @author xiongzw
 * @date 2015-07-21
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class CollectModel extends HomebaseModel{
	/**
	 * 收藏试图模型
	 * @return mixed
	 */
	public function viewModel(){
		$viewFields = array(    
				 'Collect' => array (
						'add_time',
						'goods_id',
				 		'norms_value',
				 		'_type'=>'LEFT'
				),
				'Goods' => array (
						'name',
						'attribute_id',
						'price',
						'_on' => 'Goods.goods_id=Collect.goods_id' 
				)
		);
		return $this->dynamicView($viewFields)->where($this->_scope['goods']['where']);
	}
	
	/**
	 * 删除我的收藏
	 * @param $goods_id 商品id
	 * @param $uid 用户id
	 * @return mixed
	 */
	public function delCollect($goods_id, $uid) {
		if (empty ( $goods_id )) {
			return $this->result ()->error ();
		}
		$where = array (
				'goods_id' => array (
						'in',
						$goods_id 
				),
				'uid' => $uid 
		);
		$this->startTrans();
		$result = $this->delData ( $where );
		if($this->collectNum($goods_id)>0){
		 $return = M("GoodsStatistics")->where(['goods_id'=>$goods_id])->setDec("collect",1);
		}
		if(!$result->isSuccess() || $return===false){
			$this->rollback();
		}else{
			$this->commit();
			$result = $result->success("取消收藏成功！");
		}
		return $result;
	}
	
	private function collectNum($goods_id){
		return M("GoodsStatistics")->where(['goods_id'=>$goods_id])->getField("collect");
	}
	
	/**
	 * 商品收藏
	 * @param $data 要添加的值
	 */
	public function addCollect(array $data){
		if(empty($data['goods_id'])){
			return $this->result()->error();
		}
		if($this->hasCollect($data['goods_id'], $data['uid'])){
			return $this->result()->error("该商品已被收藏，请不要重复收藏!","");
		}
		$data['norms_value'] = trim($data['norms_value'],"_");
		if(!empty($data['norms_value'])){
			$norms_return = D("Admin/Goods")->getSpecificNorms($data['goods_id'],$data['norms_value']);
			if(!$norms_return->isSuccess()){
				return $norms_return;
			}
			$norms = $norms_return->getResult();
			$data['norms_value'] = json_encode($norms['norms']);
		}
		$this->startTrans();
		//此处把一个方法赋值给一个变量$var
		$var=$this->hasCollect($data['goods_id'], $data['uid']);
		if(empty($var)){
			$return = M("GoodsStatistics")->where(['goods_id'=>$data['goods_id']])->setInc("collect",1);
			if($return === false){
				$this->rollback();
			}
		}
		$result = $this->addData($data,false,true);
		if(!$result->isSuccess()){
			$this->rollback();
		}
		$this->commit();
		return $result;
	}
	
	/**
	 * 判断商品是否已收藏过
	 * @param  $goods_id
	 * @param  $uid
	 */
    public function hasCollect($goods_id,$uid){
    	$where = array(
    			"goods_id" => $goods_id,
    			"uid" => $uid
    	);
    	return $this->where($where)->find();
    }
}
