<?php
/**
 * 路由
 */
return array(
		'URL_ROUTER_ON'   => true,
		//路由规则
		'URL_ROUTE_RULES'=>array(
				//"detail/:id"=> "detail/index"
				"/^detail\/(\d+)/"=> 'detail/index?id=:1',
				"/^list\/(\d+)/" => "list/goodslists?catId=:1",
				"/^list\/(\w+)\/(desc|asc)\/(\d+)/" => "list/goodslists?:1=:2&catId=:3",
				"/^list\/(\d+)\/(\w+)\/(\d)$/" => "list/goodslists?catId=:1&:2=:3",
				"/^list\/(\w+)\/(desc|asc)\/(\d+)\/(\w+)\/(\d)/" => "list/goodslists?:1=:2&catId=:3&:4=:5",
 				"/^brand\/(\d+)/"=> 'brand/index?brand=:1',
 				"/^order\/(\d+)/"=> 'order/view?id=:1',
 				"/^refund\/(\w{32})/"=> 'refund/info?refund_id=:1'
        )
);