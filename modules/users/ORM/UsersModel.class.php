<?php
/**
* @project    DarsiPro CMS
* @package    Users Model
* @url        https://darsi.pro
*/


namespace UsersModule\ORM;

class UsersModel extends \OrmModel
{
    public $Table  = 'users';

    protected $RelatedEntities = array(
        'inpm' => array(
            'model' => 'Messages',
            'type' => 'has_many',
            'foreignKey' => 'to',
        ),
        'outpm' => array(
            'model' => 'Messages',
            'type' => 'has_many',
            'foreignKey' => 'from',
        ),
    );



    public function getSameNics($nick)
    {
        // kirilic
        $rus = array( "А","а","В","Е","е","К","М","Н","О","о","Р","р","С","с","Т","Х","х" );
        // latin
        $eng = array( "A","a","B","E","e","K","M","H","O","o","P","p","C","c","T","X","x" );
        // Заменяем русские буквы латинскими
        $eng_new_name = str_replace( $rus, $eng, $nick );
        // Заменяем латинские буквы русскими
        $rus_new_name = str_replace( $eng, $rus, $nick );
        // Формируем SQL-запрос
        $res = getDB()->query("SELECT * FROM `" . getDB()->getFullTableName('users') . "`
            WHERE name LIKE '".getDB()->escape( $nick )."' OR
            name LIKE '".getDB()->escape( $eng_new_name )."' OR
            name LIKE '".getDB()->escape( $rus_new_name )."';");
        return $res;
    }


    public function getMessage($id)
    {

        $messagesModel = \OrmManager::getModelInstance('UsersMessages');
        $message = $messagesModel->getById($id);

        if ($message) {
            $to = $this->getById($message->getTo_user());
            $from = $this->getById($message->getFrom_user());
            $message->setToUser($to);
            $message->setFromUser($from);
            return $message;
        }
        return null;
    }


    public function getFullUserStatistic($user_id)
    {
        $stat = array();
        $modules = glob(ROOT . '/modules/*', GLOB_ONLYDIR);
        if (count($modules)) {
            foreach ($modules as $path) {
                $title = substr(strrchr($path, '/'), 1);
                $classname = \OrmManager::getModelName($title);

                // Is module on?
                if (\Config::read($title . '.active') && class_exists($classname)) {
                    @$mod = new $classname;

                    if (isset($mod)) {
                        if (is_callable(array($mod, 'getUserStatistic'))) {
                            $stats = $mod->getUserStatistic($user_id);
                            if (is_array($stats) && count($stats)) {
                                $stat = array_merge($stat, $stats);
                            }
                        }
                        unset($mod);
                    }
                }
            }
        }

        uasort($stat, function($a, $b){
            if (!empty($a['text']) && !empty($b['text'])) {
                if ($a['text'] == $b['text']) {
                    return ($a['text'] < $b['text']) ? -1 : 1;
                }
            }
            return 0;
        });

        return $stat;
    }


    public function getByName($name)
    {
        $entities = getDB()->select($this->Table, DB_FIRST, array(
            'cond' => array(
                'name' => $name
            )
        ));

        if ($entities && count($entities)) {
            $entities = $this->getAllAssigned($entities);
            $entityClassName = \OrmManager::getEntityNameFromModel(get_class($this));
            $entity = new $entityClassName($entities[0]);
            return (!empty($entity)) ? $entity : false;
        }
        return false;
    }


    public function getByNamePass($name, $password)
    {
        $entities = getDB()->query("SELECT *, UNIX_TIMESTAMP(last_visit) as unix_last_visit
            FROM `" . getDB()->getFullTableName('users') . "`  WHERE name='"
            .getDB()->escape( $name )."' LIMIT 1");

        $check_password = false;
        if (count($entities) > 0 && !empty($entities[0])) {
            $check_password = checkPassword($entities[0]['passw'], $password);
        }

        if (count($entities) > 0 && !empty($entities[0]) && $check_password) {
            $entities = $this->getAllAssigned($entities);
            $entityClassName = \OrmManager::getEntityNameFromModel(get_class($this));
            $entity = new $entityClassName($entities[0]);
            return (!empty($entity)) ? $entity : false;
        }
        return false;
    }


    public function getNewPmMessages($uid)
    {
        $res = getDB()->query("SELECT COUNT(*) as cnt
                FROM `" . getDB()->getFullTableName('messages') . "`
                WHERE `to_user` = ".$uid."
                AND `viewed` = 0 AND `id_rmv` <> ".$uid);

        return (!empty($res[0]) && !empty($res[0]['cnt'])) ? (string)$res[0]['cnt'] : 0;
    }

    function getCountComments($user_id = null) {
        $commentsModel = \OrmManager::getModelInstance('Comments');
        $cond = array();
        if ($user_id) {
            $cond['user_id'] = $user_id;
        }
        $cnt = $commentsModel->getTotal(array('cond' => $cond));

        return ($cnt) ? $cnt : false;
    }

    /**
     * @param $user_id
     * @return array|bool
     */
    function getUserStatistic($user_id) {
        $user_id = intval($user_id);
        if ($user_id > 0) {
            $result = $this->getCountComments($user_id);
            if ($result) {
                $res = array(
                    'module' => 'comments',
                    'text' => __('comments'),
                    'count' => intval($result),
                    'url' => get_url('/users/comments/' . $user_id),
                );

                return array($res);
            }
        }
        return false;
    }

    public function getMessages()
    {
        $messagesModel = \OrmManager::getModelInstance('UsersMessages');
        $messagesModel->bindModel('fromuser');
        $messagesModel->bindModel('touser');

        $messages = $messagesModel->getCollection(array(
            '(to_user = ' . $_SESSION['user']['id'] . ' OR from_user = ' . $_SESSION['user']['id'] . ')',
            "id_rmv <> '" . $_SESSION['user']['id'] . "'",
        ), array(
            'order' => 'sendtime DESC'
        ));

        if (!$messages || (is_array($messages) && count($messages) == 0)) {
            return;
        }

        $users = array();
        foreach ($messages as $i => $message) {
            if ($message->getFrom_user() != $_SESSION['user']['id']) {
                if (array_search($message->getFrom_user(), $users) !== FALSE) {
                    unset($messages[$i]);
                } else {
                    $message->setDirection('in');
                    array_push($users, $message->getFrom_user());
                }
            } else {
                if (array_search($message->getTo_user(), $users) !== FALSE) {
                    unset($messages[$i]);
                } else {
                    $message->setDirection('out');
                    array_push($users, $message->getTo_user());
                }
            }
        }
        return $messages;
    }

    public function getUserMessages($id)
    {
        $messagesModel = \OrmManager::getModelInstance('UsersMessages');

        $messages = $messagesModel->getCollection(array(
            '((to_user = '.$_SESSION['user']['id'].' and from_user = '.$id.') OR
              (to_user = '.$id.' and from_user = '.$_SESSION['user']['id'].'))',
            "id_rmv <> '".$_SESSION['user']['id']."'",
        ), array(
            'order' => 'sendtime DESC'
        ));

        if (!$messages || (is_array($messages) && count($messages) == 0)) {
            return;
        }

        foreach ($messages as $message) {
            if ($message->getFrom_user() != $_SESSION['user']['id']) {
                $message->setDirection('in');
            } else {
                $message->setDirection('out');
            }
        }
        return $messages;
    }
}