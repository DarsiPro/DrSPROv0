<?php
/**
* @project    DarsiPro CMS
* @package    OrmManager
* @url        https://darsi.pro
*/


class OrmManager {

    // Получает название модуля из названия класса (без неймспейсов)
    static function getModuleFromClassname($class) {
        return strtolower(preg_split("/\B[A-Z]+/", $class, 2)[0]);
    }
    
    // Получает полное название класса Модели
    static function getModelName($modelName)
    {
        $module = self::getModuleFromClassname($modelName);
        if (ModuleInstaller::checkInstallModule($module))
            return '\\'.ucfirst($module).'Module\\ORM\\'.ucfirst($modelName) . 'Model';
        else
            return '\\ORM\\'.ucfirst($modelName) . 'Model';
    }


    // Возвращает экзепляр класса Модели
    static function getModelInstance($modelName)
    {
        $modelName = self::getModelName($modelName);
        if (!class_exists($modelName))
            throw new Exception("Model '$modelName' not found in OrmManager::getModelInstance()");
        return new $modelName;
    }

    // Получает полное название Модели из названия модуля(поддерживает перевод из PEAR именования)
    static function getModelNameFromModule($moduleName)
    {
        $ModelName = self::_removeUnderLine($moduleName);
        $ModelName = self::getModelName($ModelName);
        return $ModelName;
    }

    // Зная название класса Модели, получает название класса Сущности
    static function getEntityNameFromModel($modelName)
    {
        $entityClassName = str_replace('Model', 'Entity', $modelName);
        return $entityClassName;
    }


    // Получает полное название класса Сущности
    static function getEntityName($entityName)
    {
        $module = self::getModuleFromClassname($entityName);
        if (ModuleInstaller::checkInstallModule($module))
            return '\\'.ucfirst($module).'Module\\ORM\\'.ucfirst($entityName) . 'Entity';
        else
            return '\\ORM\\'.ucfirst($entityName) . 'Entity';
    }

    // Получает экзепляр класса Сущности, по её короткому названию
    static function getEntityInstance($entityName)
    {
        $className = self::getEntityName($entityName);
        return new $className;
    }


    // Зная название класса Сущности, получает название класса Модели
    static function getModelNameFromEntity($entityName)
    {
        $modelName = str_replace('Entity', 'Model', $entityName);
        return $modelName;
    }

    // Переводит название класса из PEAR именования в вид с разделением через заглавные буквы
    private static function _removeUnderLine($str)
    {
        $str = explode('_', $str);
        $str = array_map('ucfirst', $str);
        $str = implode('', $str);
        return $str;
    }
}