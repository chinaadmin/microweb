<?php
/**
 * 消息推送类
 * @author cwh
 * @date 2016-04-07
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Org\Jpush\examples\PushExample;
class MessagePushController extends AdminbaseController {
	protected $curent_menu = 'MessagePush/index';
	public function index(){
		//$this->show_role_list();
		
		$data = $this->lists (M('message_push'), "", "push_addtime desc" );
		$img = M('attachment');
		foreach($data as $k => $v){
			$img_nfo = $img ->where(['att_id' => $v['push_att_id']]) ->  find();
			$data[$k]['push_img'] = $img_nfo['path']."/".$img_nfo['name'].".".$img_nfo['ext'];
		}
		
        $this->assign('lists',$data);
		$this -> display();
	}
	
	public function edit(){
		if(I()){
			$data = M('message_push')->where(['push_id'=>I('push_id')]) -> find();
			$img = M('attachment');
			$img_nfo = $img ->where(['att_id' => $v['push_att_id']]) ->  find();
			$data['path'] = $img_nfo['path']."/".$img_nfo['name'].".".$img_nfo['ext'];
			$this -> assign("info",$data);
		}
		
		$this -> display();
	}
	
	//更新，添加推送信息
	public function update(){
		$id = I('request.push_id');
        $MessagePush = D('Admin/MessagePush');
		$img_id = I('request.push_att_id');
        $data = [
            'push_name' 	=> I('request.push_name'),
            'push_title' 	=> I('request.push_title'),
            'push_att_id' 	=> $img_id[0],
            'push_brief' 	=> I('request.push_brief'),
            'push_url' 		=> I('request.push_url'),
            'push_note' 	=> I('request.push_note'),
            'push_is_app' 	=> I('request.push_is_app'),
            'push_message' 	=> I('request.push_message')
        ];
        if(!empty($id)) {
            $where = [
                'push_id' => $id
            ];
  
            $result = $MessagePush->setData($where,$data);
        }else{
			$data['push_addtime']  = time();
			$data['push_number'] = 'SZ'.date('Ymd',time()).mt_rand(100000,999999);
            $result = $MessagePush->addData($data);
        }
		
		if($data['push_is_app']==1){
			
			$jpush = new PushExample();
			$jpush -> push($data['push_message']);
		}
		
		if($result){
			$this->success('成功', 'index');
		}else{
			$this->success('失败', 'edit');
		}
	}
	
	//删除消息
	public function del_push(){
		
		if(I('post.push_id')){
			if(D('Admin/MessagePush') -> del_push(I('post.push_id'))){
				$this -> ajaxReturn(['success' =>"1"]);
			}else{
				$this -> ajaxReturn(['success' =>"0"]);
			}
		}
		
	}
	
	private function show_role_list(){
		$this->role_list = D('Admin/Role')->scope_super()->select();
	}
}