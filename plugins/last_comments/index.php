<?php

class last_comments {

    // Marker for plugin
    private $marker = '#{{\s*last_comments\s*}}#i';
    private $DB;

    public function __construct($params) {
        $this->DB = getDB();
    }

    public function common($params) {

        $config = json_decode(file_get_contents(dirname(__FILE__).'/config.json'), true);

        $output = '';

        if (preg_match($this->marker, $params) == 0) return $params;

        $Cache = new Cache;
        $Cache->lifeTime = 600;
        if ($Cache->check('pl_last_comments')) {
            $comments = $Cache->read('pl_last_comments');
            $comments = unserialize($comments);
        } else {
            $sql = "(SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`user_id`, a.`message`, b.`title`, a.`module`
                FROM `" . $this->DB->getFullTableName('comments') . "` a 
                JOIN `" . $this->DB->getFullTableName('news') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'news')
                UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`user_id`, a.`message`, b.`title`, a.`module`
                FROM `" . $this->DB->getFullTableName('comments') . "` a 
                JOIN `" . $this->DB->getFullTableName('stat') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'stat')
                UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`user_id`, a.`message`, b.`title`, a.`module`
                FROM `" . $this->DB->getFullTableName('comments') . "` a 
                JOIN `" . $this->DB->getFullTableName('loads') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'loads')
                UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`user_id`, a.`message`, b.`title`, a.`module`
                FROM `" . $this->DB->getFullTableName('comments') . "` a 
                JOIN `" . $this->DB->getFullTableName('foto') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'foto')
                ORDER BY `date` DESC LIMIT ". $config['limit'] ." ";
            $comments = $this->DB->query($sql);
            $Cache->write(serialize($comments), 'pl_last_comments', array());
        }

        if (!empty($comments)) {
            $template = file_get_contents(dirname(__FILE__).'/template/comments.html');
            $Viewer = new Viewer_Manager;
            $i = 0;
            foreach ($comments as $key => $comm) {
                $str = 'к материалу';
                    switch ($comm['module']) {
                        case 'foto': $str = 'к фотографии'; break;
                        case 'loads': $str = 'к загрузке'; break;
                        case 'news': $str = 'к новости'; break;
                        case 'stat': $str = 'к статье'; break;
                    }

                $i++;
                $comments[$key]['module'] = $str;
                $comments[$key]['number'] = $i;
                $comments[$key]['title'] = h($comm['title']);
                $comments[$key]['date'] = $comm['date'];
                $comments[$key]['url'] = get_url(matUrl($comm['entity_id'], $comm['title'], $comm['module']));
                $comments[$key]['name'] = $comm['name'];
                $comments[$key]['avatar'] = getAvatar($comm['user_id']);
                $comm_text = $comm['message'];
                $comm_text = Register::getClass('PrintText')->getAnnounce($comm_text, $comments[$key]['url'], $config['shot_comm']);
                $comments[$key]['message'] = $comm_text;
            }
            $output = $Viewer->parseTemplate($template, array('comments' => $comments));
        }
        return preg_replace($this->marker, $output, $params);
    }
}