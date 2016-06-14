<?php
/**
 * 快递单模板模型
 * @author wxb
 * @date 2015-08-4
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use Common\Org\Util\Tree;
class ExpressTemplateModel extends AdminbaseModel{

    public $_validate = [ 
			[ 
					'et_name',
					'require',
					'快递单名称不能为空' 
			],
			[ 
					'et_name',
					'',
					'该快递单已经存在',
					self::MUST_VALIDATE,
					'unique',
					self::MODEL_BOTH 
			],
			[ 
					'et_company_id',
					'require',
					'快递公司名称不能为空' 
			],
			[ 
					'et_background_pic',
					'require',
					'背景图不能为空',
					self::MUST_VALIDATE
			] 
	];
    protected $_auto = [
    	['et_add_time','time',self::MODEL_INSERT,'function'] // 对update_time字段在更新的时候写入当前时间戳     
    ];
    function _after_find(&$result, $options){
    	$result ['et_background_pic'] = D ( 'Upload/AttachMent' )->getAttach ($result ['et_background_pic']);
    }
    function _before_write(&$data,$options) {
    	if(isset($data['et_content_html'])){ //解码url
    		$data['et_content_html'] = htmlspecialchars(urldecode($data['et_content_html']));
    	}
    }
    private function delOldData($type,$elp_id){
    	if($type != 'save'){ //原来无数据
    		return;
    	}
    	//删除原来数据
    	M('express_label_position')->delete($elp_id);
    }
    function _after_insert($data, $options){
    	if($positionArr = I('label_position')){
			// elp_id++elp_top++elp_left++fk_express_label_id++fk_express_template_id
    		$m = M('express_label_position');
    		foreach($positionArr as $v){
    			$tmpArr = [];
    			$data = [];
    			$tmpArr = explode('++', $v);
    			if($tmpArr[0] == 'elp_id'){
    				$type = 'add';
    			}else{
    				$type = 'save';
    				$data['elp_id'] = $tmpArr[0];
    			}
    			if($tmpArr[1] == 'elp_top'){//无数据 1不增加任何数据 2尝试删除原来数据
    				$this->delOldData($type,$tmpArr[0]);
    				continue;
    			}
   				$data['elp_top'] = $tmpArr[1];
   				$data['elp_left'] = $tmpArr[2];
   				$data['fk_express_label_id'] = $tmpArr[3];
   				$data['fk_express_template_id'] = $tmpArr[4];
   				$m->$type($data);
    		}
    	}
    }
    
    function _after_update($data, $options){
    	$this->_after_insert($data, $options);
    }
    
    /**
     * 获取静态物流模版
     */
    public function getTemplate($field=true){
    	return $this->field($field)->select();
    }
    
    /**
     * 通过id获取物流模版
     * @param $et_id
     * @param string $field
     */
    public function getTemplateById($et_id,$field=true){
    	$where = array(
    			"et_id"=>$et_id
    	);
    	return $this->field($field)->where($where)->find();
    }
    
    /**
     * 获取动态物流模版
     * @param $et_id 模版id
     * @param $data  模版数据
     * @param $type 1:单模版 2：批量模版
     */
    public function getDyTemplate($et_id,$data,$type=1){
    	if(!$et_id){
    		$et_id = $this->find()['et_id']; //默认取第一条
    	}
    	$template = htmlspecialchars_decode(current($this->getTemplateById($et_id,"et_content_html")));
    	$label = M("ExpressLabel")->select();
    	foreach($label as $vo){
    		$template = str_replace($vo['el_name'], "{\$".$vo['el_code']."}", $template);
    	}
    	$view = new \Think\View();
    	$tmp = array();
    	foreach($data as $key=>$v){
    		$view->assign($v);
    		$tmp[$key] = $view->fetch("",$template);
    	}
    	return $tmp;
    }
}