<?php
/**
 * 广告逻辑类
 * @author cwh
 * @date 2015-05-05
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
header('Content-Type:text/html;Charset=utf-8');

class AccountLogController extends AdminbaseController {

    //账户管理
    public function index() {
        $type = I('request.type',0);
        $operate_type = I('request.operate_type','');
        $this->assign('type',$type);
        $this->assign('operate_type',$operate_type);
        $credits_model = D('User/Credits');
        $type_lists = $credits_model->getType();
        $this->assign('type_lists',$type_lists);
        $operate_type_lists = $credits_model->getOperateType();
        $this->assign('operate_type_lists',$operate_type_lists);

        $credits_view = $credits_model->getLogView();
        $where = [
            'credits_type'=>$type
        ];
        if($operate_type!==''){
            $where['type']=$operate_type;
        }

        //搜索用户账号
        $username = I('request.username');
        if(!empty($username)){
            $condition = [
                'user.uid'=>$username,
                'username'=>['like','%'.$username.'%'],
                '_logic'=>'OR'
            ];
            $where['_complex'] = $condition;
        }
        $this->assign('username',$username);

        $lists = $this->lists($credits_view,$where,'add_time desc');
        $lists = array_map(function($info)use($operate_type_lists){
            $info['type_name'] = $operate_type_lists[$info['type']];
            return $info;
        },$lists);
        $this->assign('lists',$lists);
        $this->display();
    }
	
    public function userlog(){
    	if(I('from') == 'user_blacklist'){
    		$this->curent_menu = 'User/user_blacklist';
    	}else{
	        $this->curent_menu = 'User/index';
    	}
        $uid = I('request.uid',0);
        $this->assign('uid',$uid);
        $user = D('User/User')->field(true)->find($uid);
        $this->assign('user_info',$user);

        $type = I('request.type',0);
        $this->assign('type',$type);
        $credits_model = D('User/Credits');
        $type_lists = $credits_model->getType();
        $this->assign('type_lists',$type_lists);
        $operate_type = $credits_model->getOperateType();

        //积分
        $credits = $credits_model->getCredits($uid);
        $this->assign('credits',$credits);

        $where = [
            'credits_type'=>$type
        ];
        $where['uid'] = $uid;
        $lists = $this->lists(M('AccountLog'),$where,'add_time desc');
        $lists = array_map(function($info)use($operate_type){
            $info['type_name'] = $operate_type[$info['type']];
            return $info;
        },$lists);

        foreach($lists as $k=>$v){
            switch ($v['credits_type']) {
                case 1:
                    if(in_array($v['type'],['4','7'])){
                        $lists[$k]['remark']='订单'.$v['remark'].'支付成功，抵扣'.abs(intval($v['credits'])).'积分';
                    }elseif($v['type']==5){
                        $lists[$k]['remark']='订单'.$v['remark'].'退款成功，返回积分'.intval($v['credits']);
                    }elseif($v['type']==8){
                        $lists[$k]['remark']='订单'.$v['remark'].'支付成功，赠送积分'.intval($v['credits']);
                    }elseif($v['type']==10){
                        $lists[$k]['remark']='好友订单'.$v['remark'].'支付成功，赠送积分'.intval($v['credits']);
                    }
                    break;
                case 3:
                    if(in_array($v['type'],['4','7'])){
                        $lists[$k]['remark']='订单'.$v['remark'].'支付成功，赠送成长值'.intval($v['credits']).'点';
                    }
                    break;
                
                default:
                    break;
            }
        }

        // dump($lists);die;

        $this->assign('lists',$lists);
        $this->assign('credits_type',$type);
        $this->display();
    }

    public function add(){
    	if(I('from') == 'user_blacklist'){
    		$this->curent_menu = 'User/user_blacklist';
    	}else{
	        $this->curent_menu = 'User/index';
    	}
        $uid = I('request.uid',0);
        $this->assign('uid',$uid);
        $user = D('User/User')->field(true)->find($uid);
        $this->assign('user_info',$user);

        //积分
        $credits_model = D('User/Credits');
        $credits = $credits_model->getCredits($uid);
        $this->assign('credits',$credits);

        $type_lists = $credits_model->getType();
        $this->assign('type_lists',$type_lists);

        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $uid = I('request.uid');
        $credits = I('request.credits');
        $remark = I('request.remark');
        $change = I('request.change');
        $credits_model = D('User/Credits');
        $credits_model->startTrans();
        $credits_model->setOperateType(3);
        foreach($credits as $key=>$val){
            if(!empty($val)){
                $result = $credits_model->setCredits($uid,$val,$remark,$change[$key],$key);
                if(!$result->isSuccess()){
                    $credits_model->rollback();
                    $this->ajaxReturn($result->toArray());
                    break;
                }
            }
        }
        $credits_model->commit();
        $this->ajaxReturn($result->toArray());
    }
    
    public function coupon(){
    	$this->curent_menu = "user/index";
    	$uid = I("request.uid","");
    	$coupon = D("User/Coupon")->getUserCoupon($uid);
    	foreach($coupon as &$v){
    		if($v['use_time']){
    			$v['use_status'] = date('Y-m-d H:i:s',$v['use_time']);
    			continue;
    		}
    		if($v['status']!=1 || NOW_TIME>$v['end_time']){
    			$v['use_status'] = '已失效';
    		}else{ 
    			if(NOW_TIME<$v['start_time']){
    				$v['use_status'] = '未生效';
    			}else{
    				$v['use_status'] = '未使用';
    			}
    		}
    	}
    	$this->assign("coupon",$coupon);
    	$this->display();
    }

}