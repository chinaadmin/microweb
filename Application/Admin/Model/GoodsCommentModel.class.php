<?php
/**
 * 商品评论模型
 * @author wenxiaobin
 * @date 2015-11-16
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class GoodsCommentModel extends AdminbaseModel{
	
	public function viewModel(){
		$viewFields = [
				'goods_comment' =>[
						'_as' => 'gc',
						'_type' => 'left',
						'gc_add_time' => 'add_time',
						'gc_content' => 'content',
						'*'
				],
				'order_goods' =>[
						'_as' => 'g',
						'_type' => 'left',
						'norms_value',
						'_on' => 'g.goods_id = gc.gc_goods_id and gc.gc_order_id = g.order_id',
				],
				'user' =>[
								'_as' => 'u',
								'_on' => 'u.uid = gc.gc_uid',
								'headattr',
								'aliasname',
								'username'
						]
		];
		return $this->dynamicView($viewFields)->distinct(true);
	}
	
	function formatList($list){
		$attachModle = D('Upload/AttachMent');
		$userModel = D('User/User');
		return array_map(function(&$v) use ($userModel,$attachModle){
			$v['head_pic'] = fullPath($attachModle->getAttach($v['headattr'])[0]['path']);
			$v['comment_name'] = $userModel->showUserName($v['gc_uid']);
			$v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			$tmp = json_decode(stripslashes($v['norms_value']),true);
			foreach ($tmp as $tk => &$kv){
				unset($tmp[$tk]['id']);
				unset($tmp[$tk]['photo']);
			}
			$v['norms_value'] = $tmp;
			return $v;
		}, $list);
	}
	//切换评论状态
	function switchStatus($id,$status,$remark){
		$data['gc_status'] = $status == 1 ? 0 : 1;
		if($remark){
			$data['gc_failed_remark'] = $remark;
		}
		return $this->where(['gc_id' => $id])->save($data);
	}
}