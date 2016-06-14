<?php
/**
 * 后台用户操作日志
 * @author wxb 
 * @date 2015/5/19 
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use User\Org\Util\User;
class AdminLogModel extends AdminbaseModel{
	 function getModel(){
	 	$sql = '
	 			select * from  jt_admin_log al
				LEFT JOIN jt_admin_user au  
				on al.adlog_uid = au.uid
	 	';
	 	return  $this->table('('.$sql.') as new');
	 }
	 /**
	  * 后台用户操作日志
	  * @param string $message 说明
	  * @param int $type 操作类型,1为写入，2为更新，3为删除
	  */
	  function addAdminLog($message,$type,$option = [],$sql = ''){
	 	$data['adlog_sql'] = $sql;
	 	$data['adlog_options'] = serialize($option);
	 	$data["adlog_moudle"]  = strtolower(MODULE_NAME);
	 	$data["adlog_action"] = strtolower(explode('.', ACTION_NAME)[0]);
	 	$data['adlog_uid'] = User::getInstance ()->getUser()['uid'];
	 	$data['adlog_add_time'] = NOW_TIME;
	 	$data['adlog_ip'] = get_client_ip();
	 	$data['adlog_type'] = $type;
	 	$data["adlog_data"] = serialize(I('request.'));
	 	$data["adlog_get"] = serialize(I('get.'));
	 	$data["adlog_post"] = serialize(I('post.'));
	 	$data["adlog_mark"] = $message;
	 	$this->add($data);
	 }
}