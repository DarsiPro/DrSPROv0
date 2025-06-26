<?php
/**
* @project    DarsiPro CMS
* @package    Logination class
* @url        https://darsi.pro
*
* Uses for read | write | clean system log
*/

class Logination {

    /**
    * directory for log files
    *
    * @var (str)
    */
    static $logDir;
    
    /**
    * directory name for log files
    *
    * @var (str)
    */
    static $logDirname = 'system_log';
    
    /**
    * max size for log files
    *
    * @var (int)
    */
    static private $maxFileSize = 1000000;


    /**
    * init log
    * delete execess files ( bigest Config::read('max_log_size', '__secure__') )
    *
    * @return          none
    */
    public function __construct() {
        $max_log_size = Config::read('max_log_size', '__secure__');
        
        self::$logDir = R.'sys/logs/'.self::$logDirname;
        
        /* we must no allow overflow */
        if ((int)$max_log_size > 0) {
            $log_files = glob(self::$logDir.'/*.dat');
            $log_size = (!empty($log_files)) ? (count($log_files) * self::$maxFileSize) : 0;
            
            /* delete files and free space */
            while ($log_size > $max_log_size) {
                $first_file = array_shift($log_files);
                if (@unlink($first_file)) $log_size = ($log_size - self::$maxFileSize);
            }
        }
        
        /* create log dir if !exists */
        if (!file_exists(self::$logDir))
            mkdir(self::$logDir, 0755, true);
    }


    /**
    * for write into log
    *
    * @param (str)     action.  be write into log
    * @return          none
    */
    static function write($param, $comment = '') {

        clearstatcache();
        /* prepear data */
        $param_log = array();
        $param_log['date'] = date("Y-m-d H:i");
        $param_log['action'] = $param;
        $param_log['comment'] = $comment;
        if (!empty($_SESSION['user']['name'])) {
            $param_log['user_id'] = $_SESSION['user']['id'];
            $param_log['user_name'] = $_SESSION['user']['name'];
            //$statuses = ACL::get_group_info();
            //$param_log['user_status'] = $statuses[$_SESSION['user']['status']];
            $param_log['user_status'] = (int)$_SESSION['user']['status'];
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) $param_log['ip'] = $_SERVER['REMOTE_ADDR'];

        /* get file name for writing */
        $file_name = self::getFileName();

        /* get records if exists */
        $file_path = self::$logDir.'/'.$file_name;
        if (file_exists($file_path)
            && is_array(($log_data = unserialize(file_get_contents($file_path))))) 
        {
            $log_data = array_merge($log_data, array(0 => $param_log));
        } else {
            $log_data = array();
            $log_data[] = $param_log;
        }
        $log_data = serialize($log_data);

        /* write... */
        file_put_contents($file_path, $log_data);

        return;
    }

    /**
    * read log file
    *
    * @param string $filename - file name
    * @return array - file contents
    */
    static function read($filename) {
        clearstatcache();
        $filename = self::$logDir . '/' . $filename;
        if (file_exists($filename) && is_readable($filename)) {
            $data = file_get_contents($filename);
            if (!empty($data)) {
                $data = unserialize($data);
            } else {
                $data = null;
            }
        }
        return (!empty($data)) ? $data : false;
    }


    /**
    * clean all logs
    *
    * return void
    */
    static function clean() {
        $log_files = glob(self::$logDir . '/*');
        if (!empty($log_files)) {
            foreach ($log_files as $file) {
                @unlink($file);
            }
        }
        return;
    }

    /**
    * geting file name for write
    *
    * @return (str)     filename
    */
    static private function getFileName($file_num = 1) {
        clearstatcache();
        $file_name = date("Y-m-d") . '_' . $file_num . '.dat';
        if (file_exists(($file_path = self::$logDir . '/' . $file_name))) {
            if (filesize($file_path) > self::$maxFileSize) {
                $file_name = self::getFileName(2);
            }
        }
        return $file_name;
    }
}


// При загрузке файла, сразу вызываем конструктор
new Logination();

