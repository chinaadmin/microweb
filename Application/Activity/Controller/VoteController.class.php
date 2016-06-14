<?php
/**
 * 投票控制器
 * @author xiongzw
 * @date 2015-07-09
 */
namespace Activity\Controller;
class VoteController extends ActivityBaseController{
	//每人每张照片最大点赞数
	private $maxVote = 1;
	//点赞间隔时间
	private $spac_time = 20;//分钟
	/**
	 * 投票
	 *  <code>
	 *  photo_id 照片id
	 *  code  机器码
	 *  </code>
	 */
	public function vote(){
		$photo_id = I('post.photo_id',0,'intval');
		$uid = I("post.uid",'');
		$uid = path_decrypt ( $uid );
		if(empty($photo_id)){
			$this->ajaxReturn($this->result->set("PHOTOID_REQUIRE"));
		}
		if(empty($uid) || empty(D("Connect")->getUserByUid($uid))){
			$this->ajaxReturn($this->result->set("UID_REQUIRE"));
		}
	    $data = array(
	    		'photo_id'=>$photo_id,
	    		//'machine_code'=>$code,
	    		'uid'=>$uid,
	    		'vote_ip' =>get_client_ip(),
	    		'add_time'=>NOW_TIME
	    );
	    $this->_maxVote();
	    M("ActivityVote")->startTrans();
	    $result_v =  M("ActivityVote")->add($data);
	    $where = ['photo_id'=>$photo_id];
	    $count = M("ActivityVote")->where($where)->count();
	    $result_p =M("ActivityPhoto")->where($where)->save(['vote_num'=>$count]);
	    if($result_v!==false && $result_p!==false){
	    	M("ActivityVote")->commit();
	    	$this->ajaxReturn($this->result->success());
	    }else{
	    	M("ActivityVote")->rollback();
	    	$this->ajaxReturn($this->result->error());
	    }
	}
	
	/**
	 * 是否超过最大点击数
	 *    <code>
	 *        code 机器码
	 *        photo_id 照片id
	 *    </code>
	 */
	public function _maxVote(){
		$uid = path_decrypt ( I('post.uid','') );
		$where = array(
				//'machine_code'=>I("post.code",''),
				'uid' => $uid,
				'photo_id'=>I('post.photo_id',0,'intval')
		);
		$model =  M("ActivityVote"); 
		if($this->maxVote>1){
			$where['add_time'] = array('GT',time()-20*60);
			if($model->where($where)->find()){
				$this->ajaxReturn($this->result->set('VOTE_TIME_SORT'));
			}
			unset($where['add_time']);
		}
		$count = $model->where($where)->count();
		if(($count+1)>$this->maxVote){
			$this->ajaxReturn($this->result->set('ALREADY_VOTE'));
		}
	}
}