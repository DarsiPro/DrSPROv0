<?php
/**
* @project    DarsiPro CMS
* @package    Autoloader Class
* @url        https://darsi.pro
*/

class AL {
    
    // карта для соответствия неймспейса пути в файловой системе
    private static $namespacesMap = array();
    
    // Выставляет соответствие между неймспейсом и его адресом на диске
    static function addNamespace($namespace, $rootDirs) {
        self::$namespacesMap[$namespace] = $rootDirs;
    }
    // Возвращает таблицу соответствий между неймспейсом и его адресом на диске
    static function getNamespaces($namespace) {
        return self::$namespacesMap[$namespace];
    }
    
    protected static function autoload($class) {
        $pathParts = explode('\\', $class);
        
        // Если используется PEAR именование класса
        if (count($pathParts) == 1 && count( ($PEARpathParts = explode('_', $class)) ) > 1) {
            $pathParts = $PEARpathParts;
        }
        
        // Если у неймспейса есть соответствие на диске
        if (!empty(self::$namespacesMap[$pathParts[0]])) {
            $_pathParts = $pathParts;
            $namespace = array_shift($_pathParts);
            $path_into_namespace = '/' . implode('/', $_pathParts);
            foreach(self::$namespacesMap[$namespace] as $folder_assoc) {
                
                $filePath = $folder_assoc . $path_into_namespace . '.class.php';
                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
                
                $filePath = $folder_assoc . $path_into_namespace . '.php';
                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
                
            }
        }
        
        // Иначе используем стандартный путь до классов
        $filePath = R.'sys/lib/' . implode('/', $pathParts) . '.class.php';
        if (file_exists($filePath)) {
            require_once $filePath;
            return true;
        }
        
        $filePath = R.'sys/lib/' . implode('/', $pathParts) . '.php';
        if (file_exists($filePath)) {
            require_once $filePath;
            return true;
        }
        
        
        return false;
    }
    
    static function register() {
        spl_autoload_register('self::autoload');
    }
}