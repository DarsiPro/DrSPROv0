<?php
/**
 * Класс автозагрузки (Autoloader) для DarsiPro CMS
 * 
 * Обеспечивает автоматическую загрузку классов по их namespace и имени,
 * поддерживает как современные namespace, так и старый PEAR-стиль.
 * 
 * @project    DarsiPro CMS
 * @package    Core
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */

class AL 
{
    /**
     * Карта соответствий namespace и путей в файловой системе
     * @var array
     */
    private static $namespacesMap = array();
    
    /**
     * Добавляет соответствие между namespace и директорией
     * 
     * @param string $namespace Пространство имен
     * @param array $rootDirs Массив путей к директориям
     */
    public static function addNamespace($namespace, $rootDirs) 
    {
        if (!is_array($rootDirs)) {
            $rootDirs = array($rootDirs);
        }
        
        // Нормализация путей
        foreach ($rootDirs as &$dir) {
            $dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        }
        
        self::$namespacesMap[$namespace] = $rootDirs;
    }
    
    /**
     * Возвращает пути для указанного namespace
     * 
     * @param string $namespace Пространство имен
     * @return array|null Массив путей или null если не найден
     */
    public static function getNamespaces($namespace) 
    {
        return isset(self::$namespacesMap[$namespace]) 
            ? self::$namespacesMap[$namespace] 
            : null;
    }
    
    /**
     * Метод автозагрузки классов
     * 
     * @param string $class Полное имя класса с namespace
     * @return bool Успешность загрузки
     */
    protected static function autoload($class) 
    {
        // Разбиваем полное имя класса на части
        $pathParts = explode('\\', $class);
        
        // Поддержка PEAR-стиля именования (с подчеркиваниями)
        if (count($pathParts) === 1 && strpos($class, '_') !== false) {
            $pathParts = explode('_', $class);
        }
        
        // Если для namespace есть зарегистрированные пути
        if (!empty(self::$namespacesMap[$pathParts[0]])) {
            $namespace = array_shift($pathParts);
            $classPath = implode(DIRECTORY_SEPARATOR, $pathParts);
            
            foreach (self::$namespacesMap[$namespace] as $baseDir) {
                // Проверяем возможные варианты имен файлов
                $variants = array(
                    $baseDir . $classPath . '.class.php',
                    $baseDir . $classPath . '.php',
                    $baseDir . str_replace('_', DIRECTORY_SEPARATOR, $classPath) . '.php'
                );
                
                foreach ($variants as $filePath) {
                    if (self::safeRequire($filePath)) {
                        return true;
                    }
                }
            }
        }
        
        // Стандартный путь в системной директории
        $basePath = R . 'sys' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
        $variants = array(
            $basePath . implode(DIRECTORY_SEPARATOR, $pathParts) . '.class.php',
            $basePath . implode(DIRECTORY_SEPARATOR, $pathParts) . '.php',
            $basePath . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php'
        );
        
        foreach ($variants as $filePath) {
            if (self::safeRequire($filePath)) {
                return true;
            }
        }
        
        // Логирование неудачной попытки загрузки
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("Autoload failed for class: {$class}");
        }
        
        return false;
    }
    
    /**
     * Безопасное подключение файла с проверкой его существования
     * 
     * @param string $filePath Путь к файлу
     * @return bool Успешность подключения
     */
    protected static function safeRequire($filePath) 
    {
        if (file_exists($filePath) && is_file($filePath)) {
            try {
                require_once $filePath;
                return true;
            } catch (Exception $e) {
                error_log("Autoload error: {$e->getMessage()} in file: {$filePath}");
            }
        }
        return false;
    }
    
    /**
     * Регистрирует автозагрузчик в системе
     */
    public static function register() 
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
    
    /**
     * Удаляет автозагрузчик из системы
     */
    public static function unregister() 
    {
        spl_autoload_unregister(array(__CLASS__, 'autoload'));
    }
}