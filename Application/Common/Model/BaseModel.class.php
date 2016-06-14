<?php
/**
 * 公共模型类
 * @author cwh
 * @date 2015-04-01
 */
namespace Common\Model;
use Think\Model;
class BaseModel extends Model{

    protected $code_file = '';
    // 当前使用的扩展模型
    private   $_extModel        =   null;

    public function _initialize() {

    }

    /**
     * 返回值
     * @return \Common\Org\Util\Results
     */
    public function result(){
        return result(function($result){
            $result->setFile($this->code_file);
        });
    }

    /**
     * 保存数据
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return boolean
     */
    public function save($data='',$options=array()) {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {
                $data           =   $this->data;
                // 重置数据
                $this->data     =   array();
            }else{
                $this->error    =   L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 数据处理
        $data       =   $this->_facade($data);
        if(empty($data)){
            // 没有数据则不执行
            $this->error    =   L('_DATA_TYPE_INVALID_');
            return false;
        }
        // 分析表达式
        $options    =   $this->_parseOptions($options);
        $pk         =   $this->getPk();
        if(!isset($options['where']) ) {
            // 如果存在主键数据 则自动作为更新条件
            if (is_string($pk) && isset($data[$pk])) {
                $where[$pk]     =   $data[$pk];
                unset($data[$pk]);
            } elseif (is_array($pk)) {
                // 增加复合主键支持
                foreach ($pk as $field) {
                    if(isset($data[$field])) {
                        $where[$field]      =   $data[$field];
                    } else {
                        // 如果缺少复合主键数据则不执行
                        $this->error        =   L('_OPERATION_WRONG_');
                        return false;
                    }
                    unset($data[$field]);
                }
            }
            if(!isset($where)){
                // 如果没有任何更新条件则不执行
                $this->error        =   L('_OPERATION_WRONG_');
                return false;
            }else{
                $options['where']   =   $where;
            }
        }

        if(is_array($options['where']) && isset($options['where'][$pk])){
            $pkValue    =   $options['where'][$pk];
        }
        if(false === $this->_before_update($data,$options)) {
            return false;
        }
        $result     =   $this->db->update($data,$options);
        if(false !== $result && is_numeric($result)) {
            if(isset($pkValue)) $data[$pk]   =  $pkValue;
            if(false === $this->_after_update($data,$options)){
                return false;
            }
        }
        return $result;
    }

    /**
     * 获取返回错误
     * @return $this
     */
    public function getResultError(){
        $result = $this->result();
        $error = explode('::',$this->getError());
        $error_code = $result::ERROR_CODE;
        $error_msg = $error[0];
        if(count($error) >= 2){
            $error_code = $error[0];
            $error_msg = $error[1];
        }
        return $result->set($error_code,$error_msg);
    }

    /**
     * 新增数据
     * @param array $data 条件
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function addData(array $data, $mode = false,$replace=false,$options='') {
        if (! $this->create ( $data, self::MODEL_INSERT )) {
            return $this->getResultError();
        }
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->add (null,$options,$replace);
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->content($result)->success('添加成功') : $this->result()->set('DATA_INSERTION_FAILS');
    }

    /**
     * 修改数据
     * @param array $where 条件
     * @param array $data 数据
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function setData(array $where, array $data, $mode = false) {
        if (! $this->create ( $data, self::MODEL_UPDATE)) {
            return $this->getResultError();
        }
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->where ( $where )->save ();
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('修改成功') : $this->result()->set('DATA_MODIFICATIONS_FAIL');
    }

    /**
     * 删除数据
     * @param array $where 条件
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function delData(array $where, $mode = false) {
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->where ( $where )->delete ();
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('删除成功') : $this->result()->set('DATA_DELETE_FAILED');
    }

    /**
     * 逻辑删除数据
     * @param array $where 条件
     * @param bool $mode
     *        	true为启用事务,false为不启用事务
     * @return \Common\Org\util\Results
     */
    public function tombstoneData(array $where, $mode = false){
        if ($mode) {
            $this->startTrans ();
        }
        $result = $this->where ( $where )->data(['delete_time'=>time()])->save ();
        if ($mode) {
            if ($result !== false) {
                $this->commit ();
            } else {
                $this->rollback ();
            }
        }
        return $result !== false ? $this->result()->success('删除成功') : $this->result()->set('DATA_DELETE_FAILED');
    }

    /**
     * 保存排序
     * @param array $data 排序数组
     * @param bool $mode true为启用事务,false为不启用事务
     * @param string $sort 排序字段
     * @param string $key 条件字段
     * @return string
     */
    public function saveSort(array $data, $mode = false,$sort='sort',$key='id'){
        if (!$data) {
            return $this->result()->set('DATA_ERROR');
        }
        $sql = array2UpdateSql($data, $this->getTableName(), $sort, $key);
        if ($mode) {
            $this->startTrans();
        }
        $result = $this->execute($sql);
        if ($mode) {
            if ($result !== false) {
                $this->commit();
            } else {
                $this->rollback();
            }
        }
        return $result !== false ? $this->result()->success('保存排序成功') : $this->result()->set('SAVE_SORT_FAILURE');
    }

    /**
     * 动态切换试图模型
     * @author cwh harold
     * @param $attributes array 高级模型属性键值
     * @return mixed
     */
    public function dynamicView($attributes = null){
        $this->_extModel   = new \Think\Model\ViewModel($this->name);
        $fields = 'viewFields';
        if(is_null($attributes)){
            if(property_exists($this,$fields)){
                $attributes = $this->$fields;
            }
        }
        $this->_extModel->setProperty($fields,$attributes);
        return $this->_extModel;
    }

    /**
     * 动态切换关联模型
     * @author cwh harold
     * @param $attributes array 高级模型属性键值
     * @return mixed
     */
    public function dynamicRelation($attributes = null){
    	$this->_extModel   = new \Think\Model\RelationModel($this->name);
    	$fields = '_link';
    	if(is_null($attributes)){
    		if(property_exists($this,$fields)){
    			$attributes = $this->$fields;
    		}
    	}
    	$this->_extModel->setProperty($fields,$attributes);
    	return $this->_extModel;
    }


}
