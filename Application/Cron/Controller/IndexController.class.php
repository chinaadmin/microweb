<?php
namespace Cron\Controller;
use Think\Controller;

class IndexController extends Controller {

    /**
     * 返回信息
     */
    private $message = '';

    function _initialize() {
        //单个任务最大执行时间
        $CRON_MAX_TIME = C('CRON_MAX_TIME');
        if (!$CRON_MAX_TIME) {
            C('CRON_MAX_TIME',600);
        }
    }

    //执行计划任务
    public function index() {
        //超过进程限制
        /*if ($this->psActionExist ( $this->maxProcess )) {
            return false;
        }*/
        // 锁定自动执行
        $lockfile = RUNTIME_PATH . 'cron.lock';
        if (is_writable($lockfile) && filemtime($lockfile) > $_SERVER['REQUEST_TIME'] - C('CRON_MAX_TIME')) {
            return;
        } else {
            //设置指定文件的访问和修改时间
            touch($lockfile);
        }

        ignore_user_abort(true);
        set_time_limit(0);
        //fastcgi_finish_request();

        //执行计划任务
        $this->runCron();

        // 解除锁定
        unlink($lockfile);
    }

    /**
     * 递归执行计划任务
     */
    private function runCron() {
        $_time = time();
        $cron_model = D("Cron/Cron");
        $cron = $cron_model->field(true)->where(["is_open" => ["EGT", 1]])->order(["next_time" => "ASC"])->find();
        //检测是否还有需要执行的任务
        if (!$cron || $cron['next_time'] > $_time)
            return false;
        list($day, $hour, $minute) = explode('-', $cron['loop_daytime']);
        //获取下一次执行时间
        $nexttime = $cron_model->getNextTime($cron['loop_type'], $day, $hour, $minute);
        //更新计划任务的下次执行时间
        $cron_model->where(["cr_id" => $cron['cr_id']])->save([
            "modified_time" => $_time,
            "next_time" => $nexttime,
        ]);

        $re_cron = $this->_runAction($cron['file'], $cron['cr_id']);
        //计划任务执行记录
        $data = [];
        $data['cr_id'] = $cron['cr_id'];
        $data['cr_file'] = $cron['file'];
        $data['message'] = $this->message;
        $data['add_time'] = time();
        M('CronLog')->data($data)->add();
        if (!$re_cron) {
            return false;
        }

        $this->runCron();

        return true;
    }

    //运行计划
    private function _runAction($filename = '', $cronId = 0) {
        $dir = D("Cron/Cron")->getTaskPath();
        if (!$filename || strpos($filename, "Task") === false){
            $this->message = "不存在".$filename.'.php';
            return false;
        }

        //载入文件
        $require = require_cache($dir . $filename . ".php");
        if ($require) {
            try {
                $cron = new $filename();
                G('cron_begin');
                $cron->run($cronId);
                $message = $cron->getMessage();
                $this->message = empty($message)?'执行成功':$message;
            } catch (Exception $exc) {
                $this->message = $filename.".php执行出错！";
                //Log::write("计划任务:$filename，执行出错！");
            }
        } else {
            $this->message = $filename.".php文件载入出错！";
            //Log::write("计划任务:$filename，文件载入出错！");
        }
        return true;
    }


    /**
     * 检测定时进程数量
     * @param int $limit 进程数
     * @return bool
     */
    protected function psActionExist($limit = 1) {
        $consoleName = CONTROLLER_NAME . '/' . ACTION_NAME;
        $cmd = "ps -ef | grep -v grep | grep php | grep $consoleName";
        $cmd .= "| wc -l";
        $currentProcessNum = exec  ($cmd , $test);
        if ($currentProcessNum > $limit) {
            return true;
        }
        return false;
    }

}