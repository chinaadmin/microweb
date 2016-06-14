<?php
/**
 *发送模板管理 
 * @author wxb
 * @date 2015/5/22
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class  SendTemplateController extends AdminbaseController{
		protected  $curent_menu = 'SendTemplate/index';
		public  function  index(){
			$where = [];
			$this->title = '发送模板管理';
			$model = M('template');
			$this->lists = $this->lists($model, $where) ;
			$this->display();
		}	
		public  function  edit(){
			$where['pk_temp'] = I('pk_temp',0,'int');
			if(!$where['pk_temp']){//新增加
				$this->display();
				return;
			}
			$model = M('template');
			$this->info = ($tmp = $this->lists($model, $where) ) ? $tmp[0] : [];
			$this->display();
		}
	  /**
	     * 删除
	     */
    public function del(){
        $id = I('request.pk_temp');
        if(!$id){
	        $this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
        }
	    $result = M('template')->where (['pk_temp' => $id] )->delete ();
	    $result !== false ? $this->result->success('删除成功') : $this->result->set('DATA_DELETE_FAILED');
        $this->ajaxReturn($this->result->toArray());
    }
    /**
     * 更新或增加
     */
    public function update(){
    	$model = M('template');
    	if(!$model->create()){
    		$this->ajaxReturn($this->result->set('DATA_ERROR')->toArray());
    	}
    	if($model->temp_type == 1){//短信模板
    		$model->temp_content = delhtml(htmlspecialchars_decode($model->temp_content));
    	}
    	if($model->pk_temp){//传递id 更新
    		$result = $model->save();
    		$mes = '更新成功';
    		$code = 'DATA_MODIFICATIONS_FAIL';
    	}else{//新增
    		$result = $model->add();
    		$mes = '新增成功';
    		$code = 'DATA_INSERTION_FAILS';
    	}
    	$result !== false ? $this->result->success($mes) : $this->result->set($code);
    	$this->ajaxReturn($this->result->toArray());
    }
}