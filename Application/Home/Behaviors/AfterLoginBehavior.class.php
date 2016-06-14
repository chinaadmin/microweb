<?php
/**
 * 前端用户登入后行为
 */
namespace Home\Behaviors;
class AfterLoginBehavior extends \Think\Behavior{   
	/**
	 * @param int $param 用户id
	 * @see \Think\Behavior::run()
	 */
	public function run(&$param){   
			//加入最登入时间信息
			$data = ['last_login_time' => NOW_TIME];
			$data['last_login_ip'] = get_client_ip();
			$Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
			$area = $Ip->getlocation($data['last_login_ip'])['country']; // 获取某个IP地址所在的位置
			$data['last_login_place'] = $area ? $area : '';
			D('User/User')->where(['uid' => $param])->save($data);
 	}
}
