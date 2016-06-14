<?php
/**
 * 地推管理
 * @author wxb
 * @date 2015-09-21
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class MarketPushingController extends AdminbaseController {
		protected $curent_menu = 'MarketPushing/index';
		public $model;
		public function _init(){
			$this->model = D('Admin/Admin');
		}
		public function index(){
			$d = D('Admin/Admin');
			$where = [];
			if($keywords = I('keywords')){
				$where['nickname|username'] = ['like','%'.$keywords.'%'];
				$this->keywords = $keywords;
			}
			$where['delete_time'] = 0;
			$where['status'] = 1;
			$where['role_id'] = $this->getRoleId();
			$fields = [
				'invite_code',	
				'username',
				'nickname',	
				'mobile',
				'sex',
				'uid',	
				'status'	
			];
			  
			$list = $this->lists($d,$where,'',$fields);
			$m = M('market_pushing_recommend');
			foreach ($list as &$value) {
					$value['recommendCount'] = $m->where(['mpc_recommend' => $value['uid']])->count();
			}
			$this->lists = $list;
			$this->display();
		}
		private function getRoleId(){
			return M('admin_role')->where(['type' => 2,'code' => 'dtry'])->getField('role_id');
		}
		function _after_my_edit(){
				$this->role_id = $this->getRoleId();
		}
		function market_pushing_recommend_list(){
			$mpc_recommend = I('uid');
			$uid = M('market_pushing_recommend')->where(['mpc_recommend' => $mpc_recommend])->getField('mpc_uid',true);
			
			$viewFields = [
					'User' => [
							'_as' => 'u',
							'_type' => 'left',
							'username', 
							'real_name',
							'add_time',
							'come_from',
							'uid'
					],
					'UserAnalysis' => [
							'_as' => 'ua',							
							'_on' => 'ua.uid = u.uid',
							'_type' => 'left',
							'consume_money',
							'pay_count'
					],
					'UserConnect' => [
							'_as' => 'uc',							
							'_type' => 'left',
							'_on' => 'uc.uid = u.uid',
							'type'
					]
			];
			$d = D('User/User')->dynamicView($viewFields);
			if($uid){
				$where['u.uid'] = ['in',$uid];
			}else{
				$this->lists = [];
				$this->display();exit;
			}
			
			// 			会员昵称/姓名/手机/邮箱
			if($keywords = I('keywords')){
				$where['real_name|username|mobile|email'] = ['like','%'.$keywords.'%'];
				$this->keywords = $keywords;
			}			
			$list = $this->lists($d,$where,'add_time DESC');
			$m = M('account_credits');
			foreach ($list as &$v){
						$v['acount_credits'] = $m->where(['uid' => $v['uid'],'type' =>1])->getField('credits');
						$v['acount_remain'] = $m->where(['uid' => $v['uid'],'type' => ['not in','1']])->sum('credits');
			}
			$this->lists = $list;
			$this->display();
		}
		
		public function update(){
			
			$_POST['att_id'] = $_POST['attachId'][0];
			unset($_POST['attachId']);
			if(I("request.uid")){
				unset($_POST['username']);
			} 
			parent::update();
		}
}