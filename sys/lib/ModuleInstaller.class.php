<?php
/**
* @project    DarsiPro CMS
* @package    ModuleInstaller Class
* @url        https://darsi.pro
*/
class ModuleInstaller
{

    private $modulesPath;
    private $module;

    // Обязательные файлы
    private $indexFile;
    private $configFile;
    // Необязательные файлы
    private $settingsFile;
    private $menuFile;
    private $installFile;

    /**
     *
     */
    public function __construct()
    {
        $this->modulesPath = R.'modules/';
        $this->indexFile = 'index.php';
        $this->configFile = 'config.php';
        $this->settingsFile = 'settings.php';
        $this->menuFile = 'menu.php';
        $this->installFile = 'install.php';
    }



    /** Возвращает имя модуля из пути до него */
    private function getModuleTitleFromPath($modulePath)
    {
        $title = strrchr((string)$modulePath, '/');
        $title = substr($title, 1);
        return ($title) ? $title : false;
    }

    /** Проверяет директорию. И возвращает истину, если эта директория модуль. Или проверяет на существование модуль. */
    public function checkModule($modulePath,$check_install = true)
    {
        if (mb_strpos('/',$modulePath) !== false) {
            $module = $this->getModuleTitleFromPath($modulePath);
            if (!$module) return false;
        } else {
            $module = $modulePath;
        }

        if ($check_install && $this->checkInstallModule($module))
            return false;

        $instmodPath = $this->modulesPath . $module . '/';
        //print_r($instmodPath.$this->indexFile);
        if (file_exists($instmodPath.$this->indexFile) && file_exists($instmodPath.$this->configFile))
            return true;
    }


    /** Возвращает массив неустановленных, но готовых к установке модулей */
    public function checkNewModules()
    {
        $newmodules = array();

        $modules = glob($this->modulesPath . '*', GLOB_ONLYDIR);
        if (count($modules)) {
            foreach ($modules as $modulePath) {
                $module = basename($modulePath);
                if (!in_array($module,$newmodules) && $this->checkModule($module))
                    $newmodules[] = $module;
            }
        }
        return $newmodules;
    }

    /** Проверяет был ли установлен модуль */
    public static function checkInstallModule($module)
    {
        if (is_array(Config::read('installed_modules')) && in_array($module, Config::read('installed_modules')))
            return true;
        else
            return false;
    }


    /** Устанавливает модуль */
    public function installModule($module)
    {
        // Если модуль существует и является модулем
        if ($this->checkModule($module)) {
            $instmodPath = $this->modulesPath . $module . '/';
            $customInstallFile = $instmodPath.$this->installFile;
            if (file_exists($customInstallFile)) {
                $out = include($customInstallFile);
            } else {
                $out = true;
            }

            if (!empty($out) && $out != false) {
                $this->addInstalledStatus($instmodPath.$this->configFile, $module);
            }
        }
    }

    /** Устанавливает модулю статус установленного модуля */
    public function addInstalledStatus($path, $module)
    {
        if (file_exists($path)) {
            $ModuleConfig = include($path);
            if (!empty($ModuleConfig) && is_array($ModuleConfig)) {
                $CurrSettings = Config::read('all');
                $CurrSettings['installed_modules'][] = $module;
                Config::write($CurrSettings);
            }
        }
    }

    /** Получает список шаблонов необходимых для работы модуля */
    public function getTemplateParts($module)
    {
        $pathToFile = $this->modulesPath . $module . '/template_parts.php';
        if (file_exists($pathToFile)) {
            $allowedTemplateParts = include $pathToFile;
            if (!empty($allowedTemplateParts)) return $allowedTemplateParts;
            return false;
        }
    }
}