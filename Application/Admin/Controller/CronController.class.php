<?php
/**
 * 计划任务逻辑类
 * @author cwh
 * @date 2015-06-10
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class CronController extends AdminbaseController {

    protected $curent_menu = 'Cron/index';

    /**
     * 列表
     */
    public function index(){
        $cron_model = D('Cron/Cron');
        $where = [];
        $cron_lists = $this->lists($cron_model,$where,'cr_id desc');
        $cron_lists = array_map(function($info){
            $info['type'] = D('Cron/Cron')->getCycleText($info['loop_type'],$info['loop_daytime']);
            return $info;
        },$cron_lists);
        $this->assign('lists',$cron_lists);
        $this->display();
    }

    function viewlog(){
        $where = array();
        $id = I('get.id');
        $where['cr_id'] = $id;
        $cron_model = M ('CronLog');
        $cron_lists = $this->lists($cron_model,$where,'add_time desc');
        $this->assign('lists',$cron_lists);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $cron_id = I('get.id');
        $cron_model = D('Cron/Cron');
        if ($cron_id) {
            $info = $cron_model->where(["cr_id" => $cron_id])->find();
            list ($info ['day'], $info ['hour'], $info ['minute']) = explode('-', $info ['loop_daytime']);
        }
        $this->assign ($info);
        $this->assign ("loopType",$cron_model->getLoopType());
        $this->assign ("fileList",$cron_model->getCronFileList());
        $this->display ();
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.cr_id');
        $cron_model = D('Cron/Cron');
        $data = $cron_model->loopType(I('post.'));
        if(!empty($id)) {
            $where = [
                'cr_id' => $id
            ];
            $data['cr_id']=$id;
            $result = $cron_model->setData($where,$data);
        }else{
            $result = $cron_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.id');
        $cron_model = D('Cron/Cron');
        $where = [
            'cr_id' => $id
        ];
        $result = $cron_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

}