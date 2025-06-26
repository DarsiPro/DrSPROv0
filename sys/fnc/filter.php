<?php
/**
* @project    DarsiPro CMS
* @package    Filter materials function
* @url        https://darsi.pro
*/


/**
 * Принимает массив исходных параметров фильтрации и добавляет новые на основе ключей GET запроса
 * https://darsi.pro/wiki/Фильтрация_материалов
 *
 * @param array $where
 * @return array
 */
function filter( $where = array() )
{
    if (is_string($where)) $where = array($where);
    
    $filters = (!empty($_GET['filter'])) ? trim($_GET['filter']) : '';
    if ($filters == '')
        return $where;
    
    $filters = explode('|', $filters);
    
    foreach ($filters as $filter) {
        if (!empty($_GET[$filter])) {
            $rec = h(trim($_GET[$filter]));
            // даже не пытайся использовать замечательную функцию фильтрации в своих злобных целях
            if ($rec=="password")
                continue;
            $where[$filter] = $rec;
        } else {
            continue;
        }
    }
    return $where;
}

?>