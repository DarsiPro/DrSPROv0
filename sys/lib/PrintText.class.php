<?php
/**
 * @project     DarsiPro CMS
 * @package     Print library
 * @url         https://darsi.pro
 *
 * This class uses for work with text
 * he can process by smiles, BB codes, cut string for preview, etc...
 */


class PrintText {

    static private $bbCodes = array(
        'left', 'right', 'center', 'list', 'quote', 'spoiler', 'hide',
        'code', 'html', 'xml', 'js', 'php', 'sql', 'css',
        'b', 'i', 's', 'u', 'size', 'url', 'color', 'img'
    );


    /**
     * @param string $str
     * @param array $params - Опции запуска
     * @param int $length - announce length
     * @return string announce
     *
     * create announce and close opened bb tags
     */
    static function getAnnounce($str, $params = null, $length = 500, $material = false) {

        // Формируем опции вызова
        if (empty($params) || !is_array($params))
            $params = array();

        $params = array_merge(array(
            'parse_bbcode' => true,
            'ellipsis' => '...',
            'set_title' => false
        ),$params);


        // Чистим анонс от содержимого блоков с кодом, запоминая код.
        $out = self::codesBlocksParse($str);
        $str = $out[0];
        $CodeBlocks = $out[1];

        // Ищем тег [cut]
        $cut_tag = mb_strpos($str, '[cut]');

        // Ищем теги [announce]
        $start_tag = mb_strpos($str, '[announce]');
        $end_tag = mb_strpos($str, '[/announce]');

        // Если есть тег [cut], анонсом будет текст до тега
        if (false !== $cut_tag) {
            $start = 0;
            $length = $cut_tag;

            if ($length < 1) $length = 500;

        // Если есть тег [announce], анонсом будет его содержимое
        } elseif (false !== $start_tag && false !== $end_tag && $end_tag > $start_tag) {
            // Удаляем теги announce из текста
            $end_tag -= 10;
            $str = mb_substr($str,0,$start_tag).mb_substr($str,$start_tag+10);
            $str = mb_substr($str,0,$end_tag).mb_substr($str,$end_tag+11);

            $start = $start_tag;
            $length = ($end_tag - $start_tag);

        // Иначе берем анонс из начала($start) материала
        } else {
            $start = 0;
            $length = (int)$length;

            if ($length < 1) $length = 500;
        }


        if (is_object($material)) {
            $ustatus = $material->getAuthor() ? $material->getAuthor()->getStatus() : 0;
            $title = $material->getTitle();
        } else {
            $ustatus = $material;
            $title = false;
        }

        // Принудительная установка заголовка
        if ($params['set_title'] !== false && is_string($params['set_title']))
            $title = $params['set_title'];

        // Аккуратно! вырезаем анонс, чтобы не испортить структуру вложенности бб кодов.
        $announce = self::closeOpenTags($str,$start,$length);

        // Добавляем многоточие, если нужно.
        if (strlen($announce) > $length)
            $announce .= $params['ellipsis'];

        // Производим парсинг бб кодов
        if ($params['parse_bbcode'] == true)
            $announce = self::print_page($announce, $ustatus, $title, $CodeBlocks);

        return $announce;
    }


    /**
     * @param string $str
     * @return string with closed tags
     *
     * close opened bb tags
     */
    static function closeOpenTags($str,$start,$length) {

        // Открываем теги, которые были открыты, но не закрылись перед началом вырезанного текста.
        if (!empty($start) && is_numeric($start) && $start > 4)
            $content = self::addTags(self::searchUnclosedTags(self::correctCut($str,0,$start)),true);
        else
            $content = '';

        // Добавляем остальную часть анонса.
        $content .= self::correctCut($str,$start,$length);
        $content .= self::addTags(self::searchUnclosedTags($content));


        return $content;
    }
    /**
     * Вырезает текст для анонса так, чтобы места разреза были между тегами и не резали сами теги.
     */
    static private function correctCut($str,$start,$length) {
        /* Первый разрез */
        if ($start !== 0) {
            // Помещаем место начального разреза между тегами а не в теге.
            $start_pos = mb_strpos($str, '[',$start);
            $end_pos = mb_strpos($str, ']',$start);
            if ($end_pos !== false && ($start_pos === false || $start_pos > $end_pos)) {
                $length += ($end_pos - $start + 1);
                $start = $end_pos+1;
            }
            // Для тегов, содержимое которых обрезать некорректно.(Если такой тег был разрезан, то помещаем место разреза сразу после такого тега.)
            if (preg_match('#\[\/(url|video|img)\]#u', $str , $matches, PREG_OFFSET_CAPTURE , $start) === 1) {
                $close_pos = $matches[1][1] - 2;
                $start_pos = @mb_strpos($str, '[',$start);
                if ($start_pos === $close_pos) {
                    $start = ($start_pos + mb_strlen($matches[1][0]));
                }

            }
            unset($matches);
        }


        /* Второй разрез */
        // Помещаем место конечного разреза между тегами, а не в теге.
        if ($start+$length < mb_strlen($str)) {
            $start_pos = mb_strpos($str, '[',$start+$length);
            $end_pos = mb_strpos($str, ']',$start+$length);
            if ($end_pos !== false && ($start_pos === false || $start_pos > $end_pos)) {
                $length += ($end_pos - $start + 1);
            }
            // Для тегов, содержимое которых обрезать некорректно.(Если такой тег был разрезан, то помещаем место разреза сразу после такого тега.)
            if (preg_match('#\[\/(url|video|img)\]#u', $str , $matches, PREG_OFFSET_CAPTURE , $start+$length) === 1) {
                $close_pos = $matches[1][1] - 2;
                $start_pos = @mb_strpos($str, '[',$start+$length);
                if ($start_pos === $close_pos) {
                    $length = ($start_pos + mb_strlen($matches[1][0]))-$start+1;
                }

            }
        }


        return mb_substr($str,$start,$length);
    }
    /* Ищет в строке незакрытые теги, возвращает массив массивов, где в нулевом индексе массив имен тегов, а в первом индексе массив соответсвующих именам полных имен тегов.(все содержимое между[] для открывающих тегов) */
    /* Второй параметр определяет искать незакрытые или неоткрытые теги. */
    static private function searchUnclosedTags($str,$find_unopened_tags = false) {
        $name_tags = array();
        $full_name_tags = array();

        preg_match_all('#\[(\/|!|)([a-zA-Z0-9]+)(\=[^\]\[\'\"]*|)\]#u', $str, $tags);
        if (!empty($tags[2])) {
            foreach ($tags[2] as $key => $tag_name) {
                /*
                "[{$tags[0][$key]}{$tag_name}{$tag_params}]"

                $tags[0][$key] : равно "/", если тег закрывающий. И пустоте, если открывающий. Или специальному знаку "!", если имя тега с него начинается.
                $tag_name      : имя тега. может состоять из любых латинских букв и цифр.
                $tag_params    : если и существует, то всегда начинается с "=". Не может содержать символы: ",',],[
                */
                if (in_array($tag_name,self::$bbCodes)) {// Если тег определен для интерпретирования парсером
                    if (($tags[1][$key] == '/' && $find_unopened_tags === false) || ($tags[1][$key] != '/' && $find_unopened_tags === true)) {
                        // Нашли открывающий($find_unopened_tags == true) или закрывающий($find_unopened_tags == false) тег и удалили его из списков неполноценных тегов
                        $key = array_search($tag_name,array_reverse($name_tags,true));// Ведем поиск открывающего тега с тем же имененем с конца списка.
                        if ($key !== false) {
                            unset($name_tags[$key]);
                            unset($full_name_tags[$key]);
                        }
                    } else {
                        // Нашли закрывающий($find_unopened_tags == true) или открывающий($find_unopened_tags == false) тег
                        $tag_params = ($tags[3][$key]) ? $tags[3][$key] : '';
                        $name_tags[] = $tag_name;
                        $full_name_tags[] = ($tags[1][$key] ? $tags[1][$key] : '').$tag_name.$tag_params;
                    }
                }
            }
        }

        return array($name_tags,$full_name_tags);
    }
    // Принимает массив тегов, возвращает строку закрывающих($open=false) или открывающих ($open=true) тегов
    static private function addTags($tags,$open=false) {

        $add_str = '';
        if ($open) {
            foreach ($tags[1] as $n => $full_tag) {
                $add_str .= "[{$full_tag}]";
            }
        } else {
            foreach (array_reverse($tags[0],true) as $n => $tag) {
                $add_str .= "[/{$tag}]";
            }
        }
        return $add_str;
    }

    /**
     * Pareseand return user signature
     *
     * @param stirng $str
     * @param int $uid - group id user
     * @return string
     */
    static function getSignature($str, $uid) {
        $str = htmlspecialchars($str);
        $str = nl2br($str);

        if (ACL::turn(array('__bbcodes__', 'bb_s','groups'), false, $uid))
            $str = self::parseSBb($str);
        if (ACL::turn(array('__bbcodes__', 'bb_u','groups'), false, $uid))
            $str = self::parseUBb($str);
        if (ACL::turn(array('__bbcodes__', 'bb_b','groups'), false, $uid))
            $str = self::parseBBb($str);
        if (ACL::turn(array('__bbcodes__', 'bb_i','groups'), false, $uid))
            $str = self::parseIBb($str);
        if (ACL::turn(array('__bbcodes__', 'bb_img','groups'), false, $uid))
            $str = self::parseImgBb($str);
        if (ACL::turn(array('__bbcodes__', 'bb_url','groups'), false, $uid))
            $str = self::parseUrlBb($str, $uid);

        return $str;
    }







    /**
     * @param string $message
     * @param int $ustatus
     * @param string $title
     * @return string
     *
     * bb code process
     */
    static function print_page($message, $ustatus = false, $title = false, $CodeBlocks = false, $ans = false) {

        // hook (for plugins)
        $message = Events::init('before_parse_text', $message, $ans);
        $message = Events::init('before_print_page', $message); // old

        // Разрезаем слишком длинные слова
        //$message = wordwrap($message, 70, ' ', 1);
        //$message = preg_replace("#([^\s/\]\[]{100})#ui", "\\1 ", $message);

        // Извлекаем содержимое из блоков кода, чтобы остальные бб коды не действовали на них
        if ($CodeBlocks == false || empty($CodeBlocks) || !is_array($CodeBlocks)) {
            $out = self::codesBlocksParse($message);
            $message = $out[0];
            $CodeBlocks = $out[1];
        }

        // удаление [cut] из текста
        $message = str_replace( '[cut]', '', $message );

        // Announce tags
        $start_tag = mb_strpos($message, '[announce]');
        $end_tag = mb_strpos($message, '[/announce]');
        if (false !== $start_tag && false !== $end_tag && $end_tag > $start_tag) {
            // Удаляем тег announce из текста, чтобы он не мешал функции self::correctCut()
            $end_tag -= 10;
            $message = mb_substr($message,0,$start_tag).mb_substr($message,$start_tag+10);
            $message = mb_substr($message,0,$end_tag).mb_substr($message,$end_tag+11);
            // Ищем в тексте анонса закрывающие теги, которые не были открыты, но были открыты перед текстом анонса.
            $close_before_announce = self::searchUnclosedTags(self::correctCut($message,$start_tag,($end_tag-$start_tag)),true);
            // Удаляем из списка неотрытых тегов теги, которые не были открыты вообще и дополняем информацией теги, которые всетки были открыты.
            $not_closed_tags = self::searchUnclosedTags(self::correctCut($message,0,$start_tag));
            foreach($close_before_announce[0] as $key => $tag) {
                $skey = array_search($tag,$not_closed_tags[0]);
                if ($skey === false) {
                    unset($close_before_announce[0][$key]);
                    unset($close_before_announce[1][$key]);
                } else // Вот эта операция в принципе не нужна в д.с.( т.к. эти теги будут закрыватся, а не открыватся), но оставлю, чтобы массив был полноценный.
                    $close_before_announce[1][$key] = $not_closed_tags[0][$skey];
            }
            // Финальное вырезание [announce] без потери тегов нужных для форматирования остального материала.
            $message = mb_substr($message,0,$start_tag).self::addTags($close_before_announce).self::addTags(self::searchUnclosedTags(self::correctCut($message,$start_tag,($end_tag-$start_tag))),true).mb_substr($message,$end_tag);
        }

        $spec = false;
        if (!ACL::turn(array('__bbcodes__', 'html','groups'), false, $ustatus)
        || !Config::read('allow_html')) {
            $spec = true;
            $message = htmlspecialchars($message, ENT_NOQUOTES);
            $message = preg_replace( '/&lt;div class=\"(.*)\"&gt;&lt;\/div&gt;/i', '<div class="${1}"></div>', $message );
        }

        if ($ustatus === false or ACL::turn(array('__bbcodes__', 'bb_s','groups'), false, $ustatus))
            $message = self::parseSBb($message);
        if ($ustatus === false or ACL::turn(array('__bbcodes__', 'bb_u','groups'), false, $ustatus))
            $message = self::parseUBb($message);
        if ($ustatus === false or ACL::turn(array('__bbcodes__', 'bb_b','groups'), false, $ustatus))
            $message = self::parseBBb($message);
        if ($ustatus === false or ACL::turn(array('__bbcodes__', 'bb_i','groups'), false, $ustatus))
            $message = self::parseIBb($message);
        if ($ustatus === false or ACL::turn(array('__bbcodes__', 'bb_img','groups'), false, $ustatus))
            $message = self::parseImgBb($message, $title);
        if ($ustatus === false or ACL::turn(array('__bbcodes__', 'bb_url','groups'), false, $ustatus))
            $message = self::parseUrlBb($message, $ustatus);

        $message = preg_replace("#\[quote\](.*)\[\/quote\]#uisU",'<div class="bbQuoteBlock"><div class="bbQuoteName" style=""><b>'.__('Quote').'</b></div><div class="quoteMessage" style="">\\1</div></div>',$message);
        $message = preg_replace("#\[quote=([\s-_0-9a-zа-я]{1,30})\](.*)\[\/quote\]#isuU", '<div class="bbQuoteBlock"><div class="bbQuoteName" style=""><b>\\1 '.__('write').':</b></div><div class="quoteMessage" style="">\\2</div></div>', $message);

        if ($spec)
            $message = preg_replace("#\[quote=&quot;([\s-_ 0-9a-zа-я]{1,30})&quot;\](.+)\[\/quote\]#isuU", '<div class="bbQuoteBlock"><div class="bbQuoteName" style=""><b>\\1 '.__('write').':</b></div><div class="quoteMessage" style="">\\2</div></div>', $message);
        else
            $message = preg_replace("#\[quote=\"([\s-_ 0-9a-zа-я]{1,30})\"\](.+)\[\/quote\]#isuU", '<div class="bbQuoteBlock"><div class="bbQuoteName" style=""><b>\\1 '.__('write').':</b></div><div class="quoteMessage" style="">\\2</div></div>', $message);


        $message = preg_replace("#\[color=red\](.*)\[\/color\]#uisU",'<span style="color:#FF0000">\\1</span>',$message);
        $message = preg_replace("#\[color=green\](.*)\[\/color\]#uisU",'<span style="color:#008000">\\1</span>',$message);
        $message = preg_replace("#\[color=blue\](.*)\[\/color\]#uisU",'<span style="color:#0000FF">\\1</span>',$message);
        $message = preg_replace("#\[color=\#?([0-9a-z]{3,6})\](.*)\[\/color\]#uisU",'<span style="color:#\\1">\\2</span>',$message);


        $message = preg_replace_callback("#\[list\]\s*((?:\[\*\].+)+)\[\/list\]#usiU",'self::getUnorderedList',$message);
        $message = preg_replace_callback("#\[list=([a|1])\]\s*((?:\[\*\].+)+)\[\/list\]#usiU", 'self::getOrderedList',$message);


        $message = preg_replace("#\[size=(10|15|20|25)\](.*)\[/size\]#uisU", '<span style="font-size:\\1px;">\\2</span>', $message); //для поддержки старого формата
        $message = preg_replace("#\[size=(50|85|100|150|200)\](.*)\[/size\]#uisU", '<span style="font-size:\\1%;">\\2</span>', $message);
        $message = preg_replace("#\[center\](.*)\[/center\]#uisU", '<span style="display:block;width:100%;text-align:center;">\\1</span>', $message);
        $message = preg_replace("#\[right\](.*)\[/right\]#uisU", '<span style="display:block;width:100%;text-align:right;">\\1</span>', $message);
        $message = preg_replace("#\[left\](.*)\[/left\]#uisU", '<span style="display:block;width:100%;text-align:left;">\\1</span>', $message);

        $message = preg_replace("#\[spoiler\](.*)\[/spoiler\]#suU", '<div onClick="NextToggle(this)" class="spoiler-open">' . __('Bb-spoiler open') . '</div><div class="spoiler-win">\\1</div>', $message);

        // BB код разделителя <hr/>
        $message = str_replace( '[hr]', '<hr/>', $message );

        $module = Register::getInstance();
        $module = $module['module'];
        if (Config::read('video_size_x',$module))
            $video_size_x = Config::read('video_size_x',$module);
        else
            $video_size_x = Config::read('video_size_x');
        if (Config::read('video_size_y',$module))
            $video_size_y = Config::read('video_size_y',$module);
        else
            $video_size_y = Config::read('video_size_y');

        if ($video_size_x < 1) $video_size_x = 500;
        if ($video_size_y < 1) $video_size_y = 300;

        if (preg_match_all("#\[video\](http://(www\.)*youtu(\.)*be(\.com)*/(watch\?v=)*([\w-]+))\[/video\]#isU", $message, $match)) {
            if (!empty($match[1])) {
                foreach ($match[1] as $key => $url) {
                    $message = str_replace('[video]' . $url . '[/video]',
                    '<iframe src="http://www.youtube.com/embed/'.$match[6][$key].'"'.
                        'width="'.$video_size_x.'" height="'.$video_size_y.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen>'.
                        '</iframe>', $message);
                }
            }
        }
        if (preg_match_all("#\[video\](http://(www\.)*rutube\.ru/video/([\w]+))\[/video\]#isU", $message, $match)) {
            if (!empty($match[1])) {
                foreach ($match[1] as $key => $url) {
                    $message = str_replace('[video]' . $url . '[/video]',
                    '<iframe src="http://www.rutube.ru/video/embed/'.$match[3][$key].'"'.
                        'width="'.$video_size_x.'" height="'.$video_size_y.'" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen>'.
                        '</iframe>', $message);
                }
            }
        }
        if (preg_match_all("#\[video\](http[s]*://vimeo\.com/([\w-]+))\[/video\]#isU", $message, $match)) {
            if (!empty($match[1])) {
                foreach ($match[1] as $key => $url) {
                    $message = str_replace('[video]' . $url . '[/video]',
                    '<iframe src="http://player.vimeo.com/video/'.$match[2][$key].'" '.
                        'width="'.$video_size_x.'" height="'.$video_size_y.'" frameborder="0" '.
                        'webkitAllowFullScreen mozallowfullscreen allowFullScreen>'.
                        '</iframe>', $message);
                }
            }
        }
        if (!empty($_SESSION['user']['name'])) {
            $message = preg_replace("#\[hide\](.*)\[/hide\]#isU", '\\1', $message);
        } else {
            $message = preg_replace("#\[hide\](.*)\[/hide\]#isU", sprintf(__('Guests cant see text'), get_url('/users/add_form/'), get_url('/users/login_form/')), $message);
        }


        //work for smile
        if (Config::read('allow_smiles')) {
            $message = self::smile($message, $spec);
        }

        // все возможные теги, на случай расширения возможностей парсера плагинами
        $block = '(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|p|h[1-6]|hr)';

        $message = preg_replace('|\n*$|', '', $message) ."\n\n"; // just to make things a little easier, pad the end
        $message = preg_replace('|<br />\s*<br />|', "\n\n", $message);
        $message = preg_replace('!(<'. $block .'[^>]*>)!', "\n$1", $message); // Space things out a little
        $message = preg_replace('!(</'. $block .'>)!', "$1\n\n", $message); // Space things out a little
        $message = preg_replace("/\n\n+/", "\n\n", $message); // take care of duplicates
        $message = preg_replace('/^\n|\n\s*\n$/', '', $message);
        $message = '<p>'. preg_replace('/\n\s*\n\n?(.)/', "</p>\n<p>$1", $message) ."</p>\n"; // make paragraphs, including one at the end
        $message = preg_replace("|<p>(<li.+?)</p>|", "$1", $message); // problem with nested lists
        $message = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $message);
        $message = str_replace('</blockquote></p>', '</p></blockquote>', $message);
        $message = preg_replace('|<p>\s*</p>\n?|', '', $message); // under certain strange conditions it could create a P of entirely whitespace
        $message = preg_replace('!<p>\s*(</?'. $block .'[^>]*>)!', "$1", $message);
        $message = preg_replace('!(</?'. $block .'[^>]*>)\s*</p>!', "$1", $message);
        $message = nl2br($message); // make line breaks
        $message = preg_replace('!(</?'. $block .'[^>]*>)\s*<br />!', "$1", $message);
        $message = preg_replace('!<br />(\s*</?(?:p|li|div|th|pre|td|ul|ol)>)!', '$1', $message);
        $message = preg_replace('/&([^#])(?![A-Za-z0-9]{1,8};)/', '&amp;$1', $message);


        //return block
        if ( is_array( $CodeBlocks ) and count( $CodeBlocks ) > 1 )
            $message = str_replace( $CodeBlocks['ids'], $CodeBlocks['codes'], $message );

        $message = Events::init('after_parse_text', $message, $ans);

        return $message;
    }


    /**
     * Additonal bb codes
     */
    static function parseSBb($str) {
        return preg_replace("#\[s\](.*)\[\/s\]#isU", '<span style="text-decoration:line-through;">\\1</span>', $str);
    }
    static function parseUBb($str) {
        return preg_replace("#\[u\](.*)\[\/u\]#isU", '<u>\\1</u>', $str);
    }
    static function parseBBb($str) {
        return preg_replace("#\[b\](.*)\[\/b\]#isU", '<b>\\1</b>', $str);
    }
    static function parseIBb($str) {
        return preg_replace("#\[i\](.*)\[\/i\]#isU", '<i>\\1</i>', $str);
    }
    static function parseImgBb($str, $title = false) {

        if (stripos($str, '[img') !== false and stripos($str, '[/img') !== false) {

            $title = (false !== $title) ? h(preg_replace('#[^\w\dА-я ]+#ui', ' ', $title)) : '';

            $module = Register::getInstance();
            $module = $module['module'];

            if (!empty($module) and Config::read('use_local_preview', $module)) {
                $size_x = Config::read('img_size_x', $module);
                $size_y = Config::read('img_size_y', $module);
                $preview = Config::read('use_preview', $module);
            } else {
                $size_x = Config::read('img_size_x');
                $size_y = Config::read('img_size_y');
                $preview = Config::read('use_preview');
            }


            if (preg_match_all("#\[img([lr]*)*( alt=[^\[\]\"\']+)*\][\s]*([^\"'\>\<\(\)]+)[\s]*\[\/img([lr]*)\]#isU", $str, $match)) {
                if (!empty($match[3])) {
                    foreach ($match[3] as $key => $url) {
                        $size = @getimagesize($url);
                        if (isImageFile($url)) {
                            $sizes = ' style="max-width:' . ($size_x > 150 ? $size_x : 150) . 'px; max-height:' . ($size_y > 150 ? $size_y : 150) . 'px;"';
                            if ($preview) {
                                 // Узнаем, а нужно ли превью для изображения
                                list($width, $height, $type, $attr) = $size;
                                if ((!empty($width) and !empty($height)) and ($width < $size_x or $height < $size_y)) {
                                    $preview = 0;
                                    $sizes = '';
                                }
                            }
                            $replace = ($preview ? '<a href="' . $url . '" class="gallery" rel="example_group">' : '') .
                                            '<img %s ' . $sizes . ' src="' . $url . '" />' .
                                            ($preview ? '</a>' : '');

                            // Проверяем ориентацию
                            $align = $match[1][$key];
                            switch ($align) {
                                case 'l':
                                    $params = 'align="left" class="LtextIMG"';
                                    break;
                                case 'r':
                                    $params = 'align="right" class="RtextIMG"';
                                    break;
                                default:
                                    $params = 'class="textIMG"';
                            }

                            // Если указан альтернативный текст
                            $alt = substr($match[2][$key],5);
                            if ($alt)
                                $params .= ' alt="' . $alt . '"';
                            elseif ($title)
                                $params .= ' alt="' . $title . '"';
                            else
                                $params .= ' alt=""';

                            $replace = sprintf($replace, $params);

                            $str = str_replace('[img' . $align . ($alt ? $match[2][$key] . ']' : ']') . $url . '[/img' . $align . ']',$replace, $str);
                        }
                    }
                }
            }
        }
        return $str;
    }
    static function parseUrlBb($str, $groupid) {
        if ((stripos($str, '[url') !== false or stripos($str, '[!url') !== false) && stripos($str, '[/url]') !== false) {

            $hide_method = Config::read('hide_method');
            $ignore_noindex = ($groupid === false or ACL::turn(array('__bbcodes__', 'important_links','groups'), false, $groupid));

			if (preg_match_all("#\[(!)*url\]((http[s]*://)*[\w\d\-_.]*\.\w{2,}[\w\d\s\+\-_\\/.\?=\#@&;%]*)\[\/url\]#iuU", $str, $match)) {
                if (!empty($match[2])) {
                    foreach ($match[2] as $key => $url) {

                        if ($ignore_noindex)
                            $ngindex = $match[1][$key];
                        else
                            $ngindex = '';

                        if ($hide_method != 0 and // необходимо скрывать ссылки
                            empty($_SESSION['user']['id']) and // гость
                            empty($ngindex) and // ссылка без приоритета
                            strpos($match[2][$key], (empty($match[3][$key]) ? 'http://' : $match[3][$key]) . $_SERVER['HTTP_HOST']) !== 0) // на сторонний сайт

                            if ($hide_method == 1)
                                $replace = '<noindex><a href="' . (empty($match[3][$key]) ? 'http://' : '') . $url . '" target="_blank" rel="nofollow">' . $url . '</a></noindex>';
                            else
                                $replace = sprintf(__('Guests cant see links'), get_url('/users/add_form/'), get_url('/users/login_form/'));
                        else
                            $replace = '<a href="' . (empty($match[3][$key]) ? 'http://' : '') . $url . '" target="_blank">' . $url . '</a>';

                        $str = str_replace('[' .  $match[1][$key] . 'url]' . $url . '[/url]', $replace, $str);
                    }
                }
            }

			if (preg_match_all("#\[(!)*url=((http[s]*://)*[\w\d\-_.]*\.\w{2,}[\w\d\s\+\-_\\/.\?=\#@&;%]*)\](.*)\[\/url\]#iuU", $str, $match)) {
                if (!empty($match[2])) {
                    foreach ($match[2] as $key => $url) {

                        if ($ignore_noindex)
                            $ngindex = $match[1][$key];
                        else
                            $ngindex = '';
                        $title = $match[4][$key];

                        if ($hide_method != 0 and // необходимо скрывать ссылки
                            empty($_SESSION['user']['id']) and // гость
                            empty($ngindex) and // ссылка без приоритета
                            strpos($match[2][$key], (empty($match[3][$key]) ? 'http://' : $match[3][$key]) . $_SERVER['HTTP_HOST']) !== 0) // на сторонний сайт

                            if ($hide_method == 1)
                                $replace = '<noindex><a href="' . (empty($match[3][$key]) ? 'http://' : '') . $url . '" target="_blank" rel="nofollow">' . $title . '</a></noindex>';
                            else
                                $replace = sprintf(__('Guests cant see links'), get_url('/users/add_form/'), get_url('/users/login_form/'));
                        else
                            $replace = '<a href="' . (empty($match[3][$key]) ? 'http://' : '') . $url . '" target="_blank">' . $title . '</a>';

                        $str = str_replace('[' .  $match[1][$key] . 'url=' . $url . ']' . $title . '[/url]', $replace, $str);
                    }
                }
            }
        }

        return $str;
    }

    static private function codesBlocksParse($message) {

        $CodeBlocks = array();
        $spaces = array( ' ', "\t" );
        $entities = array( '&nbsp;', '&nbsp;&nbsp;&nbsp;&nbsp;' );

        // устаревший вариант подсвечивания кода
        preg_match_all( "#\[(php|sql|js|css|html|xml)\](.+)\[\/(php|sql|js|css|html|xml)\]#isU", $message, $matches );
        $cnt = count( $matches[0] );
        for ( $i = 0; $i < $cnt; $i++ ) {
            $CodeBlocks['codes'][] = '<pre><code class="bbCodeBlock '.$matches[1][$i].'">'.str_replace($spaces, $entities, h(trim($matches[2][$i]))).'</code></pre>';

            $uniqid = '<div class="code_'.uniqid($i).'"></div>';
            $CodeBlocks['ids'][] = $uniqid;
            $message = str_replace( $matches[0][$i], $uniqid, $message );
        }

        preg_match_all( "#\[code\](.+)\[\/code\]#uisU", $message, $matches );
        $cnt = count( $matches[0] );
        for ( $i = 0; $i < $cnt; $i++ ) {
            $CodeBlocks['codes'][] = '<pre><code class="bbCodeBlock">'.str_replace($spaces, $entities, h(trim($matches[1][$i]))).'</code></pre>';

            $uniqidCode = '<div class="code_'.uniqid($i).'"></div>';
            $CodeBlocks['ids'][] = $uniqidCode;
            $message = str_replace( $matches[0][$i], $uniqidCode, $message );
        }

        preg_match_all( "#\[code=([A-Za-z0-9]*)\](.+)\[\/code\]#isU", $message, $matches );
        $cnt = count( $matches[0] );
        for ( $i = 0; $i < $cnt; $i++ ) {
            $CodeBlocks['codes'][] = '<pre><code class="bbCodeBlock '.$matches[1][$i].'">'.str_replace($spaces, $entities, h(trim($matches[2][$i]))).'</code></pre>';

            $uniqidCode = '<div class="code_'.uniqid($i).'"></div>';
            $CodeBlocks['ids'][] = $uniqidCode;
            $message = str_replace( $matches[0][$i], $uniqidCode, $message );
        }

        return array($message,$CodeBlocks);

    }



    /**
     * @param string $str
     * @return string
     *
     * smiles process
     */
    static function smile($str, $spec = false) {
        $str = Events::init('before_smiles_parse', $str);

        $smset = Config::read('smiles_set');
        $path = ROOT . '/data/img/smiles/' . (!empty($smset) ? $smset : 'drspro') . '/info.php';

        include $path;

        $from = array();
        $to = array();
        $start_chars = array("\t", "\r", "\n", '>');
        if (isset($smilesList) && is_array($smilesList)) {
            foreach ($smilesList as $smile) {
                $from_str = ($spec ? htmlspecialchars($smile['from']) : $smile['from']);
                if (strpos($str, $from_str) === 0) $str = ' ' . $str;
                foreach ($start_chars as $char) {
                    $str = str_replace($char . $from_str, $char . ' ' . $from_str, $str);
                }

                $from[] = $from_str;
                $to[] = '<img alt="' . $from_str . '" title="' . $from_str . '" src="' . WWW_ROOT . '/data/img/smiles/' . $smset . '/' . $smile['to'] . '" />';
            }
        }
        $str = str_replace($from, $to, $str);

        return $str;
    }
    
    
    
    
    static private function getUnorderedList( $matches )
    {
        $list = '<ul>';
        $tmp = trim( $matches[1] );
        $tmp = substr( $tmp, 3 );
        $tmpArray = explode( '[*]', $tmp );
        $elements = '';
        foreach ( $tmpArray as $value ) {
            $elements = $elements.'<li>'.trim($value).'</li>';
        }
        $list = $list.$elements;
        $list = $list.'</ul>';
        return $list;
    }

    static private function getOrderedList( $matches )
    {
        if ( $matches[1] == '1' )
            $list = '<ol type="1">';
        else
            $list = '<ol type="a">';
        $tmp = trim( $matches[2] );
        $tmp = substr( $tmp, 3 );
        $tmpArray = explode( '[*]', $tmp );

        $elements = '';
        foreach ( $tmpArray as $value ) {
            $elements = $elements.'<li>'.trim($value).'</li>';
        }
        $list = $list.$elements;
        $list = $list.'</ol>';
        return $list;
    }





}



