<?php
namespace Api\Controller;
class FeedbackController extends ApiBaseController {

    /**
     * 获取意见反馈类型
     * @author cwh
     */
    public function opinionType(){
        $opinion_type = [
            [
                'id'=>1,
                'name'=>'网站体验需改进'
            ],
            [
                'id'=>2,
                'name'=>'网站缺失（bug）'
            ],
            [
                'id'=>3,
                'name'=>'商品不满意'
            ],
            [
                'id'=>4,
                'name'=>'我也不知道选什么'
            ],
        ];
        $this->ajaxReturn($this->result->content(['opinionType'=>$opinion_type])->success());
    }

    /**
     * 提交意见反馈类型
     * @author cwh
     *         传入参数:
     *         <code>
     *         type 反馈类型：多个用“，”隔开
     *         content 反馈内容
     *         contact 联系方式
     *         </code>
     */
    public function submitOpinion(){
        $type = I('type','','trim');
        if(empty($type)){
            $this->ajaxReturn($this->result->error('类型不能为空'));
        }

        $content = I('content','','trim');
        if(empty($content)){
            $this->ajaxReturn($this->result->error('反馈内容不能为空'));
        }

        $contact = I('contact','','trim');
        if(empty($contact)){
            $this->ajaxReturn($this->result->error('联系方式不能为空'));
        }

        $data = [];
        $data['su_type'] = $type;
        $data['su_content'] = $content;
        $data['su_email'] = $contact;
        $data['su_add_time'] = NOW_TIME;
        $res = M('suggestion')->add($data);
        $action = $res ? 'success' : 'error';
        $msg = $res ? '提交成功' : '提交失败';
        $this->ajaxReturn($this->result->$action()->setMsg($msg));
    }

}