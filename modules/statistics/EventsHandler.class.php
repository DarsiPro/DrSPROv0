<?php

namespace StatisticsModule;

class EventsHandler {
    
    static $support_events = array(
        "user_pageviewed",
        "after_parse_global_markers",
        "add_admin_hchart"
    );
    
    static $whoOnline = null;
    static $whoOnlineHtmlLine = null;
    static $overalStats = null;
    static $todayStats = null;
    
    function user_pageviewed($module_object) {
        if (\Config::read('active', 'statistics') == 1) {
            $this->addWatch();
        } else {
            $this->viewOffCounter();
        }
    }
    
    function add_admin_hchart($hcharts) {
        
        
        $date = new \DateTime();
        $date->sub(new \DateInterval('P3M'));
        $graph_from = $date->format('Y-m-d');
        
        $Model = \OrmManager::getModelInstance('Statistics');
        $all_stats = $Model->getCollection(array(
            "date >= '{$graph_from}'",
        ));
        
        
        $stats = self::getTodayStats();

        
        if (!empty($stats)) {
            $entity = \OrmManager::getEntityName('Statistics');
            $all_stats[] = new $entity($stats);
        }
        
        
        
        $hcharts[] = array(
            "order" => 999,
            "is_row" => true,
            "title" => false,
            "body" => include('admin/homehcharts.php')
        );
        
        
        
        
        
        return $hcharts;
    }
    
    function after_parse_global_markers($markers) {
        
        $markers['all_online'] = function() {
            return count(self::getWhoOnline()['all']);
        };
        $markers['users_online'] = function() {
            return count(self::getWhoOnline()['users']);
        };
        $markers['guests_online'] = function() {
            return count(self::getWhoOnline()['guests']);
        };
        $markers['all_online_array'] = function() {
            return self::getWhoOnline();
        };
        $markers['max_online_all_time'] = function() {
            return self::getOveralStats('max_users_online');
        };
        $markers['max_online_all_time_date'] = function() {
            return self::getOveralStats('max_users_online_date');
        };
        $markers['hits_all_time'] = function() {
            return self::getOveralStats('hits');
        };
        $markers['hosts_all_time'] = function() {
            return self::getOveralStats('hosts');
        };
        $markers['hits_today'] = function() {
            return self::getTodayStats('views');
        };
        $markers['hosts_today'] = function() {
            return self::getTodayStats('visits');
        };
        $markers['checkUserOnline'] = function($user_id) {
            if (!$user_id || !is_numeric($user_id)) return false;
            $users_on_line = self::getWhoOnline()['users'];
            return (isset($users_on_line) && isset($users_on_line[$user_id]));
        };
        
        $markers['online_users_list'] = function() {
            return self::getHtmlWhoOnline();
        };
        $markers['counter'] = get_url('/data/img/counter.png?rand=' . rand(0,999999));
        
        return $markers;
    }
    
    public function addWatch() {
        
        $add_visit = 0;
        $other_site_view = 0;
        $add_view = 0;
        
        $this->botname = false;
        // Если пользователь не является ботом
        if (!$this->isBot($this->botname)) {
            $add_view = 1;
            
            
            //Если юзер вошел сегодня первый раз
            if (!isset($_SESSION['statistics_date_of_visit'])
                || $_SESSION['statistics_date_of_visit'] != date("Y-m-d"))
            {
                $_SESSION['statistics_date_of_visit'] = date("Y-m-d");
                $add_visit = 1;
            }


            // Если юзер пришел с внешнего сайта
            if (!empty($_SERVER['HTTP_REFERER'])
                && !preg_match('#^https?://' . $_SERVER['SERVER_NAME'] . '#', $_SERVER['HTTP_REFERER'])) {
                $other_site_view = 1;
                // todo: хорошо бы запоминать с каких именно сайтов
            }

        }
        
        // Запись результатов во временный файл сегодняшней статистики
        $stats = $this->getTodayStats();
        if (!empty($stats)) {

            $stats['views'] += $add_view;
            $stats['visits'] += $add_visit;
            $stats['other_site_visits'] += $other_site_view;
            
        } else {
            $stats = array(
                'views' => $add_view,
                'visits' => $add_visit,
                'other_site_visits' => $other_site_view,
                'bot_views' => array()
            );
        }
        if ($this->botname !== false) {
            if (!isset($stats['bot_views'][$this->botname]))
                $stats['bot_views'][$this->botname] = 1;
            else
                $stats['bot_views'][$this->botname] += 1;
        }
        
        
        $tmp_datafile = R.'sys/tmp/statistics/counter/' . date("Y-m-d") . '.dat';
        // Блокирующая обращения к файлу запись
        $f = fopen($tmp_datafile, 'w+');
        flock($f, LOCK_EX);
        fwrite($f, serialize($stats));
        flock($f, LOCK_UN);
        fclose($f);
        
        // Обновляет список пользователей онлайн
        $all_online = $this->updateWhoOnline();
        
        //write into data base and delete old file (one time in day)
        $tmp_files = glob(ROOT . '/sys/tmp/statistics/counter/*.dat');
        if (!empty($tmp_files) && count($tmp_files) > 1) {
            foreach ($tmp_files as $file) {
                $date = basename($file, '.dat');
                if ($date == date("Y-m-d"))
                    continue;
                
                $old_stats = unserialize(file_get_contents($file));
                $this->saveDayIntoDB($date, $old_stats);
                $this->updateOveralStats(array(
                    'online' => $all_online,
                    'today_hits' => $old_stats['views'],
                    'today_hosts' => $old_stats['visits']
                ));
                
                unlink($file);
                unset($_SESSION['statistics_date_of_visit']);
            }
        }
        $all_hits = $this->getOveralStats('hits');
        // Generate counter image
        $im = imagecreatefrompng(ROOT . "/data/img/statistics.png");
        $orange = imagecolorallocate($im, 20, 10, 20);
        imagesavealpha($im, true);
        $image_x = imagesx($im);
        imagestring($im, 1, $image_x - (strlen($all_hits) * 5 + 3), 3, $all_hits, $orange);
        imagestring($im, 1, $image_x - (strlen($stats['views']) * 5 + 3), 14, $stats['views'], $orange);
        imagestring($im, 1, $image_x - (strlen($stats['visits']) * 5 + 3), 24, $stats['visits'], $orange);
        imagepng($im, ROOT . '/data/img/counter.png');
        imagedestroy($im);

        return;
    }
    
    
    static public function getHtmlWhoOnline() {
        if (empty(self::$whoOnlineHtmlLine)) {
            $online_users = array();
            $users = self::getWhoOnline()['users'];
            // Генерируем список пользователей онлайн(html)
            foreach ($users as $key => $user) {
                // Удаляем из онлайна тех юзеров,
                // которые слишком долго не осуществляли навигацию по сайту
                if ($user['expire'] < time()) {
                    unset($users[$key]);
                    break;
                }


                // если бот
                if (strpos($key, 'bot_') === 0) {
                    $online_users[] = '<span class="botname">' . h($user['name']) . '</span>';
                    continue;
                }
                
                // если пользователь принадлежит группе
                $color = '';
                if (isset($user['status'])) {
                    $group_info = \ACL::get_group($user['status']);
                    if (!empty($group_info['color']))
                        $color = 'color:' . $group_info['color'] . ';';
                }
                $online_users[] = get_link(h($user['name']), getProfileUrl($key, true), array('style' => $color));
            }
            
            self::$whoOnlineHtmlLine = (count($online_users)) ? implode(', ', $online_users) : '';
        }
        
        return self::$whoOnlineHtmlLine;
    }
    
    
    
    private function updateWhoOnline() {
        // create $users and $guests vars
        extract(self::getWhoOnline());
        
        // Удаляем из исписка зашедших гостей тех,
        // кто давно не осуществлял навигацию по сайту
        foreach ($guests as $key => $guest) {
            if ($guest['expire'] < time())
                unset($guests[$key]);
        }
        // Удаляем из исписка зашедших пользователей тех,
        // кто давно не осуществлял навигацию по сайту
        foreach ($users as $key => $user) {
            if ($user['expire'] < time())
                unset($users[$key]);
        }
        
        $all_online = intval(count($users) + count($guests));
        
        if (!empty($_SESSION['user']['id'])) {
            $users[$_SESSION['user']['id']] = array(
                'expire' => time() + (\Config::read('time_on_line', 'statistics') * 60),
                'name' => $_SESSION['user']['name'],
                'status' => $_SESSION['user']['status'],
            );
        } else if ($this->botname === false) {
            $guests[$this->getUserIP()] = array(
                'expire' => time() + (\Config::read('time_on_line', 'statistics') * 60),
            );
        }
        if (\Config::read('show_bots', 'statistics') and $this->botname !== false) {
            $users['bot_'.$this->botname] = array(
                'expire' => time() + (\Config::read('time_on_line', 'statistics') * 60),
                'name' => $this->botname.'[bot]',
            );
        }
        
        self::$whoOnline = array('users' => $users, 'guests' => $guests);
        
        $path = R.'sys/tmp/statistics/counter_online/online.dat';
        $f = fopen($path, 'w+');
        flock($f, LOCK_EX);
        fwrite($f, serialize(self::$whoOnline));
        flock($f, LOCK_UN);
        fclose($f);
        
        return $all_online;
    }
    
    
    private function updateOveralStats($today_data = array()) {
        clearstatcache();
        
        $today_data = array_merge(array(
            'online' => 0,
            'today_hits' => 0,
            'today_hosts' => 0
        ),$today_data);
        
        
        $overal_file = R.'sys/tmp/statistics/overal_stats.dat';
        if (file_exists($overal_file)) {
            $overal = unserialize(file_get_contents($overal_file));
            // Max users online in one time
            if ($overal['max_users_online'] < $today_data['online']) {
                $overal['max_users_online'] = $today_data['online'];
                $overal['max_users_online_date'] = date("Y-m-d");
            }
            $overal['hits'] += $today_data['today_hits'];
            $overal['hosts'] += $today_data['today_hosts'];
        } else {
            $overal = array();
            
            $Model = \OrmManager::getModelInstance('Statistics');
            $res = $Model->getFirst(array(), array(
                'fields' => array('SUM(`views`) as all_hits', 'SUM(`visits`) as all_visits'),
            ));
            
            $overal['hits'] = $res ? $res->getAll_hits() : 0;
            $overal['hosts'] = $res ? $res->getAll_visits() : 0;
            $overal['max_users_online'] = $today_data['online'] ? $today_data['online'] : 0;
            $overal['max_users_online_date'] = date("Y-m-d");
        }
        
        self::$overalStats = $overal;
        
        $f = fopen($overal_file, 'w+');
        flock($f, LOCK_EX);
        fwrite($f, serialize(self::$overalStats));
        flock($f, LOCK_UN);
        fclose($f);
        
        return $overal;
    }
    
    
    /**
     * write into database
     */
    private function saveDayIntoDB($date, $stats) {

        $Model = \OrmManager::getModelInstance('Statistics');

        $res = $Model->getCollection(array('date' => $date));
        
        if ($res && is_array($res) && count($res) > 0) {
            return;
        } else {
            $data = array(
                'date' => $date,
                'views' => (int)$stats['views'],
                'visits' => (int)$stats['visits'],
                'other_site_visits' => (int)$stats['other_site_visits'],
                'bot_views' => $stats['bot_views']
            );
            $statisticEntity = new ORM\StatisticsEntity($data);
            $statisticEntity->save();
        }
    }

    
    private function getUserIP() {
        if (!empty($_SERVER['REMOTE_ADDR']))
            $ip = $_SERVER['REMOTE_ADDR'];
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = '00.00.00.00';
        
        if (mb_strlen($ip) > 20 || !preg_match('#^\d+\.\d+\.\d+\.\d+$#', $ip))
            $ip = '00.00.00.00';
        
        return $ip;
    }
    
    
    /**
     * if statistics OFF
     */
    static public function viewOffCounter() {
        copy(R.'data/img/counter_off.png', R.'data/img/counter.png');
    }

    /**
     * view counter
     */
    static public function viewCounter() {
        $model = \OrmManager::getModelInstance('Statistics');
        $_hosts = $model->getCollection(array("`date` >= '" . date("Y-m-d") . "'"));
        $hosts = (!empty($_hosts[0])) ? $_hosts[0]['visits'] : 0;
        $hits = (!empty($_hosts[0])) ? $_hosts[0]['views'] : 0;

        header("Content-type: image/png");

        $im = imagecreatefrompng(ROOT . "/data/img/statistics.png");
        $orange = imagecolorallocate($im, 20, 10, 20);
        $px = (imagesx($im) - 17);
        imagestring($im, 1, $px, 1, $hits, $orange);
        imagestring($im, 1, $px, 13, $hits, $orange);
        imagestring($im, 1, $px, 22, $hosts, $orange);
        imagepng($im);
        imagedestroy($im);
    }
    
    
    /* Эта функция будет проверять, является ли посетитель роботом поисковой системы */
    static public function isBot(&$botname = ''){
        
        $bots = array(
            'rambler','googlebot','aport','yahoo','msnbot','turtle','mail.ru','omsktele',
            'yetibot','picsearch','sape.bot','sape_context','gigabot','snapbot','alexa.com',
            'megadownload.net','askpeter.info','igde.ru','ask.com','qwartabot','yanga.co.uk',
            'scoutjet','similarpages','oozbot','shrinktheweb.com','aboutusbot','followsite.com',
            'dataparksearch','google-sitemaps','appEngine-google','feedfetcher-google',
            'liveinternet.ru','xml-sitemaps.com','agama','metadatalabs.com','h1.hrn.ru',
            'googlealert.com','seo-rus.com','yaDirectBot','yandeG','yandex',
            'yandexSomething','Copyscape.com','AdsBot-Google','domaintools.com',
            'Nigma.ru','bing.com','dotnetdotcom','DuckDuckBot'
        );
        foreach($bots as $bot) {
            if(stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false) {
                $botname = $bot;
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    static function getWhoOnline() {
        if (!empty(self::$whoOnline))
            return self::$whoOnline;

        $path = R.'sys/tmp/statistics/counter_online/online.dat';
        touchDir(R.'sys/tmp/statistics/counter_online/');
        if (file_exists($path) && is_readable($path)) {
            $data = unserialize(file_get_contents($path));
            if (!empty($data) && is_array($data) && isset($data['users']) && isset($data['guests'])) {
                $data['all'] = array_merge($data['users'],$data['guests']);
                self::$whoOnline = $data;
                return $data;
            }
        }
        
        $data = array(
            'guests' => array(),
            'users' => array(),
            'all' => array()
        );
        
        return $data;
    }

    /**
     * Get overal stats by key
     */
    static public function getOveralStats($key = false) {
        if (!empty(self::$overalStats))
            if (!empty($key))
                return (isset(self::$overalStats[$key])) ? self::$overalStats[$key] : false;
            else
                return self::$overalStats;
        else {
            $path = R.'sys/tmp/statistics/overal_stats.dat';

            if (file_exists($path) && is_readable($path)) {
                self::$overalStats = unserialize(file_get_contents($path));
                if (!empty($key)) {
                    return (isset(self::$overalStats[$key])) ? self::$overalStats[$key] : false;
                }
                return self::$overalStats;
            }
        }
        return false;
    }
    
    static public function getTodayStats($key = false) {
        if (!empty(self::$todayStats))
            if (!empty($key))
                return (isset(self::$todayStats[$key])) ? self::$todayStats[$key] : false;
            else
                return self::$todayStats;
        else {
            touchDir(R.'sys/tmp/statistics/counter/', 0777);
            $tmp_datafile = R.'sys/tmp/statistics/counter/' . date("Y-m-d") . '.dat';
            if (file_exists($tmp_datafile) && is_readable($tmp_datafile)) {
                $date = new \DateTime();
                self::$todayStats = unserialize(file_get_contents($tmp_datafile));
                self::$todayStats['date'] = $date->format('Y-m-d');
                if (!empty($key)) {
                    return (isset(self::$todayStats[$key])) ? self::$todayStats[$key] : false;
                }
                return self::$todayStats;
            }
        }
        return false;
    }
    
}