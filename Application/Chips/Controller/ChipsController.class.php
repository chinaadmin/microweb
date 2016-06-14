<?php
/**
 * 蜂蜜众筹活动
 * @author xiongzw
 * @date 2015-12-10
 */
namespace Chips\Controller;
use Api\Controller\ApiBaseController;
class ChipsController extends ApiBaseController{
	protected $chips_model;
	public function _initialize(){
		parent::_initialize();
		$this->chips_model = D("Chips/Chips");
	}
	/**
	 * 众筹方案
	 *    传入参数
	 *    <code>
	 *      fkId 项目id
	 *    </code>
	 */
	public function chips(){
		$fk_rc_id = I('request.fkId','0','intval'); 
		$scheme = $this->chips_model->getScheme($fk_rc_id);
		$return_data = array();
		foreach($scheme as $key=>$v){
			$return_data[$key] = array(
					'cdId'=>$v['cd_id'],
					'name'=>$v['cd_name'],
					'subhead'=>$v['cd_subhead'] //子标题
			);
		}
		$this->ajaxReturn($this->result->content(['scheme'=>$return_data])->success());
	}
	
	/**
	 * 获取众筹方案下的商品
	 *       传入参数
	 *       <code>
	 *       schemeId 方案id
	 *       </code>
	 */
	public function chipsGoods(){
		$id = I('request.cdId',0,'intval');
		$goods = $this->chips_model->getSchemeGoods($id);
		D("Home/List")->getThumb($goods,false,'cg_att_id');
		$return_data = array();
		foreach ($goods as $key=>$v){
			$return_data[$key] = array(
					'cgId'=>$v['cg_id'],
					'name'=>$v['cg_goods_name'],
					'price'=>$v['cg_market_price'],
					'photo' =>fullPath($v['thumb']),
					'subhead' =>$v['cg_goods_subhead'] //商品子标题
			);
		}
		$this->ajaxReturn($this->result->content(['goods'=>$return_data])->success()); 
	}
}