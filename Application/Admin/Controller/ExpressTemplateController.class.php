<?php
/**
 * 快递单模板类
 * @author wxb
 * @date 2015-08-4
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Think\Dispatcher;
class ExpressTemplateController extends AdminbaseController{
	protected $curent_menu = "express_template/index";
	protected $model;
	public function _init(){
		$this->model = D("ExpressTemplate");
	}
	/**
	 * 快递单模板列表
	 */
	public function index(){
		$this->title = '快递单模板管理';
		$et_name = I('et_name');
		$where = [];
		if($et_name){
			$where['et_name'] = ['like',"%{$et_name}%"];
		}
		if(($startTime = I('start_time')) && ($endTime = I('end_time'))){
			$startTime = strtotime($startTime.' 00:00:00');
			$endTime = strtotime($endTime.' 23:59:59');
			$where['et_add_time'] = ['between',[$startTime,$endTime]];
		}
		$this->lists = $this->lists($this->model,$where);
		$this->info = $this->model->where(['et_name' => '德绑物流模板'])->find();
		$this->display();
	}
	//编辑页和增加页面相关展示信息
	function _after_my_edit($info){
		$et_id = $info['et_id'];
		$this->logistics_company();
		$et_id = $et_id ? $et_id : -1;
// 	fk_express_label_id elp_id  elp_top  elp_left
		$fk_express_label_id_arr = M('express_label_position')->where(['fk_express_template_id' => $et_id])->getField('fk_express_label_id,elp_id,elp_top,elp_left'); 
		//elp_id++elp_top++elp_left+el_id+et_id
		$labelList = M('express_label')->select();
		foreach ($labelList as &$v){
			if(array_key_exists($v['el_id'], $fk_express_label_id_arr)){ //是否符合
				$tmp = $fk_express_label_id_arr[$v['el_id']];
				$v['checked'] = 1; 
				$v['position_str'] = "{$tmp['elp_id']}++{$tmp['elp_top']}++{$tmp['elp_left']}++{$v['el_id']}++{$et_id}";
				continue;
			}
			$v['checked'] = 0;
			$v['position_str'] = "elp_id++elp_top++elp_left++{$v['el_id']}++{$et_id}";
		}
		$this->labelList = $labelList;
	}
	//物流公司列表
	private function logistics_company(){
		$this->logistics_company_list = M('logistics_company')->select();
	}
	function printTemplate(){
		$fk_express_template_id = I('fk_express_template_id'); //模板id
		$m = M('express_label');
	/* 	$positonJson = M('express_label_position')->where(['fk_express_template_id' =>$fk_express_template_id])->select();
		foreach ($positonJson as &$tmp){
			$middle = $m->where(['el_id' => $tmp['fk_express_label_id']])->field('el_name,el_code')->find();
			$tmp['label_name'] = $middle['el_name'];
			$tmp['label_code'] = $middle['el_code']; 
			$tmp['position_str'] = "{$tmp['elp_id']}++{$tmp['elp_top']}++{$tmp['elp_left']}++{$tmp['fk_express_label_id']}++{$tmp['fk_express_template_id']}";
		}
		$this->positonJson = json_encode($positonJson); */
		$this->expressTemplate = M('express_template')->field('et_name,et_id')->select();
		$this->templateHtml = M('express_template')->where(['et_id' => $fk_express_template_id])->find();
		$where = [];
		$this->senderInfo = D('Stores/StoresUser')->getUserRelation()->where($where)->find();
		$this->storesList = D('Stores/Stores')->scope()->select();
		$this->display();
	}
	//德邦物流模板预览
	function showTemplate(){
		$id = I('fk_express_template_id',14,'int');
// 		$this->flushTemplate($id);
		$content =  M('express_template')->where(['et_id' => $id])->find();
		$content = htmlspecialchars_decode($content['et_content_html']);
		$content = preg_replace(['/(\.\.\/){1,2}/','/http:\/\/www\.[^\/]+\//'], [fullPath('/Mobile/mobile/'), 'http://'.C('JT_CONFIG_WEB_DOMAIN_NAME').'/'], $content);
		$view = new \Think\View();
		$input = [];
		$input['senderName'] = '寄件';//寄件
		$input['senderMobile'] = '电话'; //电话
		$input['senderProvinceName'] = '原寄地'; //原寄地
		$input['receiverName'] = '收货人';//收货人
		$input['receiverMobile'] = '收货人手机';//收货人手机
		$input['receiverAddress'] = '收货人地址';//收货人地址
		$input['receiverProvince'] = '目的地'; //目的地
		$input['packageCount']  = 10;
		$input['packageWeight'] = '100kg';//重量
		$input['freight'] = '10000元';//运费 0
		$input['totalCost'] = '10000元';//合计费用
		//0:发货人付款（现付）1:收货人付款（到付）2：发货人付款（月结）
		$input['payType'] = 1;
		$input['SupportValue'] = 0; //保价金额
		$input['account'] = 1414414141;//代收帐号
		$input['accountMount'] = 1000000;//代收货款
		$input['cargoName'] = '药药切克闹';//货物名称 1件
		$input['mailNo'] = '1234567890';//运单号
		$view->assign(['input' => $input]);
		$content = $view->fetch('', $content);
		header('content-type:text/html; charset=utf-8');
		echo $content;
	}
	private function flushTemplate($id){
		$content = file_get_contents('./Mobile/mobile/html/waycillCopy.html');
		$content = htmlspecialchars(preg_replace('/<script[^>]+><\/script>/', '', $content)); //剔除脚本引入
		$res = M('express_template')->where(['et_id' => $id])->save(['et_content_html' => $content]);
		var_dump($res);
		exit;
	}
}