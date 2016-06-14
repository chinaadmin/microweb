<?php
//物流管理基类
/**
 * 接口基类
*/
namespace Logistics\Controller;
use Think\Controller;
use Common\Org\Util\Results;
class LogisticsBaseController extends Controller{
	protected $code_file = 'logistics';
	public function _initialize() {
		$this->result = Result(function($result){
			$result->setFile($this->code_file);
		});
		if (method_exists($this, '_init')) {
			$this->_init();
		}
	}

	/**
	 * Ajax方式返回数据到客户端
	 * @param \Common\Org\util\Results $results 返回
	 */
	public function ajaxReturn(Results $results) {
		if(!($results instanceof Results)) {
			$results = $this->result->set('RETURNED_FORMAT_ERROR');
		}
		$result = $results->getResult();
		$return_array = [];
		if (!is_null($result) && is_array($result)) {
			$return_array = $result;
		}
		$return_array['resCode'] = $results->getCode();
		$return_array['resMsg'] = $results->getMsg();
		$return_array = $this->toString($return_array);
		parent::ajaxReturn ( $return_array );
	}

	/**
	 * 转化成string
	 * @param string $obj 值
	 * @return array|string
	 */
	public function toString($obj) {
		if (is_int ( $obj ) || is_float ( $obj )) {
			return ( string ) $obj;
		}
		if (is_null ( $obj )) {
			return '';
		}
		if (is_array ( $obj )) {
			$obj = array_map ( array (
					$this,
					'toString'
			), $obj );
		}
		return $obj;
	}

	/**
	 * 分页
	 * @param int $currentPage 当前页码
	 * @param int $listRows 列表数
	 * @return array
	 */
	public function openPage($currentPage = 0, $listRows = 0) {
		$arr = [ ];
		$var_page = C ( 'VAR_PAGE' ) ? C ( 'VAR_PAGE' ) : 'p';
		$var_listrows = C ( 'VAR_LISTROWS' ) ? C ( 'VAR_LISTROWS' ) : 'count';
		$arr ['currentPage'] = empty ( $currentPage ) ? I ( "request.{$var_page}" , 1 , 'intval' ) : $currentPage;
		$arr ['currentPage'] = ($arr ['currentPage'] > 0 )? $arr ['currentPage'] : 1;
		$arr ['listRows']  = empty ( $listRows ) ? I ("request.{$var_listrows}" ,  C ( "PAGE_LISTROWS") , 'intval' ) : $listRows;
		return $arr;
	}
	/**
	 * 列表分页
	 * @param string $model 模型
	 * @param array $where 过滤条件
	 * @param string $order 排序
	 * @param bool $field 字段
	 * @return array
	 */
	public function _lists($model, $where = [], $order = null, $field = true){
		$options = [];
		$request = I('request');
		if (is_string($model)) {
			$model = M($model);
		}
		$opt = new \ReflectionProperty ($model, 'options');
		$opt->setAccessible(true);
		$pk = $model->getPk();
		if (isset ($request ['_order']) && isset ($request ['_field'])) {
			$options ['order'] = '`' . $request ['_field'] . '` ' . $request ['_order'];
		} elseif ($order === '' && empty ($options ['order']) && !empty ($pk)) {
			$options ['order'] = $pk . ' desc';
		} elseif ($order) {
			$options ['order'] = $order;
		}
		$options ['where'] = $where;
		if (empty ($options ['where'])) {
			unset ($options ['where']);
		}
		$options = array_merge_recursive(( array )$opt->getValue($model), $options);
		$page = $this->openPage ();
		$options ['limit'] = (($page['currentPage']-1) * $page['listRows']). ',' .($page['listRows']+1);
		$model->setProperty('options', $options);
		$lists = $model->field($field)->select();
		$data = [];
		$data['lastPage'] = 0;
		if (count($lists) >= $page['listRows'] + 1) {
			$data['lastPage'] = 1;
			array_pop($lists);
		}
		$data['data'] = $lists;
		return $data;
	}
}