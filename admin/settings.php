<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/


include_once '../sys/boot.php';
include_once R.'admin/inc/adm_boot.php';
$pageTitle = __('Common settings');
$Register = Register::getInstance();


// Get current module(group of settings)
if (empty($_GET['m']) || !is_string($_GET['m'])) $_GET['m'] = 'sys';
$module = trim($_GET['m']);

$Register['module'] = $module;
$config = Config::read('all', $module);

// Список страниц настройки cms, генерирующихся автоматически

$sysMods = (isset($config['system_modules'])) ? $config['system_modules'] : array();
unset($sysMods['__db__']);


/**
 * For show template preview
 *
 * @param string $template
 * @return string
 */
function getImgPath($template) {
    $path = ROOT . '/template/' . $template . '/screenshot.png';
    if (file_exists($path)) {
        return get_url('/template/' . $template . '/screenshot.png');
    }
    return get_url('/data/img/noimage.jpg');
}

// Подготавливаем необходимые данные
if (in_array($module, $sysMods)) {

    switch($module) {
        case '__sys__':


            // Prepare templates select list
            $sourse = glob(ROOT . '/template/*', GLOB_ONLYDIR);
            if (!empty($sourse) && is_array($sourse)) {
                $templates = array();
                foreach ($sourse as $dir) {
                    if (preg_match('#.*/(\w+)$#', $dir, $match)) {
                        $templates[] = $match[1];
                    }
                }
            }
            $templateSelect = array();
            if (!empty($templates)) {
                foreach ($templates as $value) {
                    $templateSelect[$value] = ucfirst($value);
                }
            }



            // Генерация списка доступных для выбора языков
            $langSelect = array();
            $langs = getPermittedLangs();
            foreach ($langs as $lang) $langSelect[$lang] = $lang;



            // Подготовка списка выбора смайликов
            $smiles = glob(ROOT . '/data/img/smiles/*/info.php');
            $smilesSelect = array();
            if (!empty($smiles)) {
                sort($smiles);
                foreach ($smiles as $value) {
                    if (is_file($value)) {
                        include_once $value;
                        $path = dirname($value);
                        $pos = strrpos($path, "/");
                        if ($pos >= 0) {
                            $value = substr($path, $pos + 1);
                        }
                        if (isset($smilesInfo) && isset($smilesInfo['name'])) {
                            $smilesSelect[$value] = $smilesInfo['name'];
                        };
                    }
                }
            } else {
                $smilesSelect['drspro'] = 'DarsiPro';
            }




            break;


        case '__rss__':
            $pageTitle = __('RSS settings');

            // получение списка модулей, работающих с RSS
            $rss_modules = array();
            $modules = glob(ROOT.'/modules/*', GLOB_ONLYDIR);
            if (!empty($modules) && is_array($modules)) {
                foreach ($modules as $fmodule) {
                    if (file_exists($fmodule.'/index.php')) {
                        include_once ($fmodule.'/index.php');
                        $m = basename($fmodule);
                        if (isset($config[$m]) and
                            is_array($config[$m]) and
                            isset($config[$m]['active']) and
                            method_exists(ucfirst($m.'Module'), 'rss')) {

                            $rss_modules['rss_'.$m] = array(
                                    'type' => 'checkbox',
                                    'title' => __($m),
                                    'description' => '',
                                    'checked' => '1',
                                    'value' => '1',
                            );
                        }
                    }
                }
            }




            break;
        case '__hlu__':
            $pageTitle = __('SEO settings');
            break;
        case '__sitemap__':
            $pageTitle = __('Sitemap settings');
            break;
        case '__secure__':
            $pageTitle = __('Security settings');
            break;
        case '__preview__':
            $pageTitle = __('Preview settings');
            break;
        case '__watermark__':
            $pageTitle = __('Watermark settings');

            // Prepare fonts select list
            $fonts = glob(ROOT . '/data/fonts/*.ttf');
            $fontSelect = array();
            if (!empty($fonts)) {
                sort($fonts);
                foreach ($fonts as $value) {
                    $pos = strrpos($value, "/");
                    if ($pos >= 0) {
                        $value = substr($value, $pos + 1);
                    }
                    $fontSelect[$value] = $value;
                }
            }

            break;
    }



    /* properties for system settings and settings that not linked to module
    * returns
    * $settingsInfo - настройки системных модулей
    * $noSub - Модули, настройки которых не обьеденены под один ключ.
    *
    */
    include_once R.'sys/settings/conf_properties.php';


    $settingsInfo = $settingsInfo[$module];
} else {
    if (ModuleInstaller::checkInstallModule($module)) {
        $pathToModInfo = R.'modules/' . $module . '/settings.php';
        $pageTitle = __($module) . ' - ' . __('Settings of module');
        if (file_exists($pathToModInfo)) {
            include ($pathToModInfo);
        } else {
            $settingsInfo = array(
                'title' => array(
                    'title' => __('Title'),
                    'description' => __('Title: info'),
                ),
                'description' => array(
                    'title' => __('Meta-Description'),
                    'description' => __('Meta-Description: info'),
                ),

                __('Other'),

                'active' => array(
                    'type' => 'checkbox',
                    'title' => __('Module status'),
                    'description' => __('Module status: info'),
                ),
            );
        }
    } else {
        $_SESSION['message'][] = __('Module not found');
    }
}





// Save settings

if (isset($_POST['send']) && isset($settingsInfo)) {
    // Запоминаем текущее значение конфига.
    // Если мы настраиваем модуль, то запоминаем только значения этого модуля
    // Если это глобальные настройки, то запоминаем весь конфиг
    $tmpSet = (in_array($module, $sysMods) && !in_array($module, $noSub)) ? $config[$module] : $config;

    foreach ($settingsInfo as $fname => $params) {
        // Если это поле, которое нельзя изменять, то идем дальше, конфиг менять не нужно
        if (!empty($params['attr']) && !empty($params['attr']['disabled'])) continue;

        // Удаляем переменную, если она есть, чтобы небыло проблем.
        unset($value);

        // Узнаем, вложенная ли это настройка(вложена, это значит, что она обьеденена с другими настройками одним ключом, указанным в поле fields)
        if (!empty($params['fields'])) {
            // Если имя настройки начинается с sub_, значит следует формировать имя без приставки sub_
            if (false !== strpos($fname, 'sub_')) $fname = mb_substr($fname, mb_strlen('sub_'));
            // Если в данных для сохранения есть данные для этой настройки(измененные или нет, но не пустые)
            if (!empty($_POST[$params['fields']][$fname])) {
                // Меняем содержимое настройки в конфиге на то, что указано в форме
                $tmpSet[$params['fields']][$fname] = $_POST[$params['fields']][$fname];
            // Если настройка пустая, значит она не заполнена
            } else {
                // Если существует такой массив вложенных настроек
                if (isset($tmpSet[$params['fields']]) && is_array($tmpSet[$params['fields']])) {
                    // И в него входит текущая настройка
                    if (array_key_exists($fname, $tmpSet[$params['fields']]))
                        // Удаляем её, чтобы не занимала место.
                        unset($tmpSet[$params['fields']][$fname]);
                }
            }
            // Идем дальше, текущая настройка обработана
            continue;
        }


        // Если настройка существует, то запоминаем её значение
        if (isset($_POST[$fname]) || isset($_FILES[$fname])) {
            $value = trim((string)$_POST[$fname]);
        }



        if (empty($value)) $value = '';
        // Если тип checkbox, то ровняем либо 0 либо 1
        if (isset($params['type']) && $params['type'] === 'checkbox') {
            $tmpSet[$fname] = (!empty($value)) ? 1 : 0;
        // Для остальных типов сохраняем значение.
        } else {
            $tmpSet[$fname] = $value;
        }

        // Если указана специальная функция, которую следует выполнить при сохранении этой настройки
        if (!empty($params['onsave'])) {
            // Если это multiply, то это значит, что нужно умножить на указанное число введенное значение.
            if (!empty($params['onsave']['multiply'])) {
                $tmpSet[$fname] = round($tmpSet[$fname] * $params['onsave']['multiply']);
            }
            // Если это полноценная функция
            if (!empty($params['onsave']['func']) && is_callable($params['onsave']['func'])) {
                // Если функция нужна для обработки файла
                if ($params['type'] == 'file' && (isset($_POST[$fname]) || isset($_FILES[$fname]))) {
                    $tmpSet[$fname] = call_user_func($params['onsave']['func'], $tmpSet, $fname);
                    if (empty($tmpSet[$fname]))
                        unset($tmpSet[$fname]);
                    continue;
                // И если для обработки любых других данных
                } else {
                    $tmpSet[$fname] = call_user_func($params['onsave']['func'], $tmpSet, $fname);
                }
            }
        }

    }

    // Если настройки этого модуля нужно обьединять под один ключ
    if (in_array($module, $sysMods) && !in_array($module, $noSub)) {
        $_tmpSet = $config;
        $_tmpSet[$module] = $tmpSet;
        $tmpSet = $_tmpSet;
    }


    //save settings
    if (in_array($module, $sysMods))
        Config::write($tmpSet);
    else
        Config::write($tmpSet, $module);

    $_SESSION['message'][] = __('Saved');
    //clean cache
    $Cache = new Cache;
    $Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $module));
    redirect('/admin/settings.php?m=' . $module);
}





// Build form for settings editor Создайте форму для редактора настроек
$_config = (in_array($module, $sysMods) && in_array($module, $noSub) or !in_array($module, $sysMods)) ? $config : $config[$module];

$output = '';
if (isset($settingsInfo) && count($settingsInfo)) {
    foreach ($settingsInfo as $fname => $params) {
        if (is_string($params)) {
            //$output .= '<div class="col b30tm s12"><h5 class="light">' . h($params) . '</h5></div>';
            $output .= '<div class="new-row s12"><h3 class="thin underline">' . h($params) . '</h3></div>';
            continue;
        }

        // Заполняем незаполненные значения значениями по умолчанию
        if (array_key_exists('type',$params) && $params['type'] == 'checkbox')
            $params = array_merge(array(
                'value' => '1',
                'checked' => '1',
            ), $params);

        $params = array_merge(array(
            'type' => 'text',
            'title' => '',
            'description' => '',
            'help' => '',
            'options' => array(),
            'attr' => array(),
            'grid-width' => 's12'
        ), $params);



        $currValue = (!empty($_config[$fname])) ? $_config[$fname] : false;
        if (!empty($params['onview'])) {
            if (!empty($params['onview']['division'])) {
                $currValue = round($currValue / $params['onview']['division']);
            }
        }


        $attr = '';
        if (!empty($params['attr']) && count($params['attr'])) {
            foreach ($params['attr'] as $attrk => $attrv) {
                $attr .= ' ' . h($attrk) . '="' . h($attrv) . '"';
            }
        }

        $output_ = '';

        // If we have function by create sufix after input field - Если у нас есть функция by, создайте суффикс после поля ввода
        if (!empty($params['input_prefix_func']) && is_callable($params['input_prefix_func'])) {
            $output_ .= call_user_func($params['input_prefix_func'], $config, $fname);
        }
        if (!empty($params['input_prefix'])) {
            $output_ .= $params['input_prefix'];
        }

        switch ($params['type']) {
            case 'text':
                $output_ .= '<div class="'.$params['grid-width'].'" title="'.h($params['description']).'"><p class="button-height inline-large-label"><label for="'.h($fname).'" class="label">'.$params["title"].'</label>'.'<input name="'.h($fname).'" id="'.h($fname).'" class="input" value="'.$currValue.'" type="'.$params['type'].'"'.$attr.'">';
                //<input type="'.$params['type'].'" id="' . h($fname) . '" name="' . h($fname) . '" value="' . $currValue . '"' . $attr . '  class="validate"/>
                // Help note
                if (!empty($params['help'])) $output_ .= '<small class="input-info"> '.h($params['help']).'</small>';
                $output_ .= '</p></div>';
                break;
                
            case 'number':
                
                $output_ .= '<div class="'.$params['grid-width'].'" title="'.h($params['description']).'">
                <p class="button-height inline-large-label">
                <label for="'.h($fname).'" class="label">'.$params["title"].'</label>
                <span class="number input">
                    <button type="button" class="button number-down">-</button>
                    <input type="'.$params['type'].'" name="'.h($fname).'" id="'.h($fname).'" value="'.$currValue.'" size="5" class="input-unstyled"'.$attr.'>
                    <button type="button" class="button number-up">+</button>
                </span>';
                // Help note
                if (!empty($params['help'])) $output_ .= '<small class="input-info"> '.h($params['help']).'</small>';
                $output_ .= '</p></div>';
                
                break;
                
            case 'checkbox':
                $id = md5(rand(0, 99999) + rand(0, 99999));
                $state = (!empty($params['checked']) && $currValue == $params['checked']) ? ' checked="checked"' : '';
                if (!empty($params['fields'])) {
                    if (false !== strpos($fname, 'sub_'))
                        $fname = mb_substr($fname, mb_strlen('sub_'));
                    $subParams = (!empty($_config[$params['fields']])) ? $_config[$params['fields']] : array();
                    if (count($subParams) && in_array($fname, $subParams))
                        $state = ' checked="checked"';
                    $fname = $params['fields']. '[' . $fname . ']';
                }
                $output_ .= '<div class="'.$params['grid-width'].'" title="'.h($params['description']).'">
                <p>
                <input id="' . $id . '" type="checkbox" class="switch tiny" name="' . h($fname) . '" value="' . $params['value'] . '" ' . $state . '' . $attr . '>
                <label for="' . $id . '">'.$params["title"].'</label></p></div>';
                
                break;

            case 'select':
                $options = '';
                if (count($params['options'])) {
                    foreach ($params['options'] as $value => $visName) {
                        $options_ = '';
                        $state = ($_config[$fname] == $value) ? ' selected="selected"' : '';

                        $attr_option = '';
                        if (!empty($params['options_attr'])) {
                            foreach ($params['options_attr'] as $k => $v) {
                                $attr_option .= ' ' . $k . '="' . $v . '"';
                            }
                        }

                        $options_ .= '<option ' . $state . $attr_option . ' value="'
                        . h($value) . '">' . h($visName) . '</option>';
                        $options .= sprintf($options_, getImgPath($value));
                    }
                }

                
                $output_ .= '<div class="'.$params['grid-width'].'" title="'.h($params['description']).'">
                <p class="button-height inline-large-label">
                <label class="label">'.$params["title"].'</label>
                <select name="'.h($fname).'" class="select" '.$attr.'>'.$options.'</select>';
                
                $output_ .= '</p></div>';
                
                break;

            case 'file':
                $output_ .= '<div class="'.$params['grid-width'].'" title="'.h($params['description']).'">'
                                .'<p>'
                                  .'<input name="'.h($fname).'" type="file" class="file"'.$attr.'>'
                                .'</p>'
                            .'</div>';
                break;
        }


        // $params['description']
        $output .= ''
                   . $output_;



        // If we have function by create sufix after input field
        if (!empty($params['input_suffix_func']) && is_callable($params['input_suffix_func'])) {
            $output .= call_user_func($params['input_suffix_func'], $config, $fname);
        }
        if (!empty($params['input_suffix'])) {
            $output .= $params['input_suffix'];
        }

        $output .= '';
    }
    
}


include_once R.'admin/template/header.php';
?>










<!-- Main title -->
		<hgroup id="main-title" class="thin">
			<h1><?php echo $pageTitle; ?></h1>
			<h2><?php echo date('M');?> <strong><?php echo date('j');?></strong></h2>
		</hgroup>
	

		

		<!-- The padding wrapper may be omitted -->
        <div class="with-padding">
            
            
            
            
            <span class="button-group large-margin-bottom">
				<a href="javascript:void(0)" class="button icon-gear green-active active"><?php echo $pageTitle; ?></a>
				<a href="<?php echo WWW_ROOT ?>/admin/design.php" class="button icon-palette green-active"><?php echo __('Design'); ?></a>
				
				
				
				
				<a href="javascript:void(0)" class="button icon-thumbs green-active">Icons</a>
			</span>
            
            
            
            
            <form method="POST" action="settings.php?m=<?php echo $module; ?>" enctype="multipart/form-data">
            
                
                <div class="columns">
                
                
                <?php echo $output; ?>
                
                
                <!-- Second row -->
                <div class="new-row s12">12 cols</div>
            </div>
            
            
            <div class="b_save">
    <button type="submit" name="send" class="button glossy mid-margin-right">
								<span class="button-icon"><span class="icon-tick"></span></span>
								<?php echo __('Save'); ?>
							</button>
</div>
            
            
            
            </form>
            
        </div>










<?php include_once R.'admin/template/footer.php'; ?>
