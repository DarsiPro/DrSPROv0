<?php
/**
* @project    DarsiPro CMS
* @package    Events Class
* @url        https://darsi.pro
*/


class Events {
    
    static $map = null;
    
    static function create_map() {
        self::$map = glob(R.'modules/*/EventsHandler.class.php');
    }
    
    /**
    * Initialisation event
    *
    * @param string $event
    * @param mixed $params
    * @return mixed
    */
    static function init($event, $params = null, $etc = null) {
        if (self::$map === null)
            self::create_map();
        
        if (!empty(self::$map)) {
            for($i=0;count(self::$map) > $i;$i++) {
                // Если мы еще знаем только путь до обработчика, то расширяем знание
                if (is_string(self::$map[$i]) && !class_exists(self::$map[$i])) {
                    $module = basename(dirname(self::$map[$i]));
                    $className = ucfirst($module).'Module\EventsHandler';
                    if (!class_exists($className)) {
                        throw new Exception("Undefined classname '$className' in '$module' events handler.");
                        continue;
                    }
                    
                    self::$map[$i] = $className;
                }
                
                // Проверка на то, хочет ли обработчик обрабатывать текущее событие
                // Ранее мы уже загрузили файл-обработчик
                $className = is_string(self::$map[$i]) ? self::$map[$i] : get_class(self::$map[$i]);
                if (in_array($event, $className::$support_events)) {
                    // Но еще не создавали экземпляра класса-обработчика
                    if (is_string(self::$map[$i]))
                        self::$map[$i] = new self::$map[$i]($params,$etc);
                    // Теперь с чистой совестью запускаем обработчик
                    $params = self::$map[$i]->{$event}($params,$etc);
                }
            }
        }
        
        // Обработка события плагинами
        $params = Plugins::intercept($event, $params, $etc);
        
        return $params;
    }
    
    
    
}