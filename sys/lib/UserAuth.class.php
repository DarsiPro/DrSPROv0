<?php


/**
 *
 */
class UserAuth
{



    /**
     * @return void
     */
    static function setTimeVisit()
    {
        $DB = getDB();

        if (!isset($_SESSION['user']['id'])) $_SESSION['user']['id'] = 0;

        $query = "UPDATE `" . $DB->getFullTableName('users') . "`
                SET last_visit=NOW()
                WHERE id=".$_SESSION['user']['id'];
        $DB->query( $query );
    }



    /**
     * @return bool|void
     */
    static function autoLogin()
    {
        $DB = getDB();


        // Если не установлены cookie, содержащие логин и пароль
        if ( !isset( $_COOKIE['userid'] ) or !isset( $_COOKIE['password'] ) ) {
            $path ='/';
            if ( isset( $_COOKIE['userid'] ) ) setcookie( 'userid', '', time() - 1, $path );
            if ( isset( $_COOKIE['password'] ) ) setcookie( 'password', '', time() - 1, $path );
            if ( isset( $_COOKIE['autologin'] ) ) setcookie( 'autologin', '', time() - 1, $path );
            return false;
        }
        // Проверяем переменные cookie на недопустимые символы
        $user_id = intval($_COOKIE['userid']);
        if ($user_id < 1) return false;
        $password = trim($_COOKIE['password']);


        // Выполняем запрос на получение данных пользователя из БД
        $res = $DB->select('users', DB_FIRST, array(
            'cond' => array(
                'id' => $user_id,
                'passw' => $password,
            ),
            'fields' => array(
                '*',
                'UNIX_TIMESTAMP(last_visit) as unix_last_visit',
            ),
        ));


        // Если пользователь с таким логином и паролем не найден -
        // значит данные неверные и надо их удалить
        if ( count( $res ) < 1 ) {
            //$tmppos = strrpos( $_SERVER['PHP_SELF'], '/' ) + 1;
            //$path = substr( $_SERVER['PHP_SELF'], 0, $tmppos );
            $path = '/';
            setcookie( 'autologin', '', time() - 1, $path );
            setcookie( 'userid', '', time() - 1, $path );
            setcookie( 'password', '', time() - 1, $path );
            return false;
        }


        $user = $res[0];
        if ( !empty( $user['activation'] ) ) {
            //$tmppos = strrpos( $_SERVER['PHP_SELF'], '/' ) + 1;
            //$path = substr( $_SERVER['PHP_SELF'], 0, $tmppos );
            $path = '/';
            setcookie( 'autologin', '', time() - 1, $path );
            setcookie( 'userid', '', time() - 1, $path );
            setcookie( 'password', '', time() - 1, $path );

            header( 'Refresh: ' . Config::read('redirect_delay') . '; url=' . (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '/');
            $View = new Viewer_Manager();
            $output = $View->view('infomessagegrand.html', array('data' => array('info_message' => __('Your account not activated',true,'users'), 'error_message' => null)));
            echo $output;
            die();
        }

        // Если пользователь заблокирован
        if ( $user['locked'] ) {
            //$tmppos = strrpos( $_SERVER['PHP_SELF'], '/' ) + 1;
            //$path = substr( $_SERVER['PHP_SELF'], 0, $tmppos );
            $path = '/';
            setcookie( 'autologin', '', time() - 1, $path );
            setcookie( 'userid', '', time() - 1, $path );
            setcookie( 'password', '', time() - 1, $path );
            redirect('/users/baned/');
        }

        $_SESSION['user'] = $user;

        // Функция getNewThemes() помещает в массив $_SESSION['newThemes'] ID тем,
        // в которых были новые сообщения со времени последнего посещения пользователя
        self::getNewThemes();

        return true;
    }



    static function getNewThemes()
    {
        $DB = getDB();

        $query = "SELECT a.id, MAX(UNIX_TIMESTAMP(b.time)) AS unix_last_post
            FROM `" . $DB->getFullTableName('themes') . "` a
            INNER JOIN `" . $DB->getFullTableName('posts') . "` b
            ON a.id=b.id_theme
            GROUP BY a.id
            HAVING unix_last_post>" . $_SESSION['user']['unix_last_visit'];

        $res = $DB->query($query);

        if ($res) {
            foreach ($res as $key => $row) {
                $_SESSION['newThemes'][$row['id']] = $row['unix_last_post'];
            }
        }
    }



    static function countNewMessages() {
        $DB = getDB();

        $res = $DB->query("SELECT COUNT(*) as cnt
                FROM `" . $DB->getFullTableName('messages') . "`
                WHERE to_user=".(int)$_SESSION['user']['id']."
                AND viewed=0 AND id_rmv<>".(int)$_SESSION['user']['id']);
        if ( $res ) {
            return $res[0]['cnt'];
        } else {
            return 0;
        }
    }
}
