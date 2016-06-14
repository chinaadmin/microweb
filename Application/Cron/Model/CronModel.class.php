<?php
namespace Cron\Model;
/* * 
 * 计划任务
 */
use Common\Model\BaseModel;
use Common\Org\Util\Dir;

class CronModel extends BaseModel {

    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        ['subject', 'require', '计划任务名称不能为空！', 1, 'regex', 3],
        ['loop_type', 'require', '计划任务类型不能为空！', 1, 'regex', 3],
        ['file', 'require', '计划任务执行文件不能为空！', 1, 'regex', 3]
    );

    /**
     * 计划任务循环类型
     * @param array $data 计划任务数据
     * @return bool
     */
    public function loopType(&$data) {
        //计划任务循环类型
        $loop_type = $data['loop_type'];
        switch ($loop_type) {
            case 'month':
                //月份
                $day = $data['month_day'];
                //几点
                $hour = $data['month_hour'];
                //获取 计划任务 下一次执行时间
                $nexttime = $this->getNextTime('month', $day, $hour);
                $data['next_time'] = $nexttime;
                //循环类型时间（日-时-分）
                $data['loop_daytime'] = $day . '-' . $hour . '-0';
                break;
            case 'week':
                $day = $data['week_day'];
                $hour = $data['week_hour'];
                //获取 计划任务 下一次执行时间
                $nexttime = $this->getNextTime('week', $day, $hour);
                $data['next_time'] = $nexttime;
                //循环类型时间（日-时-分）
                $data['loop_daytime'] = $day . '-' . $hour . '-0';
                break;
            case 'day':
                $hour = $data['day_hour'];
                $nexttime = $this->getNextTime('day', 0, $hour);
                $data['next_time'] = $nexttime;
                //循环类型时间（日-时-分）
                $data['loop_daytime'] = '0-' . $hour . '-0';
                break;
            case 'hour':
                $minute = $data['hour_minute'];
                //获取 计划任务 下一次执行时间
                $nexttime = $this->getNextTime('hour', 0, 0, $minute);
                $data['next_time'] = $nexttime;
                $data['loop_daytime'] = '0-0-' . $minute;
                break;
            case 'now':
                $time = (int) $data['now_time'];
                $type = $data['now_type'];
                if (!$time) {
                    $this->error = "间隔时间有误！";
                    return false;
                }
                $minute = $type == 'minute' ? $time : 0;
                $hour = $type == 'hour' ? $time : 0;
                $day = $type == 'day' ? $time : 0;
                $nexttime = $this->getNextTime('now', $day, $hour, $minute);
                $data['next_time'] = $nexttime;
                $data['loop_daytime'] = $day . '-' . $hour . '-' . $minute;
                break;
            default:
                $this->error = "计划任务循环类型有误！";
                return false;
        }

        return $data;
    }

    /**
     * 获得下次执行时间
     * @param string $loopType month/week/day/hour/now
     * @param int $day 几号， 如果是99表示当月最后一天
     * @param int $hour 几点
     * @param int $minute 每小时的几分
     * @return bool|int|string
     */
    public function getNextTime($loopType, $day = 0, $hour = 0, $minute = 0) {
        $time = time();
        $_minute = intval(date('i', $time));
        $_hour = date('G', $time);
        $_day = date('j', $time);
        $_week = date('w', $time);
        $_mouth = date('n', $time);
        $_year = date('Y', $time);
        $nexttime = mktime($_hour, 0, 0, $_mouth, $_day, $_year);
        switch ($loopType) {
            case 'month':
                //是否闰年
                $isLeapYear = date('L', $time);
                //获得天数
                $mouthDays = $this->getMouthDays($_mouth, $isLeapYear);
                //最后一天
                if ($day == 99)
                    $day = $mouthDays;
                $nexttime += ($hour < $_hour ? -($_hour - $hour) : $hour - $_hour) * 3600;
                if ($hour <= $_hour && $day == $_day) {
                    $nexttime += ($mouthDays - $_day + $day) * 86400;
                } else {
                    $nexttime += ($day < $_day ? $mouthDays - $_day + $day : $day - $_day) * 86400;
                }
                break;
            case 'week':
                $nexttime += ($hour < $_hour ? -($_hour - $hour) : $hour - $_hour) * 3600;
                if ($hour <= $_hour && $day == $_week) {
                    $nexttime += (7 - $_week + $day) * 86400;
                } else {
                    $nexttime += ($day < $_week ? 7 - $_week + $day : $day - $_week) * 86400;
                }
                break;
            case 'day':
                $nexttime += ($hour < $_hour ? -($_hour - $hour) : $hour - $_hour) * 3600;
                if ($hour <= $_hour) {
                    $nexttime += 86400;
                }
                break;
            case 'hour':
                $nexttime += $minute <= $_minute ? 3600 + $minute * 60 : $minute * 60;
                break;
            case 'now':
                $nexttime = mktime($_hour, $_minute, 0, $_mouth, $_day, $_year);
                $_time = $day * 24 * 60;
                $_time += $hour * 60;
                $_time += $minute;
                $_time = $_time * 60;
                $nexttime += $_time;
                break;
        }
        return $nexttime;
    }

    /**
     * 获取该月天数
     * @param type $month 月份
     * @param type $isLeapYear 是否为闰年
     * @return int
     */
    public function getMouthDays($month, $isLeapYear) {
        if (in_array($month, array('1', '3', '5', '7', '8', '10', '12'))) {
            $days = 31;
        } elseif ($month != 2) {
            $days = 30;
        } else {
            if ($isLeapYear) {
                $days = 29;
            } else {
                $days = 28;
            }
        }
        return $days;
    }

    /**
     * 用于模板输出
     * @param string $select 选项
     * @return array
     */
    public function getLoopType($select = '') {
        $array = array('month' => '每月', 'week' => '每周', 'day' => '每日', 'hour' => '每小时', 'now' => '每隔');
        return $select ? $array[$select] : $array;
    }

    /**
     * 输出中文星期几
     * @param int $select 选项
     * @return mixed
     */
    public function capitalWeek($select = 0) {
        $array = array('日', '一', '二', '三', '四', '五', '六');
        return $array[$select];
    }

    /**
     * 获取计划任务文件路径
     * @return string
     */
    public function getTaskPath(){
        return APP_PATH . "Cron/Task/";
    }

    /**
     * 获取周期
     * @param string $type 循环类型
     * @param string $daytime 循环类型时间
     * @return string
     */
    public function getCycleText($type,$daytime){
        $cycle = $this->getLoopType($type);
        list($day, $hour, $minute) = explode('-', $daytime);
        if ($type == 'week') {
            $cycle .= '星期' . $this->capitalWeek($day);
        } elseif ($day == 99) {
            $cycle .= '最后一天';
        } else {
            $cycle .= $day ? $day . '日' : '';
        }
        if ($type == 'week' || $type == 'month') {
            $cycle .= $hour . '时';
        } else {
            $cycle .= $hour ? $hour . '时' : '';
        }
        $cycle .= $minute ? $minute . '分' : '00分';
        return $cycle;
    }

    /**
     * 可用计划任务执行文件
     * @return array
     */
    public function getCronFileList() {
        $path = $this->getTaskPath();
        $dirs = new Dir($path);
        $fileList = $dirs->toArray();
        $cronFileList = array();
        foreach ((array) $fileList AS $k => $file) {
            if (strpos($file['filename'], "Task") === false) {
                unset($fileList[$k]);
            } else {
                $cronFileList[] = $file['filename'];
            }
        }
        return $cronFileList;
    }

}