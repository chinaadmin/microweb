<?php
/**
 * 购物车模型类
 * @author cwh
 * @date 2015-05-04
 */
namespace Home\Model;
use Common\Model\SharedModel;
use User\Org\Util\Integral;
use User\Org\Util\User;

use Common\Model\HomebaseModel;
use Admin\Controller\PromotionsController;
class CartModel extends HomebaseModel{

    protected $tableName = 'cart';

    /**
     * 用户ID
     * @var int
     */
    public $user_id = 0;

    /**
     * 用户购物车标识
     * @var int
     */
    public $user_code = '';

    /**
     * 初始化
     * @see Model::_initialize()
     */
    public function _initialize(){
        parent::_initialize();
        $user = User::getInstance ();
        $user_id = $user->isLogin();
        $this->user_id = empty($user_id)?0:$user_id;
        $this->user_code = $this->mark();
    }

    /**
     * 标识
     */
    public function mark(){
        $mark = cookie('mark','',['prefix'=>'cart_']);
        if (empty($mark)){
            $mark = md5(uniqid().rand_string());
            cookie('mark',$mark,['prefix'=>'cart_']);
        }
        return $mark;
    }

    /**
     * 将本地购物车合并到自己的购物车里
     */
    public function merger(){
        //合并本地购物车中课程
        $where = array();
        $where['session_id'] = $this->user_code;
        $data = array();
        $data['uid'] = $this->user_id;
        $data['session_id'] = '';
        $count = false;
        $one_where = $this->where($where)->field('id',true)->find();
        if($one_where){
            $one_where['uid'] = $this->user_id;
            $one_where['session_id'] = '';
            $count = $this->where($one_where)->count();
        }
        if(!$count){//如果不存在相同纪录
            $this->where($where)->data($data)->save();
        }
    }

    /**
     * 获取购物车数量
     */
    public function getCartCount(){
        //缓存购物车数量
        static $_cart_count = 0;
        if ($_cart_count != 0){
            return $_cart_count;
        }
        $where = [];
        if(empty($this->user_id)){
            $where['session_id'] = $this->user_code;
        }else{
            $where['uid'] = $this->user_id;
        }
        $result = $this->where($where)->sum('number');
        $_cart_count = empty($result)?0:$result;
        return $_cart_count;
    }

    /**
     * 获取购物车列表详细
     * @param array $other_where 额外条件
     * @return mixed
     */
    public function getListsDetail($other_where = []){
        $lists = $this->getLists($other_where);
        $total = [];
        array_map(function($info) use (&$total){
            $total['goods_price'] += $info['price'];
            $total['number'] += $info['number'];
        },$lists);
        return [
            'lists'=>$lists,
            'total'=>$total
        ];
    }

    /**
     * 获取购物车列表
     * @param array $other_where 额外条件
     * @return mixed
     */
    public function getLists($other_where = []){
        $where = [];
        if(empty($this->user_id)){
            $where['session_id'] = $this->user_code;
        }else{
            $where['uid'] = $this->user_id;
        }
        $cart_ids = $this->where($where)->getField("cart_id",true);
        if($cart_ids){
        	$this->setDiscount($cart_ids);
        }
        $data = $this->goodsView()->where($other_where)->where($where)->order('add_time desc')->select();
        D('Admin/Order')->getPic($data);
        return $data;
    }
    
    /**
     * 购物车列表判断折扣
     */
    public function setDiscount($cart_id){
    	$where = array(
    			'cart_id'=>array('in',(array)$cart_id),
    	);
    	$carts = $this->where($where)->select();
    	$setData = array();
    	if($carts){
    		$goods = array_column($carts, "goods_id");
    		$promotions = D("User/Promotions")->verifyGoods($goods,"1,2");
    	    if($promotions){
    	    	foreach($carts as $v){
    	    		if($promotions[$v['goods_id']]){
    	    			if($promotions[$v['goods_id']]['discount']!=$v['promotions_discount']){
	    	    			$setData[$v['cart_id']] = array(
	    	    					'promotions_id' => $promotions[$v['goods_id']]['id'],
	    	    					'promotions_discount' => $promotions[$v['goods_id']]['discount'],
	    	    					'price'=>($promotions[$v['goods_id']]['discount']*$v['goods_price']*$v['number'])/10
	    	    			);
    	    			}
    	    		}else{
    	    			$setData[$v['cart_id']] = array(
    	    				'promotions_id' => 0,
							'promotions_discount' => 10,
    	    				'price'=>$v['goods_price']*$v['number']
    	    			);
    	    		}
    	    	}
    	    }else{
    	    	$this->where ( $where )->save ( [ 
						'promotions_id' => 0,
						'promotions_discount' => 10,
						'price' => $v ['goods_price'] * $v ['number'] 
				] );
    	    }
    	}
    	if($setData){
    		foreach($setData as $key=>$v){
    			$this->where ( [ 
						'cart_id' => $key 
				] )->save ( [ 
						'promotions_id' => $v ['promotions_id'],
						'promotions_discount' => $v ['promotions_discount'],
						'price'=>$v['price']
				] );
    		}
    	}
    }

    /**
     * 商品试图
     */
    public function goodsView(){
        $viewFields = array (
            'Cart' => array (
                'cart_id',
                'uid',
                'session_id',
                'goods_id',
                'goods_code',
                'market_price',
                'goods_price',
                'goods_credits',
                'price',
                'number',
                'norms',
                'norms_attr',
                'norms_value',
                'add_time',
                'promotions_id',
                'promotions_discount',
                '_type' => 'LEFT'
            ),
            'Goods' => array (
                "code",
                "name",
                "brand_id",
                "attribute_id",

                'is_express', // 是否支持快递

                '_on' => 'Cart.goods_id=Goods.goods_id'
            )
        );
        return $this->dynamicView($viewFields);
    }

    /**
     * 加入购物车
     * @param int $goods_id 商品id
     * @param int $num 数量
     * @param null $norms 规格
     * @param bool $is_add 是否添加
     * @return \Common\Org\util\Results
     */
    public function addCart($goods_id,$num = 1,$norms = null,$is_add = true){
        if ($num < 1){
            return $this->result()->error('购买数量有误');
        }
        $norms = empty($norms)?null:trim($norms,'_');

        $where = [
            'goods_id' => $goods_id
        ];

        $goods_model = D('Admin/Goods');
        $goods_info = $goods_model->scope('sale')->field(true)->where($where)->find();
        if (empty($goods_info)){
            return $this->result()->error('该商品不存在或已经下架');
        }
        $norms_return = $goods_model->getSpecificNorms($goods_id,$norms);
        if(!$norms_return->isSuccess()){
            return $norms_return;
        }

        $norms_info = $norms_return->getResult();
        $norms_value = [];
        $norms = '';
        if(empty($norms_info)) {
            $number = $goods_info['stock_number'];
            $price = $goods_info['price'];
        }else {
            $number = $norms_info['norms_arr']['number'];
            $price = $norms_info['norms_arr']['price'];
            $norms_value = $norms_info['norms'];
            $norms_attr = 0;
            $norms_ids = [];
            foreach($norms_value as $v){
                $norms_ids[] = $v['id'];
                if(!empty($v['photo'])) {
                    $norms_attr = $v['photo'];
                }
            }
            $norms = implode('_',$norms_ids);
        }
        $where = [
            'goods_id' => $goods_id,
            'norms'=>$norms
        ];

        if(empty($this->user_id)){
            $where['session_id'] = $this->user_code;
            $where['uid'] = 0;
        }else{
            $where['session_id'] = '';
            $where['uid'] = $this->user_id;
        }
        $cart = $this->field(true)->where($where)->find();
        $result_data = [];

        //限时折扣优惠信息
        $promtions = D('User/Promotions')->verifyGoods([$goods_id],SharedModel::SOURCE_PC);
        $promtions_goods = $promtions[$goods_id];
        if ($cart) {
            $goods_num = $is_add?($cart['number'] + $num):$num;
            if(!empty($promtions_goods)){
                $cart['promotions_id'] = $promtions_goods['id'];
                $cart['promotions_discount'] = $promtions_goods['discount'];
                if($goods_num > $promtions_goods['limit'] && $promtions_goods['limit']>0){
                    //return $this->result()->error('该商品只能限购'. $promtions_goods['limit'].'件');
                }
            }
            $cart['number'] = $goods_num;
            //折后单价
            $cart['price'] = discountAmount($cart['goods_price'],$promtions_goods['discount']) * $cart['number'];
            $cart['add_time'] = time();
            $result = $this->save($cart);
            $result_data['cart_id'] = $cart['cart_id'];
        }else{
            $data = $where;
            $goods_num = $num;
            if(!empty($promtions_goods)){
                $data['promotions_id'] = $promtions_goods['id'];
                $data['promotions_discount'] = $promtions_goods['discount'];
                if($num > $promtions_goods['limit'] && $promtions_goods['limit']>0){
                    //return $this->result()->error('该商品只能限购'. $promtions_goods['limit'].'件');
                }
            }


            //获取商品积分
            $integral = Integral::getInstance();
            $param = [];
            $param['price'] = $price;//商品价格
            $param['is_special'] = 0;//是否特价商品
            if(!empty($goods_info['integral'])) {
                $param['appoint_price'] = $goods_info['integral'];//指定积分
            }
            $goods_credits = $integral->setParam($param)->getIntegral('buy_goods',$this->user_id);

            $data['goods_code'] = $goods_info['code'];
            $data['goods_price'] = $price;
            $data['market_price'] = $goods_info['old_price'];
            $data['goods_credits'] = $goods_credits;
            $data['number'] = $goods_num;
            $data['price'] = discountAmount($data['goods_price'],$promtions_goods['discount']) * $data['number'];
            $data['norms'] = $norms;
            $data['norms_attr'] = empty($norms_attr)?0:$norms_attr;
            $data['norms_value'] = json_encode($norms_value);
            $data['add_time'] = time();
            $result = $this->add($data);
            if ($result!==false){
                $result_data['cart_id'] = $result;
            }
        }

        if($result!==false){
            return $this->result()->content($result_data)->success('加入购物车成功');
        }else{
            return $this->result()->error('加入购物车失败，请稍后再试');
        }
    }
    

    /**
     * 更新购物车
     * @param int $id 购物车id
     * @param int $num 数量
     * @return \Common\Org\util\Results
     */
    public function updateCart($id,$num){
        if ($num < 1){
            return $this->result()->error('购买数量有误');
        }

        $cart_info = $this->find($id);
        $cart_info['number'] = $num;
        $cart_info['price'] = $cart_info['goods_price'] * $cart_info['number'];
        $result = $this->save($cart_info);
        if ($result===false){
            return $this->result()->error('更新购物车失败，请稍后再试');
        }
        return $this->result()->content($cart_info)->success();
    }

    /**
     * 移除购物车中指定商品
     * @param int $goods_id 商品id
     * @return bool
     */
    public function delGoods($goods_id){
        $where = [];
        $where['goods_id'] = $goods_id;
        $result = $this->where($where)->delete();
        if($result === false){
            return $this->result()->error('删除失败');
        }else{
            return $this->result()->success();
        }
    }

    public function delAll($where){
        $result = M('Cart')->where($where)->delete();

        return $result !== false ? $this->result()->success('删除成功') : $this->result()->set('DATA_DELETE_FAILED');
    }

    /**
     * 选择全部
     * @return \Common\Org\util\Results
     */
    public function selectAllItem(){
        $where = [];
        if(empty($this->user_id)){
            $where['session_id'] = $this->user_code;
        }else{
            $where['uid'] = $this->user_id;
        }
        $select_item = $this->where($where)->getField('cart_id',true);
        $this->cacheSelectItem($select_item);
        return $this->result()->success();
    }

    /**
     * 取消全部选中
     * @return \Common\Org\util\Results
     */
    public function cancelAllItem(){
        $this->cacheSelectItem(null);
        return $this->result()->success();
    }

    /**
     * 选中商品
     * @param int $id 购物车id
     * @return \Common\Org\util\Results
     */
    public function selectItem($id){
        $select_item = $this->cacheSelectItem();
        if(empty($select_item)){
            $select_item = [];
        }
        if(!in_array($id,$select_item)){
            $select_item[] = $id;
        }
        $this->cacheSelectItem($select_item);
        return $this->result()->success();
    }

    /**
     * 取消选中商品
     * @param int $id 购物车id
     * @return \Common\Org\util\Results
     */
    public function cancelItem($id){
        $select_item = $this->cacheSelectItem();
        $new_item = [];
        foreach($select_item as $k=>$v){
            if($v != $id){
                $new_item[] = $v;
            }
        }
        if(empty($select_item)){
            $this->cacheSelectItem(null);
        }else {
            $this->cacheSelectItem($new_item);
        }
        return $this->result()->success();
    }

    /**
     * 缓存选中的商品
     * @param string $value
     * @return mixed
     */
    public function cacheSelectItem($value = ''){
        return session('cart_item',$value);
    }
    /**
     * 获取推荐商品
     * @param  $goods_id 商品id
     * @param number $limit
     * @return Ambigous <\Think\mixed, boolean, multitype:, unknown, mixed, object>
     */
    public function recommGoods($goods_id,$limit=3){
    	$goods_model = M('Goods');
    	$where = array(
    			"goods_id"=>$goods_id
    	);
    	$field = array("name","goods_id","attribute_id","price");
    	//关联商品
    	$ids = M("GoodsLink")->where($where)->limit($limit)->getField("link_id",true);
    	$count = count($ids);
    	$data = array();
    	if($ids){
    	 $where = array(
    			"goods_id"=>array('in',$ids),
    			"is_goods"=>1
    	 );
    	 $data = $goods_model->field($field)->where($where)->limit($limit)->select();
    	}
    	if(empty($ids) || $count<$limit){
    		$goods = $goods_model->field("cat_id,type_id")->where($where)->find();
    		$where = array(
    				"type_id"=>$goods['type_id'],
    				"is_goods"=>1,
    				"goods_id"=>array('NEQ',$goods_id)
    		);
    		if($ids){
    			$type_limit = $limit-$count;
    			$ids[] = $goods_id;
    			$where['goods_id'] = array("not in",$ids);
    		}
    		$type_data = $goods_model->field($field)->where($where)->limit($type_limit)->select();
    		if($type_data){
    		 $data = array_merge($data,$type_data);
    		 $count = count($data); 
    		}
    		if(empty($data) || $count<$limit){
    			unset($where['type_id']);
    			$cat_limit = $limit-$count;
    			$where["cat_id"] = $goods['cat_id'];
    			$category_data = $goods_model->field($field)->where($where)->limit($cat_limit)->select();
    			if($category_data){
    				$data = array_merge($data,$category_data);
    				$count = count($data);
    			}
    		}
    		if(empty($data) || $count<$limit){
    			unset($where['cat_id']);
    			$goods_limit = $limit-$count;
    			$goods_data = $goods_model->field($field)->where($where)->limit($goods_limit)->select();
    			if($goods_data){
    				$data = array_merge($data,$goods_data);
    			}
    		}
    	}
    	
    	D("Home/List")->getThumb($data,0);
    	return $data;
    }
    
    
   /**
     * 购物车列表判断是否限购
     * @param array $cartList 购物车数据
     * @param string $uid 用户id
     */
    public function isPurchase($cartList,$uid){
    	$goods = array_column($cartList, "goods_id");
    	$promotions_id = array_column($cartList, "promotions_id");
    	$where = array(
    			'uid'=>$uid,
    			'pay_status'=>2
    	);
    	$orders = M("Order")->where($where)->getField("order_id",true);
    	if($orders){
    		$where = array(
    				'order_id'=>array('in',$orders),
    				'promotions_id'=>array('in',$promotions_id)
    		);
    		$orderGoods = M("OrderGoods")->field("goods_id,promotions_id,number")->where($where)->select();
    	}
    	if($orderGoods){
    		foreach($cartList as &$v){
    			foreach ($orderGoods as $vo){
    				if($v['goods_id']==$vo['goods_id'] && $v['promotions_id']==$vo['promotions_id']){
    					$v['number']+=$vo['number'];
    				}
    			}
    		}
    	}
    	$promotions = D("User/Promotions")->verifyGoods($goods);
    	foreach ($cartList as $vs){
    		if($promotions[$vs['goods_id']]){
    			$limit = $promotions[$vs['goods_id']]['limit'];
    			if($limit && $limit<$vs['number']){
    				return $this->result()->error($vs['name']."限购".$limit."件");
    			}
    		}
    	}  
    	return $this->result()->success();
    }
}