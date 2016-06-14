<?php
/**
 * 订单消息模型
 * @author xiaohuakang
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class OrderMessageModel extends AdminbaseModel{
	//定时查询订单
	// public function queryTimer($uid=""){
	// 	if(empty($_POST['time'])){
	// 		exit();
	// 	}
		
	// 	$i=0; 
	// 	set_time_limit(0); //设置脚本超时时间为无限
	// 	while(true){
 // 			$i++;
	// 		$list = $this->where(['message_status' => 0])->select();
	// 		if($list){
	// 			$arr = "";
	// 			foreach($list as $k => $v){
	// 				$res = $this -> user($uid,$v['order_sn']);
	// 				if($res){
						
	// 					$this->where(['message_id' => $v['message_id']])->setField(['message_status' => 1, 'message_read_time' => time()]);
	// 					$arr[$k]= $v;
	// 				}
	// 	 		}
				
	// 			if($arr){
	// 				$arr = array('success'=>'1', 'list'=> $arr);
	// 				echo json_encode($arr);
	// 				exit();
	// 			}
				
	// 		}
	// 		//服务器($_POST['time']*0.5)秒后告诉客服端无数据
	// 	 	if($i == $_POST['time']){
	// 	 		$arr = array('success'=>'0', 'list'=> '');
	// 	 		echo json_encode($arr);
	// 	 		exit();
	// 	 	}
	// 	 	usleep(1000);
	// 	}
	// }

	public function queryTimer($uid=""){
		$list = $this->where(['message_status' => 0])->select();
		if($list){
			$arr = "";
			foreach($list as $k => $v){
				$res = $this -> user($uid,$v['order_sn']);
				if($res){
					$this->where(['message_id' => $v['message_id']])->setField(['message_status' => 1, 'message_read_time' => time()]);
					$arr[]= $v;
				}
			}
			if($arr){
				$arr = array('success'=>'1', 'list'=> $arr);
				echo json_encode($arr);
				exit();
			}else{
				$arr = array('success'=>'0', 'list'=> '');
		 		echo json_encode($arr);
		 		exit();
			}
		}else{
			$arr = array('success'=>'0', 'list'=> '');
		 	echo json_encode($arr);
		 	exit();
		}
	}
	
	//检测用户是否属于订单所在门店
	public function user($uid,$order_sn){
		$user = M('admin_user') -> where(['uid'=>$uid]) -> field('stores_id') -> find(); // 查找当前用户所属门店
		$cor_store_id = M('crowdfunding_order') -> where(['fk_com_ordersn'=>$order_sn]) -> getfield("cor_store_id"); // 查找订单对应的门店id
		if(!$cor_store_id){
			$cor_store_id = M('order') -> where(['order_sn'=>$order_sn]) -> getfield("stores_id");
		}
		
		/* if(!$cor_store_id){
			return true;
		} */
		

		// 如果用户存在并却用户所在门店和订单对应门店一样
		if($user['stores_id'] == $cor_store_id){
			return true;
		}else{
			return false;
		}
	}
}