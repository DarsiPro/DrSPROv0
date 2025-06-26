<?php
/**
* @project    DarsiPro CMS
* @package    Chat \Module
* @url        https://darsi.pro
*/


namespace ChatModule;

class ActionsHandler extends \Module {

    /**
     * @template  layout for module
     */
    public $template = 'chat';
    public static $static_template = 'chat';

    /**
     * @module_title  title of module
     */
    public $module_title = 'Чат';

    /**
     * @module module indentifier
     */
    public $module = 'chat';
    public static $static_module = 'chat';

    /**
     * default action ( show add form and iframe for messages )
     *
     * @return view content
     */
    public function index() {

        $content = '';
        if (\ACL::turnUser(array($this->module, 'add_materials'))) {
            $content = $this->form();
            $content .= $this->add_form();
        } else {
            $content = __('Permission denied');
        }

        // Navigation
        $nav = array();
        $nav['navigation'] = get_link(__('Home'), '/') . __('Separator') . __('chat');
        $this->_globalize($nav);

        return $this->_view($content);
    }

    public function ajax_messages($last = false) {
        $chatDataPath = ROOT . $this->getTmpPath('messages.dat');

        $messages = array();
        if (file_exists($chatDataPath)) {
            $data = unserialize(file_get_contents($chatDataPath));
            if (!empty($data)) {
                
                
                foreach ($data as $key => &$record) {
                    $record['message'] = \PrintText::print_page($record['message'], $record['user_group']);

                    // показывать ip и кнопку удаления для модераторов
                    if (\ACL::turnUser(array($this->module, 'delete_materials'))) {
                        $record['ip'] = '<a target="_blank" href="https://apps.db.ripe.net/search/query.html?searchtext=' . $record['ip'] . '" class="drs-ip" title="IP: ' . $record['ip'] . '"></a>';
                        $record['del'] = get_url($this->getModuleURL('del/'.$key));
                    } else {
                        $record['ip'] = '';
                        $record['del'] = '';
                    }
                    $record['date'] = date("Y-m-d", $record['unixtime']);
                    $record['time'] = date("h:i", $record['unixtime']);
					$record['avatar'] = getAvatar($record['user_id']);
                    $group_info = \ACL::getGroup($record['user_group']);
                    $record['group_info'] = $group_info;

                    // если передали unixtime последнего загруженного сообщения, отправлять только новые сообщения
                    if (!( ($last != false) and ($last >= $record['unixtime']) )) {
                        $messages[] = $data[$key];
                    }
                }
            }
        }

        echo json_encode($messages);
    }

    /**
     * add message form
     *
     * @return  none
     */
    public function add() {

        if (!\ACL::turnUser(array($this->module, 'add_materials'))) {
            return;
        }
        if (!isset($_POST['message'])) {
            die(sprintf(__('Empty field "param"'), __('Message')));
        }

        /* cut and trim values */
        $user_id = (!empty($_SESSION['user']['id'])) ? $_SESSION['user']['id'] : '0';
        $user_group = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : '0';
        $name = (!empty($_SESSION['user']['name'])) ? trim($_SESSION['user']['name']) : __('Guest');
        $message = trim(mb_substr($_POST['message'], 0, \Config::read('max_lenght', $this->module)));
        $ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
        $keystring = (isset($_POST['captcha_keystring'])) ? trim($_POST['captcha_keystring']) : '';
		
		// Очищаем от опасных HTML тегов
        $message = \PrintText::getPurifedHtml($message);

        // Check fields
        $error = '';
        
        if (!empty($name) && !\Validate::cha_val($name, V_TITLE))
            $error = $error . '<li>' . sprintf(__('Wrong chars in field "param"'), __('Login')) . '</li>' . "\n";
        if (empty($message))
            $error = $error . '<li>' . sprintf(__('Empty field "param"'), __('Message')) . '</li>' . "\n";



        // Check captcha if need exists
        if (!\ACL::turnUser(array('__other__', 'no_captcha'))) {
            if (empty($keystring))
                $error = $error . '<li>' . sprintf(__('Empty field "param"'), __('Captcha')) . '</li>' . "\n";


            // Проверяем поле "код"
            if (!empty($keystring)) {
                // Проверяем поле "код" на недопустимые символы
                if (!\Validate::cha_val($keystring, V_CAPTCHA))
                    $error = $error . '<li>' . sprintf(__('Wrong chars in field "param"'), __('Captcha')) . '</li>' . "\n";
                if (!isset($_SESSION['chat_captcha_keystring'])) {
                    if (file_exists(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat')) {
                        $_SESSION['chat_captcha_keystring'] = file_get_contents(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat');
                        @_unlink(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat');
                    }
                }
                if (!isset($_SESSION['chat_captcha_keystring']) || $_SESSION['chat_captcha_keystring'] != $keystring)
                    $error = $error . '<li>' . __('Wrong protection code') . '</li>' . "\n";
            }
            unset($_SESSION['chat_captcha_keystring']);
        }

        /* if an errors */
        if (!empty($error)) {
            $_SESSION['addForm'] = array();
            $_SESSION['addForm']['errors'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
                    "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
            $_SESSION['addForm']['message'] = $message;
            die($_SESSION['addForm']['errors']);
        }

        /* create dir for chat tmp file if not exists */
        $tmp = ROOT . $this->getTmpPath();
        if (!file_exists($tmp))
            mkdir($tmp, 0777, true);
        /* get data */
        if (file_exists($tmp . 'messages.dat')) {
            $data = unserialize(file_get_contents($tmp . 'messages.dat'));
        } else {
            $data = array();
        }


        /* cut data (no more 50 messages */
        while (count($data) > 50) {
            array_shift($data);
        }
        $data[] = array(
            'user_id' => $user_id,
            'user_group' => $user_group,
            'name' => $name,
            'message' => $message,
            'ip' => $ip,
            'unixtime' => time(),
        );


        /* save messages */
        $file = fopen($tmp . 'messages.dat', 'w+');
        fwrite($file, serialize($data));
        fclose($file);
        die('ok');
    }

    /**
     * view add message form
     *
     * @return (str)  add form
     */
    public static function add_form() {
        //$Register = \Register::getInstance();

        $Register = \Register::getInstance();
        $Register['module_class']->templateDir = self::$static_template;
        if (!\ACL::turnUser(array(self::$static_module, 'add_materials')))
            return __('Dont have permission to write post in chat',true,'chat');


        $markers = array();

        /* if an errors */
        if (isset($_SESSION['addForm'])) {
            $message = $_SESSION['addForm']['message'];
            unset($_SESSION['addForm']);
        } else {
            $message = '';
        }


        $kcaptcha = '';
        if (!\ACL::turnUser(array('__other__', 'no_captcha'))) {
            $kcaptcha = getCaptcha('chat_captcha_keystring');
        }
        $markers['action'] = get_url('/' . self::$static_module . '/add/');
        $markers['message'] = h($message);
        $markers['captcha'] = $kcaptcha;


        $View = new \Viewer_Manager();
        $View->setLayout(self::$static_module);


        // TODO не нужно сюда копии меток пихать, нужно использовать глобальные метки как и везде
        $path = \Config::read('smiles_set');
        $path = (!empty($path) ? $path : 'drspro');
        $smiles_set = $path;
        $path = ROOT . '/data/img/smiles/' . $path . '/info.php';
        include $path;
        if (isset($smilesList) && is_array($smilesList)) {
            $smiles_list = (isset($smilesInfo) && isset($smilesInfo['show_count'])) ? array_slice($smilesList, 0, $smilesInfo['show_count']) : $smilesList;
        } else {
            $smiles_list = array();
        }


        $source = $View->view('addform.html', array('data' => $markers, 'template_path' => get_url('/template/' . getTemplate()), 'smiles_set' => $smiles_set, 'smiles_list' => $smiles_list));

        return $source;
    }


    public function del($id = null) {
        \ACL::turnUser(array($this->module, 'delete_materials'),true);

        $id = intval($id);
        if ($id < 0) return;

        $chatDataPath = ROOT . $this->getTmpPath('messages.dat');
        if (file_exists($chatDataPath)) {
            $data = unserialize(file_get_contents($chatDataPath));
            if (!empty($data) and !empty($data[$id])) {
                unset($data[$id]);

                $file = fopen($chatDataPath, 'w');
                fwrite($file, serialize($data));
                fclose($file);
            }
        }
        echo true;
    }


    /**
     * view messages form
     *
     * @return (str)  form
     */
    public static function form() {

        $View = new \Viewer_Manager();
        $View->setLayout(self::$static_module);

        $source = $View->view('list.html', array('template_path' => get_url('/template/' . getTemplate())));

        return $source;
    }
}

