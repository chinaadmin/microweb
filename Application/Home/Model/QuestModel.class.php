<?php
/**
 * 问卷模型
 * @author cwh
 * @date 2015-06-04
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class QuestModel extends HomebaseModel{

	protected $tableName = 'quest_user_answer';

    public $question = [
        [
            'id'=>1,
            'title'=>'您在选择食品时最关注的是哪些方面？',
            'type'=>1,
            'options'=>[
                '食品的安全',
                '食品的口感',
                '食品的包装、颜色',
                '营养成分',
                '价格',
                '品牌'
            ]
        ],
        [
            'id'=>2,
            'title'=>'6月15日试吃活动中，您最喜欢吃哪种国产食品？(多选)',
            'type'=>2,
            'options'=>[
                '春光食品',
                '西域果园产品',
                '羊奶粉',
                '友臣肉松饼'
            ]
        ],
        [
            'id'=>3,
            'title'=>'请问您购买此类食品的频率是？',
            'type'=>1,
            'options'=>[
                '每周一次或更多',
                '每月几次',
                '每月一次',
                '低于每月一次'
            ]
        ],
        [
            'id'=>4,
            'title'=>'您每次为自己或家人购买营养保健食品的消费总金额是？',
            'type'=>1,
            'options'=>[
                '100元以下',
                '100-200元',
                '200-400元',
                '400元以上'
            ]
        ],
        [
            'id'=>5,
            'title'=>'请问您更喜欢到实体店购买产品还是网上购买？',
            'type'=>1,
            'options'=>[
                '实体店',
                '网上'
            ]
        ],
        [
            'id'=>6,
            'title'=>'如果您在网上购买，希望由哪种配送方式？',
            'type'=>1,
            'options'=>[
                '到门店自提',
                '由物流配送',
                '由商城配送'
            ]
        ],
        [
            'id'=>7,
            'title'=>'零食消费中，请问您在进口食品中消费金额比例？',
            'type'=>1,
            'options'=>[
                '30%以内',
                '30%—60%',
                '60%以上'
            ]
        ],
        [
            'id'=>8,
            'title'=>'您更喜欢那种促销方式？',
            'type'=>1,
            'options'=>[
                '特价',
                '满减',
                '赠品',
                '试吃'
            ]
        ],
        [
            'id'=>9,
            'title'=>'您的收入水平是？',
            'type'=>1,
            'options'=>[
                '3000-4000元',
                '4000-6000元',
                '6000-8000元',
                '8000元以上'
            ]
        ],
        [
            'id'=>10,
            'title'=>'您的年龄段是？',
            'type'=>1,
            'options'=>[
                '20-25岁',
                '26-30岁',
                '31-35岁',
                '36岁以上'
            ]
        ]
    ];

    /**
     * 添加用户
     * @param array $data 用户数据
     * @return mixed
     */
    public function addUser($data = []){
        $result = $this->result();
        $quest_user_model = M('QuestUser');
        $quest_user_info = $quest_user_model->where(['mobile'=>$data['mobile']])->find();
        $id = 0;
        if($quest_user_info){
            $user_result = $quest_user_model->data([
                'name'=>$data['name'],
                'update_time'=>time()
            ])->where(['id'=>$quest_user_info['id']])->save();
            $id = $quest_user_info['id'];
        }else {
            $data['update_time'] = time();
            $data['add_time'] = time();
            $user_result = $quest_user_model->data($data)->add();
            $id = $user_result;
        }
        return $user_result !== false ? $result->content($id)->success('添加成功') : $result->set('DATA_INSERTION_FAILS');
    }

    /**
     * 获取试题
     * @param null $id 试题id
     * @return array
     */
    public function getQuestion($id = null){
        return is_null($id)?$this->question:$this->question[$id];
    }


    /**
     * 导出问卷
     */
    public function export(){
        $qusrtion_count = 10;
        $excel = new \Admin\Org\Util\ExcelComponent ();
        $excel = $excel->createWorksheet ();

        $heads = [
            '客户姓名',
            '联系电话',
            '提交时间'
        ];
        $data_field = [
            'name',
            'mobile',
            'update_time'
        ];
        for($i=1;$i<=$qusrtion_count;$i++){
            $heads[] = '第'.$i.'题';
            $data_field[] = 'question'.$i;
        }
        $excel->head($heads,"Candara","16","30");

        $question_user = M('QuestUser')->field(true)->select();
        $answer_lists = M('QuestUserAnswer')->field('uid,qid,answer')->select();
        $answers = [];
        foreach($answer_lists as $v){
            $answers[$v['uid']][$v['qid']] = $v['answer'];
        }

        $questions = $this->getQuestion();
        $qusrtion_lists = [];
        foreach($questions as $v){
            $qusrtion_lists[$v['id']] = $v;
        }

        $data = array ();
        foreach ( $question_user as $key => $v ) {
            $data [$key] ['name'] = $v ['name'];
            $data [$key] ['mobile'] = $v ['mobile'];
            $data [$key] ['update_time'] = empty($v['update_time'])?'':date('Y-m-d H:i',$v ['update_time']);
            for($i=1;$i<=$qusrtion_count;$i++){
                $data [$key] ['question'.$i] = $this->formatAnswers($answers[$v['id']][$i],$qusrtion_lists[$i]['type']);
            }
        }
        $excel->listData ($data,$data_field);
        $file_name = "export";
        $excel->output ( $file_name . ".xlsx" );
    }

    /**
     * 格式化答案
     * @param string $answer 答案
     * @param int $type 类型
     * @return string
     */
    public function formatAnswers($answer,$type = 1){
        if(empty($answer)){
            return '';
        }

        switch($type){
            case 1://单选
                $answer = $this->lettersConvert($answer);
                break;
            case 2://多选
                $answer_arr = explode(',',$answer);
                $answer = '';
                foreach($answer_arr as $v){
                    $answer .= $this->lettersConvert($v);
                }
                break;
            case 3:
                break;
        }

        return $answer;
    }

    /**
     * 字母与数字转换
     * @param string $str 字母或数字
     * @param int $type 0为数字转字母，1为字母转数字
     * @return bool|int|string
     */
    protected function lettersConvert($str,$type = 0){
        $i_chr = 64;
        if(empty($str)){
            return false;
        }
        if($type===0){
            return $str>26?false:chr($str+$i_chr);
        }else{
            return ord($str)-$i_chr;
        }
    }
}