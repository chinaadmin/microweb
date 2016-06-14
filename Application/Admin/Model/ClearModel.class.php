<?php
/**
 * 清除缓存
 * @author cwh
 * @date 2015-04-08
 */
namespace Admin\Model;
use Common\Model\AdminbaseModel;
use Common\Org\Util\Dir;

class ClearModel extends AdminbaseModel {

    protected $autoCheckFields  =   false;

    /**
     * 清除所有缓存
     */
    public function all(){
        //缓存文件夹地址
        $Cachepath = RUNTIME_PATH;
        $dir = new Dir();
        $dir_lists = array(
            $Cachepath . "Cache/",
            $Cachepath . "Logs/",
            $Cachepath . "Data/",
            $Cachepath . "Temp/"
        );
        foreach($dir_lists as $dir_v){
            if (file_exists($dir_v)) {
                //删除该目录下文件
                $dir->del($dir_v);
                //删除该目录下所有目录
                $dir->listFile($dir_v, "*");
                $DrarrayCache = $dir->toArray();
                foreach ($DrarrayCache as $v) {
                    if($v['isDir']){
                        $dir->delDir($v['pathname']);
                    }
                }
            }
        }
        $dir->del($Cachepath);

        //创建缓存目录
        $this->check_runtime();
        //更新配置
        D('Admin/Configs')->SysConfigs(true);
    }

    // 检查缓存目录(Runtime) 如果不存在则自动创建
    public function check_runtime() {
        if(!is_dir(RUNTIME_PATH)) {
            mkdir(RUNTIME_PATH);
        }elseif(!is_writeable(RUNTIME_PATH)) {
            header('Content-Type:text/html; charset=utf-8');
            exit('目录 [ '.RUNTIME_PATH.' ] 不可写！');
        }
        mkdir(CACHE_PATH);  // 模板缓存目录
        if(!is_dir(LOG_PATH))   mkdir(LOG_PATH);    // 日志目录
        if(!is_dir(TEMP_PATH))  mkdir(TEMP_PATH);   // 数据缓存目录
        if(!is_dir(DATA_PATH))  mkdir(DATA_PATH);   // 数据文件目录
        return true;
    }

}