<?php
/**
 * 配送方式
 * @author wxb
 * @date 2015-05-05
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class DeliveryController extends AdminbaseController {
	protected $curent_menu = 'Delivery/index';
    //账户管理
    public function index() {
    	$this->title = '配送方式列表'; 
    	$model = M('delivery_way');
    	$this->lists = $this->lists($model);
        $this->display();
    }
    //编辑展示页
    public function edit(){
    	$this->title = '编辑配送方式';
    	$dw_id = I('dw_id',0,'int');
    	if(!$dw_id){
    		$this->ajaxReturn($this->result->set('PARAM_EMPTY')->toArray());
    	}
    	$this->info = M('delivery_way')->find($dw_id);
        $this->display('edit_add');
    }
    //增加展示页
    public function add(){
    	$this->title = '添加配送方式';
        $this->display('edit_add');
    }
    //编辑增加处理方法
    public function update(){
    	$model = M('delivery_way');
    	$this->_existsName();
    	$model->create();
    	if($model->dw_id){
	    	$mes = '更新配送方式';
    		$model->dw_update_time = NOW_TIME;
	    	$res = $model->save ();
    	}else{
	    	$mes = '增加配送方式';
    		$model->dw_add_time = NOW_TIME;
    		$res = $model->add();
    	}
    	if($res){
	    	$this->ajaxReturn($this->result->success($mes.'成功')->toArray());
    	}else{
	    	$this->ajaxReturn($this->result->error($mes.'失败')->toArray());
    	}
    }
    /**
     * 验证配送名
     */
    private function _existsName(){
    	$dw_delivery_way = I("post.dw_delivery_way","");
    	$dw_id = I("post.dw_id","0",'intval');
    	if($dw_delivery_way){
    		$where['dw_delivery_way'] = $dw_delivery_way;
    		if($dw_id){
    			$where['dw_id'] = array("NEQ",$dw_id);
    		}
    		if(M('delivery_way')->where($where)->find()){
    			$this->ajaxReturn($this->result->error("配送名称已存在！")->toArray());
    		}
    	}
    }
    //删除
    function del(){
    	$dw_id = I('dw_id',0,'int');
    	if(!$dw_id){
    		$this->ajaxReturn($this->result->set('PARAM_EMPTY')->toArray());
    	}
    	$result = M('delivery_way')->delete($dw_id);
    	if($result){
    		$this->ajaxReturn($this->result->success('删除成功')->toArray());
    	}else{
    		$this->ajaxReturn($this->result->error('删除失败')->toArray());
    	}
    }
}