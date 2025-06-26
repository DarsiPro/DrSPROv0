<?php

class popmat {

    // Marker for plugin
    private $marker = '#{{\s*popmat\s*}}#i';
    private $DB;

    public function __construct($params) {
        $this->DB = getDB();

    }


    public function common($params) {

        $config = json_decode(file_get_contents(dirname(__FILE__).'/config.json'), true);
        $output = '';


        if (preg_match($this->marker, $params) == 0) return $params;

        $Cache = new Cache;
        $Cache->lifeTime = 3600;
        if ($Cache->check('pl_popmat')) {
            $mats = $Cache->read('pl_popmat');
            $mats = unserialize($mats);
        } else {
            $mats = $this->DB->select($config['module'], DB_ALL, array(
                'order' => '`'.$config['sort'].'` DESC',
                'limit' => $config['limit']));
            $Cache->write(serialize($mats), 'pl_popmat', array());
            $author = $this->DB->select('users', DB_ALL);
        }


        if (!empty($mats)) {

            $template = file_get_contents(dirname(__FILE__).'/templates/popmat.html');
            $Viewer = new Viewer_Manager;
            foreach ($mats as $key => $mat) {

                // Acttaches img
                // Проверяем используется ли метка вывода адреса прикрепления в шаблоне если да, то производим запрос
                if (strpos($template, 'img_url }}')  !== false) {
                    $images = $this->DB->select($config['module'].'_attaches', DB_ALL, array('cond' => array('entity_id' =>  $mat['id'])));
                    if (count($images) > 0) {
                    $img_url = '/image/'.$config['module'].'/small/'.$images[0]['filename'];
                    $mats[$key]['img_url'] = $img_url;
                    }
                }

                // Author of material
                if (strpos($template, 'author_name }}')  !== false) { 
                    $author = $this->DB->select('users', DB_ALL, array('cond' => array('id' =>  $mat['author_id'])));
                    $author =  $author[0]['name'];
                    $mats[$key]['author_name'] = $author; 
                }

                $mats[$key]['author_url'] = get_url('/users/info/' . $mat['author_id']);
                $mats[$key]['url'] = get_url(matUrl($mat['id'], $mat['title'], $config['module']));
                $mats[$key]['title'] = trim(mb_substr($mats[$key]['title'], 0, $config['short_title']));
                $announce = $mats[$key]['main'];
                $announce = Register::getClass('PrintText')->getAnnounce($announce, $mats[$key]['url'], 0, $config['short_main']);
                $mats[$key]['main'] = $announce;

                $mats[$key]['date'] = drsDate($mats[$key]['date']);
                $mats[$key]['key_sort'] = $mats[$key][$config['sort']];
                if ($config['sort'] == 'date')
                    $mats[$key]['key_sort'] = drsDate($mats[$key]['key_sort']);
            }
            $output = $Viewer->parseTemplate($template, array('mats' => $mats, 'template_path' => get_url('/template/' . getTemplate())));
        }

        return preg_replace($this->marker, $output, $params);
    }

}
