<?php
/**
 * 格式化时间
 * @param integer $time 时间
 * @param string $format 格式化
 * @return void|string
 */
function dateFormat($time , $format = 'Y-m-d H:i'){
    if (!$time){
        return '-';
    }
    return date($format , $time);
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

/**
 * 返回状态文字样式
 * @param int $status 状态
 * @param array $replace 替换值
 * @param string $format
 * @return string
 */
function getSpanStatus($status = 1, Array $replace = null ,$format='<span class=" :style">:text</span>') {
    if(is_null($replace)) {
        $replace = [
            '-1' => ['style' => 'text-info', 'text' => '删除'],
            '0' => ['style' => 'text-danger', 'text' => '禁用'],
            '1' => ['style' => 'text-success', 'text' => '启用']
        ];
    }
    $search = [':text',':style'];
    return isset($replace[$status])?str_replace($search, [$replace[$status]['text'],$replace[$status]['style']], $format):'';
}

/**
 * 强调
 * @param string $text 文本
 * @param string $style 样式
 * @param string $format
 * @return mixed
 */
function toStress($text,$style='label-success',$format='<span class="label label-medium :style">:text</span>'){
    return str_replace([':text',':style'], [$text,$style], $format);
}
function getTypeText($typeKey,$typeArr,$default){
	if(isset($typeArr[$typeKey])){
		return $typeArr[$typeKey];
	}else if($default){
		return $default;
	}else {
		return '_';
	}
}
function checkEmpty($text,$default = '_'){
	if(!empty($text)){
		return $text;
	}
	return $default;
}

//显示用户
function showUserNameAdmin($uid){
	return D('Admin/Admin')->showUserName($uid);
}


/**
 * 返回退货状态
 * @param int $status 退货状态
 * @param array $replace 替换值
 * @param string $format
 * @return string
 */
function getRefundStatus($status = 1, $format='<span class="label label-medium :style">:text</span>') {
    $replace = [
        '-1' => ['style' => 'label-danger', 'text' => '审核未通过'],
        '0' => ['style' => 'label-important', 'text' => '待审核'],
        '1' => ['style' => 'label-warning', 'text' => '待退款'],
        '2' => ['style' => 'label-warning', 'text' => '退款中'],
        '3' => ['style' => 'label-success', 'text' => '已退款'],
        '4' => ['style' => 'label-warning', 'text' => '退款失败'],
        '5' => ['style' => 'label-warning', 'text' => '待退货'],
        '6' => ['style' => 'label-success', 'text' => '取消退款']
    ];
    $search = [':text',':style'];
    return isset($replace[$status]) ? str_replace($search, [$replace[$status]['text'],$replace[$status]['style']], $format) : '';
}

/**
 * 返回支付方式
 * @param string $type 支付类型
 * @return string
 */
function getPayType($type){
    $typeArr = [
        'ACCOUNT' => '账户余额',
        'WEIXIN' => '微信',
        'WEIXIN#APP' => '微信APP',
        'ALIPAY' => '支付宝',
        'COUPONPAY' => '优惠券'
    ];
    return $typeArr[$type];
}

/**
 * 返回配送方式
 * @param int $type 配型类型
 * @return string
 */
function getShippingType($type){
    $shippingType = [
        '0' => '自提',
        '1' => '快递',
        '2' => '门店发货'
    ];
    return $shippingType[$type];
}

/**
 * 返回收货状态
 * @param int $status 状态类型
 * @return string
 */
function getShippingStatus($status, $format='<span class=":style">:text</span>'){
    $shippingStatus = [
        '0' => ['style' => 'text-danger', 'text' => '未发货'],
        '1' => ['style' => 'text-warning', 'text' => '已发货'],
        '2' => ['style' => 'text-success', 'text' => '已收货'],
        '3' => ['style' => 'text-primary', 'text' => '退货'],
        '4' => ['style' => 'text-info', 'text' => '发货中']
    ];
    $search = [':style', ':text'];
    return isset( $shippingStatus[$status] ) ? str_replace($search, [ $shippingStatus[$status]['style'], $shippingStatus[$status]['text'] ], $format) : '';
}

/**
 * 获取试吃活动状态
 * @param int $endTime 活动结束时间
 * @param int $quantum 活动数量(份数)
 * @param int $surplus 剩余数量
 * @return string
 */
function getEatStatus($endTime, $quantum, $surplus){
    $now = time();
    if($endTime > $now){
        if( $quantum > 0 && $surplus == 0){
            return '<span class="text-primary">已抢光</span>';
        }else{
            return '<span class="text-success">进行中</span>';
        }
    }else{
        return '<span class="text-danger">已结束</span>';
    }
}