<?php
/**
* @project    DarsiPro CMS
* @package    Forum Model
* @url        https://darsi.pro
*/


namespace ForumModule\ORM;

class ForumModel extends \OrmModel
{
    public $Table = 'forums';

    protected $RelatedEntities = array(
        'themeslist' => array(
            'model' => 'ForumThemes',
            'type' => 'has_many',
            'foreignKey' => 'id_forum',
          ),
        'category' => array(
            'model' => 'ForumCat',
            'type' => 'has_one',
            'foreignKey' => 'id_cat',
        ),
        'last_theme' => array(
            'model' => 'ForumThemes',
            'type' => 'has_one',
            'foreignKey' => 'last_theme_id',
        ),
        'parent_forum' => array(
            'model' => 'Forum',
            'type' => 'has_one',
            'foreignKey' => 'parent_forum_id',
        ),
        'subforums' => array(
            'model' => 'Forum',
            'type' => 'has_many',
            'foreignKey' => 'parent_forum_id',
        ),
    );




    public function getStats()
    {
        $result = getDB()->query("
            SELECT `id` as last_user_id
            , (SELECT `name` FROM `" . getDB()->getFullTableName('users') . "` ORDER BY `puttime` DESC LIMIT 1) as last_user_name
            , (SELECT COUNT(*) FROM `" . getDB()->getFullTableName('posts') . "`) as posts_cnt
            , (SELECT COUNT(*) FROM `" . getDB()->getFullTableName('themes') . "`) as themes_cnt
            FROM `" . getDB()->getFullTableName('users') . "` ORDER BY `puttime` DESC LIMIT 1");
        return $result;
    }


    public function updateForumCounters($id_forum)
    {
        getDB()->query(
            "UPDATE `" . getDB()->getFullTableName('forums') . "` SET `themes` =
            (SELECT COUNT(*) FROM `" . getDB()->getFullTableName('themes') . "`
            WHERE `id_forum` = '" . $id_forum . "'), `posts` =
            (SELECT COUNT(b.`id`) FROM `" . getDB()->getFullTableName('themes') . "` a
            LEFT JOIN `" . getDB()->getFullTableName('posts') . "` b ON a.`id`=b.`id_theme`
            WHERE a.`id_forum` = '" . $id_forum . "') - (SELECT COUNT(*) FROM `" . getDB()->getFullTableName('themes') . "`
            WHERE `id_forum` = '" . $id_forum . "'),
            `last_theme_id`=IFNULL((SELECT `id` FROM `" . getDB()->getFullTableName('themes') . "`
            WHERE `id_forum`='" . $id_forum . "'
            ORDER BY `last_post` DESC  LIMIT 1), 0) WHERE `id` = '" . $id_forum . "'" );
    }


    public function updateUserCounters($id_user)
    {
        getDB()->query(
            "UPDATE `" . getDB()->getFullTableName('users') . "` SET
            `themes` = (SELECT COUNT(*) FROM `" . getDB()->getFullTableName('themes') . "`
            WHERE `id_author` = '" . $id_user . "')
            , `posts` = (SELECT COUNT(*) FROM `" . getDB()->getFullTableName('posts') . "`
            WHERE `id_author` = '" . $id_user . "')
            WHERE `id` = '" . $id_user . "'");
    }



    public function upLastPost($from_forum, $id_forum)
    {
        getDB()->query("UPDATE `" . getDB()->getFullTableName('forums') . "` as forum SET
            forum.`last_theme_id` = IFNULL((SELECT `id` FROM `" . getDB()->getFullTableName('themes') . "`
            WHERE `id_forum` = forum.`id` ORDER BY `last_post` DESC LIMIT 1), 0)
            WHERE forum.`id` IN ('" . $from_forum . "', '" . $id_forum . "')");
    }


    public function deleteCollisions()
    {
        getDB()->query("DELETE FROM `" . getDB()->getFullTableName('themes')
            . "` WHERE id NOT IN (SELECT DISTINCT id_theme FROM `" . getDB()->getFullTableName('posts') . "`)");
        getDB()->query("DELETE FROM `" . getDB()->getFullTableName('posts')
            . "` WHERE id_theme NOT IN (SELECT DISTINCT id FROM `" . getDB()->getFullTableName('themes') . "`)");
        getDB()->query("DELETE FROM `" . getDB()->getFullTableName('polls')
            . "` WHERE theme_id NOT IN (SELECT DISTINCT id FROM `" . getDB()->getFullTableName('themes') . "`)");
        getDB()->query("DELETE FROM `" . getDB()->getFullTableName('forum_attaches')
            . "` WHERE theme_id NOT IN (SELECT DISTINCT id FROM `" . getDB()->getFullTableName('themes') . "`)");
        getDB()->query("DELETE FROM `" . getDB()->getFullTableName('forum_attaches')
            . "` WHERE post_id NOT IN (SELECT DISTINCT id FROM `" . getDB()->getFullTableName('posts') . "`)");
    }


    public function addLastAuthors($forums)
    {
        $uids = array();
        if (!empty($forums)) {
            foreach ($forums as $forum) {
                if (!$forum->getLast_theme()) continue;

                $uid = $forum->getLast_theme()->getId_last_author();
                if (0 != $uid) {
                    $uids[] = $uid;
                }
            }


            if (!empty($uids)) {
                $uids = implode(', ', $uids);
                $usersModel = \OrmManager::getModelInstance('Users');
                $users = $usersModel->getCollection(array("`id` IN ({$uids})"));


                if (!empty($users)) {
                    foreach ($forums as $forum) {
                        if (!$forum->getLast_theme()) continue;
                        foreach ($users as $user) {
                            if ( $forum->getLast_theme()->getId_last_author() === $user->getId()) {
                                $forum->setLast_author($user);
                            }
                        }
                    }
                }
            }

        }
        return $forums;
    }

    /**
     * @param $user_id
     * @return array|bool
     */
    function getUserStatistic($user_id) {
        $user_id = intval($user_id);
        if ($user_id > 0) {
            $usersModel = \OrmManager::getModelInstance('Users');
            $result = $usersModel->getFirst(array('id' => $user_id));
            if ($result) {
                $res = array();

                if ($result->getThemes() > 0) {
                    $res[] = array(
                        'module' => 'forum',
                        'name' => 'posts',
                        'text' => __('themes',true,'forum'),
                        'count' => $result->getThemes(),
                        'url' => get_url('/forum/user_themes/' . $user_id),
                    );
                }

                if ($result->getPosts() > 0) {
                    $res[] = array(
                        'module' => 'forum',
                        'name' => 'posts',
                        'text' => __('messages',true,'forum'),
                        'count' => $result->getPosts(),
                        'url' => get_url('/forum/user_posts/' . $user_id),
                    );
                }
                return $res;
            }
        }
        return false;
    }
}
