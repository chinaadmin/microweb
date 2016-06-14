<?php

/**
 * 物流查询接口类
 * 
 * @author liu
 * @date 2016/4/13
 */
namespace Common\Org\KuaiDi;
use Common\Org\Util\Curl;
	
class KuaiDi {
	private $Key = 'UGolkwKB6632';
	private $Url = 'http://www.kuaidi100.com/poll';
	private $return_url;
	private $counts = 0;
	public function __construct(){
		$this -> get_url();
	}
	
	public function get_info($rec_id){
		$arr = array('在途中','已揽收','疑难','已签收','退签','同城派送中','退回','转单','已发货');
		
		//获取物流信息
		
		$number = $this -> get_number($rec_id);
		
		$re = M('message_logistics') ->where(['number' =>$number]) ->order('logistics_info desc')->field('number,logistics_info,logistics_state,type,fk_lc_code')->find();
		//$re['count'] = M('message_logistics') ->where(['order_id' =>$rec_id]) -> count();
		$re['count'] = $this ->counts;
		//获取物流公司信息
		$lc_code = M("logistics_company") -> where(['lc_id'=>$re['fk_lc_code']]) ->field('lc_code,lc_name,lc_tel') -> find();
		$re['lc_name'] = $lc_code['lc_name'];
		$re['tel'] = $lc_code['lc_tel'];

		//获取商品图片
		if($re['type'] == 1){
			$img_id = D('Admin/Order') -> getGoodsByInfo($rec_id);
			$img_url = $img_id['pic'];
		}else{
			$img_id = M('crowdfunding_goods') -> where(['cg_id'=>$rec_id]) -> getfield('cg_att_id');
			$img = D( 'Upload/AttachMent' )->getAttach ($img_id);
			$img_url = $img[0]['path'];
		}
		if(empty($img_url)){
			$re['pic'] = 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/ListDefault/default.jpg';
		}else{
			$re['pic'] = 'http://'.$_SERVER['HTTP_HOST'].$img_url;
		}
		
		unset($re['type'],$img_url,$img_id);
		
		//更新状态
		M('message_logistics') ->where(['order_id' =>$rec_id])->save(['state'=>2]);
		
		$re['logistics_state'] = $arr[$re['logistics_state']];
		
		if($re['logistics_info']){
			$re['logistics_info'] = unserialize($re['logistics_info']);
		}else{
			$re['logistics_info'] = array();
		}
		
		return $re;
	}
	
	public function poat_url($post_data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL,$this ->Url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		return $return= curl_exec($ch);		//返回提交结果，格式与指定的格式一致（result=true代表成功）
	}
	
	/**
	 * 提交订阅
	 * 
	 * lc_code   物流公司码
	 * number    运单号码
	 * from      出发地
	 * to        收货地
	 */
	public function submit_data(){
		
		$map['logistics_state']  = array('neq',3);
		$map['poll']  = array('lt',4);
		$logistics = M('message_logistics') ->where($map) ->field('number,fk_lc_code')->select();
		
		array_unique($logistics);
		
		foreach($logistics as $v){
			$arr[$v['number']] = $v;
		}
		
		$logistics_company = M('logistics_company')->field('lc_id,lc_code') -> select();
		foreach($logistics_company as $v){
			$company[$v['lc_id']] = $v['lc_code'];
		}
		
		foreach($arr as $val){
			$post_data["schema"] = 'json' ;
			//callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key  callbackurl
			//$post_data["param"] = '{"company":"yuantong", "number":"881443775034378914","from":"", "to":"", "key":"'.$this -> Key.'", "parameters":{"callbackurl":"'.$this ->return_url.'"}}';
			$post_data["param"] = '{"company":"'.$company[$val['fk_lc_code']].'", "number":"'.$val['number'].'","from":"", "to":"", "key":"'.$this -> Key.'", "parameters":{"callbackurl":"'.$this ->return_url.'"}}';
			
			$o=""; 
			foreach ($post_data as $k=>$v)
			{
				$o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
			}
			$post_data=substr($o,0,-1);
			$curl = new Curl();
			$res = $curl->st_post($this->Url,$post_data);
			$arr = json_decode($res,true);
			if($arr['result']){
				$param['poll']=4;
				$where['number'] = $val['number'];
				M('message_logistics') ->where($where) -> save($param);
			}
		}
		
	}
	
	private function get_url(){
		$d_url = array('t-bihaohuo','tp-bihaohuo','tb-bihaohuo','bihaohuo','a');
		$arr = explode('.',$_SERVER['HTTP_HOST']);
		$url = array_intersect($d_url,$arr);
		rsort($url);
		
		if(empty($url)){
			return false;
		}else{
			$this -> return_url = 'http://api.'.$url[0].'.cn/KuaiDi/return_url';
		}
	}
	
	
	//通过rec_id获取运单号
	
	private function get_number($rec_id){
		$id = M('order_goods') -> where(['rec_id' =>$rec_id])->getfield('order_id');
		$this ->counts = M('order_goods') -> where(['order_id' =>$id])->count();
		if(!$id){
			$id = M('crowdfunding_order_goods') -> where(['cog_id' =>$rec_id])->getfield('fk_cor_order_id');
			$this ->counts = M('crowdfunding_order_goods') -> where(['fk_cor_order_id' =>$id])->count();
		}
		if($id){
			
			if($send_num = M('order_send') -> where(['order_id' =>$id])->order('send_num desc')->getfield('send_num')){
				return $send_num;
			}
		}
		
		return 1;
	}
}