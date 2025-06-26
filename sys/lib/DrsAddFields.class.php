<?php
/**
* @project    DarsiPro CMS
* @package    Additional Fields
* @url        https://darsi.pro
*/



class DrsAddFields {

    public function __construct() {
    }



    /**
     * Обьединяет данные из sql запроса с выводом метки селекта
     *
     * @param array $records
     * @return mixed
     */
    public function mergeSelect($records, $module = null)
    {
        if (empty($module)) $module = $this->module;

        // Получение свойств полей
        $FieldsModelName = OrmManager::getModelInstance('AddFields');
        $where = array('module' => $module);
        $addFields = $FieldsModelName->getCollection($where);

        if (!empty($addFields) && is_array($addFields)) {
            foreach ($records as $k => $entity) {
                foreach ($addFields as $addField) {
                    $id = $addField->getField_id();

                    $type = $addField->getType();
                    
                    // Если это форма с выбором из вариантов, то нужно вывести варианты в отдельную метку.
                    if ($type == 'select') {
                        $params = $addField->getParams();
                        if (!empty($params))
                            $params = unserialize($params);
                        else
                            throw new Exception("Error getting parameters \"AddField\"(id:$id) the type SELECT");
                        
                        $field_selects_marker = 'add_field_select_' . $id;
                        $entity->$field_selects_marker = $params['values'];
                    }
                    
                    

                }
            }
        }
        return $records;
    }



    /**
     * Возвращает массив доп. полей без заполнения их данными
     * Для формы добавления материала
     */
    public function getInputs($records = array(), $module = null)
    {
        if (empty($module)) $module = $this->module;

        $Model = OrmManager::getModelInstance('AddFields');

        $where = array('module' => $module);
        if (!empty($records))
            $addFields = $records;
        else
            $addFields = $Model->getCollection($where);

        $_addFields = array();
        $_addFieldsSelect = array();
        $output = array();

        if (!empty($addFields)) {
            foreach($addFields as $key => $field) {

                $id = (is_object($field)) ? $field->getField_id() : $field['field_id'];
                $type = (is_object($field)) ? $field->getType() : $field['type'];
                $params = (is_object($field)) ? unserialize($field->getParams()) : unserialize($field['params']);

                $_addFields[$key] = 'add_field_' . $id;
                $output[$_addFields[$key]] = '';

                // Получаем значение поля из сессии, если оно есть.
                $value = '';

                switch ($type) {
                    case 'checkbox':
                        $value = 1;
                        break;
                    case 'select':
                        $_addFieldsSelect[$key] = 'add_field_select_' . $id;
                        $output[$_addFieldsSelect[$key]] = $params['values'];
                        break;
                }
                $output[$_addFields[$key]] = $value;
            }
        }
        return $output;
    }



    /**
     * Проверяет значения полей
     */
    public function checkFields($module = null) {
        if (empty($module)) $module = $this->module;

        $Model = OrmManager::getModelInstance('AddFields');

        $where = array('module' => $module);
        $addFields = $Model->getCollection($where);

        $CheckedAddFields = array();
        $error = null;
        
        
        if (!empty($addFields)) {
            foreach($addFields as $key => $field) {
                $params = $field->getParams();
                $params = (!empty($params)) ? unserialize($params) : array();
                
                $field_name = 'add_field_' . $field->getField_id();
                $content = null;
                
                $_POST[$field_name] = isset($_POST[$field_name]) ? $_POST[$field_name] : null;
                
                if (!empty($params['required']) && empty($_POST[$field_name]))
                    $error .= '<li>'.sprintf(__('Empty field "param"'), $field->getLabel()).'</li>';
                if (!empty($_POST[$field_name]) && mb_strlen($_POST[$field_name]) > (int)$field->getSize())
                    $error .= '<li>'.sprintf(__('Very big "param"'), $field->getLabel(), $field->getSize()).'</li>';

                switch ($field->getType()) {
                    case 'text':
                        $content = $_POST[$field_name];

                        if (!empty($params['pattern']) && !empty($array['content'])) {
                            
                            $pattern = $params['pattern'];
                            if (substr($pattern, 0, 2) == 'V_')
                                $pattern = constant($pattern);

                            if (!Validate::cha_val($_POST[$field_name],$pattern))
                                $error .= '<li>'.sprintf(__('Wrong chars in field "param"'), $field->getLabel()).'</li>';

                        }

                        break;
                    case 'checkbox':
                        if (!empty($_POST[$field_name]))
                            $content = 1;
                        else
                            $content = 0;
                        break;
                    case 'select':
                        if ($_POST[$field_name] !== null)
                            $content = $_POST[$field_name];
                        break;
                }
                $CheckedAddFields[$field_name] = $content;
            }
        }

        return (!empty($error)) ? $error : $CheckedAddFields;
    }
    
    /**
    * Вычленияет из массива полей доп. поля.
    */
    static function selectFromArray($arr) {
        foreach($arr as $key => $val)
            if (strpos($key, 'add_field_') !== 0)
                unset($arr[$key]);
        
        return $arr;
    }

    /**
     * Сохраняет значения доп. полей в масcив
     */
    public function set($fields, $data=array()) {
        if (empty($fields)) return $data;

        foreach ($fields as $field_name => $field_value) {
            $data[$field_name] = $field_value;
        }

        return $data;
    }

}
