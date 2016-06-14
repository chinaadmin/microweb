<?php
/**
 * 共用数据模型
 */
namespace Common\Model;
use Think\Model;
class SharedModel extends BaseModel{

    protected $autoCheckFields  =   false;

    //来源
    const SOURCE_PC = 3;//pc端
    const SOURCE_WEIXIN = 1;//微信端
    const SOURCE_MOBILE = 2;//手机端

    private $source = [
        self::SOURCE_PC => 'PC端',
        self::SOURCE_WEIXIN => '微商城',
        self::SOURCE_MOBILE => '手机端'
    ];

    /**
     * 获取来源数据
     * @param null $source 来源
     * @return array|string
     */
    public function getSource($source = null){
        return is_null($source)?$this->source:$this->source[$source];
    }
}