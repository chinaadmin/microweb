<?php
/**
 * 商品详情模型
 * @author xiongzw
 * @date 2015-07-01
 */
namespace Api\Model;
class DetailModel extends ApiBaseModel{
	protected $autoCheckFields  =   false;
	/**
	 * 格式化商品信息
	 * @param  $data
	 * @return array
	 */
	public function formatData($data){
		$size = C('THUMB_SIZE');
		foreach($data['thumb'] as &$v){
			$v['small'] = fullPath($v[$size[0]]);
			$v['middle'] = fullPath($v[$size[1]]);
			$v['big'] = fullPath($v[$size[2]]);
			unset($v[$size[0]]);
			unset($v[$size[1]]);
			unset($v[$size[2]]);
		}
		
		$pathHeader = 'http://'.C('JT_CONFIG_WEB_DOMAIN_NAME');
		$data['content'] = preg_replace('/(src=")\s*?([^http][^"]*?")/', '$1'.$pathHeader.'$2', htmlspecialchars_decode($data['content']));
// 	$data['content'] = preg_replace("/(<img.*src=\s*\"?)\s*(.+)(\s*\"?.\/*>?)/","\${1}".'http://'.C('JT_CONFIG_WEB_DOMAIN_NAME')."\${2}\${3}", html_entity_decode($data['content']));
		$return_array = array(
				"goods_id"=> $data['goods_id'],
				"name" => $data['name'],
				"marketPrice"=>$data['old_price'],
				"stockNumber"=>$data['stock_number'],
				"content"=>$data['content'],
				"sales" => $data['statistics']['sales'],
				"photo" => $data['thumb'],
				"price"=>$data['price']
		);
		return $return_array;
	}
	/**
	 * 格式化商品属性
	 * @param $attr
	 */
	public function formatAttr($attr){
		$data = array();
		foreach($attr['attr'] as $k=>$v){
			$data[$k]['name'] = $v['name'];
			$data[$k]['value']=$v['value'];
		}
		return $data;
	}
	
	/**
	 * 格式化norms
	 * @param array $norms 商品规格
	 * @return array
	 */ 
	public function formatNorms(Array $norms) {
		$norms_list = array ();
		$norms_values = array_column ( $norms, "norms_value_id" );
		if ($norms_values) {
			$where = array (
					'norms_value_id' => array (
							'in',
							$norms_values 
					) 
			);
			$data = M ( 'NormsValue' )->where ( $where )->select ();
			foreach ( $norms as $k => $v ) {
				$norms_list [$k] ['norms_value'] = $v ['norms_value'];
				$norms_list [$k] ['norms_value_id'] = $v ['norms_value_id'];
				$norms_list [$k] ['photo'] = empty ( $v ['norms_attr'] [0] ['path'] ) ? "" : fullPath($v ['norms_attr'] [0] ['path']);
				if ($data) {
					foreach ( $data as $vo ) {
						if ($vo ['norms_value_id'] === $v ['norms_value_id']) {
							$norms_list [$k] ['norms_id'] = $vo ['norms_id'];
						}
					}
				}
			}
			$norms_id = array_unique ( array_column ( $data, 'norms_id' ) );
			if(!$norms_id){
				return [];
			}
			$where = array (
					'norms_id' => array (
							'in',
							$norms_id 
					) 
			);
			$norms_data = M ( 'Norms' )->where ( $where )->select ();
			$datas = array();
			foreach ( $norms_data as $v ) {
				$arr = array();
				foreach ( $norms_list as $k=>&$vo ) {
					if ($vo ['norms_id'] == $v ['norms_id']) {
						$arr['parent_name']=$v['norms_name'];
						$arr['value'][]=$vo;
					}
				}
				array_push($datas, $arr);
			}
		}
		return $datas;
	}
}