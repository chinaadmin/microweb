<?php
/**
 * 物流查询接口类
 * 
 * @author liu
 * @date 2016/4/13
 */
namespace Api\Controller;
use Common\Org\KuaiDi\KuaiDi; 
class KuaiDiController extends ApiBaseController{
	public function get_info(){
		$rec_id = trim(I("post.rec_id"));
		if($rec_id){
			$kuaidi = new KuaiDi();
			$res =  $kuaidi -> get_info($rec_id);
			$this->ajaxReturn($this->result->content($res)->success());
		}
		$this->ajaxReturn($this->result->content()->success());
	}
	
	//回调
	public function return_url(){
		$str = $_POST['param'];
		$data = json_decode($str,true);
		if($data['status'] == 'abort'){
			M('message_logistics') -> where(['number'=>$data['lastResult']['nu']]) -> setInc('poll',1);
		}
		$param['logistics_state']=$data['lastResult']['state'];
		$param['logistics_info']=serialize($data['lastResult']['data']);
		$where['number'] = $data['lastResult']['nu'];
		M('message_logistics') ->where($where) -> save($param);
		
		ECHO '{"result":"true","returnCode":"200","message":"成功"}';
	}
}