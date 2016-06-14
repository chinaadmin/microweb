<?php
/**
 * 行政地区控制器
 * @author xiongzw
 * @date 2015-05-08
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AreaController extends AdminbaseController{

    protected $no_auth_actions = ['getAreaData'];

	public function getAreaData(){
        $pid = I('request.pid',0);
        $cid = I('request.cid',0);
        $county_id = I('request.county_id',0);
        if(empty($pid)){
            $data = D("Home/Area")->getProvice();
        }else{
            $data = D('Home/Area')->getAreaData();
            $data = $data[$pid]['child'];
            if($cid){
                $data = $data[$cid]['child'];
            }
            if($county_id){
                $data = $data[$county_id]['child'];
            }
            foreach($data as $k=>$v){
                unset($data[$k]['child']);
            }
        }
        $this->ajaxReturn($data);
	}
    
}