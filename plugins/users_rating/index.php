<?php

class users_rating {

	// Marker for plugin
	private $marker = '#{{\s*users_rating\s*}}#i';
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
        if ($Cache->check('pl_users_rating')) {
            $users = $Cache->read('pl_users_rating');
            $users = unserialize($users);
        } else {
			$users = $this->DB->select('users', DB_ALL, array(
                'order' => '`'.$config['usersort'].'` DESC',
                'limit' => $config['limit'],
                'cond' => ($config['view_banned']==False) ? array('locked' => 0) : array()
            ));

            $Cache->write(serialize($users), 'pl_users_rating', array());
        }

        if (!empty($users)) {
            $template = file_get_contents(dirname(__FILE__).'/template/users.html');
            $Viewer = new Viewer_Manager;
			

            foreach ($users as $key => $user) {
                //Проверяем используется ли метка в шаблоне, если да то выполняем запрос
                if (strpos($template, '{{ user.comments }}')  !== false) {
                    if ($Cache->check('pl_users_rating_comments')) {
                        $comments = $Cache->read('pl_users_rating_comments');
                        $comments = unserialize($comments);
                    } else {
                        $comments = $this->DB->select('comments', DB_COUNT,  array('cond' => array('user_id' => $user['id'])));
                    }
                    $users[$key]['comments'] = $comments;
                }

                if (strpos($template, '{{ user.load }}')  !== false) {
                    if ($Cache->check('pl_users_rating_loads')) {
                        $files = $Cache->read('pl_users_rating_loads');
                        $files = unserialize($files);
                    } else {
                        $files = $this->DB->select('loads', DB_COUNT,  array('cond' => array('author_id' => $user['id'])));
                    }
                    $users[$key]['load'] = $files;
                }

                if (strpos($template, '{{ user.publ }}')  !== false) {
                    if ($Cache->check('pl_users_rating_publ')) {
                        $publ = $Cache->read('pl_users_rating_publ');
                        $publ = unserialize($publ);
                    } else {
                        $publ = $this->DB->select('stat', DB_COUNT,  array('cond' => array('author_id' => $user['id'])));
                    }
                    $users[$key]['publ'] = $publ;
                }

                if (strpos($template, '{{ user.news }}')  !== false) {
                    if ($Cache->check('pl_users_rating_news')) {
                        $news = $Cache->read('pl_users_rating_news');
                        $news = unserialize($news);
                    } else {
                        $news = $this->DB->select('news', DB_COUNT,  array('cond' => array('author_id' => $user['id'])));
                    }
                    $users[$key]['news'] = $news;
                }

                $users[$key]['avatar'] = getAvatar($user['id']);
                $users[$key]['profile_url'] = get_url('/users/info/' . $user['id']);
            }
            $output = $Viewer->parseTemplate($template, array('users' => $users));
        }
        return preg_replace($this->marker, $output, $params);
    }
}
