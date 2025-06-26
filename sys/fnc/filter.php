<?php
/**
 * Функция фильтрации материалов
 * 
 * Обрабатывает GET-параметры и формирует условия для фильтрации материалов.
 * Поддерживает безопасную обработку входных данных и защиту от SQL-инъекций.
 *
 * @project    DarsiPro CMS
 * @package    Core
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */

/**
 * Формирует массив условий для фильтрации на основе GET-параметров
 * 
 * @param array|string $where Исходные условия фильтрации
 * @return array Условия фильтрации с добавленными параметрами из GET-запроса
 */
function filter($where = array())
{
    // Нормализация входных данных
    if (is_string($where)) {
        $where = array($where);
    } elseif (!is_array($where)) {
        $where = array();
    }

    // Проверка наличия параметров фильтрации
    if (empty($_GET['filter'])) {
        return $where;
    }

    // Получение списка разрешенных полей для фильтрации
    $filterString = trim($_GET['filter']);
    if ($filterString === '') {
        return $where;
    }

    // Разделение фильтров по разделителю
    $filters = explode('|', $filterString);
    
    // Обработка каждого фильтра
    foreach ($filters as $filterField) {
        // Пропускаем пустые значения
        if (empty($filterField)) {
            continue;
        }

        // Проверяем наличие значения фильтра в GET-параметрах
        if (!isset($_GET[$filterField])) {
            continue;
        }

        // Защита от попыток фильтрации по служебным полям
        if ($filterField === 'password' || strpos($filterField, ' ') !== false) {
            continue;
        }

        // Очистка и подготовка значения
        $filterValue = trim($_GET[$filterField]);
        if ($filterValue === '') {
            continue;
        }

        // Экранирование специальных символов
        $filterValue = htmlspecialchars($filterValue, ENT_QUOTES, 'UTF-8', false);
        
        // Добавление условия фильтрации
        $where[$filterField] = $filterValue;
    }

    return $where;
}