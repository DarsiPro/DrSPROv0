<?php

function custom_pagination($page,$cntPages,$url) {

    if ($cntPages <= 1) return '';
    
    // Вычисляем нужный разделитель гет запросов
    $sep = '?';
    if (preg_match('/\?[a-z0-9]+=[a-z0-9]+/i',$url))
        $sep = '&';
        
	$url = get_url($url);
    
	$html = '';
	// Проверяем нужна ли стрелка "В начало"
	if ( $page > 3 )
		$startpage = '<li class="waves-effect"><a href="'.$url.$sep.'page=1"><i class="mdi-navigation-chevron-left"></i></a></li>';
	else
		$startpage = '<li class="disabled"><a><i class="mdi-navigation-chevron-left"></i></a></li>';
	// Проверяем нужна ли стрелка "В конец"
	if ( $page < ($cntPages - 2) )
		$endpage = '<li class="waves-effect"><a href="'.$url.$sep.'page='.$cntPages.'"><i class="mdi-navigation-chevron-right"></i></a></li>';
	else
		$endpage = '<li class="disabled"><a><i class="mdi-navigation-chevron-right"></i></a></li>';

	// Находим две ближайшие станицы с обоих краев, если они есть
	if ( $page - 2 > 0 )
		$page2left = '<li class="waves-effect"><a href="'.$url.$sep.'page='.($page - 2).'">'.($page - 2).'</a></li>';
	else
		$page2left = '';
	if ( $page - 1 > 0 )
		$page1left = '<li class="waves-effect"><a href="'.$url.$sep.'page='.($page - 1).'">'.($page - 1).'</a></li>';
	else
		$page1left = '';
	if ( $page + 2 <= $cntPages )
		$page2right = '<li class="waves-effect"><a href="'.$url.$sep.'page='.($page + 2).'">'.($page + 2).'</a></li>';
	else
		$page2right = '';
	if ( $page + 1 <= $cntPages )
		$page1right = '<li class="waves-effect"><a href="'.$url.$sep.'page='.($page + 1).'">'.($page + 1).'</a></li>';
	else
		$page1right = '';

	// Выводим меню
	$html = '<ul class="pagination">'.
                $startpage.$page2left.$page1left.
                '<li class="active"><a>'.$page.'</a></li>'.
                $page1right.$page2right.$endpage.
            '</ul>';
          
          
          
    return $html;
}


?>