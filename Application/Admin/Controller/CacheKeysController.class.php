<?php
/**
 * 缓存规则逻辑类
 * @author cwh
 * @date 2015-06-12
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class CacheKeysController extends AdminbaseController {

    protected $curent_menu = 'CacheKeys/index';

    /**
     * 首页
     */
    public function index(){
        $cache_model = D('Admin/CacheKeys');
        $where = [];
        $cache_lists = $this->lists($cache_model,$where,'sort desc,id asc');
        $exists_keys = D('Common/Redis')->allKeys ();
        if ($cache_lists) {
            $cache_lists = array_map(function($info)use($exists_keys){
                $result = in_array ( $info ['key'], $exists_keys );
                $info ['status'] = $result ? 1 : 0;
                return $info;
            },$cache_lists);
        }
        $this->assign('lists',$cache_lists);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $id = I('get.id');
        $cache_model = D('Admin/CacheKeys');
        if ($id) {
            $info = $cache_model->field(true)->where(["id" => $id])->find();
            if ($info) {
                $info ['config'] = unserialize($info ['config']);
            }
        }
        $redis_type = D('Common/Redis')->getType();
        $this->assign('redis_type', $redis_type);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.id');
        $cron_model = D('Admin/CacheKeys');
        $type = I('request.type');
        $data = [
            'key' => $cron_model->checkPrefix(I('request.key')),
            'desc' => I('request.desc'),
            'redis_type' => I('request.redis_type'),
            'timer' => I('request.timer'),
            'is_auto' => I('request.is_auto'),
            'type' => $type,
            'config' => $this->_config_data($type)
        ];
        if(!empty($id)) {
            $where = [
                'id' => $id
            ];
            $data['id']=$id;
            $result = $cron_model->setData($where,$data);
        }else{
            $result = $cron_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 缓存配置数据格式
     * @param int $type 类型
     * @return string
     */
    private function _config_data($type){
        switch ($type) {
            case 1://模型
                $param = I('request.param');
                $data = [
                    'model' => I('request.model_name'),
                    'function' => I('request.function'),
                    'param' => explode(',', $param)
                ];
                break;
            case 2://sql
                $data = [
                    'sql' => I('request.sql')
                ];
                break;
        }
        return serialize($data);
    }

    /**
     * 查看缓存状态信息
     * @param string $key
     */
    public function view($key){
        $redis_model = D('Common/Redis');
        $redis = $redis_model->getRedis();
        $type = $redis->type($key);
        if ($type === 0) {
            $this->error('缓存不存在或已经失效');
        }
        $cache_info = [];
        $cache_info ['key'] = $key;
        $cache_info ['type'] = $type;
        $cache_info ['name'] = $redis_model->getType($type,'title');
        $cache_info ['ttl'] = $redis->ttl($key);
        $cache_info ['encoding'] = $redis->object('ENCODING', $key);
        $cache_info ['size'] = $redis_model->getSize($key, $type);
        $this->assign('cache_info', $cache_info);
        $this->display();
    }

    /**
     * 通过规则生成缓存
     */
    public function enable(){
        $id = I('request.id');
        $cache_model = D('Admin/CacheKeys');
        $result = $cache_model->genereateCache($id);
        if(!IS_AJAX){
        	if($result->isSuccess()){
        		$this->success('更新成功');
        	}else{
        		$this->error('更新失败');
        	}
        	exit;
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除缓存
     * @param string $key
     */
    public function delCache($key){
        $result = D ('Common/Redis')->getRedis()->delete($key);
        if ($result) {
            $out_data = $this->result->success('清空缓存成功');
        } else {
            $out_data = $this->result->error('清空缓存失败');
        }
        if(!IS_AJAX){
        	if($result){
	        	$this->success('清空缓存成功');
        	}else{
	        	$this->error('清空缓存失败');
        	}
        	exit;
        }
        $this->ajaxReturn ($out_data->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $cache_model = D('Admin/CacheKeys');
        $where = [
            'id' => $id
        ];
        $result = $cache_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }
    
    /**
     * 更新地区缓存
     */
    public function enableCity(){
    	$type= I("request.type",'1');
    	if($type==1){
    		D("Home/Area")->getProvice(true);
    	}else{
    		D("Home/Area")->getAreaData(true);
    	}
    	$this->success("更新成功！",U('Index/index'));
    }

}