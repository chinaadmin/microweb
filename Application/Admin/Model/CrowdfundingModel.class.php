<?php
/**
 * 众筹模型
 * @author wxb
 * @date 2015-12-03
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class CrowdfundingModel extends AdminbaseModel{
	protected $_auto = [
			['cr_add_time','time',self::MODEL_INSERT,'function'], // 对update_time字段在更新的时候写入当前时间戳
			['cr_start_time','startTimeFormat',self::MODEL_BOTH,'callback'],
			['cr_end_time','endTimeFormat',self::MODEL_BOTH,'callback'],
			['cr_content','encodeContent',self::MODEL_BOTH,'callback'],
			['cr_travel_content','encodeContent',self::MODEL_BOTH,'callback'],
	];
	protected $_scope = [       // 命名范围normal 
	        "default" => [          
	        		 'where'=>['cr_delete_time'=>0]        
	        ],
			'using' =>[
					'where'=>[
							// 'cr_start_time'=> ['elt',NOW_TIME],
							// 'cr_end_time'=> ['egt',NOW_TIME],
							'cr_status'=> 1,
							'cr_delete_time'=>0
					]
			]
	];
	function startTimeFormat($datetime){
		return strtotime($datetime);
	}
	function endTimeFormat($datetime){
		return strtotime($datetime.' 23:59:59');
	}
	function encodeContent($con){
		return htmlspecialchars($con);
	}
	//后台增加数据
	function updateOne($dataAll){
		$isUpdate = false;
		if($dataAll['cr_vedio_cover_attid'] && $dataAll['cr_content']){
			$posterUrl = '';
			$tmp = D('Upload/AttachMent')->getAttach($dataAll['cr_vedio_cover_attid']);
			$posterUrl = fullPath($tmp[0]['path']);
			$replace = '$1 poster = "'.$posterUrl.'" $2';
			$dataAll['cr_content'] = preg_replace('/(<video[^>]*?)(>(.*)?<\/video>)/s',$replace, $dataAll['cr_content'],1);
		}
		if($dataAll['cr_id']){
			$isUpdate = true;
			$res = $this->setData([ "cr_id" => $dataAll['cr_id']],$dataAll);
		}else{
		//添加主数据
			$res = $this->addData($dataAll);
		}
		if(!$res->isSuccess()){
			return false;
		}
		if($dataAll['cr_id']){
			$mainId = $dataAll['cr_id'];
		}else{
			$mainId = $res->getResult();
		}
		//添加或更新详情数据 及其子表
		$res = $this->updateDetail($dataAll,$mainId,$isUpdate);
		return true;
	}	
	private function updateDetail($dataAll,$mainId){
		static $tmpModel = null;
		if(!$tmpModel){
			$tmpModel = M('crowdfunding_detail');
		}
		//删除
		if($dataAll['cd_id']){
			$tmpModel->where(['cd_id' => ['not in',$dataAll['cd_id']],'fk_cr_id' => $mainId])->delete();
		}
		$count = count($dataAll['cd_name']);
		$goodsPerPay = json_decode($dataAll['crowdfunding_pay_goods'],true);
		for($i=0;$i < $count;$i++){
			$tmp = [];
			$tmp['cd_name'] = $dataAll['cd_name'][$i];
			$tmp['cd_subhead'] = trim($dataAll['cd_subhead'][$i]);
			$tmp['cd_percentage'] = (float)$dataAll['cd_percentage'][$i];
			$tmp['cd_period_unit'] = $dataAll['cd_period_unit'][$i];
			$tmp['cd_period_count'] = $dataAll['cd_period_count'][$i];
			$tmp['cd_pay_type'] = $dataAll['cd_pay_type'][$i];
			$tmp['fk_cr_id'] = $mainId;
			if($tmp['cd_id'] = $dataAll['cd_id'][$i]){
				$tmpModel->save($tmp);
				$id = $tmp['cd_id'];
			}else{
				unset($tmp['cd_id']);
				$id = $tmpModel->add($tmp);
			}
			$this->updateGoodsPerPay($goodsPerPay[$i],$id);
		}
		return true;
	}
	//添加更新每期众筹产品  添加更新每期付款数据
	private function updateGoodsPerPay($dataAll,$detailId){
		static $model = [];
		if(!$model){
			$model[] = M('crowdfunding_goods');
			$model[] = M('crowdfunding_perpay');
		}
		$goodsModel = $model[0];
		$perPayModel = $model[1];
		
		$perPayId = $dataAll['perPayId'];
		$perPay = $dataAll['perPay'];
		$goodsId = $dataAll['goodsId'];
		$goodsList = $dataAll['goodsList'];
		$goodsPic = $dataAll['goodsPic'];
		$goodsSubhead = $dataAll['goodsSubhead'];
		$perPayModel->where([ 'fk_cd_id'=> $detailId])->delete();
		//删除不存在的产品
		if($goodsId){
			$goodsModel->where(['cg_id' => ['not in' , $goodsId], 'fk_cd_id'=> $detailId])->delete();
		}
		foreach ($perPay as $k => $v){
			$tmp = [];
			$tmp['fk_cd_id'] = $detailId;
			$tmp['cp_term_index'] = ++$k;
			$tmp['cp_pay_money'] = $v;
			$perPayModel->add($tmp);
		}
		$count = count($goodsList);
		for($i = 0;$i < $count;$i++ ){
			$tmp = [];
			$tmp['fk_cd_id'] = $detailId;
			$tmp['cg_goods_name'] = $goodsList[$i];
			$tmp['cg_att_id'] = $goodsPic[$i];
			$tmp['cg_goods_subhead'] = $goodsSubhead[$i];
			if($goodsId[$i]){
				$tmp['cg_id'] = $goodsId[$i]; 
				$goodsModel->save($tmp);
			}else{
				$goodsModel->add($tmp);
			}
		}
	}
	//展示一条众筹信息
	function showOne($id){
		static $tmpModel = [];
		if(!$tmpModel){
			$tmpModel['detail'] = M('crowdfunding_detail');
			$tmpModel['goods'] = M('crowdfunding_goods');
			$tmpModel['perpay'] = M('crowdfunding_perpay');
		}
		$find = $this->find($id);
		$find['cr_vedio_cover_attid_arr'] = [];
		if($find['cr_vedio_cover_attid']){
			$tmp = D('Upload/AttachMent')->getAttach($find['cr_vedio_cover_attid']);
			$tmp[0]['path'] = fullPath($tmp[0]['path']);
			$find['cr_vedio_cover_attid_arr'] = $tmp;
		}
		
		$find['cr_content'] = htmlspecialchars_decode($find['cr_content']);
		$find['detail'] = [];
		$detailArr = $tmpModel['detail']->where(['fk_cr_id' => $id])->select();
		foreach ($detailArr as &$v){
			$v['goodsList'] = $this->format($tmpModel['goods']->where(['fk_cd_id' => $v['cd_id']])->select());
			$v['perPay'] = $tmpModel['perpay']->where(['fk_cd_id' => $v['cd_id']])->select();
		}
		$find['detail'] = $detailArr;
		return $find;
	} 
	protected function format($list){
		static $tmpModel;
		if(!$tmpModel){
			$tmpModel = D('Upload/AttachMent');
		}
		foreach ($list as &$v){
			if($v['cg_att_id']){
				$tmp = $tmpModel->getAttach($v['cg_att_id']);
				$tmp[0]['path'] = fullPath($tmp[0]['path']);
				$v['picPath'] = $tmp;
				$v['pic'] = $tmp[0]['path']; 	
			}else{
				$v['picPath'] = false;
				$v['pic'] = '';
			}
		}
		return $list;
	}
	//软删除一条众筹
	function delOne($id){
		$data = ['cr_delete_time' => time()];
		$res = $this->setData(['cr_id' => $id], $data);
		return $res;
	}
	function recommendListView(){
		$viewFields = [
				'CrowdfundingRecommend' => [
					'*',
					'_as' => 'cr',
					'_type' => 'left',	
				],
				'CrowdfundingOrder' => [
					'*',
					'_as' => 'co',
					'_type' => 'left',	
					'_on' => 'cr.fk_cor_order_id = co.cor_order_id',	
				],
				'User' => [
					'*',	
					'_as' => 'u',	
					'_on' => 'u.uid = cr.cr_uid'	
				]
		];
		return $this->dynamicView($viewFields);
	}
}