<?php
/**
* @project    DarsiPro CMS
* @package    Pages navigation function
* @url        https://darsi.pro
*/


// Функция возвращает html меню для постраничной навигации
function pagination( $total, $perPage, $url )
{
    $cntPages = ceil( $total / $perPage );
    if ($cntPages == 0) $cntPages = 1;

    if ( isset($_GET['page']) ) {
        $page = (int)$_GET['page'];
        if ( $page < 1 ) $page = 1;
    } else
        $page = 1;

    $order = (!empty($_GET['order'])) ? trim($_GET['order']) : '';
    $asc = (!empty($_GET['asc'])) ? trim($_GET['asc']) : '';
    $add_params = (!empty($order) ? '&order=' . $order : '') . (!empty($asc) ? '&asc=' . $asc : '');

    if ($page > $cntPages) {
        http_response_code(404);
        include_once R.'sys/inc/error.php';
        die();
    }

    $Register = Register::getInstance();
    $Register['pagescnt'] = $cntPages;

    if (strpos($url, WWW_ROOT.'/admin') === false)
        $url_ctm_ptn = ROOT . '/template/' . getTemplate() . '/customize/pagination.php';
    else
        $url_ctm_ptn = ROOT . '/admin/inc/pagination.php';

    if (file_exists($url_ctm_ptn)) {
        include_once($url_ctm_ptn);
        if (function_exists('custom_pagination')) {
            return array(call_user_func_array('custom_pagination', array($page, $cntPages, $url)), $page);
        }
    }
    if ($cntPages <= 1) return array('', $page);

    // Вычисляем нужный разделитель гет запросов
    $sep = '?';
    if (preg_match('/\?[a-z0-9]+=[a-z0-9]+/i',$url))
        $sep = '&';

    $url = get_url($url);

    $html = __('Pages');
    // Проверяем нужна ли стрелка "В начало"
    if ( $page > 3 )
        $startpage = '<a class="pages" href="'.$url.$sep.'page=1'.$add_params.'">'.__('left Arrow').'</a> ... ';
    else
        $startpage = '';
    // Проверяем нужна ли стрелка "В конец"
    if ( $page < ($cntPages - 2) )
        $endpage = ' ... <a class="pages" href="'.$url.$sep.'page='.$cntPages.$add_params.'">'.__('right Arrow').'</a>';
    else
        $endpage = '';

    // Находим две ближайшие станицы с обоих краев, если они есть
    if ( $page - 2 > 0 )
        $page2left = ' <a class="pages" href="'.$url.$sep.'page='.($page - 2).$add_params.'">'.($page - 2).'</a>  ';
    else
        $page2left = '';
    if ( $page - 1 > 0 )
        $page1left = ' <a class="pages" href="'.$url.$sep.'page='.($page - 1).$add_params.'">'.($page - 1).'</a>  ';
    else
        $page1left = '';
    if ( $page + 2 <= $cntPages )
        $page2right = '  <a class="pages" href="'.$url.$sep.'page='.($page + 2).$add_params.'">'.($page + 2).'</a>';
    else
        $page2right = '';
    if ( $page + 1 <= $cntPages )
        $page1right = '  <a class="pages" href="'.$url.$sep.'page='.($page + 1).$add_params.'">'.($page + 1).'</a>';
    else
        $page1right = '';

    // Выводим меню
    $html = $html.$startpage.$page2left.$page1left.'<strong class="pages">'.$page.'</strong>'.
          $page1right.$page2right.$endpage;



    return array($html, $page);
}



?>