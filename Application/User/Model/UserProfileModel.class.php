<?php
/**
 * 用户信息类
 * @author cwh
 * @date 2015-04-09
 */
namespace User\Model;
use Common\Model\BaseModel;
use Common\Org\Util\Results;
use Think\Model\RelationModel;
use User\Interfaces\UserInterface;
use User\Org\Util\Integral;

class UserProfileModel extends BaseModel{

    protected $tableName = 'user_profile';
}
