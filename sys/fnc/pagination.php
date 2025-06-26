<?php
/**
 * Функция для генерации HTML-меню постраничной навигации
 *
 * @project    DarsiPro CMS
 * @package    Pages navigation function
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.1
 * @php        5.6+
 *
 * @param int    $total   Общее количество элементов
 * @param int    $perPage Количество элементов на странице
 * @param string $url     Базовый URL для ссылок
 * @return array          Массив с HTML-кодом навигации и текущей страницей
 */
function pagination($total, $perPage, $url)
{
    // ==============================================
    // ИНИЦИАЛИЗАЦИЯ И ПРОВЕРКА ПАРАМЕТРОВ
    // ==============================================
    
    // Вычисляем общее количество страниц
    $cntPages = ceil($total / $perPage);
    if ($cntPages == 0) $cntPages = 1;

    // Получаем текущую страницу из GET-параметра
    if (isset($_GET['page'])) {
        $page = (int)$_GET['page'];
        if ($page < 1) $page = 1;
    } else {
        $page = 1;
    }

    // Проверяем наличие параметров сортировки
    $order = !empty($_GET['order']) ? trim($_GET['order']) : '';
    $asc = !empty($_GET['asc']) ? trim($_GET['asc']) : '';
    $add_params = (!empty($order) ? '&order=' . urlencode($order) : '') . 
                 (!empty($asc) ? '&asc=' . urlencode($asc) : '');

    // ==============================================
    // ПРОВЕРКА ДОСТУПНОСТИ СТРАНИЦЫ
    // ==============================================
    
    if ($page > $cntPages) {
        http_response_code(404);
        include_once R.'sys/inc/error.php';
        die();
    }

    // Сохраняем количество страниц в глобальном реестре
    $Register = Register::getInstance();
    $Register['pagescnt'] = $cntPages;

    // ==============================================
    // ПРОВЕРКА НАЛИЧИЯ КАСТОМНОГО ШАБЛОНА ПАГИНАЦИИ
    // ==============================================
    
    // Определяем путь к кастомному шаблону пагинации
    if (strpos($url, WWW_ROOT.'/admin') === false) {
        $url_ctm_ptn = ROOT . '/template/' . getTemplate() . '/customize/pagination.php';
    } else {
        $url_ctm_ptn = ROOT . '/admin/inc/pagination.php';
    }

    // Если есть кастомный шаблон - используем его
    if (file_exists($url_ctm_ptn)) {
        include_once($url_ctm_ptn);
        if (function_exists('custom_pagination')) {
            return array(
                call_user_func_array('custom_pagination', array($page, $cntPages, $url)), 
                $page
            );
        }
    }

    // Если всего одна страница - возвращаем пустую строку
    if ($cntPages <= 1) {
        return array('', $page);
    }

    // ==============================================
    // ГЕНЕРАЦИЯ СТАНДАРТНОГО МЕНЮ ПАГИНАЦИИ
    // ==============================================
    
    // Определяем разделитель для GET-параметров
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    $url = get_url($url); // Нормализуем URL

    // Инициализируем HTML-код
    $html = __('Pages');

    // Генерация стрелки "В начало"
    $startpage = ($page > 3) 
        ? '<a class="pages" href="'.$url.$sep.'page=1'.$add_params.'">'.__('left Arrow').'</a> ... ' 
        : '';

    // Генерация стрелки "В конец"
    $endpage = ($page < ($cntPages - 2)) 
        ? ' ... <a class="pages" href="'.$url.$sep.'page='.$cntPages.$add_params.'">'.__('right Arrow').'</a>' 
        : '';

    // Генерация ссылок на соседние страницы
    $page2left = ($page - 2 > 0) 
        ? ' <a class="pages" href="'.$url.$sep.'page='.($page - 2).$add_params.'">'.($page - 2).'</a>  ' 
        : '';
    
    $page1left = ($page - 1 > 0) 
        ? ' <a class="pages" href="'.$url.$sep.'page='.($page - 1).$add_params.'">'.($page - 1).'</a>  ' 
        : '';
    
    $page1right = ($page + 1 <= $cntPages) 
        ? '  <a class="pages" href="'.$url.$sep.'page='.($page + 1).$add_params.'">'.($page + 1).'</a>' 
        : '';
    
    $page2right = ($page + 2 <= $cntPages) 
        ? '  <a class="pages" href="'.$url.$sep.'page='.($page + 2).$add_params.'">'.($page + 2).'</a>' 
        : '';

    // Собираем итоговый HTML-код
    $html .= $startpage . $page2left . $page1left . 
             '<strong class="pages">'.$page.'</strong>' . 
             $page1right . $page2right . $endpage;

    return array($html, $page);
}
?>