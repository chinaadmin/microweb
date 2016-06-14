<?php
/**
 * 优惠券模型类
 * @author cwh
 * @date 2015-07-17
 */
namespace User\Model;
use Common\Model\BaseModel;
use User\Org\Util\User;

class CouponModel extends BaseModel{

    protected $tableName = 'coupon';

    public $_validate = [
        ['name','require','优惠劵名称不能为空'],
        ['money','require','面值不能为空'],
        [['id','name','money','rule','order_money','start_time','end_time'],'is_exist','该优惠劵已经存在',self::MUST_VALIDATE,'callback',self::MODEL_BOTH]
    ];

    public $_auto = [
        ['start_time','tomktime',self::MODEL_BOTH,'function'],
        ['end_time','tomkendtime',self::MODEL_BOTH,'function'],
        ['add_time','time',self::MODEL_INSERT,'function']
    ];

    //命名范围
    protected $_scope = [
        'normal'=>[// 获取正常状态
            'where'=>[
                'status'=>1,
                'end_time'=>['gt',NOW_TIME],
                'start_time'=>['lt',NOW_TIME]
            ],
        ],
        'unexpired'=>[
            'where'=>[
                'status'=>1,
                'end_time'=>['gt',NOW_TIME]
            ],
        ],
        'default'=>[

        ]
    ];

    /**
     * 验证优惠劵是否存在
     * @param array $data
     * @return bool
     */
    public function is_exist($data){
        $where = [
            'name'=>$data['name'],
            'money'=>$data['money'],
            'rule'=>$data['rule'],
            'order_money'=>$data['order_money'],
            'start_time'=>tomktime($data['start_time']),
            'end_time'=>tomkendtime($data['end_time']),
        ];
        if(!empty($data['id'])) { // 完善编辑的时候验证唯一
            $where['id'] = ['neq',$data['id']];
        }
        if($this->field(true)->where($where)->find()) {
            return false;
        }
        return true;
    }
    
    /*
     * 中奖试图模型
     */
    public function DrawViewModel(){
        $viewFields = [
            'coupon_code' => [
                'id',
                'coupon_id',
                'code',
                'uid',
                'order_id',
                'use_time',
                'add_time'=>'ar_draw_time',
                '_type' => 'LEFT',
                '_as' => 'cc'
            ],
            'award_subject'=>[
                'as_id',
                'as_name',
                'as_type',
                '_as' => 'jas',
                '_on'=> 'jas.as_coupon_id = cc.coupon_id',
                '_type' => 'LEFT',
            ],
            'User' => [
                'username',
                'aliasname',
                'mobile',
                '_as' => 'u',
                '_on' => 'cc.uid=u.uid',
                '_type' => 'LEFT'
            ],
            
            
            
        ];
        return $this->dynamicView ( $viewFields );
    }

    /**
     * 试图模型
     * @param null $viewFields
     * @return mixed
     */
    public function viewModel($viewFields = null) {
        if(is_null($viewFields)) {
            $viewFields = [
                'CouponCode' => [
                    "id",
                    'coupon_id',
                    "code",
                    "uid",
                    "order_id",
                    "use_time",
                    "add_time",
                    '_type' => 'LEFT'
                ],
                'Coupon' => [
                    "name",
                    "money",
                    "rule",
                    "order_money",
                    "status",
                    "count",
                    "remark",
                    "start_time",
                    "end_time",
                    "add_time",
                    '_on' => "Coupon.id=CouponCode.coupon_id",
                    '_type' => 'LEFT'
                ],
                'User' => [
                    "username",
                    "aliasname",
                    "mobile",
                    '_on' => 'CouponCode.uid=User.uid',
                    '_type' => 'LEFT'
                ]
            ];
        }
        return $this->dynamicView ( $viewFields );
    }

    /**
     * 发放优惠劵
     * @param int $coupon_id 优惠劵id
     * @param array $uid 用户id
     * @param int $source 来源:0为后台发放,1为促销活动,2为本人领取
     * @param string $source_associate 来源关联
     * @return bool|int
     */
    public function issuing($coupon_id,array $uid,$source = 0,$source_associate = '',$editor='系统'){
        $coupon_code_model = M('CouponCode');
        /*$exist_uid = $coupon_code_model->where([
            'coupon_id'=>$coupon_id,
            'uid'=>['in',$uid]
        ])->getField('uid',true);
        if(!empty($exist_uid)) {
            $uid = array_diff($uid, $exist_uid);
        }*/
        if(!is_array($uid)) {
            $uid = explode(',',$uid);
        }
        $count_user = count($uid);
        $codes = $this->generateCode($count_user);

        //来源为后台发放
        if($source == 0) {
            $user = User::getInstance();
            $user_info = $user->getUser();
            $editor = $user_info['username'] . '::' . $user_info['uid'];
        }

        $data_all = [];
        $data = [
            'coupon_id'=>$coupon_id,
            'add_time'=>NOW_TIME,
            'source'=>$source,
            'source_associate'=>$source_associate,
            'editor'=>$editor
        ];
        $i = 0;
        foreach($uid as $v){
            $data['uid'] = $v;
            $data['code'] = $codes[$i];
            $data_all[] = $data;
            $i++;
        }
        $coupon_result = $coupon_code_model->addAll($data_all);
        if($coupon_result !== false){
            $this->updateCouponCount($coupon_id,$count_user);
            return $count_user;
        }
        return false;
    }

    /**
     * 领取优惠劵
     * @param int $coupon_id 优惠劵id
     * @param int $uid 用户id
     * @return bool|int
     */
    public function receive($coupon_id,$uid){
        
		$coupon = $this->field(true)->where(['id'=>$coupon_id])->find();
        if(empty($coupon) || ($coupon['grant_rule'] != 2) || ($coupon['count'] >= $coupon['all_count'])){
            return false;
        }

        $coupon_code_model = M('CouponCode');
        $exist_count = $coupon_code_model->where([
            'coupon_id'=>$coupon_id,
            'uid'=>$uid
        ])->count();

        $grant_num = 1;
        if($exist_count+$grant_num > $coupon['limit_count']){//超过限领数量
            return false;
        }

        $codes = $this->generateCode($grant_num);

        $data_all = [];
        $data = [
            'coupon_id'=>$coupon_id,
            'add_time'=>NOW_TIME,
            'source'=>2
        ];

        for($i=0;$i<$grant_num;$i++){
            $data['uid'] = $uid;
            $data['code'] = $codes[$i];
            $data_all[] = $data;
        }

        $coupon_result = $coupon_code_model->addAll($data_all);
        if($coupon_result !== false){
            $this->updateCouponCount($coupon_id,$grant_num);
            return $grant_num;
        }
        return false;
    }

    /**
     * 更新优惠劵数量
     * @param int $coupon_id 优惠劵id
     * @param int $count 数量
     * @return bool
     */
    public function updateCouponCount($coupon_id,$count){
        return $this->where(['id'=>$coupon_id])->setField('count',array('exp','count+'.$count));
    }

    /**
     * 生成优惠劵码
     * @param int $num 个数
     * @return array
     */
    public function generateCode($num = 1){
        if(empty($num)){
            return [];
        }
        $codes = [];
        for($i=0;$i<$num;$i++){
            $rand_str = rand_string(9,2,'012345678901234567890123456789');
            if(!in_array($rand_str,$codes)) {
                $codes[] = $rand_str;
            }
        }
        $exist_codes = M('CouponCode')->where(['code'=>['in',$codes]])->getField('code',true);
        if(!empty($exist_codes)) {
            $codes = array_diff($codes, $exist_codes);
            $codes = array_merge($codes,$this->generateCode(count($exist_codes)));
        }
        return $codes;
    }

    /**
     * 验证优惠劵是否可用
     * @param string $id 优惠劵
     * @param int $order_amount 订单金额
     * @return array $goods_ids_arr 商品id数组
     * @return bool|array
     */
    public function verifyHasUse($id,$order_amount = 0,$goods_ids_arr){
        $where = [
            'id' => $id,
            'use_time'=>['eq',0],
            'status'=>1,
            'end_time'=>['gt',NOW_TIME],
            'start_time'=>['lt',NOW_TIME]
        ];
      /*   $where['_complex'] = [
            'rule' => 1,
            '_complex' => [
                'rule' => 2,
                'order_money' => ['elt', $order_amount]
            ],
            '_logic' => 'or'
        ]; */

        $viewFields = [
            'Coupon' => [
                "name",
                "money",
                "rule",
                "order_money",
                "status",
                "count",
                "remark",
                "start_time",
                "end_time",
                "add_time",
                "goods_ids",
                '_type' => 'LEFT'
            ],
            'CouponCode' => [
                "id",
                'coupon_id',
                "code",
                "uid",
                "order_id",
                "use_time",
                "add_time",
                '_on' => "Coupon.id=CouponCode.coupon_id",
                '_type' => 'LEFT'
            ]
        ];
        $info = $this->viewModel($viewFields)->where($where)->find();
        $return = false;
        if($info['rule'] == 1){
        	$return = true;
        }else if($info['rule'] == 2 && $info['order_money'] <= $order_amount){
        	$return = true;
        }else if($info['rule'] == 3){
        	if(array_intersect(explode(',', $info['goods_ids']), $goods_ids_arr)){
        		$return = true;
        	}
        }
        return $return ? $info : $return;
    }

    /**
     * 获取可以使用的优惠劵列表
     * @param string $uid 用户id
     * @param int $order_amount 订单金额
     * @param array $goodsArr 商品数组
     */
    public function getUseLists($uid,$order_amount = 0,$goodsArr = []){
        $where = [
            'uid' => $uid,
            'use_time'=>['eq',0],
            'status'=>1,
            'end_time'=>['gt',NOW_TIME],
            'start_time'=>['lt',NOW_TIME]
        ];
/*         $where['_complex'] = [
            'rule' => 1,
            '_complex' => [
                'rule' => 2,
                'order_money' => ['elt', $order_amount]
            ],
            '_logic' => 'or'
        ]; */
        $viewFields = [
            'Coupon' => [
                "name",
                "money",
                "rule",
                "order_money",
                "status",
                "count",
                "remark",
                "start_time",
                "end_time",
                "add_time",
            	"goods_ids",	
                '_type' => 'LEFT'
            ],
            'CouponCode' => [
                "id",
                'coupon_id',
                "code",
                "uid",
                "order_id",
                "use_time",
                "add_time",
                '_on' => "Coupon.id=CouponCode.coupon_id",
                '_type' => 'LEFT'
            ]
        ];
        $coupon = $this->viewModel($viewFields)->where($where)->select();
/*         return array_map(function($info){
            $info['money'] = formatAmount($info['money']);
            return $info;
        },$coupon); */
        foreach ($coupon as $k => &$info){
        	if($info['rule'] == 2 && $info['order_money'] <= $order_amount){
        		//该代金券可用 
        	}else if($info['rule'] == 3 && array_intersect(explode(',',$info['goods_ids']),$goodsArr )){
        		//该代金券可用
        	}else if($info['rule'] == 1){
        		//该代金券可用
        	}else{
        		//剔除该代金券
        		unset($coupon[$k]);
        		continue;
        	}
        	$info['money'] = formatAmount($info['money']);
        }
        return $coupon;
    }

    /**
     * 使用优惠劵
     * @param int $id 优惠劵id
     * @param int $order_id 订单id
     * @return bool
     */
    public function useCoupon($id,$order_id){
        return M('CouponCode')->where(['id'=>$id])->data([
            'order_id'=>$order_id,
            'use_time'=>NOW_TIME
        ])->save();
    }
    
    /**
     * 获取用户优惠劵列表
     * @param  int $uid 用户id
     * @return array 
     */
    public function getUserCoupon($uid){
    	$model = $this->viewModel();
    	$coupon = $model->where ( [ 
				'uid' => $uid,
				/* 'status' => 1,
				'start_time' => [ 
						'lt',
						NOW_TIME 
				]  */
		] )->select ();
    	return $coupon;
    }

    /**
	* 获取发放优惠劵的张数
	* @param  string $coupon_id 优惠券id
	* @return string
	*/
	public function getCounts($coupon_id){
		if($coupon_id){
			return M('Coupon_code')->where(['coupon_id'=>$coupon_id])->count();
		}
		return 0;
    }
}