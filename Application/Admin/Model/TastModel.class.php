<?php
/**
 * 免费试吃活动模型
 * @author xiongzw
 * @date 2015-09-14
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
class TastModel extends AdminbaseModel{
	protected $tableName = "activity_tast";
	/**
	 *公司人数
	 */
	public function peopleNum(){
		return array(
				'1'=>'20-50',
				'2'=>'50-100',
				'3'=>'100-200',
				'4'=>'200以上'
		);
	}
	
	/**
	 * 公司职位
	 */
	public function position(){
		return array(
				'1'=>'员工',
				'2'=>'主管',
				'3'=>'经理',
				'4'=>'总监',
				'5'=>'总裁',
				'6'=>'董事长'
		);
	}
}