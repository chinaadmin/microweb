<?php
/**
 * 第三方登陆模型
 * @author xiongzw
 * @date 2015-05-06
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
use Think\Model\RelationModel;
class OauthModel extends HomebaseModel{
	protected $tableName = "user_connect";
	/**
	 * 账号是否绑定
	 * @param  $openid 第三方用户id
	 * @param  $type   第三方类型
	 * @return array
	 */
	public function isBind($openid, $type, $unionid = '', $field = true) {
		$where = array (
				'openid' => $openid,
				'type' => strtolower ( $type ) 
		);
		$result_data = $this->field ( $field )->where ( $where )->find ();
		if ($unionid && strtolower($type) == 'wechat') {
			$where ['unionid'] = $unionid;
			unset ( $where ['openid'] );
			$data = $this->field ( $field )->where ( $where )->find ();
			if (empty ( $data ) && ! empty ( $result_data )) {
				$this->where ( array (
						'openid' => $openid,
						'type' => strtolower ( $type ) 
				) )->save ( [ 
						'unionid' => $unionid 
				] );
			} else {
				$result_data = $data;
			}
			//都为空的情况下
			if(empty ( $data ) && empty ( $result_data )){
				$openids = M("UserUnion")->where(['unionid'=>$unionid])->getField("openid",true);
				if($openids){
					$this->where ( array (
							'openid' => array('in',$openids),
							'type' => strtolower ( $type )
					) )->save ( [
							'unionid' => $unionid
							] );
				   $result_data = $this->field ( $field )->where ( $where )->find ();
				}
			}
			$this->addUnionid ( $openid, $unionid );
		}
		return $result_data;
	}
	
	/**
	 * 下载第三方用户头像
	 */
	public function userPic($url,$user=array()){
		$config = C('HEAD_PIC');
		$data = D('Upload/Base')->cacheImage($url,$config,$user);
		return $data;
	}
	
	public function addUnionid($openid,$unionid){
		if($openid && $unionid){
			M('UserUnion')->add(['openid'=>$openid,'unionid'=>$unionid],'',true);
		}
	}
}