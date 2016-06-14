<?php
/**
 * 推荐位
 * @author cwh
 * @date 2015-04-23
 */
namespace Home\Widget;
use Think\Controller;
class RecommendWidget  extends Controller {

    /**
     * 显示推荐
     * @param string $id 推荐位id
     * @return mixed|void
     */
    public function show($id){
        $adp_model = D('Admin/AdPlace');
        $adp_info = $adp_model->scope()->where(['adp_id'=>$id])->field(true)->find();
        if(empty($adp_info)){
            return '';
        }
        $ad_lists = D('Admin/Ad')->getListsToInfo($adp_info);
        $ad_lists = array_map(function($info){
            if($info['url_type']==1 && empty($info['url'])){
                switch($info['link_point']){
                    case 1:
                        $info['url'] = U('detail/index',['id'=>$info['link_id']]);
                        break;
                }
            }
            return $info;
        },$ad_lists);
        $this->adp_info = $adp_info;
        $this->assign('ad_lists',$ad_lists);
        $show_fun = 'show_'.$adp_info['type'];
        $this->$show_fun($ad_lists);
    }

    /**
     * 图片广告
     * @param array $ad_lists 广告列表
     */
    private function show_1(array $ad_lists){
        shuffle($ad_lists);
        $this->assign('ad_info',current($ad_lists));
        $this->display('Widget:Recommend:image');
    }

    /**
     * 轮播广告
     * @param array $ad_lists 广告列表
     */
    private function show_2(array $ad_lists){
        $this->display('Widget:Recommend:banner');
    }
} 