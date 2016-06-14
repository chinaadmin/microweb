<?php
/**
 * 推荐模型
 * @author cwh
 * @date 2015-04-24
 */
namespace Home\Model;
use Common\Model\HomebaseModel;

class RecommendModel extends HomebaseModel{

    protected $autoCheckFields = false;

    /**
     * 首页爆款列表
     * @param $num 获取个数
     * @return array
     */
    public function defaultHotList($num=0){
        //首页爆款列表
        $hots_lists = D('Admin/Ad')->getLists(4,$num);
        $goods_ids = array_column($hots_lists,'goods_id');
        //商品列表
        if(empty($goods_ids)){
            $goods = [];
        }else {
            $where = [
                'goods_id' => ['in', $goods_ids]
            ];
            $goods = D('Admin/Goods')->where($where)->select();
        }
        $good_statistics = M('GoodsStatistics')->where($where)->getField('goods_id,sales');
        $goods_lists = [];
        foreach($goods as $v){
            $v['sales'] = empty($good_statistics[$v['goods_id']])?0:$good_statistics[$v['goods_id']];
            $goods_lists[$v['goods_id']] = $v;
        }

        return array_map(function($data) use ($goods_lists){
            $goods_info = $goods_lists[$data['goods_id']];
            $info = [];
            $info['name'] = empty($data['name'])?$goods_info['name']:$data['name'];
            $info['photo'] = $data['photo'];
            $info['photo_alt'] = $data['photo_alt'];
           // $info['url'] = U('Detail/index',['id'=>$data['goods_id']]);
            $info['url'] = $data['url'];
            $info['sales'] = $goods_info['sales'];
            $info['price'] = $goods_info['price'];
            $info['old_price'] = $goods_info['old_price'];
            $info['goods_id'] = $goods_info['goods_id'];
            $info['desc'] = $data['desc'];
            $info['url_type'] = $data['url_type'];
            $info['link_point'] = $data['link_point'];
            $info['link_id'] = $data['link_id'];
            return $info;
        },$hots_lists);
    }

}
