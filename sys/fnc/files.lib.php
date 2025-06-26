<?php
/**
* @project    DarsiPro CMS
* @package    Files library
* @url        https://darsi.pro
*/



/**
 * Check attached files
 *
 * @param string $module
 * @param bool $onlyimg (если разрешено загружать только изображения)
 */

function checkAttaches($module, $onlyimg = null) {
    $error = null;

    // Получаем максимальное количество допустимых прикреплений
    $max_attach = Config::read('max_attaches', $module);
    if (empty($max_attach) || !is_numeric($max_attach)) $max_attach = 5;

    // Получаем максимально возможный размер файла
    $max_attach_size = Config::read('max_attaches_size', $module);
    if (empty($max_attach_size) || !is_numeric($max_attach_size)) $max_attach_size = 1048576;

    // Если настройка не задана принудительно, то получаем её сами
    if (empty($onlyimg))
        $onlyimg = intval(Config::read('onlyimg_attaches', $module));

    for ($i = 1; $i <= $max_attach; $i++) {
            // Формируем имя формы с файлом
            $attach_name = 'attach' . $i;
            // Находим прикрепленный файл
            if (!empty($_FILES[$attach_name]['name'])) {
                $name = $_FILES[$attach_name]['name'];
                // Если точку не находим, то возвращаем запрет на такой файл.
                if (!strrpos($name, '.', 0))
                    $error .= '<li>' . sprintf(__('Wrong file format'), '(' . $name . ')') .'</li>' . "\n";
                // Проверяем файл на максимальный размер
                if ($_FILES[$attach_name]['size'] > $max_attach_size)
                    $error .= '<li>' . sprintf(__('Very big file'), $name, getSimpleFileSize($max_attach_size)) . '</li>' . "\n";
                // Если разрешено загружать только изображения проверяем и это
                if ($onlyimg and !isImageFile($_FILES[$attach_name]))
                    $error .= '<li>' . sprintf(__('Wrong file format'), '(' . $name . ')') .'</li>' . "\n";
            }
    }
    return $error;
}



 /**
 * Download attached files
 *
 * @param string $module
 * @param int $entity_id(id material or id post)
 * @param bool $unlink удалять ли прикрепления, если нужно
 */
function downloadAttaches($module, $entity_id, $unlink = false) {

    $error = null;
    if (empty($entity_id) || !is_numeric($entity_id)) return '<li>' . __('Some error occurred') . '</li>' . "\n";
    // delete collizions if exists
    //$this->deleteCollizions(array('id' => $post_id), true);

    // Получаем максимальное количество допустимых прикреплений
    $max_attach = Config::read('max_attaches', $module);
    if (empty($max_attach) || !is_numeric($max_attach)) $max_attach = 5;

    // Получаем максимально возможный размер файла
    $max_attach_size = Config::read('max_attaches_size', $module);
    if (empty($max_attach_size) || !is_numeric($max_attach_size)) $max_attach_size = 1048576;

    for ($i = 1; $i <= $max_attach; $i++) {

        // Формируем имя формы с файлом
        $attach_name = 'attach' . $i;

        // при редактировании, удаляет замененные файлы или у которых стоит глочка "удалить".
        if ($unlink and (!empty($_POST['unlink' . $i]) or !empty($_FILES[$attach_name]['name'])))
            deleteAttach($module, $entity_id, $i);

        // Находим прикрепленный файл
        if (!empty($_FILES[$attach_name]['name'])) {
            
            $files_dir = ROOT . '/data/files/' . $module . '/';
            
            // Формируем имя файла
            $filename = getSecureFilename($_FILES[$attach_name]['name'], $entity_id, $i);

            // Узнаем, изображение ли мы загружаем
            $is_image = isImageFile($_FILES[$attach_name]) ? 1 : 0;
            if ($is_image)
                $files_dir = ROOT . '/data/images/' . $module . '/';

            // Перемещаем файл из временной директории сервера в директорию files
            if (!file_exists($files_dir)) mkdir($files_dir,0766);
            if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], $files_dir . $filename)) {
                // Если изображение, накладываем ватермарк
                if ($is_image) {
                    $watermark_path = ROOT . '/data/img/' . (Config::read('watermark_type') == '1' ? 'watermark_text.png' : Config::read('watermark_img'));
                    if (Config::read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
                        $waterObj = new DrsImg;
                        $save_path = $files_dir . $filename;
                        $waterObj->createWaterMark($save_path, $watermark_path);
                    }
                }

                // если возможно выставляем доступ к файлу.
                chmod($files_dir . $filename, 0644);

                // Формируем данные о файле
                $attach_file_data = array(
                    'user_id'       => $_SESSION['user']['id'],
                    'attach_number' => $i,
                    'filename'      => $filename,
                    'size'          => $_FILES[$attach_name]['size'],
                    'date'          => new Expr('NOW()')
                );

                if ($is_image)
                    $attach_file_data['is_image'] = $is_image;

                if ($module == 'forum')
                    $attach_file_data['post_id'] = $entity_id;
                else
                    $attach_file_data['entity_id'] = $entity_id;

                // Сохраняем данные о файле
                $className = '\\' . ucfirst($module) . 'Module\\ORM\\' . ucfirst($module) . 'AttachesEntity';
                $entity = new $className($attach_file_data);
                // Если при сохранении в БД произошла ошибка, то выводим сообщение и удаляем файл, чтобы не занимал места.
                if ($entity->save() == NULL) {
                    $error .= '<li>' . sprintf(__('File is not load'), $_FILES[$attach_name]['name']) . '</li>' . "\n";
                    _unlink($files_dir . $filename);
                }

            } else
                $error .= '<li>' . sprintf(__('File is not load'), $_FILES[$attach_name]['name']) . '</li>' . "\n";
        }
    }

    return $error;
}



/**
 * Check and download attached avatar
 */
function downloadAvatar($module, $tmp_key) {
    if (!empty($_FILES['avatar']['name'])) {
        touchDir(ROOT . '/sys/tmp/images/', 0755);

        $path = ROOT . '/sys/tmp/images/' . $tmp_key . '.jpg';
        if (!isImageFile($_FILES['avatar'])) {
            $errors = '<li>' . __('Wrong avatar') . '</li>' . "\n";
            return $errors;
        }
        if ($_FILES['avatar']['size'] > \Config::read('max_avatar_size', $module)) {
            $errors = '<li>' . sprintf(__('Avatar is very big'), getSimpleFileSize(\Config::read('max_avatar_size', $module))) . '</li>' . "\n";
            return $errors;
        }
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
            chmod($path, 0644);
            $DrsImg = new \DrsImg;
            @$sizes = $DrsImg->resampleImage($path, $path, 100);
            if (!$sizes) {
                @unlink($path);
                $errors = '<li>' . __('Some error in avatar') . '</li>' . "\n";
                return $errors;
            }
        } else {
            $errors = '<li>' . __('Some error in avatar') . '</li>' . "\n";
            return $errors;
        }
    }

    return null;
}



 /**
 * Delete attached file
 *
 * @param string $module
 * @param int $entity_id(id material or id post)
 * @param int $attachNum номер прикрепленного файла
 */
function deleteAttach($module, $entity_id, $attachNum) {
    $Register = Register::getInstance();

    $attachModelClass = OrmManager::getModelNameFromModule($module . 'Attaches');
    $attachModel = new $attachModelClass;
    if ($module == 'forum')
        $attaches = $attachModel->getCollection(array(
            'post_id' => $entity_id,
            'attach_number' => $attachNum,
        ), array());
    else
        $attaches = $attachModel->getCollection(array(
            'entity_id' => $entity_id,
            'attach_number' => $attachNum,
        ), array());

    if (count($attaches) > 0 && is_array($attaches)) {
        foreach ($attaches as $attach) {
            if (!empty($attach)) {
                if ($attach->getIs_image())
                    $filePath = ROOT . '/data/images/' . $module . '/' . $attach->getFilename();
                else
                    $filePath = ROOT . '/data/files/' . $module . '/' . $attach->getFilename();
                
                if (file_exists($filePath))
                    _unlink($filePath);
                
                $attach->delete();
            }
        }
    }
    return true;
}


/**
 * Create secure and allowed filename.
 * Check to dublicate;
 *
 * @param string $filename
 * @param string $dirToCheck - dirrectory to check by dublicate
 * @return string
 */
function getSecureFilename($filename, $post_id, $i) {

    // Извлекаем из имени файла расширение
    $ext = strrchr($filename, ".");

    // Если имя файла содержит не только литиницу и: '.', '-', '_' то "очищаем имя от примесей"
    if (preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/" , $filename)==NULL) {
        $drsurl = new DrsUrl;
        $filename = $desurl->translit($filename);
        $filename = strtolower(preg_replace('#[^a-zA-Z\_0-9\.-]#i', '_', $filename));
        $filename = preg_replace('/(_)+/', '_', $filename);
        $filename = preg_replace('/^(_)/', '', $filename);
        $filename = preg_replace('/(_)$/', '', $filename);
    }


    // Формируем название файла
    if (!isPermittedFile($ext)) {
        //если расширение запрещенное, то заменяем его на .txt
        $filename = substr($filename, 0, strrpos($filename, '.', 0));
        $filename = ($filename) ? $filename : 'noname';
        $file = $post_id . '-' . $i . '_' . $filename . '.txt';
    } else
        $file = $post_id . '-' . $i . '_' . $filename;


    return $file;
}

/**
* Выясняет изображение ли файл
*
* param string|array $file - путь до файла либо массив $_FILES[$field_name]
*/
function isImageFile($file) {

    // Types of images
    $__fileslib__allowed_types = array('image/jpeg','image/jpg','image/gif','image/png', 'image/gif', 'image/pjpeg', 'image/tiff', 'image/vnd.microsoft.icon', 'image/x-icon', 'image/bmp', 'image/vnd.wap.wbmp');
    // Images extensions
    $__fileslib__img_extentions = array('.png','.jpg','.gif','.jpeg','.tiff', '.ico', '.bmp', '.wbmp');
    
    if (is_string($file))
        $file = array("tmp_name" => $file);
    
    $file = array_merge(array(
        "name"=> false,
        "tmp_name"=> false,
        "type" => false
    ), $file);
    
    if (empty($file['name']) && !is_string($file['name']))
        $file['name'] = $file['tmp_name'];
    
    // Простейшее отбрасывание файлов, не являющихся изображением
    if(($file['type'] !== false) and (strpos($file['type'],'image') === false)) {
        return false;
    }
    
    // Серьезная проверка "изображение ли"
    // Получение mime-type файла
    $imageinfo = @getimagesize($file['tmp_name']);
    // Извлекаем из имени файла расширение
    $ext = strrchr($file['name'], ".");
    $is_image = isset($imageinfo) && is_array($imageinfo) && in_array($imageinfo['mime'], $__fileslib__allowed_types);
    if (!empty($ext)) $is_image = $is_image && in_array(strtolower($ext), $__fileslib__img_extentions);
    return $is_image;
}

// Проверка на то, что файл является исполняемым(по расширению)
function isPermittedFile($ext) {
    // Wrong extention for download files
    $__fileslib__deny_extentions = array('.php', '.phtml', '.phps', '.phar', '.php3', '.php4', '.php5', '.html', '.htm', '.pl', '.js', '.htaccess', '.run', '.sh', '.bash', '.py');
    return !(empty($ext) || in_array(strtolower($ext), $__fileslib__deny_extentions));
}


function user_download_file($module, $file = null, $mimetype = 'application/octet-stream') {

    $error = null;
    //turn access
    ACL::turnUser(array($module, 'download_files'),true);

    if (empty($file))
        return __('File not found');
    
    $path = ROOT . getFilePath($file, $module);
    
    if (!file_exists($path))
        return __('File not found');
    $from = 0;
    $size = filesize($path);
    $to = $size;
    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('#bytes=-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок от конца файла
            $from = $size - $range[1];
            $to = $size;
        } elseif (preg_match('#bytes=([0-9]*)-#', $_SERVER['HTTP_RANGE'], $range)) {// если указана только начальная метка
            $from = $range[1];
            $to = $size;
        } elseif (preg_match('#bytes=([0-9]*)-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок файла
            $from = $range[1];
            $to = $range[2];
        }
        header('HTTP/1.1 206 Partial Content');

        $cr = 'Content-Range: bytes ' . $from . '-' . $to . '/' . $size;
    } else
        header('HTTP/1.1 200 Ok');

    $etag = md5($path);
    $etag = substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
    header('ETag: "' . $etag . '"');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($to - $from));
    if (isset($cr))
        header($cr);
    header('Connection: close');
    header('Content-Type: ' . $mimetype);
    header('Last-Modified: ' . gmdate('r', filemtime($path)));
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($path)) . " GMT");
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3600) . " GMT");
    $f = fopen($path, 'rb');


    if (preg_match('#^image/#', $mimetype))
        header('Content-Disposition: filename="' . substr($file, strpos($file, '_', 0)+1) . '";');
    else
        header('Content-Disposition: attachment; filename="' . substr($file, strpos($file, '_', 0)+1) . '";');

    fseek($f, $from, SEEK_SET);
    $size = $to;
    $downloaded = 0;
    while (!feof($f) and ($downloaded < $size)) {
        $block = min(1024 * 8, $size - $downloaded);
        echo fread($f, $block);
        $downloaded += $block;
        flush();
    }
    fclose($f);
}

/**
 * Format file size from bytes to K|M|G
 *
 * @param int $size
 * @return string - simple size with letter
 */
function getSimpleFileSize($size) {
    $size = intval($size);
    if (empty($size)) return '0 B';

    if (Config::read('IEC60027-2')==1) {
        $ext = array('B', 'KiB', 'MiB', 'GiB');
    } else {
        $ext = array('B', 'KB', 'MB', 'GB');
    }
    $i = 0;

    while (($size / 1024) > 1) {
        $size = $size / 1024;
        $i++;
    }


    $size = round($size, 2) . ' ' . $ext[$i];
    return $size;
}


/**
 * Similar to copy
 * @Recursive
 */
function copyr($source, $dest)
{
    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }

    // If the source is a symlink
    if (is_link($source)) {
        $link_dest = readlink($source);
        return symlink($link_dest, $dest);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        if ($dest !== "$source/$entry") {
            copyr("$source/$entry", "$dest/$entry");
        }
    }

    // Clean up
    $dir->close();
    return true;
}

/**
 * Find all files in directory
 * @Recursive
 */
function getDirFiles($path) {
    $ret = array();
    $dir_iterator = new RecursiveDirectoryIterator($path);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if($file->isFile()) $ret[] = str_replace(ROOT, '', (string)$file);
    }

    return $ret;
}


function _unlink($path, $ht = false) {
    if(is_file($path)) return unlink($path);

    if(!is_dir($path)) return;
    $dh = opendir($path);
    if ($dh === false) return;
    while (false !== ($file = readdir($dh))) {
        if($file == '.' || $file == '..' || ($file=='.htaccess' && $ht == true)) continue;
        _unlink($path."/".$file);
    }
    closedir($dh);

    return @rmdir($path);
}

// Сохраняет массив данныйх в файл в интерпретируемом формате
function save_export_file($data,$path,$method = 'return') {
    if ($fopen=@fopen($path, 'w')) {

        // Получаем строчное представление массива
        $export = var_export($data, true);
        // Убираем перенос перед array (
        $export = preg_replace('#=>[\s]+array \(#is', '=> array(', $export);
        // Убираем лишний пробел внутри array()
        $export = preg_replace('#array\([\s]+\)#is', 'array()', $export);

        // Записываем в файл
        fputs($fopen, '<?php '."\n".$method.' '.$export."\n".'?>');
        @fclose($fopen);
        return true;
    } else {
        return false;
    }
}



/**
* touch and create dir
*/
function touchDir($path, $chmod = 0777) {
    if (!file_exists($path)) {
        mkdir($path, $chmod, true);
        chmod($path, $chmod);
    }
    return true;
}

// without ROOT or WWW_ROOT
function getFilePath($filename, $module) {
    // Images extensions
    $__fileslib__img_extentions = array('.png','.jpg','.gif','.jpeg','.tiff', '.ico', '.bmp', '.wbmp');

    // Извлекаем из имени файла расширение
    $ext = strrchr($filename, ".");
    if (!empty($ext) && in_array(strtolower($ext), $__fileslib__img_extentions))
        return '/data/images/' . $module . '/' . $filename;
    return '/data/files/' . $module . '/' . $filename;
}
