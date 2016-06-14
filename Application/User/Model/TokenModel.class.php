<?php

/**
 * 用户Token类
 */
namespace User\Model;

use Think\Model;

class TokenModel extends Model {

    protected $device = 'default';
    protected $channel_id = '';
    protected $device_type =1; //1：微商城 2：IOS 3：Android'

    /**
     * 设置设备
     * @param string $device_str 设备值
     * @return $this
     */
    public function device($device_str){
        if(!empty($device_str)){
            $this->device = $device_str;
        }
        return $this;
    }
    /**
     * 设置推送设备
     * @param string $channel_id 推送id
     * @return $this
     */
    public function setChannel($channel_id){
    	$this->channel_id = $channel_id;
    	return $this;
    }
    
    /**
     * 设置登陆设备类型
     */
    public function setType($type){
      $this->device_type = $type;
      return $this;
    }
    
    /**
     * 设置令牌（只允许一个）
     * @param int $uid 用户ID
     * @return string
     */
    public function set($uid){
        //清除用户之前所有token
        $this->cleanUser($uid);
        //设置令牌
        $toon_model = M('TokenOnline');
        $data = array();
        $data['uid'] = $uid;
        $token = md5(rand_string().uniqid());
        $data['token'] = $token;
        $data['token_add_time'] = time();
        $this->data($data)->add();
        $data = array();
        $data['token'] = $token;
        $data['device'] = $this->device;
        $data['channel_id'] = $this->channel_id;
        $data['type'] = $this->device_type;
        $data['toon_add_time'] = time();
        $toon_model->data($data)->add();
        return $token;
    }


    /**
     * 设置令牌（允许多个）
     * @param int $uid 用户ID
     * @return string
     */
    public function setMore($uid){
        $where = array();
        $where['uid'] = $uid;
        $token_info = $this->field(true)->where($where)->find();
        $toon_model = M('TokenOnline');
        $token = '';
        if (empty($token_info)){
            $data = array();
            $data['uid'] = $uid;
            $token = md5(rand_string().uniqid());
            $data['token'] = $token;
            $data['token_add_time'] = time();
            $this->data($data)->add();
            $data = array();
            $data['token'] = $token;
            $data['device'] = $this->device;
            $data['channel_id'] = $this->channel_id;
            $data['type'] = $this->device_type;
            $data['toon_add_time'] = time();
            $toon_model->data($data)->add();
        }else{
            $token = $token_info['token'];
            $where = array();
            $where['token'] = $token;
            $where['device'] = $this->device;
            $toon_info = $toon_model->field(true)->where($where)->find();
            if(empty($toon_info)){
            	$where['channel_id'] = $this->channel_id;
            	$where['type'] = $this->device_type;
                $where['toon_add_time'] = time();
                $toon_model->data($where)->add();
            }
        }
        return $token;
    }

    /**
     * 认证token
     * @param string $token
     * @return array|bool
     */
    public function auth($token){
        $where = array();
        $where['token'] = $token;
        $token_info = $this->field(true)->where($where)->find();
        if(empty($token_info)){
            $this->error = '该token不存在';
            return false;
        }
        $where = array();
        $where['token'] = $token;
        //暂不对设备进行验证
        if(!empty($this->device)){
            $where['device'] = $this->device;
        }
        $token_online_info = M('TokenOnline')->field(true)->where($where)->order('toon_add_time')->find();
        if(empty($token_online_info)){
            //$this->error = '你的IP地址发生变化';
            $this->error = '你的设备发生变化';
            return false;
        }
        return array_merge($token_info,$token_online_info);
    }

    /**
     * 删除当前token
     * @param string $token token值
     * @return bool
     */
    public function del($token){
        $where = array();
        $where['token'] = $token;
        $result = $this->field(true)->where($where)->count();
        if(empty($result)){
            return true;
        }
        $where = array();
        $where['token'] = $token;
        //暂不对设备进行验证
        if(!empty($this->device)){
            $where['device'] = $this->device;
        }
        M('TokenOnline')->where($where)->delete();
        $where = array();
        $where['token'] = $token;
        $cut = M('TokenOnline')->where($where)->count();
        if(empty($cut)){
            $this->where($where)->delete();
        }
        return true;
    }

    /**
     * 清除用户全部token
     * @param int $uid 用户ID
     * @return bool
     */
    public function cleanUser($uid){
        $where = array();
        $where['uid'] = $uid;
        $token_lists = $this->field(true)->where($where)->select();
        if(empty($token_lists)){
            return true;
        }
        $token = array_column($token_lists,'token');
        return $this->clean(array('in',$token));
    }

    /**
     * 清除token
     * @param string $token token值
     * @return bool
     */
    public function clean($token){
        $where = array();
        $where['token'] = $token;
        $this->where($where)->delete();
        M('TokenOnline')->where($where)->delete();
        return true;
    }
}