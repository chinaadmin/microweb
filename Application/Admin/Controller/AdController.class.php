<?php
/**
 * 广告逻辑类
 * @author cwh
 * @date 2015-04-20
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Org\Util\Date;

class AdController extends AdminbaseController {

    protected $curent_menu = 'Ad/place';

    /**
     * 类型
     * @var int
     */
    public $type = 0;

    public function _init(){

    }

    /**
     * 设置类型
     * @param int $type 类型
     */
    private function _set_type($type){
        $this->type = $type;
        $this->assign('type',$type);
        if(!empty($type)) {
            $adp_model = D('Admin/AdPlace');
            $type_name = $adp_model->getType($type);
            $this->assign('type_name', $type_name);
        }
        $this->setCurrentMenu('Ad/place?type='.$type);
    }

    public function place(){
        //设置类型
        $this->_set_type(I('request.type',0));
        $adplace_model = D('Admin/AdPlace');
        $where = [];
        $where['type'] = $this->type;
        $lists = $this->lists($adplace_model,$where,'add_time desc');
        /*$lists = array_map(function($info)use($adplace_model){
            $types = $adplace_model->getType();
            $info['type_name'] = $types[$info['type']];
            return $info;
        },$lists);*/
        $this->assign('lists',$lists);
        $this->display();
    }

    /**
     * 编辑
     */
    public function placeedit(){
        $adplace_model = D('Admin/AdPlace');
        //获取类型
        //$type_list = $adplace_model->getType();
        //$this->assign('type_list',$type_list);

        $id = I('request.adp_id');
        if(!empty($id)) {
            $info = $adplace_model->field(true)->find($id);
        }
        //设置类型
        if(empty($info['type'])){
            $this->_set_type(I('request.type',0));
        }else{
            $this->_set_type($info['type']);
        }

        $this->assign('info', $info);
        $this->assign('att_feat',D('Admin/Goods')->getAttrFeat());
        $this->display();
    }

    /**
     * 更新
     */
    public function placeupdate(){
        $id = I('request.adp_id');
        $adplace_model = D('Admin/AdPlace');
        $featured = I('request.featured');
        $featured_val = 0;
        if(!empty($featured) && !in_array(0,$featured)){
            foreach($featured as $v){
                $featured_val |= $v;
            }
        }
        $data = [
            'type' => I('request.type'),
            'name' => I('request.name'),
            'desc' => I('request.desc'),
            'featured'=>$featured_val,
            'width' => I('request.width'),
            'height' => I('request.height'),
            'status' => I('request.status')
        ];
        if(!empty($id)) {
            $where = [
                'adp_id' => $id
            ];
            $data['adp_id']=$id;
            $result = $adplace_model->setData($where,$data);
        }else{
            $result = $adplace_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function placedel(){
        $id = I('request.adp_id');
        $adplace_model = D('Admin/AdPlace');
        if(D('Admin/Ad')->isExistAd($id) > 0){
            $this->ajaxReturn($this->result->error('请先删除广告')->toArray());
        }else {
            $where = [
                'adp_id' => $id
            ];
            $result = $adplace_model->delData($where);
            $this->ajaxReturn($result->toArray());
        }
    }

    /**
     * 列表
     */
    public function index(){
        $adp_id = I('request.adp_id');
        $this->assign('adp_id',$adp_id);

        $info = D('Admin/AdPlace')->field(true)->find($adp_id);
        //设置类型
        $this->_set_type($info['type']);

        $ad_model = D('Admin/Ad');
        $where = [];
        $where['adp_id'] = $adp_id;
        $ad_lists = $this->lists($ad_model,$where,'sort desc,add_time desc');
        if($this->type ==3){
            $goods_ids = array_column($ad_lists,'goods_id');
            if(empty($goods_ids)){
                $goods_lists = [];
            }else {
                $goods_lists = D('Admin/Goods')->where(['goods_id' => ['in', $goods_ids]])->getField('goods_id,name,code');
            }
            $ad_lists = array_map(function($info)use($goods_lists){
                $info['goods_name'] = $goods_lists[$info['goods_id']]['name'];
                return $info;
            },$ad_lists);
        }
        $Date = new Date();
        foreach($ad_lists as $i=>$v){
            if($v['status']==0){
                $ad_lists[$i]['date'] = '--';
                $ad_lists[$i]['status_name'] = toStress('禁用', 'label-important');
            }else {
                if ($v['end_time'] < NOW_TIME) {
                    $ad_lists[$i]['date'] = 0;
                    $ad_lists[$i]['status_name'] = toStress('已过期', 'label-important');
                } else if ($v['start_time'] > NOW_TIME) {
                    $ad_start_time = date('Y-m-d', $v['start_time']);
                    $day = $Date->dateDiff($ad_start_time);
                    $ad_lists[$i]['date'] = ceil($day) <= 0 ? 0 : ceil($day);
                    $ad_lists[$i]['status_name'] = toStress('未开始', 'label-danger');
                } else {
                    $ad_end_time = date('Y-m-d', $v['end_time']);
                    $day = $Date->dateDiff($ad_end_time);
                    $ad_lists[$i]['date'] = ceil($day) <= 0 ? 0 : ceil($day);
                    $ad_lists[$i]['status_name'] = toStress('投放中', 'label-success');
                }
            }
        }
        $this->assign('lists',$ad_lists);
        $this->display();
    }

    /**
     * 编辑
     */
    public function edit(){
        $ad_model = D('Admin/Ad');
        $id = I('request.ad_id');
        if(!empty($id)) {
            $ad = $ad_model->field(true)->find($id);
        }

        $adp_id = I('request.adp_id');
        if(!empty($adp_id)){
            $ad['adp_id'] = $adp_id;
        }
        $this->_set_type($ad['adp_id']);
        if(!empty($ad['photo'])) {
            $ad ['photo'] = D('Upload/AttachMent')->getAttach($ad ['photo']);
        }
        if(!empty($ad['mobile_photo'])) {
        	$ad ['mobile_photo'] = D('Upload/AttachMent')->getAttach($ad ['mobile_photo']);
        }
        $adp_model = D('Admin/AdPlace');
        $adp_info = $adp_model->field(true)->find($ad['adp_id']);
        //设置类型
        $this->_set_type($adp_info['type']);

        $ad['adp_name'] = $adp_info['name'];
        $ad['featured'] = $adp_info['featured'];
        //$ad['adp_type'] = $adp_model->getType($adp_info['type']);
        $ad['width'] = $adp_info['width'];
        $ad['height'] = $adp_info['height'];
        //获取裁剪图
//         $ad['mobile_photo'] = "";
//         $pathinfo = pathinfo($ad['photo'][0]['path']);
//         $mobile_photo = $pathinfo['dirname']."/".$pathinfo['filename']."_crop.".$pathinfo['extension'];
//         if(is_file(".".trim($mobile_photo,"."))){
//         	$ad['mobile_photo'] = $mobile_photo;
//         }
        $this->assign('info', $ad);
        if($ad['link_point']==1){
        	$goods_info = D('Admin/Goods')->where(['goods_id'=>$ad['link_id']])->find();
        	$this->assign('goods_info', $goods_info);
        }
        if($ad['link_point']==2){
        	$cat_info = D('Admin/Category')->where(['cat_id'=>$ad['link_id']])->find();
        	$this->assign('cat_info', $cat_info);
        }
        $template = 'edit';
        switch($this->type){
            case 3://商品推荐
                $template .= '_3';
                break;
            default:
        }
        $this->display($template);
    }

    /**
     * 更新
     */
    public function update(){
        $id = I('request.ad_id');
        $admin_model = D('Admin/Ad');
        $data = [
            'adp_id' => I('request.adp_id'),
            'name' => I('request.name'),
            'url' => I('request.url'),
            'url_type'=>I('request.url_type'),
            'goods_id' => I('request.goods_id'),
            'photo' => I('request.photo'),
            'mobile_photo' => I('request.mobile_photo'),
            'photo_alt' => I('request.photo_alt'),
            'desc' => I('request.desc'),
            'start_time' => I('request.start_time'),
            'end_time' => I('request.end_time'),
            'sort'=>I('request.sort'),
            'status' => I('request.status'),
            'remark' => I('request.remark'),
            'bd_color'=>I('request.bd_color',''),
            'link_point'=>I('request.link_point',''),
            //'link_id'=>I('request.goods_id')
        ];
        $data['link_id'] = $data['link_point']==1?I("request.goods_id",''):I("request.cat_id");
        if($data['url_type']==1){
        	$data['url']='';
        	if(empty($data['link_id'])){
        		$this->ajaxReturn($this->result->error("请选择商品或分类！")->toArray());
        	}
        }
        if($data['url_type']==2){
        	// $data['link_id']==0;
            // $data['link_point']=0;

            $data['link_point'] = I('request.link_point_out'); // 外部链接活动类型
        	$data['link_id'] = I('request.link_id_out'); // 外部链接链接id


        	if(empty($data['url'])){
        		$this->ajaxReturn($this->result->error("链接不能为空")->toArray());
        	}
        	$preg_url = '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/';
            if(!preg_match($preg_url, $data['url'])){
            	$this->ajaxReturn($this->result->error("请填写正确的链接地址！")->toArray());
            }
        }
        if(!empty($id)) {
            $where = [
                'ad_id' => $id
            ];
            $data['ad_id']=$id;
            $result = $admin_model->setData($where,$data);
        }else{
            $result = $admin_model->addData($data);
        }
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('request.ad_id');
        $ad_model = D('Admin/Ad');
        $where = [
            'ad_id' => $id
        ];
        $result = $ad_model->delData($where);
        $this->ajaxReturn($result->toArray());
    }

    /**
     * 保存排序
     */
    public function sort(){
        $configs_model = D('Admin/Ad');
        $sort = I('request.sort');
        $result = $configs_model->saveSort($sort,false,'sort','ad_id');
        $this->ajaxReturn($result->toArray());
    }
    
    /**
     * 图片裁剪
     */
    public function cropper(){
    	$path = ".".trim(I("post.path",""),".");
    	if(!is_file($path)){
    		$this->ajaxReturn($this->result->error("图片文件错误！")->toArray());
    	}
    	$w = I("post.width",0,'intval');
    	$h = I("post.height",0,'intval');
    	$x = I("post.x",0,'intval');
    	$y = I("post.y",0,'intval');
    	$path = D("Upload/ProcessImage")->crop($path,$w, $h, $x, $y,$w,$h);
    	if(is_file($path)){
    		$path = trim($path,".");
    		$this->ajaxReturn($this->result->content($path)->success()->toArray());
    	}else{
    		$this->ajaxReturn($this->result->error()->toArray());
    	}
    }
    
    /**
     * 获取分类
     */
    public function getCategory(){
    	$icon = array (
    			'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
    			'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
    			'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
    	);
    	$lists = D("Admin/Category")->getTree(true,$icon);
        $this->ajaxReturn($this->result->content($lists)->success()->toArray());
    }

}