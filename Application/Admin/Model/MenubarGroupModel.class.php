<?php
/**
 * 菜单分组模型类
 * @author cwh
 * @date 2015-04-03
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class MenubarGroupModel extends AdminbaseModel{

    protected $tableName = 'admin_menubar_group';

    /**
     * 获取分组列表
     */
    public function getNames(){
        return $this->getField('gr_id,name');
    }

}