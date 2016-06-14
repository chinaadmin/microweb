<?php
/**
 * 会员等级模型
 * @author xiongzw
 * @date 2015-06-10
 */
namespace User\Model;
use Common\Model\BaseModel;
class UserGradeModel extends BaseModel{
	public $express = array(
			"1"=>"等于",
			"2"=>"大于",
			"3"=>"小于",
			"4"=>"介于"
	);
	public $_validate = [ 
			[ 
					'grade_name',
					'require',
					'会员等级名不能为空',
					self::EXISTS_VALIDATE 
			],
			[ 
					'grade_name',
					'',
					'会员等级名已存在',
					self::EXISTS_VALIDATE,
					'unique' 
			],
			[
			        'grade_money',
			        'require',
			        '消费金额不能为空',
			        self::EXISTS_VALIDATE,
			],
			[ 
					'grade_ex_money',
					'require',
					'消费金额不能为空',
					self::EXISTS_VALIDATE 
			],
			[ 
					'grade_discount',
					'require',
					'折扣不能为空' 
			],
			[ 
					'grade_discount',
					 array(1,10),
					'折扣范围为1~10',
					self::EXISTS_VALIDATE,
					'between' 
			]
	];
	
	protected $_scope = array (
			// 默认的命名范围
			'default' => array (
					'where' => array (
							'grade_status' => 1 
					) 
			) 
	)
	;
	/**
	 * 自动验证消费金额
	 */
	public function check_money() {
		$grade_express = I ( 'post.express', 0, 'intval' );
		$start_money = I ( "post.start_money", '' );
		$end_money = I ( "post.end_money", '' );
		
	}
	/**
	 * 通过id获取折扣
	 * @param unknown $grade_id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
	 * @param 	老会员等级系会员的等级的获取等级不一样，逻辑冲突   默认10折   rong   2016/5/19
	 */
	public function getById($grade_id,$field=true){
		// return M('UserGrade')->where(["grade_id"=>$grade_id])->find();
		return ['grade_discount'=>10];
	}

	/**
	 * 通过id获取会员等级信息
	 * @param unknown $grade_id
	 * @param string $field
	 * @return Ambigous <\Think\mixed, boolean, NULL, multitype:, mixed, unknown, string, object>
	 * @param 	老会员等级系会员的等级的获取等级不一样，逻辑冲突   默认10折   rong   2016/5/19
	 */
	public function getGradeById($grade_id,$field=true){
		return M('UserGrade')->where(["grade_id"=>$grade_id])->find();
	}

	/**
	 *	获取最后一条会员等级信息
	 *
	 */
	public function getLastGrade(){
		$info =  M('UserGrade')->order('grade_id desc')->limit(1)->select();
		return $info[0]['grade_id'];
	}

	/**
	 *	添加会员等级
	 *	@param $data 需添加的数据
	 */
	public function addGrade($data){
		// $grade_id=$this->getLastGrade();
		// $this->startTrans();
		if($data && is_array($data)){
			$result = M("UserGrade")->add($data);
			if($result){
				/*if($grade_id){
					$res = M("UserGrade")->where(['grade_id'=>$grade_id])->save(['end_value'=>$data['start_value']-1]);
					if($res){
						$this->commit();
						return $this->result()->success();
					}else{
						$this->rollback();
						return $this->result()->error();
					}
				}else{
					$this->commit();
					return $this->result()->success();
				}*/

				return $this->result()->success();
			}else{
				// $this->rollback();
				return $this->result()->error();
			}
		}else{
			return $this->result()->error('添加的数据不能为空');
		}
	}

	public function toSto(){
		return $this->commit();
	}

	/**
	 *	删除会员等级
	 *	@param $grade_id Array 要删除的主键的id数组
	 */
	public function delGrade($grade_id){
		$res = M("UserGrade")->where(array('grade_id'=>array('IN',$grade_id)))->delete();
		if($res){
			return $this->result()->success();
		}else{
			return $this->result()->error();
		}
	}


	/**
	 *	获取用户的等级名称
	 *	@param $value 成长值
	 */
	public function getUserGrade($value=0){
		$res = M("UserGrade")->where(array('start_value'=>array('elt',$value)))->order('start_value DESC')->limit(1)->find();
		return $res['grade_name'];
	}

	/**
	 *	获取用户的等级信息
	 *	@param $value 成长值
	 */
	public function getUserGradeInfo($value=0){
		$res = M("UserGrade")->where(array('grade_status'=>1,'start_value'=>array('elt',$value)))->order('start_value DESC')->limit(1)->find();
		$return_data = array(
			'grade_id'=>$res['grade_id'],
			'grade_name'=>$res['grade_name']
		);
		return $return_data;
	}
	
	/**
	 * 清除其他默认等级
	 * @param $gid
	 * @return Ambigous <boolean, unknown>
	 */
	public function clearDefault($gid){
		$where = array(
			"gid"=>array('NEQ',$gid)	
		);
		return $this->where($where)->save(array("grade_default"=>0));
	}
	/**
	 * 删除时判断是否有会员等级
	 * @param  $gid 等级id
	 * @return boolean
	 */
	public function userHasGrade($gid){
		$result = array();
		$where = array(
				"grade_id" => array('in',$gid)
		);
		$user_gids = M("User")->where($where)->getField("grade_id",true);
		if($user_gids){
			$user_gids = array_unique($user_gids);
			$result['success'] = array_diff($gid, $user_gids);
			$result['success_count'] = count($result['success']);
			$result['fail_count'] = count($user_gids);
		}else{
			$result['success'] = $gid;
			$result['success_count'] = count($result['success']);
		}
		return $result;
	}
	
	/**
	 * 通过用户id获取用户等级信息
	 * @param $uid 用户id
	 */
	public function getGradeByUser($uid,$field=true){
		$result = D("User/User")->getUserById($uid,"grade_id")->toArray();
		$gid = current($result['result']);
        if(empty($gid)){
            return ['grade_discount'=>10];
        }
	    return $this->getById($gid,$field);
	}
	
	/**
	 * 获取等级级别最大值
	 */
	public function getMaxGrade(){
		$max = $this->max("level");
		return is_null($max)?0:$max;
	}
	
	/**
	 * 设置默认等级
	 */
	public function setDefaultGrade(){
		$where = array(
				"grade_default"=>1
		);
		if(!$this->where($where)->find()){
			$min = $this->min("level");
			$where = array(
					"level"=>$min?$min:1
			);
			$this->setData($where, ["grade_default"=>1]);
		}
	}
	
	/**
	 * 更新等级级别
	 */
	public function updateLevel($gid,$level,$type){
		if(empty($gid)){
			return $this->result()->error("等级id不能为空");
		}
		if(empty($level)){
			return $this->result()->error("等级不能为空");
		}
		if(empty($type)){
			return $this->result()->error("更新类型不能为空");
		}
		$grade_money = $this->where(['gid'=>$gid])->getField("grade_money");
		//提升等级
		if($type==1){
			//获取上一个gid
			$where = array(
					"level"=>array("GT",$level)
			);
			$sort_level = $this->where($where)->order("level ASC")->find();
			if($sort_level && $grade_money<$sort_level['grade_money']){
				return $this->result()->error("消费金额不能小于上一等级金额");
			}
		}
		//降低等级
		if($type==2){
			$where = array(
					"level"=>array("LT",$level)
			);
			$sort_level = $this->where($where)->order("level DESC")->find();
			if($sort_level && $grade_money>$sort_level['grade_money']){
				return $this->result()->error("消费金额不能大于上一等级金额");
			}
		}
		if($sort_level){
			$this->startTrans();
			$result1 = $this->where(['gid'=>$gid])->save(['level'=>$sort_level['level']]);
			$result2 = $this->where(['gid'=>$sort_level['gid']])->save(['level'=>$level]);
			if($result1!=false && $result2!=false){
				$this->commit();
			}else{
				$this->rollback();
				return $this->result()->error();
			}
		}
		return $this->result()->success();
	}
	
	/**
	 * 获取默认用户等级
	 * @return Ambigous <\Think\mixed, NULL, mixed, unknown, multitype:Ambigous <unknown, string> unknown , object>
	 */
	public function getDefaultGrade(){
		return $this->where(['grade_default'=>1])->getField("grade_id");
	}
	
	/**
	 * 判断并更新用户等级用户等级
	 * @param $uid 用户id
	 * @param $money 消费金额/充值金额
	 * @param $type 1:消费金额 2：充值金额
	 */
	public function userGrade($uid,$type=1){
		$user_grade = M("User")->where(['uid'=>$uid])->getField("grade_id");
		if($user_grade){
		 $user_grade = $this->getById($user_grade);
		}
		/* $where = array(
				"grade_money"=>array("ELT",$money),
		); */
		if($type==1){
			$money = M("UserAnalysis")->where(['uid'=>$uid])->getField("consume_money");//消费总金额
			$where["_string"] = "(grade_money<".$money." AND grade_express=2) OR (grade_money>".$money." AND grade_express=3 ) OR (grade_money=".$money." AND grade_express=1) OR (grade_money<=".$money."<=grade_ex_money AND grade_express=4)";
		}else{
			$money = M("UserAnalysis")->where(['uid'=>$uid])->getField("recharge_money");//消费总金额
			$where["_string"] = "(recharge_money<".$money." AND recharge=2) OR (recharge_money>".$money." AND recharge=3 ) OR (recharge_money=".$money." AND recharge=1) OR (recharge_money<=".$money."<=recharge_ex_money AND recharge=4)";
		}
		$grade = $this->scope()->where($where)->order("level DESC")->find();
		$isUpdate = 0; //是否需要更新用户等级
		if($user_grade){
			if($user_grade['level']<$grade['level']){
				$isUpdate = 1;
			}
		}else{
			$isUpdate = 1;
		}
		if($isUpdate){
			return M("User")->where(['uid'=>$uid])->save(['grade_id'=>$grade['gid']]);
		}
	}
	
	/**
	 * 通过用户等级积分
	 * @param $uid 用户id
	 * @param $money 消费金额
	 */
	public function integral($uid,$money){
		$user_grade = M("User")->where(['uid'=>$uid])->getField("grade_id");
		if($user_grade){
			$user_grade = $this->getById($user_grade);
		}
		$rate = ceil($money/$user_grade['integral_money']);
		$integral = $rate*$user_grade['integral'];
		$result = $this->result()->error();
		if($integral){
			$result = D("User/Credits")->setCredits($uid,$integral,'',0,1);
		}
		return $result;
	}
}