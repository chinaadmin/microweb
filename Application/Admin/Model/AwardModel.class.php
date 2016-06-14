<?php
/**
 * 抽奖模型
 * @author wxb 
 * @date 2016/1/14 
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class AwardModel extends AdminbaseModel{
    //缓存Key
    const CACHE_KEY = '_AdminAwardModelCache_';
	protected $tableName = 'award_plan';
	protected $attchModel = null;
	protected $awardUserModel = null;
	protected $planRecordModel = null;
	protected $planDetailModel = null;
	protected $objectContainer = null;
	protected $_scope = [
			'using' =>[
					'where' => [
						'ap_is_using' => 1,
						'ap_start_time' => ['elt',NOW_TIME],
						'ap_end_time' => ['egt',NOW_TIME],
					]
			]
	];

	//奖品类型
	public $prizeType = [
		"1" => "赠品",
		"2" => "优惠券",
		"3" => "红包",
		"4" => "积分"
	];


	function _initialize(){
		$this->attchModel = D('Upload/AttachMent');
		$this->planRecordModel = M('award_record');
		$this->awardUserModel = M('award_user');		
		$this->planDetailModel = M('award_plan_detail');		
	}
    /**
     * 获取方案奖品列表
     * @param int $ap_id 抽奖方案id
     */
	function getAwardGoods($ap_id){
		if(!$this->objectContainer['detailView']){
			$view = [
					'award_plan_detail' => [
							'_as' => 'apd',
							'_type' => 'left',
					],
					'award_subject' => [
							'_as' => 'asu',
							'as_name',
							'as_id',
							'_on' => 'asu.as_id = apd.fk_as_id'
					],
			];
			$this->objectContainer['detailView'] = $this->dynamicView($view);
		}
		$res = $this->objectContainer['detailView']->where(['fk_ap_id' => $ap_id])->select();
		return $res;
	}
	//奖品列表
	function awardList(){
		if(!$this->objectContainer['subject']){
			$this->objectContainer['subject'] = M('award_subject');
		}
		return $this->objectContainer['subject']->select();
	}
	function update($data){
		$planModel = $this;
		$planDetailModel = M('award_plan_detail');
		$planRuleModel = M('award_rule');
		if(!$planModel->create()){
			return false;
		}
		if(!$planModel->ap_is_using){
			$planModel->ap_is_using = 0;
		}
		// $planModel->ap_start_time = strtotime($planModel->ap_start_time.' 00:00:00');
		// $planModel->ap_end_time =  strtotime($planModel->ap_end_time.' 23:59:59');

		$planModel->ap_start_time = strtotime($planModel->ap_start_time);
		$planModel->ap_end_time =  strtotime($planModel->ap_end_time);

		$planModel->ap_add_time =  NOW_TIME;
		if($data['ap_id']){
			$planModel->save();
			$ap_id = $data['ap_id'];
		}else{
			$ap_id = $planModel->add();
		}
		$count = count($data['fk_as_id']);
		for($i=0;$i < $count;$i++){
			$tmp = [];
			$tmp['fk_ap_id'] = $ap_id;
			$tmp['fk_as_id'] = $data['fk_as_id'][$i];
			$tmp['apd_id'] = $data['apd_id'][$i];
			$tmp['apd_award_total'] = $data['apd_award_total'][$i];
			$tmp['apd_alias_name'] = trim($data['apd_alias_name'][$i]);
			$tmp['apd_probability'] = $data['apd_probability'][$i];
			$tmp['apd_pic_id'] = $data['apd_pic_id'][$i] ? $data['apd_pic_id'][$i] : 0;

			$tmp['apd_one_max_num'] = $data['apd_one_max_num'][$i]; //每人最多领取数量

			if($tmp['apd_id']){
				$planDetailModel->save($tmp);
			}else{
				unset($tmp['apd_id']);
				$planDetailModel->add($tmp);
			}			
		}

		//增加好友帮抽
		if($data['ap_haoyoubangchou_status']){
			$count = count($data['bc_fk_as_id']);
			for($i=0;$i < $count;$i++){
				$bcTmp = [];
				$bcTmp['fk_ap_id'] = $ap_id;
				$bcTmp['fk_as_id'] = $data['bc_fk_as_id'][$i];
				$bcTmp['apd_id'] = $data['bc_apd_id'][$i];
				$bcTmp['apd_award_total'] = $data['bc_apd_award_total'][$i];
				$bcTmp['apd_alias_name'] = trim($data['bc_apd_alias_name'][$i]);
				$bcTmp['apd_probability'] = $data['bc_apd_probability'][$i];
				$bcTmp['apd_pic_id'] = $data['bc_apd_pic_id'][$i] ? $data['bc_apd_pic_id'][$i] : 0;
				$bcTmp['apd_bangchou_type'] = 2;
				$bcTmp['apd_one_max_num'] = $data['bc_apd_one_max_num'][$i]; //每人最多领取数量
				if($bcTmp['apd_id']){
					$planDetailModel->save($bcTmp);
				}else{
					unset($bcTmp['apd_id']);
					$planDetailModel->add($bcTmp);
				}			
			}
		}

		$planRuleModel->where(['fk_ap_id' => $ap_id])->delete();
		$planRuleModel->add(['ar_type' => 2,'ar_order_type' => $data['ar_order_type'],'fk_ap_id' => $ap_id]);
		return true;
	}
	/**
	 * 获取抽奖方案
	 * @param int $planId
	 */
	function getOnePlan($planId){
		$planModel = $this;
		$planDetailModel = M('award_plan_detail');
		$planRuleModel = M('award_rule');
		$info = $planModel->where(['ap_id' => $planId])->find();
		// $info['detail'] = $this->formatDetailData($planDetailModel->where(['fk_ap_id' => $info['ap_id']])->select());

		$info['detail'] = $this->formatDetailData($planDetailModel->where(['fk_ap_id' => $info['ap_id'], 'apd_bangchou_type' => 1])->select()); // 个人抽奖奖品列表
		$info['bcDetail'] = $this->formatDetailData($planDetailModel->where(['fk_ap_id' => $info['ap_id'], 'apd_bangchou_type' =>2])->select());//	好友帮抽奖品列表
		$info['zpType'] = $this->getPrizeTypeByPrizeId($info['fk_zp_as_id']); // 获赠奖品类型
		$info['zpList'] = $this->getPrizelistByPrizeId($info['fk_zp_as_id']); // 获赠奖品列表
		$info['bczpType'] = $this->getPrizeTypeByPrizeId($info['fk_bc_as_id']); // 帮抽好友获赠奖品类型
		$info['bczpList'] = $this->getPrizelistByPrizeId($info['fk_bc_as_id']); // 帮抽好友获赠奖品列表

		$info['ar_order_type'] = $planRuleModel->where(['fk_ap_id' => $planId])->getField('ar_order_type');
		return $info;		
	}
	//格式化抽奖方案详情数据
	private function  formatDetailData($data){
		foreach ($data as &$v){
			$v['picPath'] = $this->attchModel->getAttach($v['apd_pic_id']);
			$v['ptype'] = $this->getPrizeTypeByPrizeId($v['fk_as_id']);
			$v['plist'] = $this->getPrizelistByPrizeId($v['fk_as_id']);
		}
		return $data;
	}
	//格式化抽奖方案数据
	function formatAwardList($list){
		foreach ($list as &$v){
			$v['join_count']	= $this->getJoinCount($v['ap_id']);			
			$v['receive_count']	= $this->getReceiveCount($v['ap_id']);		
			$v['not_receive_count'] =  $this->getNotReceiveCount($v['ap_id']);	
		}
		return $list;
	}
	//参数人数 重复不算
	function getJoinCount($planId){
		$res =  $this->planRecordModel->where(['fk_ap_id' => $planId])->field('ar_uid')->distinct(true)->select();
		return count($res);
	}
	function getReceiveCount($planId){
		return $this->planRecordModel->where(['fk_ap_id' => $planId,'ar_is_reveive' => 1])->count();
	}
	function getNotReceiveCount($planId){
		return $this->planRecordModel->where(['fk_ap_id' => $planId,'ar_is_reveive' => 0])->count();
	}
	/**
	 * 获取用户抽奖次数
	 * @param int $uid 用户id
	 * @param int $type 抽奖类型 1：WONGWONGWONG 2十周年
	 * @return number
	 */
	function getAwardChance($uid,$type=1){
		if(!$uid){
			return 0;
		}
		$model = $this->awardUserModel;
		$find = $model->where(['au_uid' => $uid,'type'=>$type])->getField('au_remain_count');
		return  $find ? $find : 0;
	}
	/**
	 * 增加用户抽奖次数
	 * @param int $uid 用户id
	 * @param int $num
	 * @param int $type 活动类型
	 * @return boolean
	 */
	function increaseAwardChance($uid,$num = 1,$type=0){
		$model = $this->awardUserModel;
		if(!$model->where(['au_uid' => $uid,'type'=>$type])->count()){
			$res = $model->add(['au_uid' => $uid,'au_remain_count' => $num,'type'=>$type]);
		}else{
			$res = $model->where(['au_uid' => $uid,'type'=>$type])->setInc('au_remain_count',$num);
		}
		return  $res === false ?  false : true;
	}
	/**
	 * 减少用户抽奖次数
	 * @param int $uid 用户id
	 * @param int $num
	 * @param int $type  活动类型(1:嗡嗡嗡,2:十周年)
	 * @return boolean
	 */
	function reduceAwardChance($uid,$num,$type = 0){
		if(!$this->awardUserModel->where(['au_uid' => $uid])->count()){
			return true;
		}
		$res1 = $this->awardUserModel->where(['au_uid' => $uid,'type'=>$type])->setDec('au_remain_count',$num); //减少总抽奖次数
		$res2 = $this->awardUserModel->where(['au_uid' => $uid,'type'=>$type])->setInc('au_use_count',$num);//增加已用次数
		return ($res1 && $res2) ? true : false;
	}
	/**
	 * 减少奖品的总数量
	 * @param integer $detailId 奖品ID
	 * @param integer $num  减去的数量
	 */
	public function reduceAwardTotal($detailId,$num=1){
	    if (!$this->planDetailModel->where(['apd_id'=>$detailId])->Sum('apd_award_total')){
	        return true;
	    }
	    $res = $this->planDetailModel->where(['apd_id'=>$detailId])->setDec('apd_award_total',$num);
	    return $res ? true :false;
	}
	
	//获取正在使用的抽奖方案
	function getUseingAward($field){
		$data = $this->scope('using')->order('ap_id desc')->field($field)->find();
		return $data;
	}
	
	/**
	 * 获取嗡嗡嗡活动数据  add by yt 2016-03-04
	 * @param integer $ap_id 
	 * @param string $field
	 * @return array
	 */
	public function getWongAward($ap_id){
	    $key = md5(self::CACHE_KEY.'ap_id'.$ap_id);
	    $redis = D('Common/Redis')->getRedis();
	    $data = $redis->get($key);
	    if (!$data){
	        $data = $this->where(['ap_id'=>$ap_id,'ap_is_using'=>1])->field(true)->find();
	        $redis->set($key,$data,120);
	    }
	    return $data;
	}
	
	/**
	 * 判断用户是否有参与资格
	 * @param integer $user_id
	 * @param integer $cd_id
	 * @return integer
	 */
	public function getWongAllowUser($user_id,$cd_id){
	    $user_record = $this->awardUserModel->where(['au_uid'=>$user_id,'type'=>1])->field(['au_id','au_remain_count','au_use_count'])->find();
	    if (!$user_record){ //如果没有记录
	        return 0;
	    }
	    else{  //如果存在记录
	        return $user_record['au_remain_count'] ? (int)$user_record['au_remain_count'] : 0;
	    }
	}
	
	/**
	 * 获取获奖的金额及累积总数  add by yt 2016-03-07
	 * @param integer $detailId  award_plan_detail表中主键ID
	 * @param integer $user_id  用户ID
	 * @return array | bool
	 */
	public function getAwardAmount($detailId,$user_id){
	    //获取当前中奖的金额
	    $pd_data = $this->planDetailModel->where(['apd_id'=>$detailId])->field(['fk_as_id'=>'as_id','fk_ap_id'=>'ap_id'])->find();
	    if (!$pd_data) return false;
	    $iniResult['priceNumber'] = M('award_subject')->where(['as_id'=>$pd_data['as_id']])->getField(['as_hongbao_amount']);
	    
	    //获取总共中奖的金额
	    $viewField = [
	        'award_record' => [
	            '_as' => 'ar',
	            '_type' => 'left',
	            'ar_id' => 'id', //获奖记录id
	        ],
	        'award_plan_detail' =>[
	            '_as' => 'apd',
	            '_type' => 'left',
	            '_on' => 'apd.apd_id = ar.fk_apd_id',
	            'fk_as_id'=> 'as_id',
	        ],
	        'award_subject' => [
	            '_as' => 'asj',
	            '_on' => 'asj.as_id = apd.fk_as_id', 
	            'as_hongbao_amount' => 'as_amount',
	        ],
	    ];
	    $model = $this->dynamicView($viewField);
	    $totalAmount = $model->where(['ar.ar_uid'=>$user_id,'ar.fk_ap_id'=>$pd_data['ap_id']])->Sum('asj.as_hongbao_amount');
	    $iniResult['priceTotal'] = sprintf("%.2f",$totalAmount);
	    $iniResult['gameNumber'] = (int)$this->getAwardChance($user_id);
	    
	    return $iniResult;
	}
	
	/**
	 * 获取wongwongwong我中奖列表 add by yt 2016-03-07
	 * @param integer $awardId  活动ID
	 * @param string $user_id   用户ID
	 */
	public function getMyAward($user_id,$awardId){
	    $viewField = [
	        'award_record' => [
	            '_as' => 'ar',
	            '_type' => 'left',
	            'ar_id' => 'id',
	            'ar_draw_time' => 'draw_time',
	            'is_friend' => 'is_friend',
	        ],
	        'award_plan_detail' => [
	            '_as' => 'pd',
	            '_type' => 'left',
	            '_on' => 'pd.apd_id = ar.fk_apd_id',
	        ],
	        'award_subject' => [
	            '_as' => 'asj',
	            '_type' => 'left',
	            '_on' => 'asj.as_id = pd.fk_as_id',
	            'as_hongbao_amount' => 'amount',
	        ]
	    ];
	    $model = $this->dynamicView($viewField);
	    $list_tmp = $model->where(['ar.ar_uid'=>$user_id,'ar.fk_ap_id'=>$awardId])->order('ar.ar_id DESC')->select();
	    $list = [];
	    if (count($list_tmp)){
	        $mask_arr = ['自己采蜂','好友助力'];
	        foreach ($list_tmp as $k=>$v){
	            $list[$k]['number'] = doubleval($v['amount']);
	            $list[$k]['dateTime'] = date('Y-m-d H:i:s',$v['draw_time']);
	            $list[$k]['mask'] = isset($mask_arr[$v['is_friend']])?$mask_arr[$v['is_friend']]:'系统';
	        }
	    }
	    return $list;
	}
	
	/**
	 * 获取wongwongwong系统获奖列表 add by yt 2016-03-07
	 * @param int $awardId
	 * @param int $pageNum  返回的条数
	 */
	public function getSystemAward($awardId,$pageNum = 20){
		//去掉读取缓存，实时查询数据
	    // $key = md5(self::CACHE_KEY.$awardId.'_pageNum_'.$pageNum);
	    // //从缓存中获取数据
     //    $redis = D('Common/Redis')->getRedis();
     //    $data = $redis->get($key);
     //    if ($data == false){
    	    $viewField = [
    	        'award_record' =>[
    	            '_as' => 'ar',
    	            '_type'=> 'left',
    	            'is_friend'=> 'isFriend',
    	        ],
    	        'award_plan_detail' => [
    	            '_as' => 'pd',
    	            '_type' => 'left',
    	            '_on' => 'pd.apd_id = ar.fk_apd_id',
    	        ],
    	        'award_subject'=> [
    	            '_as' => 'asj',
    	            '_type' => 'left',
    	            '_on'  => 'asj.as_id = pd.fk_as_id',
    	            'as_hongbao_amount'=>'number',
    	        ],
    	        'user' => [
    	            '_as' =>  'u',
    	            '_type' => 'left',
    	            '_on' => 'u.uid = ar.ar_uid',
    	            'mobile' => 'mobile',
    	        ]
    	    ];
    	    $model = $this->dynamicView($viewField);
    	    $data = $model->where(['ar.fk_ap_id'=>$awardId])->order('ar.ar_id DESC')->limit($pageNum)->select();
    	    if ($data){
    	        foreach ($data as $k=>$val){
    	            $data[$k]['mobile'] = hideStr($val['mobile'], 'mobile');
    	        }
    	        // $data = json_encode($data);
    	        // $redis->set($key,$data,30); // 去掉写入缓存
    	    }
        // }
	    // return $data ? json_decode($data,true): $data;

    	return $data;
	}
	
	/**
	 * 获取wongwongwong活动的参与次数 add by yt 2016-03-07
	 * @param unknown $user_id
	 * @param integer $type   1wongwongwong2十周年
	 */
	private function getWongBuyTotal($user_id,$type=1){
	    //购买众筹开始时间 期间有效
	    /*$s_time = strtotime(C('WONG_CONFIG.zc_start_time'));
	    $e_time = strtotime(C('WONG_CONFIG.zc_end_time'));
	    $where = [
	        'cor_uid'=>$user_id,
	        'fk_cd_id'=>$cd_id,
	        //'cor_order_status' => 4,  //订单的状态;0待确认,1已确认,2已取消,3退货,4已完成,5已过期 6无效
	        'cor_pay_status'=>2,      //支付状态;0待支付;1支付中;2已支付 3申请退款 4已退款
	        'cor_pay_time' => ['between',$s_time.','.$e_time],
	    ];
	    return M('crowdfunding_order')->where($where)->count();*/
	    $result = $this->awardUserModel->where(['au_uid'=>$user_id,'type'=>1])->find();
	    if(!$result){
	        return 0;
	    }
	    return intval($result['au_remain_count'] + $result['au_use_count']);
	}
	
	/**
	 * 用户获取助力码  add by yt 2016-03-07
	 * @param string $user_id
	 * @return string
	 */
	public function getHelpCode($user_id){
	    $code = M('award_code')->where(['uid'=>$user_id])->getField('code');
	    if (empty($code)){
	        $code = $this->createHelpCode($user_id);
	    }
	    return $code;
	}
	
	/**
	 * 创建好友助力码 add by yt 2016-03-07
	 * @param string $user_id
	 */
	private function createHelpCode($user_id){
	    $code = shortUrl($user_id);
	    $model = M('award_code');
	    $data = [
	        'uid' => $user_id,
	        'code' => $code,
	        'add_time' => NOW_TIME
	    ];
	    $model->add($data);
	    return $code;
	}
	
	/**
	 * 获取给好友加蜜权限
	 * isAllow 逻辑:1,活动开始后注册的用户2,助力池未满3,是否已经助力(一个好友只能限制助力一次)
	 * @param string $code
	 * @param string $user_id
	 */
	public function getFriendDetail($code,$user_id){
	    $iniReturn = [
	        'code'=>$code,
	        'isAllow' => 0,
	        'doTime' => 0,
	        'totalTime' => 0,  //总共能使用多少次
	    ];
	    //找出获取开始时间
	    $ap_id = C('WONG_CONFIG.awardPlanID');
	    $award_data = $this->getWongAward($ap_id);
	    //是否在活动开始后注册
	    $regis_user = M('user')->where(['uid'=>$user_id,'add_time'=>['egt',$award_data['ap_start_time']]])->count();
	    if (!$regis_user){
	        return -1;
	    }
	    //根据助力码找出主人
	    $owner_uid = M('award_code')->where(['code'=>$code])->getField('uid');
	    if (is_null($owner_uid)){
	        return -4;
	    }
	    if ($owner_uid == $user_id){
	        //自己无法给自己助力
	        return -6;
	    }
	    //认筹次数
	    $buy_times = $this->getWongBuyTotal($owner_uid);
	    //能助力的总次数
	    $iniReturn['totalTime'] = intval($buy_times * C("WONG_CONFIG.one_help_times"));
	    if (!$iniReturn['totalTime']){
	        //如果没有能够助力的次数
	        return -5;
	    }
	    $iniReturn['doTime'] = M('award_code_user')->where(['code'=>$code])->count();
	    //查看自己是否已助力过
	    $self_has_help = M('award_code_user')->where(['user_id'=>$user_id])->count();
	    if ($self_has_help){
	        return -2;
	    }
	    if (!($iniReturn['doTime'] < $iniReturn['totalTime'])){
	       return -3;
	    }
	    $iniReturn['isAllow'] = 1;
	    return $iniReturn;
	}
	
	/**
	 * 获取优惠劵ID
	 * @param integer $ap_id
	 */
	private function getCouponId($ap_id){
	    $viewField = [
	        'award_plan' =>[
	            '_as' => 'ap',
	            '_type'=> 'left',
	        ],
            'award_subject'=> [
            '_as' => 'asj',
            '_type' => 'left',
            '_on'  => 'asj.as_id = ap.fk_bc_as_id',
            'as_coupon_id'=>'coupon_id',
	        ],
	    ];
        $model = $this->dynamicView($viewField);
        $coupon_id = $model->where(['ap.ap_id'=>$ap_id])->getField('coupon_id');
        return $coupon_id ? intval($coupon_id): 0;
	}
	/**
	 * 给好友助力加蜜
	 * @param string $code
	 * @param string $user_id
	 * @todo 获取金额
	 */
	public function doFriendHelp($code,$user_id){
	    $iniResult = [
	        'doCode' => 0,
	        'cash' => 0,
	        'totalTime' => 0,
	        'doTime' => 0,
	        'ticket' => []
	    ];
	    $ap_id = C('WONG_CONFIG.awardPlanID');
	    $award_data = $this->getWongAward($ap_id);
	    //是否在活动开始后注册
	    $regis_user = M('user')->where(['uid'=>$user_id,'add_time'=>['egt',$award_data['ap_start_time']]])->count();
	    if (!$regis_user){
	        return -6;
	    }
	    //根据助力码找出主人
	    $owner_uid = M('award_code')->where(['code'=>$code])->getField('uid');
	    if ($owner_uid == null){
	        return -3;
	    }
	    if ($owner_uid == $user_id){
	        //自己无法给自己助力
	        return -1;
	    }
	    $buy_times = $this->getWongBuyTotal($owner_uid);
	    //能助力的总次数
	    $totalTime = intval($buy_times * C("WONG_CONFIG.one_help_times"));
	    if (!$totalTime){
	        //如果没有能够助力的次数
	        return -5;
	    }
	    //助力码已经助力过多少次
	    $doTime = M('award_code_user')->where(['code'=>$code])->count();
	    if ($doTime > $totalTime - 1){
	        return -7;
	    }
	    //检查是否已加蜜过
	    if (M('award_code_user')->where(['user_id'=>$user_id])->count()){
	        return -4;
	    }
	    //$detailId  帮助主人抽奖
	    $apd_id = $this->helpDraw($owner_uid,$ap_id);
	    if (!$apd_id){
	        return -2;
	    }
	    //自己领优惠劵
	    $coupon_id = $this->getCouponId($ap_id);
	    if ($coupon_id){
    	    $myCoupon = D('User/Coupon')->receive($coupon_id,$user_id);
    	    if ($myCoupon !== false){
    	        $coupon_data = M('coupon')->where(['id'=>$coupon_id])->field(['money','color'])->find();
    	        $iniResult['ticket'] = [
    	            'price'=> doubleval($coupon_data['money']),
    	            'background' => '',
    	            'color' => $coupon_data['color'],
    	        ];
    	    }
	    }
	    $iniResult['doCode'] = 1;
	    $viewField = [
	        'award_plan_detail'=>[
	            '_as' => 'pd',
	            '_type' => 'left',
	            'fk_as_id'=> 'id',
	        ],
	        'award_subject' => [
	            '_as' => 'asj',
	            '_type' => 'left',
	            '_on' => 'asj.as_id = pd.fk_as_id',
	            'as_hongbao_amount' => 'cash',
	        ],
	    ];
	    $model = $this->dynamicView($viewField);
	    $list_tmp = $model->where(['pd.apd_id'=>$apd_id])->find();
	    $iniResult['cash'] = doubleval($list_tmp['cash']);
	    //如果成功加入记录到code_user表
	    $code_user = [
	        'user_id' => $user_id,
	        'code'    => $code,
	        'amount'  => doubleval($list_tmp),
	        'add_time'=> NOW_TIME
	    ];
	    M('award_code_user')->add($code_user);
	    //认筹次数
	    $cd_id = C('WONG_CONFIG.cd_id');
	    $buy_times = $this->getWongBuyTotal($owner_uid, $cd_id);
	    //能助力的总次数
	    $iniResult['totalTime'] = intval($buy_times * C("WONG_CONFIG.one_help_times"));
	    $iniResult['doTime'] = M('award_code_user')->where(['code'=>$code])->count();
	    return $iniResult;
	}
	
	/**
	 * 纪录用户抽奖
	 * @param string $uid
	 * @param int $detailId
	 * @param int $is_friend 是否是好友助力
	 */
	function recordAward($uid,$detailId,$is_friend=0){
		$data = [];
		$planId = $this->planDetailModel->where(['apd_id' => $detailId])->getField('fk_ap_id');
		$data = [
				'ar_uid' => $uid,
				'fk_ap_id' => $planId,
				'fk_apd_id' => $detailId,
				'ar_draw_time' => NOW_TIME,
		        'is_friend' => $is_friend
		];
		$this->planRecordModel->add($data);
	}
	
	/**
	 * 帮好友抽奖 add by yt 2016-03-07
	 * @param string $uid
	 * @param integer $ap_id
	 * @param boolean $limit_total 是否限制奖品总数
	 */
	private function helpDraw($uid,$ap_id=0,$limit_total = true){
	    $ap_id = $ap_id ? $ap_id : $this->scope('using')->getField('ap_id');
	    //apd_bangchou_type  1:自己抽2:好友帮抽
	    $where = ['fk_ap_id' => $ap_id,'apd_bangchou_type'=>2];
	    if ($limit_total){
	        $where['apd_award_total'] = ['gt',0];
	    }
	    $arr = $this->planDetailModel->where($where)->field(['apd_id' => 'id','apd_probability' => 'probability'])->select();
	    if (!count($arr)) return false;
	    $arr = array_column($arr, 'probability','id');
	    $detailId = get_rand($arr);
	    //如果限制奖品数量，抽到奖品后，奖品数量减1
	    if ($limit_total && $detailId){
	        $this->reduceAwardTotal($detailId);
	    }
	    //纪录抽奖过程
	    $this->recordAward($uid,$detailId,1);
	    //增加抽奖人次
	    M('award_plan')->where(['ap_id' => $ap_id])->setInc('ap_count');
	    //将红包转换成账户余额
	    D('Admin/Prize')->receive($uid,$detailId);
	    return $detailId;
	}
	
	/**
	 * wongwongwong获取说明
	 * @param integer $ap_id
	 */
	public function getWongDetail($ap_id){
	   $key = md5(self::CACHE_KEY."detail_ap_id_".$ap_id);
	   $redis = D('Common/Redis')->getRedis();
	   $data = $redis->get($key);
	   if ($data == false){
    	   $data =  $this->where(['ap_id'=>$ap_id])->field(['ap_name'=>'title','ap_remark'=>'content'])->find();
    	   if ($data){
    	       $data['content'] = htmlspecialchars_decode($data['content']);
    	   }
    	   $data = serialize($data);
    	   $redis->set($key,$data,3600 * 24);
	   }
	   return unserialize($data);
	}
    /**
     * 抽奖    modify by yt 2016-03-07  增加ap_id参数 wongwongwong指定抽奖
     * @param string $uid 用户id
     * @param integer $ap_id award_plan id
     * @param integer $click_times 用户点击次数
     * @param boolean $limit_total 是否限制奖品数量
     * @return boolean|mix
     */
	function draw($uid,$ap_id=0,$click_times=0,$limit_total = true){
		if($this->getAwardChance($uid) <= 0){
			return false;
		}
		//modify by yt
		$ap_id = $ap_id ? $ap_id : $this->scope('using')->getField('ap_id');
		//apd_bangchou_type  1:自己抽2:好友帮抽
		$where = [
		    'fk_ap_id' => $ap_id,
		    'apd_bangchou_type'=>1,
		];
		//如果限制数量开启
		if ($limit_total){
		    $where['apd_award_total'] = ['gt',0];
		}
		$arr = $this->planDetailModel->where($where)->field(['apd_id' => 'id','apd_probability' => 'probability'])->select();
		if (!count($arr)) return false;
		$arr = array_column($arr, 'probability','id');
		//如果有点击次数，则重组中奖率，一等奖增加几率，小奖减少或者去掉
		if ($click_times > 0){
		     $click_times = floor($click_times / 1.5);
		     $arr = $this->_probabyChange($arr,$click_times);
		}
		$detailId = get_rand($arr);
		//如果限制奖品数量，抽到奖品后，奖品数量减1
		if ($limit_total && $detailId){
		    $this->reduceAwardTotal($detailId);
		}
		//减少抽奖机会
		$this->reduceAwardChance($uid,1,1);
		//纪录抽奖过程
		$this->recordAward($uid,$detailId);
		//将红包转换成账户余额
		D('Admin/Prize')->receive($uid,$detailId);
		//增加抽奖人次
		M('award_plan')->where(['ap_id' => $ap_id])->setInc('ap_count');
		return $detailId;
	}
	
	/**
	 * 中奖几率改变
	 * @param array $arr
	 * @param integer $click_times
	 * @return array
	 */
	private function _probabyChange($arr,$click_times){
	    $probability = 0;
	    $probability = $click_times < 10 ? 0 : ($click_times > 100 ? 100 : $click_times);
	    //如果中奖几率为0 返回不用调整
	    if (!$probability) return $arr;
	    //如果只有 一个
	    if (count($arr) == 1) return $arr;
	    $new_arr = [];
	    ksort($arr);
	    //取出最大奖
	    list($key,$val) = each($arr);
	    $v = $val + $probability;
	    $new_arr[$key] = $v > 100 ? 100 : $v;
	    unset($arr[$key]);
	    krsort($arr);
	    foreach ($arr as $key=>$pro){
	       if ($pro > $probability){
	           $tmp = $pro - $probability;
	           $new_arr[$key] = $tmp;
	           $probability = 0;
	       }
	       else{
	           if ($pro == $probability){
	               $probability = 0;
	           }
	           else{
	               $probability -= $pro;
	           }
	       }
	    }
        ksort($new_arr);
	    return $new_arr;
	}
	
	//我的获取列表
	function myDrawList($uid){
		$viewField = [
				'award_record' => [
						'_as' => 'ar',
						'_type' => 'left',
						'ar_id' => 'id',//奖品纪录id
						'ar_is_reveive' => 'is_reveive' //是否领取
				],
				'award_plan_detail' => [
						'_as' => 'pd',
						'_type' => 'left',
						'_on' => 'pd.apd_id = ar.fk_apd_id',
						'apd_alias_name' => 'alias_name',//奖品别名
						'apd_pic_id' => 'pic_id',//pic
				],
				'award_subject' => [
						'_as' => 'asu',
						'_on' => 'asu.as_id = pd.fk_as_id',
						'as_name' => 'name',//奖品名
				],
		];
		$model = $this->dynamicView($viewField);
		$list =  $model->where(['ar_uid' => $uid])->order('ar_draw_time desc')->select();
		$picModel = D('Upload/AttachMent');
		foreach ($list as &$v){
			$v['src'] = fullPath($picModel->getAttach($v['pic_id'])[0]['path']);
			unset($v['pic_id']);
		}
		return $list;
	}
	
	/**
	 * 是否有抽奖资格
	 * @param string $user_id
	 * @param integer $type
	 * @return bool
	 */
	public function getDrawQualifi($user_id,$type=1){
	    $num = $this->awardUserModel->where(['au_uid'=>$user_id,'type'=>$type])->Sum('au_remain_count');
	    return $num ? true : false;
	}


	/**
     * 概率算法
     */
    public function getRands($proArr){
        $result = '';
        $proSum = 0;    //概率数组的总概率精度
        for($i=0;$i<count($proArr);$i++){
            $proSum+=$proArr[$i]['apd_probability'];
        }

        //概率数组循环
        foreach ($proArr as $key=>$v) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v['apd_probability']){
                $result = $key;
                break;
            }else{
                $proSum-=$v['apd_probability'];
            }
        }
        unset($proArr);
        return $result;
    }

    /**
	* 生成随机优惠券号
	* @param $len  优惠券号长度
	*/
    public function randCodeStr($len=6) {
		$chars='AB56CD123EFGHIJ456KLM8N9PQR789STUV589WXYZ';
		$code='';
		while(strlen($code)<$len){
			$code.=substr($chars,(mt_rand()%strlen($chars)),1);
		}

		return $code;
	}


    /**
	* 抽奖
	* @param String $user_id	用户id
	* @param String $num	抽奖次数
	* @param Array  $crowsOrder	众筹订单
	*/
	public function getReturnData($user_id,$num,$crowsOrder){
		$info  = C('WONG_CONFIG');
		$ap_id = $info['tenPlanId'];
		$fkId  = $info['fkId'];
		$plan  = M('Award_plan')->where(['ap_id'=>$ap_id])->find();

        if(!empty($plan)){
			if(NOW_TIME>$plan['ap_end_time']){
				$status = -1; //已结束
			}elseif(NOW_TIME<$plan['ap_start_time']){
				$status = 0;  //未开始
			}else{
				$status = 1;  //进行中
			}

			if($plan['ap_is_using']==0){
				$status = 0;
			}
        }else{
			$status = 0;
        }

		if($num<=0){	//过滤抽奖次数为0的
			$return_data=array(
				'isStart'=>$status,
				'isAllow'=>!empty($crowsOrder)?1:0,
                'isWin'=>0,
                'cash'=>0,
                'gameNumber'=>0,
                'tiket'=>0,
            );

            return $return_data;
            exit;
		}

		$detail = M("award_plan")->join('left join __AWARD_PLAN_DETAIL__ ON __AWARD_PLAN__.ap_id=__AWARD_PLAN_DETAIL__.fk_ap_id left join __AWARD_SUBJECT__ ON __AWARD_PLAN_DETAIL__.fk_as_id = __AWARD_SUBJECT__.as_id')->where(['ap_id'=>$ap_id])->select();
        $sanWenyu = M('Award_subject')->where(['as_id'=>$plan['fk_zp_as_id']])->find();   //三文鱼抵扣券的详细信息
        $mySanWenYu = M('Award_record')->where(array('ar_uid'=>$user_id,'fk_as_id'=>$sanWenyu['as_id']))->select();
		// return $detail;

        $sum=0;
        foreach($detail as $v){
			if($v['apd_award_total']>0){
				$sum+=$v['apd_probability'];
			}
        }
        /*if($sum<100){	//如果中奖概率之和小于100，则剩余概率都为未中奖
            $detail[]=array(
                'apd_alias_name'=>'未中奖',
                'as_id'=>0,
                'prize'=>'未中奖',
                'apd_probability'=>(100-$sum),
                'price'=>0
            );
        }*/

        //奖项数组
        $prize_arr = array();
        $i = 0;
        foreach($detail as $k=>$v){
			if($v['apd_award_total']>0){	//过滤奖品数量为0的奖项
				$prize_arr[$i] = array(
					'id'=>$i+1,
					'as_id'=>$v['as_id'],
					'ap_id'=>$v['ap_id'],
					'apd_id'=>$v['apd_id'],
					'prize'=>$v['as_name'],
					'price'=>$v['as_hongbao_amount'],
					'apd_award_total'=>$v['apd_award_total'],
					'v'=>$v['apd_probability']
				);

				$i++;
			}
        }

        foreach ($prize_arr as $key => $val) {
            $details[$val['id']] = $val['v'];
        }
        $rid = $this->getRands($details); //根据概率获取奖项id

        /*if($sum<100 && $rid==(count($detail)-1)){   //如果中奖概率小于100，且抽到奖项数组的最后一个（未中奖）元素，则为未中奖
            $return_data=array(
                'isWin'=>0,
                'cash'=>0,
                'gameNumber'=>$num-1,
                'tiket'=>0,
            );

            M("Award_user")->where(array('au_uid'=>$user_id,'type'=>2))->setInc('au_use_count',1); //已抽奖次数+1
            M("Award_user")->where(array('au_uid'=>$user_id,'type'=>2))->setDec('au_remain_count',1); //剩余抽奖次数-1

            // $this->ajaxReturn($this->result->content($return_data)->success());
            return $return_data;
            exit;
        }*/

        $res = array();
        $res = $prize_arr[$rid-1]; //中奖项
        $return_data = array();
        if(!empty($res)){
            $isWin = 1;
            $cash  = $res['price'];
            $gameNumber = $num-1;

            //一个用户只能领5张三文鱼抵扣券
            if(count($mySanWenYu)<5){
                $tiket = 1;

                //用户中奖信息
                $data = array();
                $data[]=array(
                      'ar_uid'=>$user_id,
                      'fk_ap_id'=>$res['ap_id'],
                      'fk_as_id'=>$sanWenyu['as_id'],
                      'fk_apd_id'=>$res['apd_id'],
                      'ar_draw_time'=>time(),
                      'ar_is_reveive'=>1,
                      'ar_is_send'=>1,
                      'ar_reveive_time'=>time()
                    );
                $data[]=array(
                      'ar_uid'=>$user_id,
                      'fk_ap_id'=>$res['ap_id'],
                      'fk_as_id'=>$res['as_id'],
                      'fk_apd_id'=>$res['apd_id'],
                      'ar_draw_time'=>time(),
                      'ar_is_reveive'=>1,
                      'ar_is_send'=>1,
                      'ar_reveive_time'=>time()
                    );

                //系统自动发放优惠券
                $code	= $this->randCodeStr(9);
                $coupon = array();
				$coupon = array(
					'coupon_id'=>$sanWenyu['as_coupon_id'],
					'code'=>$code,
					'uid'=>$user_id,
					'source'=>1,
					'editor'=>'SYSTEM',
					'add_time'=>time()
				);

                M('Coupon_code')->add($coupon);		//写入到用户优惠券
                M("Award_record")->addAll($data);	//写入中奖记录
            }else{
                $tiket = 0;
                $data = array();
                $data = array(
                      'ar_uid'=>$user_id,
                      'fk_ap_id'=>$res['ap_id'],
                      'fk_as_id'=>$res['as_id'],
                      'fk_apd_id'=>$res['apd_id'],
                      'ar_draw_time'=>time(),
                      'ar_is_reveive'=>1,
                      'ar_is_send'=>1,
                      'ar_reveive_time'=>time()
                    );
                M("Award_record")->add($data); //写入中奖记录
            }

            if($res['apd_award_total']>0){
				$this->reduceAwardTotal($res['apd_id']);	//中奖扣除奖品数量
            }
            D("Admin/Prize")->receive($user_id,$res['apd_id']);//调用自动领奖接口
        }else{
            $isWin = 0;
            $cash = 0;
            $gameNumber = $num-1;
            $tiket = 0;
        }

        $return_data=array(
            'isWin'=>$isWin,
            'cash'=>$cash,
            'gameNumber'=>$gameNumber,
            'tiket'=>$tiket,
            'isStart'=>$status,
			'isAllow'=>!empty($crowsOrder)?1:0,
        );

        M("Award_user")->where(array('au_uid'=>$user_id,'type'=>2))->setInc('au_use_count',1); //已抽奖次数+1
        M("Award_user")->where(array('au_uid'=>$user_id,'type'=>2))->setDec('au_remain_count',1); //剩余抽奖次数-1

        unset($isWin,$gameNumber,$data,$coupon,$tiket,$prize_arr,$myRemainCount);
		unset($sanWenyu,$mySanWenYu,$detail,$num,$status,$plan,$crowsOrder,$res,$cash);

		return $return_data;
	}

	//获取十周年用户中奖纪录
	public function getTenYearRecodes($user_id){
		$info = C('WONG_CONFIG');
		$ap_id = $info['tenPlanId'];

        $myRecodes = M("Award_record")->join('__AWARD_SUBJECT__ ON __AWARD_RECORD__.fk_as_id = __AWARD_SUBJECT__.as_id')->where(array('ar_uid'=>$user_id,'fk_ap_id'=>$ap_id))->select();
        $totalCash = 0;
        if(empty($myRecodes)){      //用户还没有中奖纪录
            $return_data = array();
            $return_data['totalCash'] = $totalCash;
            $return_data['tiket'] = 0;
            $return_data['number'] = 0;

            return $return_data;
            exit;
        }
        $return_data = array();
        $tiket = array();
        if(is_array($myRecodes)){
            foreach($myRecodes as $key=>$v){
                if($v['as_type']==3){
					$totalCash+=$v['as_hongbao_amount'];
                }elseif ($v['as_type']==2) {
					$tiket[]=$v;
                }
            }

            $return_data['totalCash'] = $totalCash;
            /*$return_data['tiket'] = array(
					'price'=>$tiket[0]['as_hongbao_amount'],
                    'number'=>count($tiket),
                    'name'=>$tiket[0]['as_name'],
                    'background'=>'',
                    'color'=>'#fff'
			);*/
            $return_data['tiket'] = 1;
            $return_data['number'] = count($tiket);

            return $return_data;
        }
	}

	//十周年抽奖资格
	public function getTenYearAllow($user_id){
		$awardUser = M("award_user")->where(array('au_uid'=>$user_id,'type'=>2))->find();    //用户剩余抽奖信息
		$info=C('WONG_CONFIG');
		$fkId = $info['fkId'];

        $crowsOrder = M('Crowdfunding_detail')->join('__CROWDFUNDING_ORDER__ ON __CROWDFUNDING_DETAIL__.cd_id=__CROWDFUNDING_ORDER__.fk_cd_id')->where(array('cor_uid'=>$user_id,'cd_id'=>$info['cd_id'],'cor_pay_status'=>2))->select();  //查询用户是否存在‘蜂蜜方案二’众筹的已支付的订单
        $ap = M("award_plan")->where(['ap_id'=>$info['tenPlanId']])->find();    //活动信息
        // return $crowsOrder;	

        if(!empty($ap)){
			if(NOW_TIME>$ap['ap_end_time']){
				$status=-1; //已结束
			}elseif(NOW_TIME<$ap['ap_start_time']){
				$status=0;  //未开始
			}else{
				$status=1;  //进行中
			}
        }else{
			$status=0;
        }

        $return_data = array();
        if(!empty($awardUser) && !empty($ap)){
			if($ap['ap_is_using']==0){	//活动未开启，直接返回活动未开始
				$return_data = array(
					'isStart'=>0,
					'isAllow'=>!empty($crowsOrder)?1:0,
					'gameNumber'=>empty($crowsOrder)?0:$awardUser['au_remain_count'],
					'fkId'=>$fkId
				);
			}else{
				$return_data = array(
					'isStart'=>$status,
					'isAllow'=>!empty($crowsOrder)?1:0,
					'gameNumber'=>empty($crowsOrder)?0:$awardUser['au_remain_count'],
					'fkId'=>$fkId
				);
			}
		}else{
			$return_data = array(
				'isStart'=>$status,
				'isAllow'=>!empty($crowsOrder)?1:0,
				'gameNumber'=>0,
				'fkId'=>$fkId
			);
		}
        return $return_data;
	}

	/**
	 * 根据奖品id获取奖品类别
	 * @param:  $pid 奖品id
	 */
	private function getPrizeTypeByPrizeId($pid){
		return M('AwardSubject')->where(['as_id' => $pid])->getField('as_type');
	}

	/**
	 * 根据奖品id获取奖品列表
	 * @param: $pid 奖品id
	 */
	private function getPrizelistByPrizeId($pid){
		$ptype = $this->getPrizeTypeByPrizeId($pid);
		$plist = M('AwardSubject')->field('as_id, as_name')->where(['as_type' => $ptype])->select();
		return $plist;
	}

}