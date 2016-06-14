<?php
/**
 * 格式化时间
 * @param integer $time 时间
 * @param string $format 格式化
 * @return void|string
 */
function dateFormat($time , $format = 'Y-m-d H:i'){
    if (!$time){
        return ;
    }
    return date($format , $time);
}
/**
 * 当前seo
 * @param array $arr 额外参数
 * 				title 标题
 * 				keywords 关键字
 *  			description 描述
 * @return string
 */
function cl_seo($arr = array()){
    $seo_info = array();
    $seo_info['title'] = C('jt_config_web_title');
    $seo_info['keywords'] = C('jt_config_web_keywords');
    $seo_info['description'] =  C('jt_config_web_description');
    if(!empty($arr)){
        //标题
        $seo_info['title'] = isset($arr['title'])?$arr['title']:$seo_info['title'];
        //关键字
        $seo_info['keywords'] = isset($arr['keywords'])?$arr['keywords']:$seo_info['keywords'];
        //描述
        $seo_info['description'] = isset($arr['description'])?$arr['description']:$seo_info['description'];
    }
    return array_map('delhtml',$seo_info);
}

/**
 * 返回状态
 * @param int $status 状态
 * @param array $replace 替换值
 * @param string $format
 * @return string
 */
function getStatus($status = 1, Array $replace = null ,$format='<span class="label label-medium :style">:text</span>') {
	if(is_null($replace)) {
		$replace = [
		'-1' => ['style' => 'label-important', 'text' => '删除'],
		'0' => ['style' => 'label-danger', 'text' => '禁用'],
		'1' => ['style' => 'label-success', 'text' => '启用']
		];
	}
	$search = [':text',':style'];
	return isset($replace[$status])?str_replace($search, [$replace[$status]['text'],$replace[$status]['style']], $format):'';
}
//显示用户
function showUserName($uid){
	return D('User/User')->showUserName($uid);
}