<?php
/**
 * 问卷
 * @author cwh
 * @date 2015-06-04
 */
namespace Home\Controller;
use Common\Controller\HomeBaseController;
class QuestController extends HomeBaseController{

	public function _initialize(){
		parent::_initialize();
	}

	public function index(){
		$this->display();
	}

    /**
     * 获取试题
     */
    public function getQuestion(){
        $question = D('Home/Quest')->getQuestion();
        $this->ajaxReturn($this->result->content($question)->success()->toArray());
    }

    public function user(){
        $name = I('post.name');
        $mobile = I('post.mobile');
        $result = $this->result;
        if(empty($name)){
            $this->ajaxReturn($result->error('真实姓名不能为空')->toArray());
        }
        if(empty($mobile)){
            $this->ajaxReturn($result->error('手机号码不能为空')->toArray());
        }else{
            if(!checkMobile($mobile)){
                $this->ajaxReturn($result->error('手机号格式错误')->toArray());
            }
        }

        if(D('Code')->checkMobileCode('quest/mobCode',$mobile,I('post.code')) === false){
            $this->ajaxReturn($this->result->error('验证码错误')->toArray());
        }

        $data = [
            'mobile'=>$mobile,
            'name'=>$name,
        ];
        $result = D('Home/Quest')->addUser($data);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 手机验证码
     */
    public function mobCode(){
        $tel = I('post.mobile');
        if(!$tel){
            $this->ajaxReturn($this->result->error('手机号错误')->toArray());
        }
        $result = D('Code')->sendMobileCode('quest/mobCode',$tel,'verify_mobile');
        $this->ajaxReturn($this->result->set($result)->toArray());
    }

    public function answer(){
        $uid = I('get.id',0);
        $this->assign('uid',$uid);
        $this->display('answer');
    }

    public function submitAnswer(){
        $uid = I('post.uid');
        $answer = I('post.answer');
        $user_answer_model = M('QuestUserAnswer');
        $user_answer_model->where(['uid'=>$uid])->delete();
        $dataall = [];
        $data = [
            'uid'=>$uid
        ];
        foreach($answer as $k=>$v){
            $data['qid'] = $k;
            $data['answer'] = $v;
            $dataall[] = $data;
        }
        $result = $user_answer_model->addAll($dataall);
        $result = $result !== false ? $this->result->success('提交成功') : $this->result->error('提交失败');
        $this->ajaxReturn($result->toArray());
    }

    public function finish(){
        $this->display();
    }

}