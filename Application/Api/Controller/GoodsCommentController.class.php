<?php
/**
 * 商品评论控制器
 * @author wxb 
 * @date 2015/11/14
 */
namespace Api\Controller;
class GoodsCommentController extends ApiBaseController {
    public function _init(){
        
    }
    /**
     * 提交评论
     */
	function commitComment(){
		$this->authToken();
		if(!$gc_match_start = I('post.matchStart','','int')){
			$this->returnCode('MATCH_START_REQUIRE');
		}
		if(!$gc_content = I('post.content','','trim')){
			$this->returnCode('COMMENT_CONTENT_REQUIRE');
		}
		if(!$gc_seller_start = I('post.sellerStart','','int')){
			$this->returnCode('SELLER_START_REQUIRE');
		}
		if(!$gc_logistics_start = I('post.logisticsStart','','int')){
			$this->returnCode('LOGISTICS_START_REQUIRE');
		}
		if(!$gc_goods_id = I('post.goodsId','','int')){
			$this->returnCode('COMMENT_GOODSID_REQUIRE');
		}
		if(!$order_id = I('post.orderId','','trim')){
			$this->returnCode('COMMENT_ORDERID_REQUIRE');
		}
		$data['gc_match_start'] = $gc_match_start;
		$data['gc_content'] = $gc_content;
		$data['gc_seller_start'] = $gc_seller_start;
		$data['gc_logistics_start'] = $gc_logistics_start;
		$data['gc_goods_id'] = $gc_goods_id;
		$data['gc_order_id'] = $order_id;
		$data['gc_add_time'] = time();
		$data['gc_uid'] = $this->user_id;
		if(M('goods_comment')->add($data) &&  D('Home/Order')->finishedComment($order_id,$gc_goods_id) !== false){
			$this->result->success();
		}else{
			$this->result->error();
		}
		$this->ajaxReturn($this->result);
	}
    /**
     * 展示评论
     */
	function commentList(){
		if(!$gc_goods_id = I('post.goodsId','','int')){
			$this->returnCode('COMMENT_GOODSID_REQUIRE');
		}
		$viewFields = [
				'goods_comment' =>[
						'_as' => 'gc',
						'_type' => 'left',
						'gc_add_time' => 'add_time',
						'gc_content' => 'content',
						'gc_uid'
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
						'headAttr' => 'head_attr'
				]
		];
		$m = D('Api/Goods')->dynamicView($viewFields)->distinct(true);
		$where = ['gc_goods_id' => $gc_goods_id,'gc_status' => 1];
		$list = $this->_lists($m,$where,'gc_add_time desc');
		$attachModle = D('Upload/AttachMent');
		$userModel = D('User/User');
		$list['data'] = array_map(function(&$v) use ($userModel,$attachModle){
			$v['head_pic'] = fullPath($attachModle->getAttach($v['head_attr'])[0]['path']);
			unset($v['head_attr']);
			$v['comment_name'] = $userModel->showUserName($v['gc_uid']);
			unset($v['gc_uid']);
			$v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
			$tmp = json_decode($v['norms_value'],true);
			foreach ($tmp as $tk => &$kv){
				unset($tmp[$tk]['id']);
				unset($tmp[$tk]['photo']);
			}
			$v['norms_value'] = $tmp ? $tmp : [];
			return $v;
		}, $list['data']);
		$list['total'] = $m->where($where)->distinct(true)->count(); 
		$this->ajaxReturn($this->result->content($list)->success());
	}
	function returnCode($code){
		$this->result->set($code);
		$this->ajaxReturn($this->result);
	}
}