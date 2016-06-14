<?php
/**
 * 公用逻辑类
 * @author cwh
 * @date 2015-05-11
 */
namespace Stores\Controller;
use Think\Verify;
class PublicController extends StoresbaseController {

    const VERIFYID = 'adl_stores_verify';

    /**
     * 登录
     */
    public function login(){
        if($this->user->isLogin() && $this->stores_id){
            $this->redirect ( 'index/index' );
        }
        $stores = D('Stores/Stores')->scope()->getField('stores_id,name',true);
        $this->assign('stores',$stores);
        $stores_id = cookie('sel_stores_id');
        $this->assign('stores_id',$stores_id);
        $this->display();
    }

    /**
     * 验证登录
     */
    public function verifyLogin(){
        $stores_id = I ( 'post.stores_id', '');
        cookie('sel_stores_id',$stores_id);
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
        $this->user->recordLog = false;
        //验证账号和密码
        $credentials = [
            'account' => $username,
            'password' => $password
        ];
        $result = $this->user->login ( $credentials );
        if ($result->isSuccess()) {
            $uid = $result->getResult();
            if(D('Stores/StoresUser')->inStores($uid,$stores_id)===false){
                $this->user->logout ();
                $this->ajaxReturn($this->result->error('该用户不在门店里')->toArray());
            }else{
                $this->stores->set($stores_id);
                $is_super = false;
                // 超级管理员
                if ($uid === C ( 'SUPPER_USER_ID' )) {
                    $is_super = true;
                }
                $this->user->saveAdmin ($is_super);
            }
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
        $uid = $this->user->isLogin ();
        if ($uid) {
            $this->user->logout ();
            $this->stores->clean();
            // 超级管理员
            if ($uid === C ( 'SUPPER_USER_ID' )) {
                $this->user->clearAdmin ();
            }
            $this->success ( '登出成功', U ( C ( 'USER_AUTH_GATEWAY' ) ) );
        } else {
            $this->error ( '已经退出登录' );
        }
    }

}