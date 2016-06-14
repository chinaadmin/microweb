<?php
/**
 * 管理员逻辑类
 * @author cwh
 * @date 2015-04-01
 */
namespace Api\Controller;
class MessagePushController extends ApiBaseController {

	public function _initialize(){
        parent::_initialize();
        //不需要验证token的接口方法，写入数组
        if (!in_array(strtoupper(ACTION_NAME), ['DETAIL'])){
            $this->authToken();
        }
    }
	
	public function get_num(){
		
		$token = I('post.token');
		$uid = M('token') -> where(['token'=>$token])->getfield('uid');
		$name_id = I('post.name_id');
		if($token && $uid){
			//查询用户是否存在
			$user = M('user') -> where(['uid'=>$uid]) ->getfield('uid');
			if($user){
				$message_info = array();
				$user_message = M('message_user') -> where(['user_id' => $uid]) -> max("addtime");
				$where["push_addtime"] = array('gt',empty($user_message)?time():$user_message);
				$data = M('message_push') ->field("push_id,push_addtime,push_name,is_bind") -> where($where) ->  select();
				if($data){
					foreach($data as $k => $v){
						if($v['is_bind']==0){
							$message_info[$k]['user_id'] = $uid;
							$message_info[$k]['fk_push_id'] = $v['push_id'];
							$message_info[$k]['addtime'] = $v['push_addtime'];
							$message_info[$k]['fk_push_name'] = $v['push_name'];
						}
					}
					M('message_user')-> addAll($message_info);
				}
				//获取消息
				$res = D("Api/MessageUser") -> get_message($uid,$name_id);
				if($res){
					$str['data'] = array_values($res);
					$str['count'] = array_sum(array_column($res,'count'));
					$this->ajaxReturn($this->result->content($str)->success());
					exit();
				}
				
			}
		}
		$this->ajaxReturn($this->result->content($str)->success());
	}
	
	//修改消息状态
	public function up_push(){
		//更新消息状态
		M('message_user') -> where(['id' =>I('post.id')]) -> save(['state' => 2]);
	}
}