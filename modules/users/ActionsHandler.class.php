<?php
/**
* @project    DarsiPro CMS
* @package    Users Module
* @url        https://darsi.pro
*/
namespace UsersModule;

Class ActionsHandler extends \Module {

    /**
     * @template  layout for module
     * @var string
     */
    public $template = 'users';

    /**
     * @module_title  title of module
     * @var string
     */
    public $module_title = 'Пользователи';

    /**
     * @module module indentifier
     * @var string
     */
    public $module = 'users';
    
    function __construct($params) {
        parent::__construct($params);
        
        $this->setModel();
        
        
    }
    
    
    // Функция возвращает html списка пользователей форума
    public function index() {
        
        $Register = \Register::getInstance();
        
        //turn access
        \ACL::turnUser(array($this->module, 'view_list'),true);
        $this->page_title = __('Users list');
        // Выбираем из БД количество пользователей - это нужно для
        // построения постраничной навигации
        $total = $this->Model->getTotal(array());
        $perPage = intval(\Config::read('users_per_page', $this->module));
        if ($perPage < 1)
            $perPage = 10;
        list($pages, $page) = pagination($total, $perPage, $this->getModuleURL());


        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('Users list');
        $nav['pagination'] = $pages;

        $cntPages = ceil($total / $perPage);
        $recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
        $firstOnPage = ($page - 1) * $perPage + 1;
        $lastOnPage = $firstOnPage + $recOnPage - 1;

        $nav['meta'] = __('All users') . ' ' . $total . '. ' . ($total > 1 ? __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage : '');
        $this->_globalize($nav);


        if (!$total)
            return $this->_view(__('No users'));


        //order by
        $order = getOrderParam(__CLASS__);
        $queryParams = array(
            'order' => getDB()->escape($order),
            'page' => $page,
            'limit' => $perPage
        );
        $records = $this->Model->getCollection(array(), $queryParams);


        foreach ($records as $user) {
            $markers = array();
            $uid = $user->getId();


            $markers['moder_panel'] = '';
            if (\ACL::turnUser(array($this->module, 'edit_users'))) {
                $markers['moder_panel'] = get_link('', $this->getModuleURL('edit_form_by_admin/' . $uid), array('class' => 'drs-edit'));
            }


            $status = \ACL::get_group($user->getStatus());
            $markers['group'] = h($status['title']);
            $markers['group_color'] = h($status['color']);
            $markers['rank'] = h($user->getState());
            
            if ($user->getLocked())
                if (date('Y-m-d H:i:s') > $user->getBan_expire())
                $markers['baned'] = '&infin;';
                else
                $markers['baned'] = $user->getBan_expire();
            else
                $markers['baned'] = '';

            if (isset($_SESSION['user']['name'])) {
            if($_SESSION['user']['id'] != $uid)
            {
                $markers['pm'] = get_link(__('Send PM'), $this->getModuleURL('send_pm_form/' . $uid));
            }
            } else {
            
                $markers['pm'] = __('You are not authorized');
            }
            if ($user->getUrl())
                $markers['url'] = get_link(h($user->getUrl()), h($user->getUrl()), array('target' => '_blank'));
            else
                $markers['url'] = '&nbsp;';


            if ($user->getPol() === 'f')
                $markers['pol'] = __('f');
            else if ($user->getPol() === 'm')
                $markers['pol'] = __('m');
            else
                $markers['pol'] = __('no sex');


            if ($user->getByear() && $user->getBmonth() && $user->getBday()) {
                $markers['age'] = getAge($user->getByear(), $user->getBmonth(), $user->getBday());
            } else {
                $markers['age'] = '';
            }

            foreach ($markers as $k => $v) {
                $setter = 'set' . ucfirst($k);
                $user->$setter($v);
            }
        }


        $source = $this->render('list.html', array('entities' => $records));
        return $this->_view($source);
    }

    /**
     * @param string $key
     * if exists, user say "YES" and ready to register
     */
    public function add_form($key = null) {
        if (!empty($_SESSION['user']['id']) || (!empty($key) && $key !== 'yes'))
            return $this->showMessage(__('Some error occurred'), '/');

        // Registration denied
        if (!\Config::read('open_reg', $this->module)) {
            return $this->showMessage(__('Registration denied'), '/');
        }

        $this->page_title = __('Registration');
        
        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('Registration');
        $this->_globalize($nav);


        // View rules
        if (empty($key)) {
            $tash = R.'data/reg_rules.msg';
            if (file_exists($tash)) {
                $rules = file_get_contents($tash);
            } else {
                $rules = '';
            }
            $markers = array();
            $markers['rules'] = $rules;
            $markers['reg_url'] = get_url($this->getModuleURL('add_form/yes'));
            $content = $this->render('viewrules.html', array('context' => $markers));
            $this->_view($content);
            die();
        }


        // View \Register Form
        $markers = array();

        // Add fields
        $_addFields = $this->AddFields->getInputs();
        foreach ($_addFields as $k => $field) {
            $markers[strtolower($k)] = $field;
        }


        if (isset($_SESSION['captcha_keystring']))
            unset($_SESSION['captcha_keystring']);

        $markers['captcha'] = get_url('/sys/inc/kcaptcha/kc.php?' . session_name() . '=' . session_id());
        $markers['keystring'] = '';
        $markers['action'] = get_url($this->getModuleURL('add/'));


        $markers['byears_selector'] = createOptionsFromParams(1940, 2014);
        $markers['bmonth_selector'] = createOptionsFromParams(1, 12);
        $markers['bday_selector'] = createOptionsFromParams(1, 31);

        $source = $this->render('addnewuserform.html', array('context' => $markers));
        return $this->_view($source);
    }

    /**
     * Write into base and check data. Also work for additional fields.
     */
    public function add() {
        if (!empty($_SESSION['user']['id']))
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);

        // Если не переданы данные формы - значит функция была вызвана по ошибке
        if (!isset($_POST['login']) or
                !isset($_POST['password']) or
                !isset($_POST['confirm']) or
                !isset($_POST['email']) or
                !isset($_POST['keystring'])
        ) {
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
        }
        $errors = '';


        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $fields = array(
            'login',
            'full_name',
            'password',
            'confirm',
            'email',
            'pol',
            'byear',
            'bmonth',
            'bday',
            'url',
            'about',
            'signature',
            'keystring'
        );

        $fields_settings = (array) \Config::read('fields', $this->module);
        $fields_settings = array_merge($fields_settings, array('email', 'login', 'password', 'confirm'));

        foreach ($fields as $field) {
            if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
                $errors = $errors . '<li>' . sprintf(__('Empty field "param"'), __($field)) . '</li>' . "\n";
                $$field = null;
            } else {
                $$field = (isset($_POST[$field])) ? trim($_POST[$field]) : '';
            }
        }


        if ('1' === $pol)
            $pol = 'm';
        else if ('2' === $pol)
            $pol = 'f';
        else
            $pol = '';



        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $name = mb_substr($login, 0, 30);
        $full_name = mb_substr($full_name, 0, 255);
        $password = mb_substr($password, 0, 64);
        $confirm = mb_substr($confirm, 0, 64);
        $email = mb_substr($email, 0, 60);
        $byear = intval(mb_substr($byear, 0, 4));
        $bmonth = intval(mb_substr($bmonth, 0, 2));
        $bday = intval(mb_substr($bday, 0, 2));
        $url = mb_substr($url, 0, 60);
        $about = mb_substr($about, 0, 1000);
        $signature = mb_substr($signature, 0, 500);



        // Проверяем, заполнены ли обязательные поля
        // Additional fields checker
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $errors .= $_addFields;


        

        // check login
        if (!empty($name) and mb_strlen($name) < 3)
            $errors .= '<li>' . sprintf(__('Very small "param"'), __('login'), 3) . '</li>' . "\n";
        if (!empty($name) and mb_strlen($name) > 20)
            $errors .= '<li>' . sprintf(__('Very big "param"'), __('login'), 20) . '</li>' . "\n";
        
        // Проверяем, не слишком ли короткий пароль
        if (!empty($password) and mb_strlen($password) < \Config::read('min_password_lenght', $this->module))
            $errors .= '<li>' . sprintf(__('Very small "param"'), __('password'), \Config::read('min_password_lenght', $this->module)) . '</li>' . "\n";
        // Проверяем, совпадают ли пароли
        if (!empty($password) and !empty($confirm) and $password != $confirm)
            $errors .= '<li>' . __('Passwords are different') . '</li>' . "\n";

        // Проверяем поле "код"
        if (!empty($keystring)) {
            // Проверяем поле "код" на недопустимые символы
            if (!\Validate::cha_val($keystring, V_CAPTCHA))
                $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Captcha')) . '</li>' . "\n";

            if (!isset($_SESSION['captcha_keystring'])) {
                if (file_exists(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat')) {
                    $_SESSION['captcha_keystring'] = file_get_contents(ROOT . '/sys/logs/captcha_keystring_'
                            . session_id() . '-' . date("Y-m-d") . '.dat');
                }
            }
            if (!isset($_SESSION['captcha_keystring']) || $_SESSION['captcha_keystring'] != $keystring)
                $errors .= '<li>' . __('Wrong protection code') . '</li>' . "\n";
        }
        unset($_SESSION['captcha_keystring']);


        // Проверяем поля формы на недопустимые символы
        if (!empty($name) and (
            (\Config::read('users.only_latin') and !\Validate::cha_val($name, V_LOGIN_LATIN)) or
            (!\Validate::cha_val($name, V_LOGIN))))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('login')) . '</li>' . "\n";
        if (!empty($full_name) and !\Validate::cha_val($full_name, V_FULLNAME))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('full_name')) . '</li>' . "\n";
        if (!empty($about) and !\Validate::cha_val($about, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('about')) . '</li>' . "\n";
        if (!empty($signature) and !\Validate::cha_val($signature, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('signature')) . '</li>' . "\n";
        // Проверяем корректность e-mail
        if (!empty($email) and !\Validate::cha_val($email, V_MAIL))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('email')) . '</li>' . "\n";
        // Проверяем корректность URL домашней странички
        if (!empty($url) and !\Validate::cha_val($url, V_URL))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('url')) . '</li>' . "\n";
        if (!empty($byear) && !\Validate::cha_val($byear, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('byear')) . '</li>' . "\n";
        if (!empty($bmonth) && !\Validate::cha_val($bmonth, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('bmonth')) . '</li>' . "\n";
        if (!empty($bday) && !\Validate::cha_val($bday, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('bday')) . '</li>' . "\n";

        
        // Проверяем на занятость ник
        $res = $this->Model->getSameNics($name);
        if (is_array($res) && count($res) > 0)
            $errors .= '<li>' . sprintf(__('Name already exists'), $name) . '</li>' . "\n";

        /* check and download avatar */
        $tmp_key = rand(0, 9999999);
        $out = downloadAvatar($this->module, $tmp_key);
        if ($out != null)
            $errors .= $out;

        // Errors
        if (!empty($errors)) {
            $error_msg = '<p class="errorMsg">' . __('Some error in form') . '</p>'
                . "\n" . '<ul class="errorMsg">' . "\n" . $errors . '</ul>' . "\n";
            return $this->showMessage($error_msg, getReferer(),'error', true);
        }

        if (!empty($url) && mb_substr($url, 0, mb_strlen('http://')) !== 'http://' && mb_substr($url, 0, mb_strlen('https://')) !== 'https://')
            $url = 'http://' . $url;

        // Уникальный код для активации учетной записи
        $email_activate = \Config::read('email_activate', $this->module);
        $code = (!empty($email_activate)) ? md5(uniqid(rand(), true)) : '';
        // Все поля заполнены правильно - продолжаем регистрацию
        $data = array(
            'name' => $name,
            'full_name' => $full_name,
            'passw' => md5crypt($password),
            'email' => $email,
            'url' => $url,
            'pol' => $pol,
            'byear' => $byear,
            'bmonth' => $bmonth,
            'bday' => $bday,
            'about' => $about,
            'signature' => $signature,
            'photo' => '',
            'puttime' => new \Expr('NOW()'),
            'last_visit' => new \Expr('NOW()'),
            'themes' => 0,
            'status' => 1,
            'activation' => $code
        );

        $data = $this->AddFields->set($_addFields, $data);
        $entity = new \UsersModule\ORM\UsersEntity($data);
        $id = $entity->save();


        if (file_exists(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg')) {
            if (copy(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg', ROOT . '/data/avatars/' . $id . '.jpg')) {
                chmod(ROOT . '/data/avatars/' . $id . '.jpg', 0644);
            }
            unlink(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg');
        }


        // Activate by Email
        if (!empty($email_activate)) {
            // Посылаем письмо пользователю с просьбой активировать учетную запись
            $mailer = new \DrsMail();

            $link = (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $this->getModuleURL('activate/' . $code);
            $subject = sprintf(__('Registration to'), $_SERVER['SERVER_NAME']);

            $mail = array(
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'link' => $link,
            );

            $mailer->setTo($email);
            $mailer->setSubject($subject);
            $mailer->setContentHtml(__('mail_content_html_activation'));
            $mailer->setContentText(__('mail_content_text_activation'));

            if ($mailer->sendMail($mail))
                $msg = __('End of registration');
            else {
                
                if ($this->isLogging)
                \Logination::write('adding user but mail not sended', 'user id(' . $id . ')');
                
                return $this->showMessage(__('Registration complete but mail not sended'), $this->getModuleURL('login_form/'),'grand');
            }
        } else { // Activate without Email
            $msg = __('Registration complete');
        }
        
        if ($this->isLogging)
            \Logination::write('adding user', 'user id(' . $id . ')');
            
        return $this->showMessage($msg, $this->getModuleURL('login_form/'),'grand');
    }

    // Активация учетной записи нового пользователя
    public function activate($code = null) {
        // Если не передан параметр $code - значит функция вызвана по ошибке
        if (empty($code) || mb_strlen($code) !== 32) {
            return $this->showMessage(__('Some error occurred'), '/');
        }

        // Т.к. код зашифрован с помощью md5, то он представляет собой
        // 32-значное шестнадцатеричное число
        $code = substr($code, 0, 32);
        $code = preg_replace("#[^0-9a-f]#i", '', $code);
        $res = $this->Model->getFirst(array('activation' => $code));

        if ($res) {
            $id = $res->getId();
            $res->setActivation('');
            $res->setLast_visit(new \Expr('NOW()'));
            $res->save();
            if ($this->isLogging)
                \Logination::write('activate user', 'user id(' . $id . ')');
            return $this->showMessage(__('Account activated'), $this->getModuleURL('login_form/'),'ok');
        }
        if ($this->isLogging)
            \Logination::write('wrong activate user', 'activate code(' . $code . ')');
        return $this->showMessage(__('Wrong activation code'), '/');
    }

    /**
     * Return form to request new password
     *
     */
    public function new_password_form() {
        $markers = array();
        $markers['errors'] = '';
        if (isset($_SESSION['newPasswordForm']['errors'])) {
            $context = array(
                'message' => $_SESSION['newPasswordForm']['errors'],
            );
            $markers['errors'] = $this->render('infomessage.html', $context);
            unset($_SESSION['newPasswordForm']['errors']);
        }
        
        $this->page_title = __('Password repair');
        
        // Navigation panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('Password repair');
        $this->_globalize($nav);

        $markers['action'] = get_url($this->getModuleURL('send_new_password/'));
        $source = $this->render('newpasswordform.html', array('context' => $markers));

        return $this->_view($source);
    }

    // Функция высылает на e-mail пользователя новый пароль
    public function send_new_password() {

        // Если не переданы методом POST логин и e-mail - перенаправляем пользователя
        if (!isset($_POST['username']) and !isset($_POST['email'])) {
            return $this->showMessage(__('Some error occurred'), '/');
        }

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $name = mb_substr($_POST['username'], 0, 30);
        $email = mb_substr($_POST['email'], 0, 60);
        $name = trim($name);
        $email = trim($email);

        // Проверяем, заполнены ли обязательные поля
        $errors = '';
        
        if (empty($name) and empty($email))
            $errors = $errors . '<li>' . __('Name and e-mail is empty') . '</li>' . "\n";

        // Проверяем поля формы на недопустимые символы
        if (!empty($name) and !\Validate::cha_val($name, V_LOGIN))
            $errors = $errors . '<li>' . sprintf(__('Wrong chars in field "param"'), __('login')) . '</li>' . "\n";
        // Проверяем корректность e-mail
        if (!empty($email) and !\Validate::cha_val($email, V_MAIL))
            $errors = $errors . '<li>' . sprintf(__('Wrong chars in field "param"'), __('email')) . '</li>' . "\n";
        // Проверять существование такого пользователя есть смысл только в том
        // случае, если поля не пустые и не содержат недопустимых символов
        if (empty($errors)) {
            touchDir(ROOT . '/sys/tmp/activate/');

            if (!empty($name)) {
                $res = $this->Model->getCollection(array('name' => $name));
            } else {
                $res = $this->Model->getCollection(array('email' => $email));
            }
            // Если пользователь с таким логином и e-mail существует
            if (is_array($res) && count($res) > 0 && empty($errors)) {
                // Небольшой код, который читает содержимое директории activate
                // и удаляет старые файлы для активации пароля (были созданы более суток назад)
                if ($dir = opendir(ROOT . '/sys/tmp/activate')) {
                    $tmp = 24 * 60 * 60;
                    while (false !== ($file = readdir($dir))) {
                        if (is_file($file))
                            if ((time() - filemtime($file)) > $tmp)
                                unlink($file);
                    }
                    closedir($dir);
                }


                // Как происходит процедура восстановления пароля? Пользователь ввел свой логин
                // и e-mail, мы проверяем существование такого пользователя в таблице БД. Потом
                // генерируем с помощью функции getNewPassword() новый пароль, создаем файл с именем
                // хэша пароля в директории activate. Файл содержит ID пользователя.
                // В качестве кода активации выступает хэш пароля.
                // Когда пользователь перейдет по ссылке в письме для активации своего нового пароля,
                // мы проверяем наличие в директории activatePassword файла с именем кода активации,
                // и если он существует, активируем новый пароль.
                $user = $res[0];
                $id = $user->getId();
                $name = $user->getName();
                $email = $user->getEmail();
                $newPassword = $this->_getNewPassword();
                $code = md5crypt($newPassword);
                $filename = md5($code);
                $fp = fopen(ROOT . '/sys/tmp/activate/' . $filename, "w");
                fwrite($fp, $id . "\n" . $code);
                fclose($fp);


                // Посылаем письмо пользователю с просьбой активировать пароль
                $mailer = new \DrsMail();

                $link = (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $this->getModuleURL('activate_password/' . $filename);
                $subject = sprintf(__('Password restore'), $_SERVER['SERVER_NAME']);

                $mail = array(
                    'name' => $name,
                    'password' => $newPassword,
                    'link' => $link,
                );

                $mailer->setTo($email);
                $mailer->setSubject($subject);
                $mailer->setContentHtml(__('mail_content_html_restore'));
                $mailer->setContentText(__('mail_content_text_restore'));
                $mailer->sendMail($mail);

                $msg = __('We send mail to your e-mail');
                return $this->showMessage($msg, $this->getModuleURL('new_password_form/'), 'grand');


                if ($this->isLogging)
                    \Logination::write('send new passw', 'name(' . $name . '), mail(' . $email . ')');
                return $this->_view($source);
            } else {
                $errors = $errors . '<li>' . __('Wrong login or email') . '</li>' . "\n";
            }
        }


        if ($this->isLogging)
            \Logination::write('wrong send new passw', 'name(' . $name . '), mail(' . $email . ')');
        // Если были допущены ошибки при заполнении формы - перенаправляем посетителя
        if (!empty($errors)) {
            $_SESSION['newPasswordForm'] = array();
            $_SESSION['newPasswordForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' . "\n"
                    . '<ul class="errorMsg">' . "\n" . $errors . '</ul>' . "\n";
            return $this->showMessage($_SESSION['newPasswordForm']['errors'], $this->getModuleURL('new_password_form/'));
        }
    }

    // Активация нового пароля
    public function activate_password($code = null) {
        if (!isset($code))
            return $this->showMessage(__('Some error occurred'), '/');

        // Т.к. код активации создан с помощью md5, то он
        // представляет собой 32-значное шестнадцатеричное число
        $code = mb_substr($code, 0, 32);
        $code = preg_replace("#[^0-9a-f]#i", '', $code);

        if (empty($code))
            return $this->showMessage(__('Some error occurred'), '/');

        $f_path = ROOT . '/sys/tmp/activate/' . $code;
        if (is_file($f_path) and ((time() - filemtime($f_path)) < 24 * 60 * 60)) {
            $file = file($f_path);
            unlink($f_path);
            $id_user = intval(trim($file[0]));
            $user = $this->Model->getById($id_user);
            if ($user) {
                $user->setPassw(count($file) > 1 ? trim($file[1]) : $code);
                $user->save();
            }
            if ($this->isLogging)
                \Logination::write('activate new passw', 'user id(' . $id_user . ')');
                
            return $this->showMessage(__('New pass is ready'), $this->getModuleURL('login_form/'),'grand');
        } else {
            if ($this->isLogging)
                \Logination::write('wrong activate new passw', 'code(' . $code . ')');
                
            return $this->showMessage(__('Error when activate new pass'), $this->getModuleURL('new_password_form/'));
        }

    }

    // Функция возвращает случайно сгенерированный пароль
    private function _getNewPassword() {
        $length = rand(10, 30);
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $range = rand(1, 3);
            switch ($range) {
                case 1: $password = $password . chr(rand(48, 57));
                    break;
                case 2: $password = $password . chr(rand(65, 90));
                    break;
                case 3: $password = $password . chr(rand(97, 122));
                    break;
            }
        }
        return $password;
    }

    // Функция возвращает html формы для редактирования данных о пользователе(с которого открыта форма)
    public function edit_form() {
        if (!isset($_SESSION['user']['name']))
            redirect('/');
        
        $this->page_title = __('Editing');

        //turn access
        \ACL::turnUser(array($this->module, 'edit_mine'),true);


        $user = $this->Model->getById((int)$_SESSION['user']['id']);
        if ($user) {
            $user = $this->AddFields->mergeSelect(array($user));
            $user = $user[0];
        }

        $data = $user;

        $fpol = ($data->getPol() && $data->getPol() === 'f') ? ' checked="checked"' : '';
        $data->setFpol($fpol);
        $mpol = ($data->getPol() && $data->getPol() === 'm') ? ' checked="checked"' : '';
        $data->setMpol($mpol);


        $data->setAction(get_url($this->getModuleURL('update/')));
        if ($data->getPol() === 'f')
            $data->setPol(__('f'));
        else if ($data->getPol() === 'm')
            $data->setPol(__('m'));
        else
            $data->setPol(__('no sex'));



        $data->setAvatar(getAvatar($user->getId()));

        $data->setByears_selector(createOptionsFromParams(1950, 2008, $data->getByear()));
        $data->setBmonth_selector(createOptionsFromParams(1, 12, $data->getBmonth()));
        $data->setBday_selector(createOptionsFromParams(1, 31, $data->getBday()));

        $dir = opendir(ROOT . '/template');
        $template = '';
        while ($tempdef = readdir($dir)) {
            if ($tempdef{0} != '.') {
                $tempdef = str_replace('.css', '', $tempdef);
                $template .= '<option' . (getTemplate() == $tempdef ? ' selected="selected">' : '>') . $tempdef . '</option>';
            }
        }
        $data->setTemplate($template);

        $unlinkfile = '';
        if (is_file(ROOT . '/data/avatars/' . $_SESSION['user']['id'] . '.jpg')) {
            $unlinkfile = '<input type="checkbox" name="unlink" value="1" />'
                    . __('Are you want delete file') . "\n";
        }
        $data->setUnlinkfile($unlinkfile);

        // Navigation Panel
        $navi = array();
        $navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator')
                . get_link(h($user->getName()), '/' . $this->module . '/info/' . $user->getId()) . __('Separator')
                . __('Editing');
        $this->_globalize($navi);

        $source = $this->render('edituserform.html', array('context' => $data));

        return $this->_view($source);
    }

    /**
     * Update record into Data Base
     */
    public function update() {
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'));

        //turn access
        \ACL::turnUser(array($this->module, 'edit_mine'),true);

        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($_POST['password']) or
            !isset($_POST['newpassword']) or
            !isset($_POST['confirm']) or
            !isset($_POST['email'])
        ) {
            return $this->showMessage(__('Some error occurred'),$this->getModuleURL('edit_form/'));
        }


        $errors = '';
        $markers = array();


        $fields = array(
            'full_name',
            'email',
            'pol',
            'byear',
            'bmonth',
            'bday',
            'url',
            'about',
            'signature',
            'template'
        );


        // Additional fields
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $errors .= $_addFields;


        $fields_settings = (array) \Config::read('fields', $this->module);


        foreach ($fields as $field) {
            if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __($field)) . '</li>' . "\n";
            $$field = null;
            } else {
            $$field = (isset($_POST[$field])) ? trim($_POST[$field]) : '';
            }
        }



        if ('1' === $pol)
            $pol = 'm';
        else if ('2' === $pol)
            $pol = 'f';
        else
            $pol = '';



        // Обрезаем лишние пробелы
        $password = (!empty($_POST['password'])) ? trim($_POST['password']) : '';
        $newpassword = (!empty($_POST['newpassword'])) ? trim($_POST['newpassword']) : '';
        $confirm = (!empty($_POST['confirm'])) ? trim($_POST['confirm']) : '';


        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $full_name = mb_substr($full_name, 0, 255);
        $password = mb_substr($password, 0, 64);
        $newpassword = mb_substr($newpassword, 0, 64);
        $confirm = mb_substr($confirm, 0, 64);
        $email = mb_substr($email, 0, 60);
        $byear = intval(mb_substr($byear, 0, 4));
        $bmonth = intval(mb_substr($bmonth, 0, 2));
        $bday = intval(mb_substr($bday, 0, 2));
        $url = mb_substr($url, 0, 60);
        $about = mb_substr($about, 0, 1000);
        $signature = mb_substr($signature, 0, 500);
        $template = mb_substr($template, 0, 255);


        
        // Если заполнено поле "Текущий пароль" - значит пользователь
        // хочет изменить его или поменять свой e-mail
        $changePassword = false;
        $changeEmail = false;
        if (!empty($password)) {
            if (!checkPassword($_SESSION['user']['passw'], $password))
            $errors .= '<li>' . __('Wrong current pass') . '</li>' . "\n";
            // Надо выяснить, что хочет сделать пользователь:
            // поменять свой e-mail, изменить пароль или и то и другое
            if (!empty($newpassword)) { // хочет изменить пароль
                $changePassword = true;
                if (empty($confirm))
                    $errors .= '<li>' . sprintf(__('Empty field "param"'), __('confirm')) . '</li>' . "\n";
                if (mb_strlen($newpassword) < \Config::read('min_password_lenght', $this->module))
                    $errors .= '<li>' . sprintf(__('Very small "param"'), __('password'), \Config::read('min_password_lenght', $this->module)) . '</li>' . "\n";
                if (!empty($confirm) and $newpassword != $confirm)
                    $errors .= '<li>' . __('Passwords are different') . '</li>' . "\n";
            }
            if ($email != $_SESSION['user']['email']) { // хочет изменить e-mail
            $changeEmail = true;
            if (!empty($email) and !\Validate::cha_val($email, V_MAIL))
                $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('email')) . '</li>' . "\n";
            }
        // Не указав пароль(старый), указывает новый пароль или email
        } elseif ($email != $_SESSION['user']['email'] or !empty($newpassword))
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __('password')) . '</li>' . "\n";
        
        if (!empty($full_name) and !\Validate::cha_val($full_name, V_FULLNAME))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('full_name')) . '</li>' . "\n";
        if (!empty($about) and !\Validate::cha_val($about, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('about')) . '</li>' . "\n";
        if (!empty($signature) and !\Validate::cha_val($signature, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('signature')) . '</li>' . "\n";
        if (!empty($url) and !\Validate::cha_val($url, V_URL))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('url')) . '</li>' . "\n";
        if (!empty($byear) && !\Validate::cha_val($byear, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('byear')) . '</li>' . "\n";
        if (!empty($bmonth) && !\Validate::cha_val($bmonth, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('bmonth')) . '</li>' . "\n";
        if (!empty($bday) && !\Validate::cha_val($bday, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('bday')) . '</li>' . "\n";
        if (!empty($template) and !\Validate::cha_val($template, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('template')) . '</li>' . "\n";

        /* check and download avatar */
        $tmp_key = rand(0, 9999999);
        $out = downloadAvatar($this->module, $tmp_key);
        if ($out != null)
            $errors .= $out;

        if (!empty($template) and ($template{0} == '.' or !is_dir(ROOT . '/template/' . $template))) {
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('template')) . '</li>' . "\n";
        }
        
        if ($email != $_SESSION['user']['email'] and empty($errors)) {
            $Cache = new \Cache;
            $Cache->prefix = 'gravatar';
            $Cache->cacheDir = 'sys/cache/users/gravatars/';
            if ($Cache->check('user_' . $_SESSION['user']['id']))
            $Cache->remove('user_' . $_SESSION['user']['id']);
        }
        
        // Errors
        if (!empty($errors)) {
            $error_msg = '<p class="errorMsg">' . __('Some error in form') . '</p>'
                . "\n" . '<ul class="errorMsg">' . "\n" . $errors . '</ul>' . "\n";
            return $this->showMessage($error_msg, $this->getModuleURL('edit_form/'));
        }

        // если выбран дефолтный шаблон или не изменён то не записываем
        if ($template == \Config::read('template'))
            $template = null;

        // но если выбран и не дефолтный то пишем в сессию
        if ($template != null)
            $_SESSION['user']['template'] = $template;

        // Если выставлен флажок "Удалить загруженный ранее файл"
        if (isset($_POST['unlink']) and is_file(ROOT . '/data/avatars/' . $_SESSION['user']['id'] . '.jpg')) {
            unlink(ROOT . '/data/avatars/' . $_SESSION['user']['id'] . '.jpg');
        }
        /* copy and delete tmp image */
        if (file_exists(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg')) {
            if (copy(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg', ROOT . '/data/avatars/' . $_SESSION['user']['id'] . '.jpg')) {
            chmod(ROOT . '/data/avatars/' . $_SESSION['user']['id'] . '.jpg', 0644);
            }
            unlink(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg');
        }

        // Все поля заполнены правильно - записываем изменения в БД
        if (!empty($url) && mb_substr($url, 0, mb_strlen('http://')) !== 'http://' && mb_substr($url, 0, mb_strlen('https://')) !== 'https://')
            $url = 'http://' . $url;


        $user = $this->Model->getById($_SESSION['user']['id']);

        if ($user) {
            if ($changePassword) {
            $npass = md5crypt($newpassword);
            $user->setPassw($npass);
            $_SESSION['user']['passw'] = $npass;
            }
            if ($changeEmail) {
            $user->setEmail($email);
            $_SESSION['user']['email'] = $email;
            }
            $user->setFull_name($full_name);
            $user->setUrl($url);
            $user->setPol($pol);
            $user->setByear($byear);
            $user->setBmonth($bmonth);
            $user->setBday($bday);
            $user->setAbout($about);
            $user->setSignature($signature);
            $user->setTemplate($template);
            $user->setAdd_fields($_addFields);
            $user->save();
        }


        // ... и в массиве $_COOKIE
        if (isset($_COOKIE['autologin'])) {
            $path = "/";
            setcookie('autologin', 'yes', time() + 3600 * 24 * \Config::read('cookie_time'), $path);
            setcookie('userid', $_SESSION['user']['id'], time() + 3600 * 24 * \Config::read('cookie_time'), $path);
            setcookie('password', $_SESSION['user']['passw'], time() + 3600 * 24 * \Config::read('cookie_time'), $path);
        }
        if ($this->isLogging)
            \Logination::write('editing user', 'user id(' . $_SESSION['user']['id'] . ')');
        return $this->showMessage(__('Your profile has been changed'), $this->getModuleURL('info/' . $_SESSION['user']['id']), 'ok');
    }

    /**
     * Edit form by admin
     */
    public function edit_form_by_admin($id = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'edit_users'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
            
        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Can not find user'),getReferer(),'error', true);
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'), getReferer(),'error', true);

        $statusArray = \ACL::get_group_info();
        if (!empty($statusArray))
            unset($statusArray[0]);
        $markers = array();
        

        // Получаем данные о пользователе из БД
        $user = $this->Model->getById($id);
        if (!$user || count($user) == 0)
            return $this->showMessage(__('Can not find user'), getReferer(),'error', true);
        if (is_array($user) && count($user) > 0) {
            $user = $this->AddFields->mergeSelect(array($user));
            $user = $user[0];
        }

        $this->page_title = __('Editing').' '.$user->getName();

        $data = $user;


        $fpol = ($data->getPol() && $data->getPol() === 'f' || $data->getPol() === '2') ? ' checked="checked"' : '';
        $data->setFpol($fpol);
        $mpol = ($data->getPol() && $data->getPol() === 'm' || $data->getPol() === '1') ? ' checked="checked"' : '';
        $data->setMpol($mpol);


        $data->setAction(get_url($this->getModuleURL('update_by_admin/' . $id)));
        if ($data->getPol() === 'f')
            $data->setPol(__('f'));
        else if ($data->getPol() === 'm')
            $data->setPol(__('m'));
        else
            $data->setPol(__('no sex'));



        $data->setAvatar(getAvatar($data->getId()));

        $data->setByears_selector(createOptionsFromParams(1950, 2008, $data->getByear()));
        $data->setBmonth_selector(createOptionsFromParams(1, 12, $data->getBmonth()));
        $data->setBday_selector(createOptionsFromParams(1, 31, $data->getBday()));

        $dir = opendir(ROOT . '/template');
        $template = '';
        while ($tempdef = readdir($dir)) {
            if ($tempdef{0} != '.') {
                $tempdef = str_replace('.css', '', $tempdef);
                $template .= '<option' . (getTemplate() == $tempdef ? ' selected="selected">' : '>') . $tempdef . '</option>';
            }
        }
        $data->setTemplate($template);

        $unlinkfile = '';
        if (is_file(ROOT . '/data/avatars/' . $_SESSION['user']['id'] . '.jpg')) {
            $unlinkfile = '<input type="checkbox" name="unlink" value="1" />'
                    . __('Are you want delete file') . "\n";
        }
        $data->setUnlinkfile($unlinkfile);


        $userStatus = '<select name="status">' . "\n";
        foreach ($statusArray as $key => $value) {
            if ($key == $data->getStatus())
                $userStatus = $userStatus . '<option value="' . $key . '" selected>' . $value['title'] . '</option>' . "\n";
            else
                $userStatus = $userStatus . '<option value="' . $key . '">' . $value['title'] . '</option>' . "\n";
        }
        $userStatus = $userStatus . '</select>' . "\n";
        $data->setStatus($userStatus);
        $data->setOldemail(h($user->getEmail()));
        $data->setLogin($data->getName());


        $activation = ($user->getActivation()) ? __('Activate') . ' <input name="activation" type="checkbox" value="1" >' : __('Active');
        $data->setActivation($activation);


        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator')
                . get_link(h($user->getName()), '/' . $this->module . '/info/' . $user->getId()) . __('Separator')
                . __('Editing');
        $this->_globalize($nav);


        $source = $this->render('edituserformbyadmin.html', array('context' => $data));

        return $this->_view($source);
    }

    // Функция обновляет данные пользователя (только для администратора форума)
    public function update_by_admin($id = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'edit_users'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
            
        $id = (int)$id;
        // ID зарегистрированного пользователя не может быть меньше
        // единицы - значит функция вызвана по ошибке
        if ($id < 1)
            return $this->showMessage(__('Can not find user'),getReferer(),'error', true);
        // Если профиль пытается редактировать не зарегистрированный
        // пользователь - функция вызвана по ошибке
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'),'/');



        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($_POST['status']) or
            !isset($_POST['email']) or
            !isset($_POST['newpassword']) or
            !isset($_POST['confirm'])
        ) {
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
        }


        // Получаем данные о пользователе из БД
        $user = $this->Model->getById($id);
        if (!$user)
            return $this->showMessage(__('Can not find user'),getReferer(),'error', true);
        if ($user) {
            $user = $this->AddFields->mergeSelect(array($user));
            $user = $user[0];
        }



        $errors = '';
        $fields = array(
            'full_name',
            'email',
            'pol',
            'byear',
            'bmonth',
            'bday',
            'url',
            'about',
            'signature',
            'template',
            'status'
        );

        $fields_settings = (array) \Config::read('fields', $this->module);
        $fields_settings = array_merge($fields_settings, array('email'));

        foreach ($fields as $field) {
            if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
            $errors = $errors . '<li>' . sprintf(__('Empty field "param"'), __($field)) . '</li>' . "\n";
            $$field = null;
            } else {
            $$field = (isset($_POST[$field])) ? trim($_POST[$field]) : '';
            }
        }


        if ('1' === $pol)
            $pol = 'm';
        else if ('2' === $pol)
            $pol = 'f';
        else
            $pol = '';



        // Обрезаем лишние пробелы
        $password = (!empty($_POST['password'])) ? trim($_POST['password']) : '';
        $newpassword = (!empty($_POST['newpassword'])) ? trim($_POST['newpassword']) : '';
        $confirm = (!empty($_POST['confirm'])) ? trim($_POST['confirm']) : '';


        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $full_name = mb_substr($full_name, 0, 255);
        $password = mb_substr($password, 0, 64);
        $newpassword = mb_substr($newpassword, 0, 64);
        $confirm = mb_substr($confirm, 0, 64);
        $email = mb_substr($email, 0, 60);
        $oldEmail = $user->getEmail() ? mb_substr($user->getEmail(), 0, 60) : '';
        $byear = intval(mb_substr($byear, 0, 4));
        $bmonth = intval(mb_substr($bmonth, 0, 2));
        $bday = intval(mb_substr($bday, 0, 2));
        $url = mb_substr($url, 0, 60);
        $about = mb_substr($about, 0, 1000);
        $signature = mb_substr($signature, 0, 500);
        $template = mb_substr($template, 0, 255);



        // Additional fields
        $_addFields = $this->AddFields->checkFields();
        if (is_string($_addFields))
            $errors .= $_addFields;

        
        // Надо выяснить, что хочет сделать администратор:
        // поменять e-mail, изменить пароль или и то и другое
        $changePassword = false;
        $changeEmail = false;

        if (!empty($newpassword)) { // хочет изменить пароль
            $changePassword = true;
            if (empty($confirm))
                $errors .= '<li>' . sprintf(__('Empty field "param"'), __('confirm')) . '</li>' . "\n";
            if (mb_strlen($newpassword) < \Config::read('min_password_lenght', $this->module))
                $errors .= '<li>' . sprintf(__('Very small "param"'), __('password'), \Config::read('min_password_lenght', $this->module)) . '</li>' . "\n";
            if (!empty($confirm) and $newpassword != $confirm)
                $errors .= '<li>' . __('Passwords are different') . '</li>' . "\n";
        }
        if (!empty($email) && $email != $oldEmail) { // хочет изменить e-mail
            $changeEmail = true;
            if (empty($email))
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __('email')) . '</li>' . "\n";
            if (!empty($email) and !\Validate::cha_val($email, V_MAIL))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('email')) . '</li>' . "\n";
        }


        // Проверяем поля формы на недопустимые символы
        if (!empty($full_name) and !\Validate::cha_val($full_name, V_FULLNAME))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('full_name')) . '</li>' . "\n";
        if (!empty($about) and !\Validate::cha_val($about, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('about')) . '</li>' . "\n";
        if (!empty($signature) and !\Validate::cha_val($signature, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('signature')) . '</li>' . "\n";
        if (!empty($url) and !\Validate::cha_val($url, V_URL))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('url')) . '</li>' . "\n";
        if (!empty($byear) && !\Validate::cha_val($byear, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('byear')) . '</li>' . "\n";
        if (!empty($bmonth) && !\Validate::cha_val($bmonth, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('bmonth')) . '</li>' . "\n";
        if (!empty($bday) && !\Validate::cha_val($bday, V_INT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('bday')) . '</li>' . "\n";
        if (!empty($template) and !\Validate::cha_val($template, V_TEXT))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('template')) . '</li>' . "\n";

        /* check and download avatar */
        $tmp_key = rand(0, 9999999);
        $out = downloadAvatar($this->module, $tmp_key);
        if ($out != null)
            $errors .= $out;

        if (!empty($template) and ($template{0} == '.' or !is_dir(ROOT . '/template/' . $template))) {
            $errors = $errors . '<li>' . sprintf(__('Wrong chars in field "param"'), __('template')) . '</li>' . "\n";
        }
        
        if (!empty($email) && $email != $oldEmail && empty($errors)) {
            $Cache = new \Cache;
            $Cache->prefix = 'gravatar';
            $Cache->cacheDir = R.'sys/cache/users/gravatars/';
            if ($Cache->check('user_' . $id))
            $Cache->remove('user_' . $id);
        }
        
        // Errors
        if (!empty($errors)) {
            $error_msg = '<p class="errorMsg">' . __('Some error in form') . '</p>'
                . "\n" . '<ul class="errorMsg">' . "\n" . $errors . '</ul>' . "\n";
            return $this->showMessage($error_msg, $this->getModuleURL("edit_form_by_admin/$id/"));
        }

        // Если выставлен флажок "Удалить загруженный ранее файл"
        if (isset($_POST['unlink']) and is_file(ROOT . '/data/avatars/' . $id . '.jpg')) {
            unlink(ROOT . '/data/avatars/' . $id . '.jpg');
        }
        if (file_exists(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg')) {
            if (copy(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg', ROOT . '/data/avatars/' . $id . '.jpg')) {
                chmod(ROOT . '/data/avatars/' . $id . '.jpg', 0644);
            }
            unlink(ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg');
        }



        // Все поля заполнены правильно - записываем изменения в БД
        if (!empty($url) && mb_substr($url, 0, mb_strlen('http://')) !== 'http://' && mb_substr($url, 0, mb_strlen('https://')) !== 'https://')
            $url = 'http://' . $url;

        if ($changePassword) {
            $user->setPassw(md5crypt($newpassword));
        }
        if ($changeEmail) {
            $user->setEmail($email);
        }
        if (isset($_POST['activation'])) {
            $user->setActivation('');
        }
        $user->setFull_name($full_name);
        $user->setStatus($status);
        $user->setUrl($url);
        $user->setPol($pol);
        $user->setByear($byear);
        $user->setBmonth($bmonth);
        $user->setBday($bday);
        $user->setAbout($about);
        $user->setSignature($signature);
        $user->setTemplate($template);
        $user->setAdd_fields($_addFields);
        $user->save();


        if ($this->isLogging)
            \Logination::write('editing user by adm', 'user id(' . $id . ') adm id(' . $_SESSION['user']['id'] . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('info/' . $id),'ok');
    }

    // Функция возврашает информацию о пользователе
    public function info($id = null) {
        //turn access
        \ACL::turnUser(array($this->module, 'view_users'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
            
        $id = (int)$id;
        if ($id < 1)
            return $this->showMessage(__('Can not find user'), $this->getModuleURL());


        $user = $this->Model->getById($id);
        if (!$user || count($user) == 0)
            return $this->showMessage(__('Can not find user'), $this->getModuleURL());


        if (isset($_SESSION['user']['name'])) {
            $privateMessage = get_link(__('Send PM'), $this->getModuleURL('send_msg_form/' . $id));
        } else {
            $privateMessage = '';
        }



        $postsModel = \OrmManager::getModelInstance('ForumPosts');
        $posts = $postsModel->getFirst(array('id_author' => $id), array('order' => 'time DESC'));
        if ($posts) {
            $last_post = $posts->getTime();
        } else {
            $last_post = '';
        }

        $status_info = \ACL::get_group($user->getStatus());


        $markers = array();
        $markers['user_id'] = intval($user->getId());
        $markers['regdate'] = h($user->getPuttime());
        $markers['group'] = h($status_info['title']);
        $markers['group_id'] = h($user->getStatus());
        $markers['group_color'] = h($status_info['color']);
        $markers['rank'] = h($user->getState());
        $markers['signature'] = \PrintText::getSignature($user->getSignature(), $user->getStatus());
        $markers['lastvisit'] = h($user->getLast_visit());
        $markers['lastpost'] = h($last_post);
        $markers['totalposts'] = h($user->getPosts());
        
        $get_difference_time = (time() - strtotime($user->getPuttime())) / 86400;
        if ($get_difference_time < 0) $get_difference_time = 0;
        $markers['reg_days'] = round($get_difference_time);

        if ($user->getLocked())
            if (date('Y-m-d H:i:s') > $user->getBan_expire())
            $markers['baned'] = '&infin;';
            else
            $markers['baned'] = $user->getBan_expire();
        else
            $markers['baned'] = '';

        if ($user->getPol() === 'f')
            $markers['pol'] = __('f');
        else if ($user->getPol() === 'm')
            $markers['pol'] = __('m');

        $markers['fpol'] = ($user->getPol() && ($user->getPol() === 'f' || $user->getPol() === '0')) ? ' checked="checked"' : '';
        $markers['mpol'] = ($user->getPol() && $user->getPol() !== 'f') ? ' checked="checked"' : '';
        if (!$user->getPol() || $user->getPol() === '') {
            $markers['fpol'] = '';
            $markers['mpol'] = '';
        }


        $markers['byear'] = (is_numeric($user->getByear())) ? (int)$user->getByear() : '';
        $markers['bmonth'] = (is_numeric($user->getBmonth())) ? (int)$user->getBmonth() : '';
        $markers['bday'] = (is_numeric($user->getBday())) ? (int)$user->getBday() : '';
        if ($user->getByear() && $user->getBmonth() && $user->getBday()) {
            $markers['age'] = getAge($user->getByear(), $user->getBmonth(), $user->getBday());
        } else {
            $markers['age'] = '';
        }


        $markers['privatemessage'] = $privateMessage;


        // Аватар
        $markers['avatar'] = getAvatar($user->getId());


        // Edit profile link {EDIT_PROFILE_LINK}
        $markers['edit_profile_link'] = '';
        if (\ACL::turnUser(array($this->module, 'edit_mine'))
            && (!empty($_SESSION['user']['id']) && $user->getId() === $_SESSION['user']['id'])) {
            $markers['edit_profile_link'] = get_link(__('Edit profile'), $this->getModuleURL('edit_form/'));
        } else if (\ACL::turnUser(array($this->module, 'edit_users'))) {
            $markers['edit_profile_link'] = get_link(__('Edit profile'), $this->getModuleURL('edit_form_by_admin/' . $user->getId()));
        }
        
        $this->page_title = h($user->getName());
        
        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('Profile');
        $this->_globalize($nav);
        
        $Model = $this->Model;
        $user->setStat(function() use($Model,$id) {return $Model->getFullUserStatistic($id);});

        foreach ($markers as $k => $v) {
            $setter = 'set' . ucfirst($k);
            $user->$setter($v);
        }
        $source = $this->render('showuserinfo.html', array('user' => $user));
        return $this->_view($source);
    }

    /**
     * Multi message Delete
     */
    public function delete_message_pack() {
        $this->delete_message();
    }

    // Функция удаляет личное сообщение; ID сообщения передается методом GET
    public function delete_message($id_msg = null) {
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'));
        $messagesModel = \OrmManager::getModelInstance('UsersMessages');

        $ids = array();
        if (!empty($_POST['ids']) && is_array($_POST['ids']) && count($_POST['ids']) > 0)
        {
            foreach ($_POST['ids'] as $id) {
                $id = (int)$id;
                if ($id < 1)
                    continue;
                $ids[] = $id;
            }
        }
        else
        {
            if (!is_numeric($id_msg))
                return $this->showMessage(__('Value must be numeric'), getReferer(),'error', true);

            $id_msg = (int)$id_msg;
            if ($id_msg < 1)
                return $this->showMessage(__('Some error occurred'), getReferer(),'error', true);

            $ids[] = $id_msg;
        }

        if (count($ids) < 1)
            return $this->showMessage(__('Some error occurred'), getReferer(),'error', true);

        $redirect = get_url($this->getModuleURL('in_msg_box/'));
        foreach ($ids as $id_msg) {
            // В этом запросе условие нужно для того, чтобы
            // пользователь не смог удалить чужое сообщение, просто указав
            // ID сообщения в адресной строке браузера
            $messages = $messagesModel->getCollection(array(
                'id' => $id_msg,
                "(`to_user` = '" . $_SESSION['user']['id'] . "' OR `from_user` = '" . $_SESSION['user']['id'] . "')"
            ));
            if (count($messages) == 0) {
                continue;
            }


            $message = $messages[0];
            $id_rmv = $message->getId_rmv();
            $redirect = getReferer();
            // id_rmv - это поле указывает на то, что это сообщение уже удалил
            // один из пользователей. Т.е. сначала id_rmv=0, после того, как
            // сообщение удалил один из пользователей, id_rmv=id_user. И только после
            // того, как сообщение удалит второй пользователь, мы можем удалить
            // запись в таблице БД
            if ($id_rmv == 0) {
                $message->setId_rmv($_SESSION['user']['id']);
                $message->save();
            } else {
                $message->delete();
            }
        }

        if ($this->isLogging)
            \Logination::write('delete pm message(s)', 'message(s) id(' . implode(', ', $ids) . ')');
        return $this->showMessage(__('Operation is successful'), $redirect,'ok',true);
    }

    // Функция возвращает html формы для авторизации на форуме
    public function login_form() {
        // For return to previos page(referer)
        $_SESSION['authorize_referer'] = getReferer();



        if (isset($_SESSION['loginForm']['errors'])) {
            $errors = $this->render('infomessage.html', array(
                'message' => $_SESSION['loginForm']['errors']
            ));
            unset($_SESSION['loginForm']['errors']);
        }



        $markers = array(
            'form_key' => '',
            'action' => get_url($this->getModuleURL('login/')),
            'new_password' => get_link(__('Forgot password?'), $this->getModuleURL('new_password_form/')),
            'error' => (!empty($errors)) ? $errors : '',
        );
        if (\Config::read('autorization_protected_key', '__secure__') === 1) {
            $_SESSION['form_key_mine'] = rand(1000, 9999);
            $form_key = rand(1000, 9999);
            $_SESSION['form_hash'] = md5($form_key . $_SESSION['form_key_mine']);
            $markers['form_key'] = '<input type="hidden" name="form_key" value="' . $form_key . '" />';
        }

        $this->page_title = __('Authorize');

        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('Authorize');
        $this->_globalize($nav);


        $source = $this->render('loginform.html', array(
            'context' => $markers,
        ));
        return $this->_view($source);
    }

    // Вход на форум - обработчик формы авторизации
    public function login() {
        // Если не переданы данные формы - значит функция была вызвана по ошибке
        if (!isset($_POST['username']) or !isset($_POST['password']))
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
        $errors = '';


        if (\Config::read('autorization_protected_key', '__secure__') === 1) {
            if (empty($_SESSION['form_key_mine'])
             || empty($_POST['form_key'])
             || md5(substr($_POST['form_key'], 0, 10) . $_SESSION['form_key_mine']) != $_SESSION['form_hash']) {
                $this->showMessage(__('Use authorize form'),$this->getModuleURL('login_form/'));
            }
        }


        // Защита от перебора пароля - при каждой неудачной попытке время задержки увеличивается
        if (isset($_SESSION['loginForm']['count']) && $_SESSION['loginForm']['count'] > time()) {
            $errors = '<li>' . sprintf(__('You must wait'), ($_SESSION['loginForm']['count'] - time())) . '</li>';
        }


        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $name = mb_substr($_POST['username'], 0, 30);
        $password = mb_substr($_POST['password'], 0, 64);
        // Обрезаем лишние пробелы
        $name = trim($name);
        $password = trim($password);


        // Проверяем, заполнены ли обязательные поля
        
        if (empty($name))
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __('login')) . '</li>' . "\n";
        if (empty($password))
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __('password')) . '</li>' . "\n";


        // Проверяем поля формы на недопустимые символы
        if (!empty($name) && !\Validate::cha_val($name, V_LOGIN))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('login')) . '</li>' . "\n";
        //if (!empty($password) && !\Validate::cha_val($password, V_LOGIN))
        //    $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('password')) . '</li>' . "\n";


        // Проверять существование такого пользователя есть смысл только в том
        // случае, если поля не пустые и не содержат недопустимых символов
        if (empty($errors)) {
            $user = $this->Model->getByNamePass($name, $password);
            if (empty($user))
                $errors .= '<li>' . __('Wrong login or pass') . '</li>' . "\n";
            else
                getDB()->save($this->module, array(
                'id' => $user->getId(),
                'last_visit' => new \Expr('NOW()')
                ));
        }


        // Если были допущены ошибки при заполнении формы
        if (!empty($errors)) {
            if (!isset($_SESSION['loginForm']['count']))
                $_SESSION['loginForm']['count'] = 1;
            else if ($_SESSION['loginForm']['count'] < 10)
                $_SESSION['loginForm']['count']++;
            else if ($_SESSION['loginForm']['count'] < time())
                $_SESSION['loginForm']['count'] = time() + 10;
            else
                $_SESSION['loginForm']['count'] = $_SESSION['loginForm']['count'] + 10;

            $_SESSION['loginForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
                    "\n" . '<ul class="errorMsg">' . "\n" . $errors . '</ul>' . "\n";
            return $this->showMessage($_SESSION['loginForm']['errors'], $this->getModuleURL('login_form/'));
        }

        // Все поля заполнены правильно и такой пользователь существует - продолжаем...
        unset($_SESSION['loginForm']);


        if ($user->getActivation())
            return $this->showMessage(__('Your account not activated'),getReferer(),'error', true);

        // Если пользователь заблокирован
        if ($user->getLocked())
            return $this->showMessage(__('Banned'), $this->getModuleURL('baned/'));
        $_SESSION['user'] = $user->asArray();

        // TODO: Функция getNewThemes() помещает в массив $_SESSION['newThemes'] ID тем,
        // в которых были новые сообщения со времени последнего посещения пользователя
        \UserAuth::getNewThemes();

        // Выставляем cookie, если пользователь хочет входить автоматически
        if (isset($_POST['autologin'])) {
            $path = '/';
            setcookie('autologin', 'yes', time() + 3600 * 24 * \Config::read('cookie_time'), $path);
            setcookie('userid', $_SESSION['user']['id'], time() + 3600 * 24 * \Config::read('cookie_time'), $path);
            setcookie('password', $_SESSION['user']['passw'], time() + 3600 * 24 * \Config::read('cookie_time'), $path);
        }


        // Authorization complete. Redirect
        if (isset($_SESSION['authorize_referer'])) {
            $referer = $_SESSION['authorize_referer'];
            unset($_SESSION['authorize_referer']);
            return $this->showMessage(__('Operation is successful'),$referer,'ok',true);
        }
        
        return $this->showMessage(__('Operation is successful'), getReferer(),'ok',true);
    }

    // Выход из системы
    public function logout() {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        
        if (isset($_SESSION['user']))
            unset($_SESSION['user']);
        if (isset($_SESSION))
            unset($_SESSION);
        
        $path = '/';
        if (isset($_COOKIE['autologin']))
            setcookie('autologin', '', time() - 1, $path);
        if (isset($_COOKIE['userid']))
            setcookie('userid', '', time() - 1, $path);
        if (isset($_COOKIE['password']))
            setcookie('password', '', time() - 1, $path);
        redirect('/');
    }

    /**
     * @param int $id - user id
     *
     * baned user
     */
    public function onban($id) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        
        //turn access
        \ACL::turnUser(array($this->module, 'ban_users'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
            
        $id = (int)$id;
        if ($id < 1) {
            return $this->showMessage(__('Some error occurred'), getReferer(),'error', true);
        }
        $user = $this->Model->getById($id);
        if (!empty($user)) {
            $user->setLocked(1);
            $user->save();
            return $this->showMessage(__('Operation is successful'), getReferer(),'ok',true);
        }
    }

    /**
     * @param int $id - user id
     *
     * baned user
     */
    public function offban($id) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        //turn access
        \ACL::turnUser(array($this->module, 'ban_users'),true);
        if (!is_numeric($id))
            return $this->showMessage(__('Value must be numeric'));
            
        $id = (int)$id;
        if ($id < 1) {
            return $this->showMessage(__('Some error occurred'), getReferer(),'error', true);
        }
        $user = $this->Model->getById($id);
        if (!empty($user)) {
            $user->setLocked(0);
            $user->save();
            return $this->showMessage(__('Operation is successful'), getReferer(),'ok',true);
        }
    }

    /**
     * Change users rating
     * This action take request from AJAX(recomented).
     *
     * @param int $to_id
     */
    public function rating($to_id = null) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $points = $_GET['points'];
        
        // Drs counter OFF
        $this->counter = false;
        $this->cached = false;


        // Check rules
        if (!isset($_SESSION['user']['name']))
            die(__('Permission denied'));
        if (!\ACL::turnUser(array($this->module, 'set_rating')))
            die(__('Permission denied'));
        $from_id = intval($_SESSION['user']['id']);
        if (!is_numeric($to_id))
            return $this->showMessage(__('Value must be numeric'));
            
        $to_id = (int)$to_id;
        if ($to_id < 1)
            die(__('Can not find user'));
        if ($from_id == $to_id)
            die(__('No voting for yourself'));

        $points = intval($points);
        if ($points > 1)
            $points = 1;
        if ($points < -1)
            $points = -1;


        // Check user exists
        $user = $this->Model->getById($to_id);
        if (empty($user))
            die(__('Can not find user'));


        // Comment
        $comment = '';
        if (isset($_POST['comment'])) {
            $comment = trim($_POST['comment']);
            if (mb_strlen($comment) > \Config::read('rating_comment_lenght', $this->module))
                die(sprintf(__('Very big "param"'),__('Comment'), \Config::read('rating_comment_lenght', $this->module)));
            $comment = \PrintText::getSignature($comment, $_SESSION['user']['status']);
        }



        $votesModel = \OrmManager::getModelInstance('UsersVotes');
        $last_vote = $votesModel->getFirst(array(
            'to_user' => $to_id,
            'from_user' => $from_id,
        ), array(
            'order' => 'date DESC',
        ));



        if (empty($last_vote) || (time() - strtotime($last_vote->getDate()) > 604800)) {
            $user->setRating($user->getRating() + $points);
            $user->save();

            $voteEntity = \OrmManager::getEntityName('UsersVotes');
            $voteEntity = new $voteEntity(array(
                        'from_user' => $from_id,
                        'to_user' => $to_id,
                        'comment' => $comment,
                        'points' => $points,
                        'date' => new \Expr('NOW()'),
                    ));
            $voteEntity->save();
            die('ok');
        } else {
            die (__('You already voted'));
        }
        die(__('Some error occurred'));
    }

    /**
     * View rating story
     *
     * @param int $user_id
     */
    public function votes_story($user_id) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        // Without wrapper we can use this for ajax requests
        $this->wrap = (!isset($_GET['wrapper'])) ? false : true;

        if (!is_numeric($user_id))
            return $this->showMessage(__('Value must be numeric'));
            
        $user_id = (int)$user_id;
        if ($user_id < 1)
            return $this->showMessage(__('Can not find user'), getReferer(),'error', true);


        // Check user exists
        $to_user = $this->Model->getById($user_id);
        if (empty($to_user))
            return $this->showMessage(__('Can not find user'), getReferer(),'error', true);


        $votesModel = \OrmManager::getModelInstance('UsersVotes');
        $votesModel->bindModel('touser');
        $votesModel->bindModel('fromuser');
        $messages = $votesModel->getCollection(array('to_user' => $user_id), array('order' => '`date` DESC'));
        if (!is_array($messages) || count($messages) < 1) {
            return $this->_view(__('No votes for user'));
        }




        foreach ($messages as $message) {
            // Admin buttons
            $message->setModer_panel('');
            if (\ACL::turnUser(array($this->module, 'delete_rating_comments'))) {
                $message->setModer_panel(get_link('', '#', array('onclick' => "deleteUserVote('" . $message->getId() . "'); return false;", 'class' => 'drs-delete')));
            }
        }



        $source = $this->render('rating_tb.html', array(
            'to_user' => $to_user,
            'messages' => $messages,
        ));
        return $this->_view($source);
    }

    /**
     * Delete users votes
     *
     * @param int - vote ID
     */
    public function delete_vote($voteID) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        if (!is_numeric($voteID))
            die('fail');;
            
        $voteID = (int)$voteID;
        if ($voteID < 1)
            die('fail');


        if (\ACL::turnUser(array($this->module, 'delete_rating_comments'))) {
            $votesModel = \OrmManager::getModelInstance('UsersVotes');
            $vote = $votesModel->getById($voteID);


            if (!empty($vote)) {
                $user = $this->Model->getById($vote->getTo_user());
                $points = $vote->getPoints();
                $vote->delete();
                if ($user) {
                    $user->setRating($user->getRating() - intval($points));
                    $user->save();
                }
                die('ok');
            }
        }
        die('fail');
    }

    /**
     * page for baned users
     */
    public function baned() {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $source = $this->render('baned.html', array());
        $this->_view($source);
    }

    /**
     * Creane warnings for bad users
     */
    public function add_warning($uid = null) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        if (!\ACL::turnUser(array($this->module, 'users_warnings')))
            die(__('Permission denied'));
        $this->counter = false;
        $this->cached = false;

            
        if (empty($uid) && !empty($_POST['uid']))
            $uid = intval($_POST['uid']);
        if (empty($uid))
            die(__('Some error occurred'));

        if (!is_numeric($uid))
            die(__('Value must be numeric'));
        $uid = (int)$uid;

        $intruder = $this->Model->getById($uid);
        if (empty($intruder))
            die(__('Can not find user'));


        // Action and cause
        $points = (!empty($_POST['points'])) ? intval($_POST['points']) : 1;
        if (intval($points) != 1 && intval($points) != -1)
            $points = 1;
        $cause = (!empty($_POST['cause'])) ? trim($_POST['cause']) : '';

        // Interval
        if (!empty($_POST['permanently']))
            $timestamp = time() + 99999999;
        else if (!empty($_POST['mult']) && !empty($_POST['cnt'])) {
            switch (trim($_POST['mult'])) {
                case 'h':
                    $timestamp = intval($_POST['cnt']) * 3600;
                    break;
                case 'd':
                    $timestamp = intval($_POST['cnt']) * 86400;
                    break;
                case 'w':
                    $timestamp = intval($_POST['cnt']) * 604800;
                    break;
                case 'm':
                    $timestamp = intval($_POST['cnt']) * 2419200;
                    break;
                default:
                    $timestamp = intval($_POST['cnt']) * 29030400;
                    break;
            }
        }


        if (!empty($timestamp)) {
            $interval = date("Y-m-d H:i:s", time() + $timestamp);
            $ban = 1;
        } else {
            $interval = '0000-00-00 00:00:00';
            $ban = 0;
        }


        $adm_id = (!empty($_SESSION['user']['id'])) ? intval($_SESSION['user']['id']) : 0;
        if ($adm_id < 1)
            die(__('Permission denied'));
        if ($adm_id == $uid)
            die(__('Some error occurred'));

        if (!$ban) {
            $max_warnings = \Config::read('warnings_by_ban', $this->module);
            if ($intruder->getWarnings() > 0 && $intruder->getWarnings() + $points >= $max_warnings) {
                $ban = 1;
                $interval = \Config::read('autoban_interval', $this->module);
                $interval = time() + intval($interval);
                $interval = date("Y-m-d H:i:s", $interval);

                $clean_warnings = true;
            }
        }


        $intruder->setBan_expire($interval);
        $intruder->setLocked($ban);



        if (!empty($clean_warnings)) {
            $intruder->setWarnings(0);
            $warningsModel = \OrmManager::getModelInstance('UsersWarnings');
            $warningsModel->deleteUserWarnings($uid);
        } else {
            $intruder->setWarnings($intruder->getWarnings() + $points);
            $warningsEntityName = \OrmManager::getEntityName('UsersWarnings');
            $warningsEntity = new $warningsEntityName(array(
                        'user_id' => $uid,
                        'admin_id' => $adm_id,
                        'points' => $points,
                        'date' => new \Expr('NOW()'),
                        'cause' => $cause,
                    ));
            $warningsEntity->save();
        }
        $intruder->save();



        if (!empty($_POST['noticepm'])) {
            $messEntityName = \OrmManager::getEntityName('UsersMessages');
            $messEntity = new $messEntityName(array(
                        'to_user' => $uid,
                        'from_user' => $adm_id,
                        'subject' => __('You have new warnings'),
                        'message' => __('Warnings cause') . $cause,
                        'sendtime' => new \Expr('NOW()'),
                        'id_rmv' => $adm_id,
                    ));
            $messEntity->save();
        }

        die('ok');
    }

    /**
     * View warnings story
     *
     * @param int $uid
     */
    public function warnings_story($uid) {
    
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        // Without wrapper we can use this for ajax requests
        $this->wrap = (!isset($_GET['wrapper'])) ? false : true;

        if (!is_numeric($uid))
            die(__('Value must be numeric'));
            
        $uid = (int)$uid;
        if ($uid < 1) {
            if ($this->wrap)
                return $this->showMessage('', '/');
            else
                die(__('Some error occurred'));
        }


        // Check user exists
        $to_user = $this->Model->getById($uid);
        if (empty($to_user)) {
            if ($this->wrap)
                return $this->showMessage('', '/');
            else
                die(__('Can not find user'));
        }


        $warModel = \OrmManager::getModelInstance('UsersWarnings');
        $warModel->bindModel('Users');
        $warnings = $warModel->getCollection(array(
            'user_id' => $uid
                ), array(
            'order' => 'date DESC'
                ));
        if (empty($warnings)) {
            return $this->_view(__('No warnings for user'));
        }



        $max_warnings_by_ban = \Config::read('warnings_by_ban', $this->module);
        $user_procent_warnings = (100 / $max_warnings_by_ban) * $to_user->getWarnings();
        foreach ($warnings as $warning) {
            // Admin buttons
            $warning->setModer_panel('');
            if (\ACL::turnUser(array($this->module, 'delete_warnings'))) {
                $warning->setModer_Panel(get_link('', '#', array('onclick' => "deleteUserWarning('" . $warning->getId() . "'); return false;", 'class' => 'drs-delete')));
            }
        }

        
        
        $source = $this->render('warning_tb.html', array(
            'to_user' => $to_user,
            'warnings' => $warnings,
                ));
        return $this->_view($source);
    }

    /**
     * Delete users warnings
     *
     * @param int - warning ID
     */
    public function delete_warning($wID) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        if (!is_numeric($wID))
            die('fail');
            
        $wID = (int)$wID;
        if ($wID < 1)
            die('fail');


        if (\ACL::turnUser(array($this->module, 'delete_warnings'))) {
            $warModel = \OrmManager::getModelInstance('UsersWarnings');
            $warning = $warModel->getById($wID);


            if (!empty($warning)) {
                $user_warnings = $this->Model->getById($warning->getUser_id());
                $warning->delete($wID);

                $ban = 1;
                if (!empty($user_warnings)) {
                    if ($user_warnings->getWarnings() < \Config::read('warnings_by_ban', $this->module)) {
                        $ban = 0;
                    }
                    $user_warnings->setLocked($ban);
                    $user_warnings->setWarnings($user_warnings->getWarnings() - $warning->getPoints());
                    $user_warnings->save();
                }
                die('ok');
            }
        }
        die('fail');
    }

    /**
     * Check users PM (AJAX)
     */
    public function get_count_new_pm() {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        
        // Check rules
        if (!isset($_SESSION['user']['id']))
            die();

        $Cache = new \Cache;
        $Cache->prefix = 'messages';
        $Cache->cacheDir = 'sys/cache/users/new_pm/';
        if ($Cache->check('user_' . $_SESSION['user']['id']))
            $res = $Cache->read('user_' . $_SESSION['user']['id']);
        else {
            $usersModel = \OrmManager::getModelInstance('Users');
            $res = $usersModel->getNewPmMessages($_SESSION['user']['id']);
            $Cache->write($res, 'user_' . $_SESSION['user']['id'],array());
        }
        
        if (empty($res)) $res = 0;
        
        header('CountNewPMs: '.(string)$res);
        
        if ($res > 0)
            return $this->showMessage(sprintf(__('New messages count'),$res), $this->getModuleURL('pm/'),'alert');
        
        die('0');
    }

    public function update_group($user_id) {
        
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        
        $group = $_GET['group'];
        
        // Drs counter OFF
        $this->counter = false;
        $this->cached = false;

        // Check rules
        if (!isset($_SESSION['user']['name']))
            die(__('Permission denied'));
        if (!\ACL::turnUser(array($this->module, 'edit_users')))
            die(__('Permission denied'));

        if (!is_numeric($user_id))
            die(__('Value must be numeric'));
            
        $user_id = (int)$user_id;
        if ($user_id < 1)
            die(__('Can not find user'));
        if (intval($_SESSION['user']['id']) == $user_id)
            die(__('No changing own group'));

        if ($group === null && !empty($_POST['group']))
            $group = $_POST['points'];
        $group = intval($group);

        $groups = \ACL::getGroups();
        if ($group < 1 || !isset($groups[$group]))
            die(__('Can not find user'));

        // Check user exists
        $user = $this->Model->getById($user_id);
        if (empty($user))
            die(__('Can not find user'));

        $user->setStatus($group);
        $user->save();
        die('ok');
    }

    public function search_niks($name = Null) {
        header('X-Robots-Tag: noindex,nofollow');
        
        $this->counter = false;
        $this->cached = false;
        
        if ($name === Null)
        return;

        $where = array("`name` LIKE '%" . getDB()->escape($name) . "%'");
        if (isset($_SESSION['user']['name'])) {
        $user_name = getDB()->escape($_SESSION['user']['name']);
        $where[] = "`name` NOT LIKE '" . $user_name . "' ";
        }

        $users = getDB()->select('users', DB_ALL, array(
        'cond' => $where,
        'limit' => 10));

        if ($users)
        foreach ($users as $user)
            print '<option value="' . $user['name'] . '">';
    }

    /**
     * Show comments by user.
     */
    public function comments($id = null) {
        return include_once(ROOT . '/sys/inc/get_comments_user.php');
    }

    // Папка личных сообщений (список собеседников)
    public function pm() {
        
        $this->page_title = __('PM nav');
        
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'), '/');


        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('PM nav');
        $this->_globalize($nav);


        $markers = array('error' => '');
        $messages = $this->Model->getMessages();
        if (!$messages || (is_array($messages) && count($messages) == 0)) {
            $markers['messages'] = array();
            $markers['errors'] = __('This dir is empty');
            $source = $this->render('pm.html', array('messages' => array(), 'context' => $markers));
            return $this->_view($source);
        }

        $markers['count'] = count($messages);
        foreach ($messages as $message) {
            // Если сообщение еще не прочитано
            $icon = ($message->getViewed() == 0) ? 'folder_new' : 'folder';
            $message->setIcon(get_img('/template/' . getTemplate() . '/img/' . $icon . '.gif'));
            

            if ($message->getFrom_user() != $_SESSION['user']['id']) {
                $message->setUser($message->getFromuser());
            } else {
                $message->setUser($message->getTouser());
            }
            // Обрезанный текст последнего сообщения
            $message->setText(\PrintText::getAnnounce($message->getMessage(), '', 170));
            // Полный текст последнего сообщения
            $message->setMessage(\PrintText::print_page($message->getMessage()));
            
            $message->setDelete(get_link(__('Delete'), $this->getModuleURL('delete_messages_user/' . $message->getUser()->getId()), array('onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu(this)}; return false")));
        }

        $source = $this->render('pm.html', array('messages' => $messages, 'context' => $markers));
        return $this->_view($source);
    }

    // Функция возвращает личное сообщение для просмотра пользователем
    public function pm_view($id_user = null) {
    
        $this->page_title = __('Message');
        
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'), '/');
        if (!is_numeric($id_user))
            return $this->showMessage(__('Value must be numeric'));
        $id_user = (int)$id_user;
        if ($id_user < 1)
            return $this->showMessage(__('Some error occurred'), $this->getModuleURL('pm/'));
        $interlocutor = $this->Model->getById($id_user);
        if (!$interlocutor || count($interlocutor) == 0)
            return $this->showMessage(__('Can not find user'), $this->getModuleURL('pm/'));

        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('Message');
        $this->_globalize($nav);

        $messages = $this->Model->getUserMessages($id_user);

        $markers = array();
        $markers['interlocutor'] = $interlocutor->getName();
        if (!$messages || (is_array($messages) && count($messages) == 0)) {
            $messages = array();
        } else {
            foreach ($messages as $message) {
                $to_user_id = $message->getTo_user();
                if ($to_user_id == $_SESSION['user']['id']) {
                    $inBox = true;
                    $text = \PrintText::print_page($message->getMessage(), $interlocutor->getStatus());
                } else {
                    $inBox = false;
                    $text = \PrintText::print_page($message->getMessage());
                }

                // Помечаем сообщение, как прочитанное
                if ($inBox and $message->getViewed() != 1) {
                    $message->setViewed(1);
                    $Cache = new \Cache;
                    $Cache->prefix = 'messages';
                    $Cache->cacheDir = 'sys/cache/users/new_pm/';
                    if ($Cache->check('user_' . $to_user_id)) {
                    $newpm = $Cache->read('user_' . $to_user_id);
                    $Cache->remove('user_' . $to_user_id);
                    $Cache->write($newpm - 1, 'user_' . $to_user_id,array());
                    }
                    $message->save();
                }
                $message->setText($text);

                $message->setUser($interlocutor);

                $message->setDelete(get_link(__('Delete'), $this->getModuleURL('delete_message/' . $message->getId()), array('onClick' => "if (confirm('" . __('Are you sure?') . "')) {sendu(this)}; return false")));
            }
        }

        $source = $this->render('pm_view.html', array(
            'context' => $markers,
            'messages' => $messages,
        ));

        return $this->_view($source);
    }


    // Функция возвращает html формы для отправки личного сообщения
    public function send_pm_form($id = null) {
        // Незарегистрированный пользователь не может отправлять личные сообщения
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'), '/');
        $writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

        $toUser = '';
        if (isset($id)) {
            if (!is_numeric($id))
                return $this->showMessage(__('Value must be numeric'));
                
            $id = (int)$id;
            if ($id > 0) {
                $res = $this->Model->getById($id);
                if ($res) {
                    $toUser = $res->getName();
                }
            }
        }


        $message = ''; // TODO


        if (isset($_SESSION['viewMessage']) && !empty($_SESSION['viewMessage']['message'])) {
            $prevMessage = \PrintText::print_page($_SESSION['viewMessage']['message'], $writer_status);
            $prevSource = $this->render('previewmessage.html', array('message' => $prevMessage));
            $toUser = h($_SESSION['viewMessage']['toUser']);
            $message = h($_SESSION['viewMessage']['message']);
            unset($_SESSION['viewMessage']);
        }

        $action = get_url($this->getModuleURL('send_pm'));
        $errors = '';
        // Если при заполнении формы были допущены ошибки
        if (isset($_SESSION['sendMessageForm'])) {
            $errors = $this->render('infomessage.html', array('message' => $_SESSION['sendMessageForm']['errors']));
            $toUser = h($_SESSION['sendMessageForm']['toUser']);
            $message = h($_SESSION['sendMessageForm']['message']);
            unset($_SESSION['sendMessageForm']);
        }


        $markers = array();
        $markers['errors'] = $errors;
        $markers['action'] = $action;
        $markers['touser'] = $toUser;
        $markers['touser_id'] = $id;
        $markers['main_text'] = $message;
        $markers['preview'] = (!empty($prevSource)) ? $prevSource : '';

        $this->page_title = __('PM nav');
        
        // Navigation Panel
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
                . get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('PM nav');
        $this->_globalize($nav);

        $source = $this->render('sendpmform.html', array('context' => $markers));
        
        return $this->_view($source);

    }



    public function send_pm() {
        // Незарегистрированный пользователь не может отправлять личные сообщения
        if (!isset($_SESSION['user']['name'])) {
            return $this->showMessage(__('Some error occurred'));
        }
        // Если не переданы данные формы - функция вызвана по ошибке
        if (!isset($_POST['toUser']) or
                !isset($_POST['mainText'])) {
            return $this->showMessage(__('Some error occurred'),getReferer(),'error', true);
        }

        $msgLen = mb_strlen($_POST['mainText']);

        // Обрезаем переменные до длины, указанной в параметре maxlength тега input
        $toUser = mb_substr($_POST['toUser'], 0, 30);
        $message = mb_substr($_POST['mainText'], 0, \Config::read('max_message_lenght', $this->module));
        // Обрезаем лишние пробелы
        $toUser = trim($toUser);
        $message = trim($message);

        // Проверяем, заполнены ли обязательные поля
        $errors = '';
        
        if (empty($toUser))
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __('Receiver')) . '</li>' . "\n";
        if (empty($message))
            $errors .= '<li>' . sprintf(__('Empty field "param"'), __('Message')) . '</li>' . "\n";
        if ($msgLen > \Config::read('max_message_lenght', $this->module))
            $errors .= '<li>' . sprintf(__('Very big "param"'), __('Message'), \Config::read('max_message_lenght', $this->module)) . '</li>' . "\n";


        // Проверяем поля формы на недопустимые символы
        if (!empty($toUser) && !\Validate::cha_val($toUser, V_LOGIN))
            $errors .= '<li>' . sprintf(__('Wrong chars in field "param"'), __('Receiver')) . '</li>' . "\n";


        // Проверяем, есть ли такой пользователь
        if (!empty($toUser)) {
            $to = preg_replace("#[^- _0-9a-zА-Яа-я]#iu", '', $toUser);
            $user = $this->Model->getFirst(
                    array(
                        'name' => $toUser
                    )
            );


            if (!$user)
                $errors .= '<li>' . sprintf(__('No user with this name'), $to) . '</li>' . "\n";
            elseif ($user->getId() == $_SESSION['user']['id'])
                $errors .= '<li>' . __('You can not send message to yourself') . '</li>' . "\n";


            //chek max count messages
            if ($user && $user->getId()) {
                $id_to = intval($user->getId());
                $id_from = intval($_SESSION['user']['id']);


                $model = \OrmManager::getModelInstance('UsersMessages');
                $cnt_to = $model->getTotal(array(
                    'cond' => array(
                        "(`to_user` = '{$id_to}' OR `from_user` = '{$id_to}') AND `id_rmv` != '{$id_to}'"
                    )
                ));
                $cnt_from = $model->getTotal(array(
                    'cond' => array(
                        "(`to_user` = '{$id_from}' OR `from_user` = '{$id_from}') AND `id_rmv` != '{$id_from}'"
                    )
                ));


                if (!empty($cnt_to) && $cnt_to >= \Config::read('max_count_mess', $this->module)) {
                    $errors .= '<li>' . __('This user has full messagebox') . '</li>' . "\n";
                }
                if (!empty($cnt_from) && $cnt_from >= \Config::read('max_count_mess', $this->module)) {
                    $errors .= '<li>' . __('You have full messagebox') . '</li>' . "\n";
                }
            }
        }



        // Errors
        if (!empty($errors)) {
            $_SESSION['sendMessageForm'] = array();
            $_SESSION['sendMessageForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
                    "\n" . '<ul class="errorMsg">' . "\n" . $errors . '</ul>' . "\n";
            $_SESSION['sendMessageForm']['toUser'] = $toUser;
            $_SESSION['sendMessageForm']['message'] = $message;
            return $this->showMessage($_SESSION['sendMessageForm']['errors'], getReferer(),'error', true);
        }

        // Все поля заполнены правильно - "посылаем" сообщение
        $to = $user->getId();
        $from = $_SESSION['user']['id'];
        
        $Cache = new \Cache;
        $Cache->prefix = 'messages';
        $Cache->cacheDir = 'sys/cache/users/new_pm/';
        if ($Cache->check('user_' . $to)) {
            $newpm = $Cache->read('user_' . $to);
            $Cache->remove('user_' . $to);
            $Cache->write($newpm + 1, 'user_' . $to,array());
        }

        $data = array(
            'to_user' => $to,
            'from_user' => $from,
            'sendtime' => new \Expr('NOW()'),
            'message' => $message,
            'id_rmv' => 0,
            'viewed' => 0,
        );
        $msg = new \UsersModule\ORM\UsersMessagesEntity($data);
        if ($msg) {
            $id_msg = $msg->save();
            if (\Config::read('new_pm_mail', $this->module) == 1) {
                // формируем заголовки письма
                $link = (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $this->getModuleURL('get_message/' . $id_msg);

                $mail = array(
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'link' => $link,
                    'from_name' => $_SESSION['user']['name'],
                    'from_email' => $_SESSION['user']['email'],
                );

                $mailer = new \DrsMail();

                $mailer->setTo($user->getEmail());
                $mailer->setSubject(__('New message notification'));
                $mailer->setContentHtml(__('mail_content_html_newpm'));
                $mailer->setContentText(__('mail_content_text_newpm'));
                $mailer->sendMail($mail);
            }
        }

        /* clean DB cache */
        if ($this->isLogging)
            \Logination::write('adding pm message', 'message id(' . $id_msg . ')');
        return $this->showMessage(__('Message successfully send'), $this->getModuleURL('pm_view/' . $to),'ok');
    }

    // Функция удаляет личные сообщения собеседника
    public function delete_messages_user($id_user = null) {
        if (!isset($_SESSION['user']['name']))
            return $this->showMessage(__('Some error occurred'),'/');
        $messagesModel = \OrmManager::getModelInstance('UsersMessages');

        $ids = array();
        if (!empty($_POST['ids']) && is_array($_POST['ids']) && count($_POST['ids']) > 0)
        {
            foreach ($_POST['ids'] as $id) {
                $id = (int)$id;
                if ($id < 1)
                    continue;
                $ids[] = $id;
            }
        }
        else
        {
            if (!is_numeric($id_user))
                return $this->showMessage(__('Value must be numeric'), $this->getModuleURL('pm/'));

            $id_user = (int)$id_user;
            if ($id_user < 1)
                return $this->showMessage(__('Some error occurred'), $this->getModuleURL('pm/'));

            $ids[] = $id_user;
        }

        if (count($ids) < 1)
            return $this->showMessage(__('Some error occurred'), $this->getModuleURL('pm/'));

        $new_pms_count = 0;
        foreach ($ids as $id_user) {
            $messages = $this->Model->getUserMessages($id_user);
            
            if (!$messages || (is_array($messages) && count($messages) == 0)) {
                return $this->showMessage(__('Some error occurred'),$this->getModuleURL('pm/'));
            }
            
            foreach ($messages as $message) {
                // В этом запросе дополнительное условие нужно для того, чтобы
                // пользователь не смог удалить чужое сообщение, просто указав
                // ID сообщения в адресной строке браузера
                $messages = $messagesModel->getCollection(array(
                    'id' => $message->getId(),
                    "(`to_user` = '" . $_SESSION['user']['id'] . "' OR `from_user` = '" . $_SESSION['user']['id'] . "')"
                        ));
                if (count($messages) == 0) {
                    continue;
                }


                $message = $messages[0];
                $toUser = $message->getTo_user();
                $id_rmv = $message->getId_rmv();
                
                // Считаем количество непрочитанных сообщений, которые будут удалены
                if ($message->getViewed() == 0)
                    $new_pms_count++;
                
                // id_rmv - это поле указывает на то, что это сообщение уже удалил
                // один из пользователей. Т.е. сначала id_rmv=0, после того, как
                // сообщение удалил один из пользователей, id_rmv=id_user. И только после
                // того, как сообщение удалит второй пользователь, мы можем удалить
                // запись в таблице БД
                if ($id_rmv == 0) {
                    $message->setId_rmv($_SESSION['user']['id']);
                    $message->save();
                } else {
                    $message->delete();
                }
            }
        }
        
        // Т.к. количество новых сообщений сохраняется в кеш, то следует обновить его.
        $Cache = new \Cache;
        $Cache->prefix = 'messages';
        $Cache->cacheDir = 'sys/cache/users/new_pm/';
        if ($Cache->check('user_' . $_SESSION['user']['id']) && $new_pms_count) {
            $newpm = $Cache->read('user_' . $_SESSION['user']['id']);
            $Cache->remove('user_' . $_SESSION['user']['id']);
            $Cache->write($newpm - $new_pms_count, 'user_' . $_SESSION['user']['id'],array());
        }

        /* clean DB cache */
        if ($this->isLogging)
            \Logination::write('delete pm message(s)', 'message(s) id(' . implode(', ', $ids) . ')');
        return $this->showMessage(__('Operation is successful'), $this->getModuleURL('pm/'),'ok');
    }

    public function avatar_url($id = null) {
        print getAvatar(intval($id));
    }
}
