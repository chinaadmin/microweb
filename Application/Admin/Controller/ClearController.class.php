<?php
/**
 * 清除逻辑类
 * @author cwh
 * @date 2015-04-02
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class ClearController extends AdminbaseController {

    public function index(){
        D('Admin/Clear')->all();
        $this->success("缓存清理成功！");
    }

}