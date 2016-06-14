<?php
/**
 * 公用逻辑类
 * @author cwh
 * @date 2015-03-30
 */
namespace Admin\Controller;
use Think\Verify;
use Common\Controller\AdminbaseController;
class PublicController extends AdminbaseController {

    const VERIFYID = 'adl_verify';

    /**
     * 登录
     */
    public function login(){
        if($this->user_instance->isLogin()){
            $this->redirect ( 'index/index' );
        }
        $this->display();
    }

    /**
     * 验证登录
     */
    public function verifyLogin(){
        $username = I ( 'post.username', '', 'htmlspecialchars,trim' );
        $password = I ( 'post.password', '', 'htmlspecialchars,trim' );
        if (empty ( $username )) {
            $this->ajaxReturn($this->result->set('ACCOUNT_REQUIRE')->toArray());
        }
        if (empty ( $password )) {
            $this->ajaxReturn($this->result->set('PASSWORD_REQUIRE')->toArray());
        }

        //验证码验证
        $vertify = new Verify();
        $verify_code = I ( 'post.verify', '', 'htmlspecialchars,trim' );
        if ($vertify->check ( $verify_code, self::VERIFYID ) === false) {
            $this->ajaxReturn($this->result->set('VERIFICATION_CODE_ERROR')->toArray());
        }

        // 启用登录日志
        $this->user_instance->recordLog = true;
        //验证账号和密码
        $credentials = [
            'account' => $username,
            'password' => $password
        ];
        $result = $this->user_instance->login ( $credentials );
        if ($result->isSuccess()) {
            $uid = $result->getResult();
            $is_super = false;
            // 超级管理员
            if ($uid === C ( 'SUPPER_USER_ID' )) {
                $is_super = true;
            }
            $this->user_instance->saveAdmin ($is_super);
            // 缓存访问权限
            //RBAC::saveAccessList ( $id );
            //$this->redirect ( '/' );
        } else {
            $this->ajaxReturn($result->toArray());
        }
        $this->ajaxReturn($this->result->success('登录成功')->toArray());
    }

    /**
     * 产生验证码
     */
    public function genreateVerify() {
        $verify_config = C ( 'VERIFY_CONFIG' );
        $vertify = new Verify ( $verify_config );
        $vertify->entry ( self::VERIFYID );
    }

    /**
     * 用户登出
     */
    public function logout() {
        $uid = $this->user_instance->isLogin ();
        if ($uid) {
            $this->user_instance->logout ();
            // 超级管理员
            if ($uid === C ( 'SUPPER_USER_ID' )) {
                $this->user_instance->clearAdmin ();
            }
            $this->success ( '登出成功', U ( C ( 'USER_AUTH_GATEWAY' ) ) );
        } else {
            $this->error ( '已经退出登录' );
        }
    }

}