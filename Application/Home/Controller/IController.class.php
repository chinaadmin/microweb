<?php
/**
 * 用户中心
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
use Think\Verify;
class IController extends HomeBaseController{
    protected $noLoginAction = ['verifyemail', 'checkemailsuccess', 'checkemailexpire', 'changeemail', 'newemailcheck', 'genreateverify', 'checkverify', 'changeemailsending', 'ifexist'];
    private $verifyid = ['bind_email_verify', 'check_email_verify', 'change_email_verify'];

    //保证用记登入
    public function _initialize(){
        parent::_initialize();
        if (in_array(ACTION_NAME, $this->noLoginAction)) {//邮件绑定方法
            return;
        }
        if (!$this->uid) {
            redirect(U('Passport/login'));
        }
    }

    /**
     * 用户选中样式
     */
    private function userSelCurrent($select = 'baseinfo'){
        $this->assign('userSelCurrent',$select);
    }

    /**
     * 用户安全选中样式
     */
    private function  usersafeCurrent($select = 'usersafe'){
        $this->userSelCurrent($select);
    }

    /**
     * 个人中心
     */
    public function index(){
        $this->userSelCurrent('baseinfo');

        $this->assign('if_check_mobile',$this->ifCheckMobile() ? 1 : 0);
        $this->assign('if_check_email',$this->ifCheckEmail() ? 1 : 0);

        $this->assign('order_to_paid_count',$this->order_count(1));
        $this->assign('order_inbound_count',$this->order_count(2));

        $where = array(
            "Refund.delete_time"=>0,
            "Refund.refund_uid"=>$this->user['uid'],
            'refund_status' =>1
        );
        $this->assign('refund_count', D('Home/Refund')->viewModel()->where($where)->count());

        //订单列表
        $where = [
            'uid'=>$this->uid,
            'delete_time'=>['eq',0]
        ];
        $order_model = D('Admin/Order');
        $order_lists = $order_model->where($where)->order('add_time desc')->limit(5)->select();
        //$order_lists = $this->lists ($order_model, $where ,'add_time desc');
        if($order_lists){
            $order_lists = $order_model->formatList($order_lists);
            $order_lists = array_map(function($info) {
                foreach ($info['goods'] as &$v){
                    if($v['refund_status']){ //已经申请了退款/退货
                        $refund = M('refund')->where([
                            'order_id' => $info['order_id'],
                            'rec_id'=>$v['rec_id']
                        ])->order('refund_time desc')->field('refund_id,refund_status')->find();
                        $v['refund_real_status'] = $refund['refund_status'];
                        $v['refund_id'] = $refund['refund_id'];
                    }
                }
                return D('Home/Order')->formatStatus($info);
            },$order_lists);
        }
        $this->assign('order_lists',$order_lists);
        $this->display();
    }

    /**
     * 数量
     */
    private function order_count($type = 0){
        $type = $type ? $type : I('get.type',0);
        $where = [
            'uid'=>$this->uid,
            'delete_time'=>['eq',0]
        ];
        switch($type){
            case 0://全部
                break;
            case 1://未支付
                $where['pay_status'] = 0;
                $where['status'] = ['in',[0,1]];
                break;
            case 2://待收货
                $where['status'] = 1;
                break;
            case 3://已完成
                $where['status'] = 6;
                break;
            case 4://已取消
                $where['status'] = 2;
                break;
        }
        return D('Admin/Order')->where($where)->count();
    }

    /**
     * 用户中心-个人资料 表单验证
     * @package string $type 验证类型
     */
    public function baseinfoCheck(){
        $type = I('type', 'aliasname', 'trim');
        if ($type == 'aliasname') {
            $nickname = I('nickname', '', 'trim');
            $where['uid'] = ['not in', $this->user['uid']];
            $where['aliasname'] = $nickname;
            $count = M('user')->where($where)->count();
            echo !$count ? 'true' : 0;
        } else if ($type == 'username') {
            $username = I('username', '', 'trim');
            $where['uid'] = ['not in', $this->user['uid']];
            $where['username'] = $username;
            $count = M('user')->where($where)->count();
            echo !$count ? 'true' : 0;
        }
    }

    /**
     * 个人资料
     */
    public function baseinfo(){
        $this->userSelCurrent('baseinfo');
        $uid = $this->uid;
        $where = array('uid' => $uid);
        if (IS_POST) {
            $birthday = I('post.u_year', 0, 'intval') . "-" . I('post.u_month', '0', 'intval') . "-" . I('post.u_day', 0, 'intval');
            //跟新User主表
            $user = array(
                'aliasname' => I('post.nickname', ''),
                'real_name' => I('post.mepo_name', ''),
                'sex' => I('post.mepo_sex', 1)
            );
            $username = I('name', '', 'trim');
            if (I('if_modify_username', 0, 'int') && $this->user['username'] != $username) {
                $user['is_modify_username'] = 1;
                $user['username'] = $username;
            }
            $result = D('User/User')->setData($where, $user);
            //更新附表
            $data = array(
                'uid' => $uid,
                'UserProfile' => array(
                    'user_birthday' => strtotime($birthday)
                ),
                'UserAddress' => array(
                    'user_provice' => I('post.provice_id', ''),
                    'user_city' => I('post.city_id', ''),
                    'user_county' => I('post.county_id', ''),
                    'user_town' => I('post.town_id', ''),
                    'user_localtion' => trim(I('post.private', ''), "请选择") . trim(I('post.city', ''), "请选择") . trim(I('post.county', ''), "请选择") . trim(I('post.town', ''), "请选择"),
                    'user_detail_address' => I('post.address', ''),
                    'type' => 1
                )
            );
            $id = D('User/User')->userRelation()->where($where)->save($data);
            if ($id === false) {
                $result->setCode($result->ERROR_CODE);
            }
            $this->ajaxReturn($result->toArray());
        }
        //获取用户信息
        $viewTable = array(
            'User', 'UserProfile', 'UserAddress'
        );
        $info = D('User/User')->userView($viewTable)->where($where)->find();
        $info['birthday_year'] = date("Y", $info['user_birthday']);
        $info['birthday_month'] = date("m", $info['user_birthday']);
        $info['birthday_day'] = date("d", $info['user_birthday']);
        $this->assign("info", $info);
        $this->assign("baseInfoClass", 'slect');
        $this->display();
    }

    /**
     * 账户安全
     */
    public function usersafe(){
        $this->phone_class = $this->ifCheckMobile() ? 'safe-icon-complete' : 'safe-icon-uncomplete';
        $this->email_class = $this->ifCheckEmail() ? 'safe-icon-complete' : 'safe-icon-uncomplete';
        $this->ifCheckMobile = $this->ifCheckMobile() ? 1 : 0;
        $this->ifCheckEmail = $this->ifCheckEmail() ? 1 : 0;
        $this->usersafeCurrent();
        $this->display();
    }

    /**
     * 修改密码页
     */
    public function passwordPage(){
        $this->usersafeCurrent();
        $this->display();
    }

    public function  checkPassword(){
        $password = I('password', '', 'trim');
        if ($this->get('user')['pass'] == getpass($password)) {
            echo 'true';
        } else {
            echo 0;
        }
    }

    /**
     * 修改密码 处理过程
     */
    public function  updatePassword(){
        $where['uid'] = $this->uid;
        $data['pass'] = I('newPassWord', '');
        $password = I('password', '');
        $m = D('User/User');
        if (!$m->checkPassWord($this->uid, $password)) {
            $this->ajaxReturn($this->result->set('PASSWORD_ERROR')->toArray());
        }
        $res = $m->setData($where, $data);
        $this->ajaxReturn($res->toArray());
    }

    /**
     * 手机验证并验证手机号
     */
    public function  phoneVerify(){
        $this->usersafeCurrent();
        $this->display();
    }

    /**
     * 发送验证码给用户验证手机号
     */
    public function sendCode($id = 'sendCode'){
        $tel_code = to_guid_string(session_id() . $this->uid . '_' . $id);
        $tel = I('post.telephone');
        if (!$tel) {
            $this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
        }
        //发送短信
        $mess = new \Common\Org\Util\MobileMessage();
        $arr['mobile_code'] = rand(100000, 999999);
        if ($mess->sendMessByTel($tel, $arr, 'bind_mobile') == true) {//发送成功
            S($tel_code, $tel . '_' . $arr['mobile_code'], C('JT_CONFIG_WEB_SORT_MESSAGE_VAILD') * 60);
            $this->ajaxReturn($this->result->set('MESSAGE_CODE_SEND')->toArray());
        }
        $this->ajaxReturn($this->result->set('SEND_MESSAGE_FAIL')->toArray());
    }

    public function checkCode($id = 'sendCode'){
        $tel = I('get.telephone');
        $checkCode = I('post.checkCode', 0, 'int');
        if (!$tel || !$checkCode) {
            $this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
        }
        $code = S(to_guid_string(session_id() . $this->uid . "_" . $id));
        if (!$code) {//验证码已过期
            $this->ajaxReturn($this->result->set('MESSAGE_CODE_EXPIRE')->toArray());
        } else if ($code !== $tel . '_' . $checkCode) {//验证码不正确
            $this->ajaxReturn($this->result->set('MESSAGE_CODE_MATCHERROR')->toArray());
        }
        //增加手机号到用户表中
        $where['uid'] = $this->uid;
        $data['mobile'] = $tel;
        $data['mobile_status'] = 1;
        D('User/User')->setData($where, $data);
        $this->ajaxReturn($this->result->set('TEL_MATCH_SUCCESS')->toArray());
    }

    /**
     * 是否已经验证过手机号
     * @return boolean
     */
    private function ifCheckMobile(){
        $mobile = D('User/User')->where(['uid' => $this->uid])->getField('mobile_status');
        return $mobile ? true : false;
    }

    /**
     * 是否已经验证过手机号
     * @return boolean
     */
    private function ifCheckEmail(){
        $email_status = D('User/User')->where(['uid' => $this->uid])->getField('email_status');
        return $email_status ? true : false;
    }

    /**
     * 手机验证成功页
     */
    public function phoneSucc(){
        $this->usersafeCurrent();
        $this->display();
    }

    /**
     * 验证邮箱
     */
    public function checkEmail(){
        $this->usersafeCurrent();
        //判断处于验证哪一步 选择不同的显示模板
        $stepNum = $this->bindEmailStep();
        $them = '';
        if ($stepNum == 2) {
            $this->loginEmail = 'http://mail.' . explode('@', $this->sendEmail)[1];
            $them = 'checkemailsending';
        } elseif ($stepNum == 3) {
            $them = 'checkemailsuccess';
        }
        $this->display($them);
    }

    /**
     * 判断处于绑定邮件的哪一步
     * @return number
     */
    private function bindEmailStep(){
        $find = D('User/User')->where(['uid' => $this->uid])->find();
        $email_status = $find['email_status'];
        $where['add_time'] = array('egt', strtotime('-1 hours'));
        $where['uid'] = $this->uid;
        $where['type'] = 4;
        $count = M('code')->where($where)->count();//有效验证信息
        if ($email_status) {//已经通过了
            return 3;
        } else if (!$email_status && !$count) { //没有发送邮件
            return 1;//第一步 未验证
        } else if (!$email_status && $count) {
            $this->sendEmail = $find['email'];
            return 2;//已发送过邮件且至少一次未过期
        }
    }

    //绑定邮箱地址-保存邮件地址
    public function  bindEmail(){
        $verifyCode = I('verifyCode', 0, 'trim');
        $email = I('email', '', 'trim');
        $vertify = new Verify ();
        if ($vertify->check($verifyCode, $this->verifyid[0]) === false) {//验证码不匹配
            $this->ajaxReturn($this->result->set('VERIFICATION_CODE_ERROR')->toArray());
        }
        //增加邮件到用户表中 未验证
        $where['uid'] = $this->uid;
        $data['email'] = $email;
        $data['email_status'] = 0;
        $res = D('User/User')->setData($where, $data);

        //保存code信息
        $codeData['code'] = rand_string(10, 5);
        $codeData['type'] = 4;
        $codeData['add_time'] = NOW_TIME;
        $codeData['uid'] = $this->uid;
        $codeData['extend'] = '接收邮件地址  ' . $email;
        $codeData['token'] = md5(NOW_TIME . $codeData['code'] . $this->uid);
        $res = M('code')->add($codeData);

        //发送邮件--------------------------------------
        //模板变量
        $tempArr['userName'] = $this->user['aliasname'];
        $tempArr['checkEmailUrl'] = U("verifyEmail", ['token' => urlencode($codeData['token'])], true, true);
        $emailContent = getTempContent('email_template', 2, $tempArr);
        $res = sendMail($email, $tempArr['userName'], '邮件绑定验证(必好货)', $emailContent);
        //发送邮件--------------------------------------
        $this->ajaxReturn($this->result->success()->toArray());
    }

    //绑定邮箱地址-验证邮件地址
    public function verifyEmail(){
        //已绑定 ...
        $token = I('token', '', 'urldecode');
        $co_model = M('Code');
        $where = array();
        $where['token'] = $token;//$token
        $where['type'] = 4;
        $where['add_time'] = array('egt', strtotime('-1 hours'));
        $info = $co_model->field(true)->where($where)->find();
        $them = '';
        if (!empty($info)) {//删除绑定记录
            $where = array();
            $where['type'] = $info['type'];
            $where['uid'] = $info['uid'];
            $co_model->where($where)->delete();
            //绑定邮件修改验证状态
            $res = D('User/User')->setData(['uid' => $info['uid']], ['email_status' => 1]);
            $this->usersafeCurrent();
            $them = 'checkemailsuccess';//成功页
        } else {
            $them = 'checkemailexpire';//过期页
        }
        $this->display($them);//验证过期 或失效
    }

    /**
     * 产生验证码
     */
    public function genreateVerify($type = 0){
        $type = intval($type);
        if (!$type) {
            $verifyId = $this->verifyid[0];
        } else {
            $verifyId = $this->verifyid[$type];
        }
        $verify_config = [
            'imageH' => 40,
            'imageW' => 100,
            'fontSize' => 12,
            'type' => 'gif',
            'length' => 4,
            'useNoise' => false,
            'useCurve' => false
        ];
        $vertify = new Verify ($verify_config);
        $vertify->entry($verifyId);
    }

    public function  opinion(){
        if (IS_AJAX) {
            $data['su_type'] = implode(',', I('type', '', 'trim'));
            $data['su_content'] = I('content', '', 'trim');
            $data['su_email'] = I('email', '', 'trim');
            $data['su_add_time'] = NOW_TIME;
            $res = M('suggestion')->add($data);
            $action = $res ? 'success' : 'error';
            $msg = $res ? '提交成功' : '提交失败';
            $this->ajaxReturn($this->result->$action()->setMsg($msg)->toArray());
        }
        $this->userSelCurrent('opinion');
        $this->display();
    }

    /**
     * 修改邮箱
     */
    public function editEmail(){
        if (IS_AJAX) {//邮箱验证
            //记录发送码
            $token = D('Code')->saveVerifyCode('', 7, json_encode($this->user));
            //发送邮件--------------------------------------
            //模板变量
            $tempArr['userName'] = $this->user['aliasname'];
            $tempArr['changeEmailUrl'] = U("changeEmail", ['token' => urlencode($token)], true, true);
            $emailContent = getTempContent('edit_email', 2, $tempArr);
            $res = sendMail($this->user['email'], $tempArr['userName'], '原有邮件地址验证(必好货)', $emailContent);
            //发送邮件--------------------------------------
            $action = $res === true ? 'success' : 'error';
            $this->ajaxReturn($this->result->$action()->toArray());
        }
        $this->usersafeCurrent();
        $this->email = hideStr($this->user['email'], 'email');
        $this->display();
    }

    //更改邮件页
    public function changeEmail(){
        if (IS_AJAX) {
            $newEmail = I('email', '', 'trim');
            if (!$newEmail) {
                $this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
            }
            $info = json_decode(session('tempUser')['extend'], true);
            $info = array_merge($info, ['email' => $newEmail]);
            //记录发送码 修改的邮箱
            $token = D('Code')->saveVerifyCode('', 7, json_encode($info));
            //发送邮件--------------------------------------
            //模板变量
            $tempArr['userName'] = $info['aliasname'];
            $tempArr['changeEmailUrl'] = U("newEmailCheck", ['token' => urlencode($token)], true, true);
            $emailContent = getTempContent('edit_email', 2, $tempArr);
            $res = sendMail($info['email'], $tempArr['userName'], '新邮件地址验证(必好货)', $emailContent);
            //发送邮件--------------------------------------
            $hideEmail = hideStr($newEmail, 'email');
            $url = U('changeEmailSending', ['email' => urlencode($hideEmail)], true, true);
            session('tempUser', null);
            if ($res) {
                $this->ajaxReturn($this->result->success()->setMsg($url)->toArray());
            }
            $this->ajaxReturn($this->result->error()->toArray());
        }
        $this->usersafeCurrent();
        $token = I('token', '', 'urldecode');
        $check = D('Code')->verifyCode($token, 3600);
        $them = '';
        if (!$check) {//过期或已经验证
            $them = 'checkemailexpire';
        } else {
            //生成用户信息
            session('tempUser', $check);
        }
        $this->display($them);
    }

    //提示新邮件验证成功页
    public function  changeEmailSending(){
        $this->usersafeCurrent();
        $this->display();
    }

    /**
     * 验证更改后的邮件
     */
    public function newEmailCheck(){
        $token = I('token', '', 'urldecode');
        $check = D('Code')->verifyCode($token, 3600, true);
        $them = 'newEmailCheckSuc';//成功
        if (!$check) {//过期或已经验证
            $them = 'checkemailexpire';
        } else {
            //验证通过修改邮件地址并通过
            $info = json_decode($check['extend'], true);
            $where['uid'] = $info['uid'];
            $data['email'] = trim($info['email']);
            D('User/User')->setData($where, $data);
        }
        //生成用户信息
        $this->display($them);
    }

    /**
     *
     * @param number $type 验证码类别
     * @param unknown $reutrnType 返回类型 1: echo  2:ajax 返回
     * @return Ambigous <number, string>
     */
    public function  checkVerify($type = 1, $reutrnType = 1){
        $verifyCode = I('verifyCode', 0, 'trim');
        $vertify = new Verify ();
        $return = 0;
        if ($vertify->check($verifyCode, $this->verifyid[$type]) === false) {//验证码不匹配
            $return = 0;
        } else {
            $return = 'true';
        }
        if ($reutrnType == 1) {
            echo $return;
        } else {
            return $return;
        }
    }

    /**
     * 修改手机
     */
    public function editMobile(){
        if (IS_AJAX) {
            //发送手机验证码
            $_POST['telephone'] = $this->user['mobile'];
            $this->sendCode('editMobile');
        }
        $this->usersafeCurrent();
        $this->display();
    }

    public function checkOldMobile($id = 'editMobile'){
        $tel = I('post.mobile');
        $tel = $tel ? $tel : $this->user['mobile'];
        $checkCode = I('post.verifyCode', 0, 'int');
        if (!$tel || !$checkCode) {
            $this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
        }
        $code = S(to_guid_string(session_id() . $this->uid . "_" . $id));
        if (!$code || ($code !== $tel . '_' . $checkCode)) {//验证码已过期
            echo 0;
            exit;
        }
        echo 'true';
    }

    public function  doEditMobile(){
        $url = U('addNewMobile', '', true, true);
        $this->ajaxReturn($this->result->success()->setMsg($url)->toArray());
    }

    public function addNewMobile(){
        if (IS_AJAX) {
            //发送手机验证码
            $_POST['telephone'] = I('mobile');
            $this->sendCode('addNewMobile');
        }
        $this->usersafeCurrent();
        $this->display();
    }

    public function  doAddNewMobile(){
        //修改手机号
        D('User/User')->where(['uid' => $this->user['uid']])->save(['mobile' => I('mobile'), 'mobile_status' => 1]);
        $url = U('addNewMobileSuc', '', true, true);
        $this->ajaxReturn($this->result->success()->setMsg($url)->toArray());
    }

    public function addNewMobileSuc(){
        $this->usersafeCurrent();
        $this->display();
    }

    /**
     *查询邮件 手机是否可用
     * @package int $type 1:邮件  2：手机  3:用户名
     * @package string $email 邮件地址
     * @package string $mobile 手机号
     * @return void
     */
    public function ifExist(){
        $email = I('email', '', 'trim');
        $mobile = I('mobile', '', 'trim');
        $username = I('username', '', 'trim');
        if ($email) {
            $type = 1;
            $val = $email;
        } else if ($mobile) {
            $type = 2;
            $val = $mobile;
        } else {
            $type = 3;
            $val = $username;
        }
        $res = D('User/User')->ifExist($type, $val);
        if ($res) {
            echo 'true';
        } else {
            echo 0;
        }
    }

    /**
     * 我的优惠劵
     */
    public function coupon(){
        $this->userSelCurrent('coupon');
        $coupon_model = D('User/Coupon');
        $where = [
            'uid'=>$this->uid
        ];
        $type = I('type', 1, 'intval');
        switch($type){
            case 2://已使用
                $where['use_time'] = ['neq',0];
                break;
            case 3://已过期
                $where['end_time']=['lt',NOW_TIME];
                $where['use_time'] = 0;
                break;
            case 1://未使用
            default:
                $where['end_time']=['gt',NOW_TIME];
                //$where['start_time']=['lt',NOW_TIME];
                $where['use_time'] = 0;
        }
        $coupon_lists = $this->lists ($coupon_model->viewModel(), $where ,'end_time asc');
        $this->assign('coupon_lists',$coupon_lists);
        $this->assign('type',$type);
        $this->assign('coupon_type',[
            1=>$this->coupon_count(1),
            2=>$this->coupon_count(2),
            3=>$this->coupon_count(3)
        ]);
        $this->display();
    }

    /**
     * 数量
     * @param int $type 类型：1为未使用，2为已使用，3为已过期
     */
    public function coupon_count($type = 0){
        $type = $type ? $type : I('get.type',1);
        $where = [
            'uid'=>$this->uid
        ];
        switch($type){
            case 2://已使用
                $where['use_time'] = ['neq',0];
                break;
            case 3://已过期
                $where['end_time']=['lt',NOW_TIME];
                $where['use_time'] = 0;
                break;
            case 1://未使用
            default:
                $where['end_time']=['gt',NOW_TIME];
                //$where['start_time']=['lt',NOW_TIME];
                $where['use_time'] = 0;
        }
        $count = D('User/Coupon')->viewModel()->where($where)->count();
        return $count;
    }

    /**
     * 积分
     */
    public function integral(){
        $this->userSelCurrent('integral');
        $credits = D('User/Credits')->getCredits($this->uid,1);
        $this->assign('credits',intval($credits));
        $account_log_model = M('AccountLog');
        $where = [
            'uid'=>$this->uid,
            'credits_type'=>1
        ];
        //搜索类型，0为全部，1为收入，2为消耗
        $type = I('type', 0, 'intval');
        switch($type){
            case 1:
                $where['credits'] = ['egt',0];
                break;
            case 2:
                $where['credits'] = ['lt',0];
                break;
        }
        $this->assign('type',$type);
        $account_log_lists = $this->lists ($account_log_model, $where ,'add_time desc');
        $this->assign('account_log_lists',$account_log_lists);
        $this->display();
    }

    /**
     * 我要推荐
     */
    public function recommend(){
        $this->userSelCurrent('recommend');
        $share_url = U('Passport/reg',['invitation'=>$this->uid],true,true);
        $this->assign('share_url',$share_url);

        $viewFields = [
            'UserRecommend' => [
                "id",
                "uid",
                "recommend",
                "add_time",
                '_type' => 'LEFT'
            ],
            'User' => [
                "username",
                "aliasname",
                "mobile",
                '_on' => 'UserRecommend.uid=User.uid',
                '_type' => 'LEFT'
            ],
        ];
        $user_recommend = D('User/User')->dynamicView ( $viewFields )->where(['recommend'=>$this->uid])->select();
        $this->assign('user_recommend',$user_recommend);
        $this->display();
    }

    /**
     * 收货地址
     */
    public function recaddress(){
        $this->userSelCurrent('recaddress');
        $this->display();
    }
}