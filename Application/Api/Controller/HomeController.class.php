<?php
/**
 * App首页控制器
 * @author xiongzw
 * @date 2015-06-30
 */
namespace Api\Controller;
class HomeController extends ApiBaseController{
	/**
	 * banner图
	 */
	public function banner(){
		$home_model = D("Api/Home");
		$banner = $home_model->banner();
		$banner = $home_model->formatBanner($banner);
		$this->ajaxReturn($this->result->success()->content(['bannerList'=>$banner]));
	}

	/*
	 *	测试接口（批量生成用户的成长值数据）
	*/
	public function test(){
		$uids = M('AccountCredits')->field('uid')->group("uid")->select();
		$uid = array_column($uids,'uid');
		$dat = array();
		$i=0;
		foreach($uid as $k => $v){
			$res = M('AccountCredits')->where(array('uid'=>$v,'type'=>3))->field('type')->find();
			if(!$res){
				$dat[$i]=array(
					'uid'=>$v,
					'type'=>3,
					'credits'=>0,
				);
				$i++;
			}
		}
		if($dat){
			$res = M('AccountCredits')->addAll($dat);
		}else{
			$res = false;
		}

		if($res){
			$this->ajaxReturn($this->result->success()->content(['res'=>'已批量生成'.$res.'条数据！']));
		}
		$this->ajaxReturn($this->result->error('成就数据已批量生成！','ERROR'));
	}

	public function delD(){
		/*$uid = M('AccountCredits')->alias('ac')->join('LEFT JOIN __USER__ u ON ac.uid=u.uid')->where(array('username'=>'18818680902'))->group('ac.uid')->select();
		$uid = array_column($uid,'uid');

		$res = M('AccountCredits')->where(array('uid'=>array('IN',$uid)))->delete();
		dump($res);*/
	}

	/*
	 *	测试接口（获取用户积分信息）
	*/
	public function getU(){
		$uid = I('uid');
		$model = D("User/User");

		$res = $model->getUserCreditsInfo($uid);
		$this->ajaxReturn($this->result->success()->content(['res'=>$res]));
	}

	/*
	 *	测试接口（获取会员等级）
	*/
	public function grade(){
		$num = I('num');
		$model = D("User/UserGrade");
		$res  = $model->getUserGrade($num);
		$this->ajaxReturn($this->result->success()->content(['res'=>$res]));
	}
	
	/**
	 * 广告位
	 */
	public function advent(){
		$home_model = D("Api/Home");
		$advent = $home_model->advent();
		$data = array();
		foreach ($advent as $key=>$v){
			$data[$key] = array(
					"name"=>$v['name'],
					"url"=>$v['url'],
					"goodsId"=>$v['goods_id'],
					"photo"=>fullPath($v['photo']),
					"photoAlt"=>$v['photo_alt']
			);
		}
		$this->ajaxReturn($this->result->success()->content(['adList'=>$data]));
	}
	
	/**
	 * 首页爆款
	 *      传入参数
	 *      <code>
	 *        count 获取个数(选传) 
	 *      </code>
	 */
	public function recommend(){
		$count = I("post.count",0,'intval'); //获取个数
		$data = D('Home/Recommend')->defaultHotList($count);
		/* foreach($data as &$v){
			$v['url'] = fullPath($v['url']);
			$v['photo'] = fullPath($v['photo']);
			$v['photoAlt'] = $v['photo_alt'];
			unset($v['photo_alt']);
			$v['marketPrice'] = $v['old_price'];
			unset($v['old_price']);
			$v['goodsId'] = $v['goods_id'];
			unset($v['goods_id']);
		} */
		$data = D('Api/Home')->formatRecommend($data);
		$this->ajaxReturn($this->result->success()->content(['goodsList'=>$data]));
	}
	
	/**
	 * 首页推荐位
	 *       传入参数
	 *       <code>
	 *       feat 商品展示位
	 *       </code>
	 */
	public function position($limit = 16){
		// $data= D("Home/Home")->featGoods('sort desc,sales desc,Goods.add_time',C("JT_CONFIG_WEB_RECOM_CONFIG_BOUT_NUM"),2,1);
		$data= D("Home/Home")->featGoods('sort desc,sales desc,Goods.add_time', $limit, 2, 1);
		$lists = array();
		foreach($data as $key=>&$v){
			$lists[$key] = array(
					"feat"=>$v['feat_id'],
					"featName"=>$v['feat_name'],
					"photo"=>fullPath($v['photo']),
					"goods"=>D("Api/Home")->formatPosition($v['goods'])
			);
		}
		$this->ajaxReturn($this->result->success()->content(['goodsList'=>$lists]));
	}
	
	/**
	 * 展位列表
	 */
	public function feats(){
		$data=  D("Admin/Feat")->getMobilelFeats(1,1);
		$lists = array();
		foreach($data as $key=>&$v){
			$lists[$key] = array(
					"feat"=>$v['feat_id'],
					"featName"=>$v['feat_name'],
					"photo"=>fullPath($v['photo']),
					'skipUrl' => $v['feat_url'] ? $v['feat_url'] : ''
			);
		}
		$this->ajaxReturn($this->result->success()->content(['featList'=>$lists]));
	}
	
	/**
	 * 首页品牌列表
	 *        传入参数
	 *        <code>
	 *        count 获取个数(选传)
	 *        </code>
	 */
	public function brands(){
		//$count = I("post.count",0,'intval');
		$brands = D("Home/Home")->getBrands(C("JT_CONFIG_WEB_BRAND_NUM"));
		$brands = D("Api/Home")->formatBrand($brands);
		$this->ajaxReturn($this->result->success()->content(['brands'=>$brands]));
	}
	/**
	 * 分类列表
	 */
	public function getCats(){
		$cats = D("Api/Home")->getCarts();
		$this->ajaxReturn($this->result->success()->content(['cats'=>$cats]));
	}
	
	/**
	 * App启动图片接口
	 */
	public function put(){
		$put_model = D("Admin/Put");
		$type = I("post.type","1",'trim');
		if(empty($type)){
			$type = 1;
		}
		$type = json_encode((array)$type);
		$is_guide = I("post.guide",0,'intval');
		$where = array(
				"put_status"=>1,
				"end_time"=>array("EGT",NOW_TIME),
				"is_guide"=>$is_guide,
				"put_type"=>$type,
		);
		$order = [];
		$order['put_sort'] = 'desc';
		$order['add_time'] = 'desc';
		$data = $put_model->where($where)->order($order)->select();
		D("Home/List")->getThumb($data,"",'put_attr');
		$return_array = array();
		foreach($data as $key=>$v){
			$return_array[$key] = array(
					"title"=>$v['put_title'],
					"url"=>$v['put_url'],
					"description"=>$v['put_description'],
					"photo"=>fullPath($v[thumb])
			);
		}
		$this->ajaxReturn($this->result->content(['list'=>$return_array]));
	}
	
	/**
	 * 首页活动
	 */
	public function activity(){
		$data = D("Home/Home")->getActivity(null,0,1);
	    $this->ajaxReturn($this->result->content(['activity'=>$data]));
	}
	
}