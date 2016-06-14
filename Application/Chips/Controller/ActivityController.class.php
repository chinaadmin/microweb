<?php
/**
 * 活动
 * @author yt
 * @date 2016-03-04
 */
namespace Chips\Controller;

use Api\Controller\ApiBaseController;

class ActivityController extends ApiBaseController
{
    public function _initialize(){
        parent::_initialize();
        //不需要验证token的接口方法，写入数组
        if (!in_array(strtoupper(ACTION_NAME), ['DETAIL'])){
            $this->authToken();
        }
    }
    
    /*
     * 获取翁翁翁活动
     */
    public function getWong() {
        //获取嗡活动数据
        $awardId = C('WONG_CONFIG.awardPlanID');
        
        $cd_id   = C('WONG_CONFIG.cd_id');
        //初始化返回参数
        $iniData = [
            'isAwardStart'  =>0,  //-1活动已经结束，0活动未开始，1活动进行中
            'isAllowUser'   =>0,  //0没有资格参加抽奖活动，1有资格参加抽奖活动
            'gameNumber'    =>0,  //游戏可进行轮数
            'gameTime'      =>10, //每轮游戏的时长
            'fkId'          =>C('WONG_CONFIG.fkId'), //众筹ID
        ];
        $awardModel = D('Admin/Award');
        $plan = $awardModel->getWongAward($awardId);
        //如果未找到相关活动 返回初始化参数
        if (!$plan){
            $this->ajaxReturn($this->result->content($iniData)->success());
        }
        $iniData['isAwardStart'] = getTimePeriod($plan['ap_start_time'], $plan['ap_end_time']);
        $number = $awardModel->getWongAllowUser($this->user_id,$cd_id);
        $iniData['isAllowUser']  = $number ? 1 : 0;
        $iniData['gameNumber']   = $number;
        $iniData['gameTime']   = $plan['ap_execute_time'];
        
        $this->ajaxReturn($this->result->content($iniData)->success());
    }
    
    /*
     * 翁翁翁抽奖
     */
    public function award() {
        $awardId = C('WONG_CONFIG.awardPlanID');
        $click_times = I('clickTimes',0,'intval');  //点击次数
        $user_id = $this->user_id;
        $awardModel = D('Admin/Award');
        //初始化输出结果
        $iniResult = [
            'priceNumber' => -1,
            'priceTotal'  => 0,
            'gameNumber'  => 0, //游戏可以玩的次数
            'isAwardStart' => 0, //-1活动已经结束，0活动未开始，1活动进行中
            'isAllowUser' => 0, //0没有资格参加抽奖活动，1有资格参加抽奖活动
        ];
        $plan = $awardModel->getWongAward($awardId);
        if (!$plan){
            $this->ajaxReturn($this->result->content($iniResult)->success());
        }
        $iniResult['isAwardStart'] = getTimePeriod($plan['ap_start_time'], $plan['ap_end_time']);
        $cd_id   = C('WONG_CONFIG.cd_id');
        $number = $awardModel->getWongAllowUser($this->user_id,$cd_id);
        $iniResult['isAllowUser']  = $number ? 1 : 0;
        if ($iniResult['isAwardStart'] < 1 || !$iniResult['isAllowUser']){
            $this->ajaxReturn($this->result->content($iniResult)->success());
        }
        $detailId = $awardModel->draw($user_id,$awardId,$click_times);
        if (!$detailId){
            $this->ajaxReturn($this->result->content($iniResult)->success());
        }
        $award_amount = $awardModel->getAwardAmount($detailId,$user_id);
        if ($award_amount === false){
            $this->ajaxReturn($this->result->content($iniResult)->success());
        }
        $iniResult['priceNumber'] = $award_amount['priceNumber'];
        $iniResult['priceTotal'] = $award_amount['priceTotal'];
        $iniResult['gameNumber'] = $award_amount['gameNumber'];
        //输出参数
        $this->ajaxReturn($this->result->content($iniResult)->success());
    }
    
    /*
     * 翁翁翁我的奖品记录列表
     */
    public function myAward() {
        $awardId = C('WONG_CONFIG.awardPlanID');
        $user_id = $this->user_id;
        $awardModel = D('Admin/Award');
        //初始化中奖返回数据
        $iniReturn = [
            'price' => []
        ];
        $iniReturn['price'] = $awardModel->getMyAward($user_id,$awardId);
        //输出参数
        $this->ajaxReturn($this->result->content($iniReturn)->success());
    }
    
    /*
     * 翁翁翁系统中奖记录列表
     */
    public function systemAward() {
        $pageNum = (int)I('request.pageNum',20);
        $awardId = C('WONG_CONFIG.awardPlanID');
        $awardModel = D('Admin/Award');
        //初始化中奖返回数据
        $iniReturn = [
            'price' => []
        ];
        $iniReturn['price'] = $awardModel->getSystemAward($awardId,$pageNum);
        //输出参数
        $this->ajaxReturn($this->result->content($iniReturn)->success());
    }
    
    /*
     * 翁翁翁获取助力码
     */
    public function getCode() {
        $awardModel = D('Admin/Award');
        $code = $awardModel->getHelpCode($this->user_id);
        //输出参数
        $this->ajaxReturn($this->result->content(['code'=>$code,'wxId'=>C('WX_ORIGIN_ID')])->success());
    }
    
    /*
     * 翁翁翁获取给好友加蜜助力
     */
    public function getFriendDetail() {
        $code = I('code','','trim');
        if (empty($code)){
            $this->ajaxReturn($this->result->set('CODE_NOT_FOUND'));
        }
        $awardModel = D('Admin/Award');
        $result = $awardModel->getFriendDetail($code,$this->user_id);
        if (!is_array($result)){
            $msg = '';
            switch ($result){
                case -1:
                    $msg = 'MUST_REGISTER_ACT_START'; //必须活动开始后注册用户才能注力
                    break;
                case -2:
                    $msg = 'HAS_HELP_FRIEND';  //只能助力一次
                    break;
                case -3:
                    $msg = 'HELP_FULL';  //加蜜已满
                    break;
                case -4:
                    $msg = 'CODE_NOT_EXISTS'; //助力代码不存在
                    break;
                case -5:
                    $msg = 'FRIEND_NOT_BUY'; //您的好友还没有获得抽奖资格，提醒好友看清活动说明哦！
                    break;
                case -6:
                    $msg = 'NOT_HELP_SELF';  //无法给自己助力
                    break;
                default:
                    $msg = 'SYSTEM_BUSY';
            }
            $this->ajaxReturn($this->result->set($msg));
        }
        //输出参数
        $this->ajaxReturn($this->result->content($result)->success());
    }
    
    /*
     * 翁翁翁给好友助力
     */
    public function friendCode() {
        $code = I('code','','trim');
        if (empty($code)){
            $this->ajaxReturn($this->result->set('CODE_NOT_FOUND'));
        }
        $awardModel = D('Admin/Award');
        $result = $awardModel->doFriendHelp($code,$this->user_id);
        if (!is_array($result)){
            $msg = '';
            switch ($result){
                case -1:
                    $msg = 'NOT_HELP_SELF';  //无法给自己助力
                    break;
                case -2:
                    $msg = 'HELP_FAILURE';   //助力失败
                    break;
                case -3:
                    $msg = 'CODE_NOT_EXISTS';  //助力码不存在
                    break;
                case -4:
                    $msg = 'HAS_HELP_FRIEND'; //每个用户只能助力一次
                    break;
                case -5:
                    $msg = 'FRIEND_NOT_BUY'; //您的好友还没有获得抽奖资格，提醒好友看清活动说明哦！
                    break;
                case -6:
                    $msg = 'MUST_REGISTER_ACT_START';//必须活动开始后注册用户才能注力
                    break;
                case -7:
                    $msg = 'HELP_FULL';
                    break;
                default:
                    $msg = 'SYSTEM_BUSY';
            }
            $this->ajaxReturn($this->result->set($msg));
        }
        //输出参数
        $this->ajaxReturn($this->result->content($result)->success());
    }
    
    /*
     * 翁翁翁活动说明
     */
    public function detail() {
        $ap_id = C('WONG_CONFIG.awardPlanID');
        $iniReturn = [
            'content' => []
        ];
        $iniReturn['content'] = D('Admin/Award')->getWongDetail($ap_id);
        //输出参数
        $this->ajaxReturn($this->result->content($iniReturn)->success());
    }
    
    /*
     * 是否有抽奖资格
     */
    public function isDraw(){
        $is_Draw = D('Admin/Award')->getDrawQualifi($this->user_id);
        $this->ajaxReturn($this->result->content(['isDraw'=>$is_Draw])->success());
    }

    /**
     * 十周年庆 活动抽奖资格
     *    传入参数
     *    <code>
     *      token 登陆令牌  t-:d71212f7b868d0d8b5c20f95f009a12a
            token 登陆令牌  tp:a907eb6a91ed972cd106d88b41903ced
     *    </code>
     */
    public function tenYear(){
        $return_data = D('Admin/Award')->getTenYearAllow($this->user_id);
        $this->ajaxReturn($this->result->content($return_data)->success());
    }

    /**
     * 十周年庆 抽奖接口
     *      token 登陆令牌  t-:d71212f7b868d0d8b5c20f95f009a12a
     *      token 登陆令牌  tp:a907eb6a91ed972cd106d88b41903ced
     */
    public function tenYearAward(){
        $info = C('WONG_CONFIG');
        $ap_id = $info['tenPlanId'];
        $fkId = $info['fkId'];

        $crowsOrder = M('Crowdfunding_detail')->join('__CROWDFUNDING_ORDER__ ON __CROWDFUNDING_DETAIL__.cd_id=__CROWDFUNDING_ORDER__.fk_cd_id')->where(array('cor_uid'=>$this->user_id,'cd_id'=>$info['cd_id'],'cor_pay_status'=>2))->select();  //查询用户是否存在‘蜂蜜方案二’众筹的已支付的订单
        $plan = M('Award_plan')->where(['ap_id'=>$ap_id])->find();

        if(!empty($plan)){
            if(NOW_TIME>$plan['ap_end_time']){
                $status=-1; //已结束
            }elseif(NOW_TIME<$plan['ap_start_time']){
                $status=0;  //未开始
            }else{
                $status=1;  //进行中
            }

            if($plan['ap_is_using']==0){
                $status=0;
            }
        }else{
            $status=0;
        }

        $myRemainCount = M("Award_user")->where(array('au_uid'=>$this->user_id,'type'=>2))->find(); //判断用户是否还有抽奖次数

        if($myRemainCount['au_remain_count']==0){   //如果剩余次数为0，则提示他没有抽奖次数了
            // exit('剩余抽奖次数为0');
            $return_data = array(
                'isStart'=>$status,
                'isAllow'=>!empty($crowsOrder)?1:0,
                'isWin'=>0,
                'cash'=>0,
                'gameNumber'=>0,
                'tiket'=>0,
            );
            // dump($return_data);die;
            $this->ajaxReturn($this->result->content($return_data)->success());
            exit;
        }

        $return_data = D('Admin/Award')->getReturnData($this->user_id,$myRemainCount['au_remain_count'],$crowsOrder);
        // dump($return_data);die;
        $this->ajaxReturn($this->result->content($return_data)->success());
    }

    /**
     * 十周年庆 我的奖品记录
     *    token 登陆令牌   t-:d71212f7b868d0d8b5c20f95f009a12a
     *    token 登陆令牌   tp:a907eb6a91ed972cd106d88b41903ced
     */
    public function tenMyAward(){
        $return_data = array();
        $return_data = D('Admin/Award')->getTenYearRecodes($this->user_id);
        $this->ajaxReturn($this->result->content($return_data)->success());
    }

    public function test(){
        dump(C('JT_CONFIG_WEB_SORT_MESSAGE_VAILD'));
    }

    /**
     * 十周年庆活动说明接口
     * [description] 活动说明接口
     * Author CL
     * @return [type] [description]
     */
    public function tenYearDetail(){
        // $token = I('token');
        // $token = "6279814c7682491fd4870bc97ae3cc78&id=1";
        // $this->authToken($token);
        $id = I('request.id');
        $list = M("awardPlan")->field('ap_name as title, ap_remark as content')->find($id);
        $this->ajaxReturn($this->result->content(['content' => $list])->success());
    }
}