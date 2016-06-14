<?php
/**
 * 历史记录
 * @author xiongzw
 * @date 2015-07-06
 */
namespace Api\Controller;
class HistoryController extends ApiBaseController{
	public function _initialize(){
		parent::_initialize();
		$this->authToken();
	}
	/**
	 * 浏览历史记录
	 *       <code>
	 *       goods 缓存的浏览记录值
	 *       </code>
	 */
	public function history() {
		$goods = I ( 'post.goods', '' );
		if ($goods) {
			$goods = json_decode ( html_entity_decode($goods), true );
			D ( "Home/History" )->appHistory ( $goods, $this->user_id );
		}
		$result = D ( 'Home/History' )->getHisByUid ( $this->user_id, 2 );
		$goods_ids = array_column ( $result, "goods_id" );
		$lists = array ();
		if ($goods_ids) {
			$model = D ( 'Home/List' )->viewModel ();
			$where = array (
					'goods_id' => array (
							'in',
							$goods_ids 
					) 
			);
			$lists = $this->_lists ( $model, $where,'add_time desc' );
			D ( 'Home/List' )->getThumb ( $lists ['data'], 0 );
			$lists ['data'] = D ( "Api/List" )->formatData ( $lists ['data'] );
		}
		$this->ajaxReturn ( $this->result->content ( $lists )->success () );
	}
	
	/**
	 * 清空浏览记录
	 */
	public function del(){
		D ( 'Home/History' )->where(['uid'=>$this->user_id])->delete();
		$this->ajaxReturn($this->result->success());
	}
}