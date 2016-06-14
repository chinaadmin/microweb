<?php
/**
 * 网站公共类
 * @author cwh
 * @date 2015-3-27
 */
namespace Common\Controller;
use Think\Controller;
class BaseController extends Controller{

    protected $result = null;
    protected $code_file = '';

	public function _initialize() {
		$this->_initConfigs();
		$this->initPageParam();
        $this->result = result(function($result){
            $result->setFile($this->code_file);
        });
        $this->assign ( '__this__', $this );

        if (method_exists($this, '_init')) {
            $this->_init();
        }
	}

    /**
     * 初始化函数
     */
    public function _init(){}

	/**
	 * 初始化页面参数
	 */
	public function initPageParam() {
		$urlCase = C ( 'URL_CASE_INSENSITIVE' );
		if (C ( 'APP_SUB_DOMAIN_RULES' )) {
			$appName = array_search(MODULE_NAME , C ( 'APP_SUB_DOMAIN_RULES' ));
		} else {
			$appName = '';
		}
		$controllerName = $urlCase ? parse_name ( CONTROLLER_NAME )  : CONTROLLER_NAME;
		$actionName = $urlCase ?  parse_name ( ACTION_NAME ) : ACTION_NAME;
		$this->assign ( 'url_upload', C('JT_CONFIG_WEB_UPLOAD'));
		$this->assign ( 'url_group', strtolower($appName));
		$this->assign ( 'url_model', strtolower($controllerName));
		$this->assign ( 'url_action', strtolower($actionName));
	}
	
	/**
	 * 初始化网站配置
	 */
	private function _initConfigs(){
        $configs_model = D('Admin/Configs');
        $configs_model->SysConfigs();
        $data = $configs_model->Configs();
        if ($data) {
            $configs = [];
            foreach ($data as $key => $val) {
                $configs['JT_CONFIG_'.strtoupper($key)] = $val;
            }
            C($configs);
        }
    }

    /**
     * 获取购物车总数
     * @return mixed
     */
    public function getCartCount(){
        return D('Home/Cart')->getCartCount();
    }
}