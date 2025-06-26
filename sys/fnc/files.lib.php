<?php
/**
 * Библиотека для работы с файлами в DarsiPro CMS
 * 
 * Предоставляет функционал для:
 * - Проверки и загрузки вложений
 * - Управления файлами и изображениями
 * - Безопасной обработки загружаемых файлов
 * - Работы с директориями
 * 
 * @project    DarsiPro CMS
 * @package    Files Library
 * @author     Петров Евгений <email@mail.ru>
 * @url        https://darsi.pro
 * @version    1.0
 * @php        5.6+
 */

/**
 * Проверяет прикрепленные файлы на соответствие требованиям
 * 
 * @param string $module Имя модуля
 * @param bool $onlyimg Разрешена загрузка только изображений
 * @return string|null Сообщения об ошибках или null если ошибок нет
 */
function checkAttaches($module, $onlyimg = null) 
{
    $error = null;
    $config = Register::getInstance();
    $config = $config['Config'];

    // Получаем настройки из конфигурации
    $max_attach = (int)$config->read('max_attaches', $module) ? (int)$config->read('max_attaches', $module) : 5;
    $max_attach_size = (int)$config->read('max_attaches_size', $module) ? (int)$config->read('max_attaches_size', $module) : 1048576; // 1MB по умолчанию
    
    if ($onlyimg === null) {
        $onlyimg = (bool)$config->read('onlyimg_attaches', $module);
    }

    for ($i = 1; $i <= $max_attach; $i++) {
        $attach_name = 'attach' . $i;
        
        if (!empty($_FILES[$attach_name]['name'])) {
            $file = $_FILES[$attach_name];
            $name = $file['name'];

            // Проверка расширения файла
            if (strpos($name, '.') === false) {
                $error .= '<li>' . sprintf(__('Wrong file format'), '(' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ')') . '</li>';
                continue;
            }

            // Проверка размера файла
            if ($file['size'] > $max_attach_size) {
                $error .= '<li>' . sprintf(
                    __('Very big file'), 
                    htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), 
                    getSimpleFileSize($max_attach_size)
                ) . '</li>';
            }

            // Проверка типа файла (только изображения)
            if ($onlyimg && !isImageFile($file)) {
                $error .= '<li>' . sprintf(
                    __('Wrong file format'), 
                    '(' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ')'
                ) . '</li>';
            }
        }
    }

    return $error ? $error : null;
}

/**
 * Загружает прикрепленные файлы на сервер
 * 
 * @param string $module Имя модуля
 * @param int $entity_id ID материала или поста
 * @param bool $unlink Удалять ли старые файлы
 * @return string|null Сообщения об ошибках или null если ошибок нет
 */
function downloadAttaches($module, $entity_id, $unlink = false) 
{
    if (empty($entity_id)) {
        return '<li>' . __('Some error occurred') . '</li>';
    }

    $error = null;
    $config = Register::getInstance();
    $config = $config['Config'];
    $max_attach = (int)$config->read('max_attaches', $module) ? (int)$config->read('max_attaches', $module) : 5;

    for ($i = 1; $i <= $max_attach; $i++) {
        $attach_name = 'attach' . $i;

        // Удаление старых файлов при необходимости
        if ($unlink && (!empty($_POST['unlink' . $i]) || !empty($_FILES[$attach_name]['name']))) {
            deleteAttach($module, $entity_id, $i);
        }
        

        // Обработка нового файла
        if (!empty($_FILES[$attach_name]['name'])) {
            $file = $_FILES[$attach_name];
            $is_image = isImageFile($file);
            
            // Определяем директорию для сохранения
            $subdir = $is_image ? 'images' : 'files';
            $files_dir = ROOT . "/data/{$subdir}/{$module}/";
            
            // Создаем директорию если нужно
            if (!file_exists($files_dir)) {
                mkdir($files_dir, 0766, true);
            }

            // Генерируем безопасное имя файла
            $filename = getSecureFilename($file['name'], $entity_id, $i);
            $file_path = $files_dir . $filename;

            // Перемещаем файл
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                chmod($file_path, 0644);

                // Накладываем водяной знак для изображений
                if ($is_image && $config->read('use_watermarks')) {
                    $watermark_path = ROOT . '/data/img/' . (
                        $config->read('watermark_type') == '1' 
                            ? 'watermark_text.png' 
                            : $config->read('watermark_img')
                    );
                    
                    if (file_exists($watermark_path)) {
                        $waterObj = new DrsImg;
                        $waterObj->createWaterMark($file_path, $watermark_path);
                    }
                }

                // Сохраняем информацию о файле в БД
                $attach_data = array(
                    'user_id'       => isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0,
                    'attach_number' => $i,
                    'filename'      => $filename,
                    'size'          => $file['size'],
                    'date'          => new Expr('NOW()'),
                    'is_image'      => $is_image ? 1 : 0
                );

                // Добавляем ID в зависимости от модуля
                $attach_data[$module == 'forum' ? 'post_id' : 'entity_id'] = $entity_id;

                try {
                    $className = '\\' . ucfirst($module) . 'Module\\ORM\\' . ucfirst($module) . 'AttachesEntity';
                    $entity = new $className($attach_data);
                    
                    if (!$entity->save()) {
                        throw new Exception('Failed to save file info');
                    }
                } catch (Exception $e) {
                    $error .= '<li>' . sprintf(__('File is not load'), htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8')) . '</li>';
                    @unlink($file_path);
                }
            } else {
                $error .= '<li>' . sprintf(__('File is not load'), htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8')) . '</li>';
            }
        }
    }

    return $error ? $error : null;
}

/**
 * Загружает аватар пользователя
 */
function downloadAvatar($module, $tmp_key) 
{
    if (empty($_FILES['avatar']['name'])) {
        return null;
    }

    $config = Register::getInstance();
    $config = $config['Config'];
    $avatar = $_FILES['avatar'];
    $errors = null;

    // Проверка типа файла
    if (!isImageFile($avatar)) {
        return '<li>' . __('Wrong avatar') . '</li>';
    }

    // Проверка размера файла
    $max_size = (int)$config->read('max_avatar_size', $module) ? (int)$config->read('max_avatar_size', $module) : 2097152; // 2MB по умолчанию
    if ($avatar['size'] > $max_size) {
        return '<li>' . sprintf(
            __('Avatar is very big'), 
            getSimpleFileSize($max_size)
        ) . '</li>';
    }

    // Подготовка директории
    $tmp_dir = ROOT . '/sys/tmp/images/';
    if (!file_exists($tmp_dir)) {
        mkdir($tmp_dir, 0755, true);
    }

    // Сохранение и обработка аватара
    $path = $tmp_dir . $tmp_key . '.jpg';
    if (move_uploaded_file($avatar['tmp_name'], $path)) {
        chmod($path, 0644);
        
        $DrsImg = new DrsImg;
        if (!$DrsImg->resampleImage($path, $path, 100)) {
            @unlink($path);
            return '<li>' . __('Some error in avatar') . '</li>';
        }
    } else {
        return '<li>' . __('Some error in avatar') . '</li>';
    }

    return null;
}

/**
 * Удаляет прикрепленный файл
 */
function deleteAttach($module, $entity_id, $attachNum) 
{
    $attachModelClass = OrmManager::getModelNameFromModule($module . 'Attaches');
    $attachModel = new $attachModelClass;
    
    $conditions = array(
        'attach_number' => $attachNum,
        ($module == 'forum' ? 'post_id' : 'entity_id') => $entity_id
    );

    $attaches = $attachModel->getCollection($conditions, array());

    foreach ($attaches as $attach) {
        if ($attach) {
            $subdir = $attach->getIs_image() ? 'images' : 'files';
            $filePath = ROOT . "/data/{$subdir}/{$module}/" . $attach->getFilename();
            
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            $attach->delete();
        }
    }

    return true;
}

/**
 * Генерирует безопасное имя файла
 */
function getSecureFilename($filename, $post_id, $i) 
{
    $ext = strrchr($filename, ".");
    
    // Транслитерация и очистка имени файла
    if (!preg_match("/^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*)$/", $filename)) {
        $drsurl = new DrsUrl;
        $filename = $drsurl->translit($filename);
        $filename = strtolower(preg_replace('/[^a-z0-9\.\-_]/i', '_', $filename));
        $filename = preg_replace(array('/_+/', '/^_|_$/'), array('_', ''), $filename);
    }

    // Формирование итогового имени
    $name = substr($filename, 0, strrpos($filename, '.', 0)) ? $filename : 'noname';
    $file = $post_id . '-' . $i . '_' . $name;
    
    return isPermittedFile($ext) ? $file . $ext : $file . '.txt';
}

/**
 * Проверяет, является ли файл изображением
 */
function isImageFile($file) 
{
    static $allowed_types = array(
        'image/jpeg', 'image/jpg', 'image/gif', 'image/png', 
        'image/pjpeg', 'image/tiff', 'image/vnd.microsoft.icon', 
        'image/x-icon', 'image/bmp', 'image/vnd.wap.wbmp'
    );
    
    static $img_extensions = array(
        '.png', '.jpg', '.gif', '.jpeg', 
        '.tiff', '.ico', '.bmp', '.wbmp'
    );

    if (is_string($file)) {
        $file = array('tmp_name' => $file, 'name' => $file);
    }

    $file += array('name' => '', 'tmp_name' => '', 'type' => '');

    // Быстрая проверка по MIME-типу
    if ($file['type'] && strpos($file['type'], 'image') === false) {
        return false;
    }

    // Проверка расширения
    $ext = strtolower(strrchr($file['name'], "."));
    if ($ext && !in_array($ext, $img_extensions)) {
        return false;
    }

    // Глубокая проверка содержимого файла
    $imageinfo = @getimagesize($file['tmp_name']);
    return $imageinfo && in_array($imageinfo['mime'], $allowed_types);
}

/**
 * Проверяет разрешенное ли расширение файла
 */
function isPermittedFile($ext) 
{
    static $deny_extensions = array(
        '.php', '.phtml', '.phps', '.phar', '.php3', '.php4', '.php5', 
        '.html', '.htm', '.pl', '.js', '.htaccess', '.run', '.sh', 
        '.bash', '.py', '.exe', '.bat', '.cmd'
    );
    
    return $ext && !in_array(strtolower($ext), $deny_extensions);
}
 
 /**
 * Скачивание файла пользователем
 * 
 * @param string $module Имя модуля
 * @param string|null $file Имя файла для скачивания
 * @param string $mimetype MIME-тип файла
 * @return string|null Сообщение об ошибке или null при успехе
 */
function user_download_file($module, $file = null, $mimetype = 'application/octet-stream') 
{
    if (empty($file)) {
        return __('File not found');
    }

    // Проверка прав доступа
    ACL::turnUser(array($module, 'download_files'), true);

    $path = ROOT . getFilePath($file, $module);
    
    if (!file_exists($path)) {
        return __('File not found');
    }

    // Подготовка заголовков
    $size = filesize($path);
    $from = 0;
    $to = $size - 1;

    // Обработка HTTP Range запросов
    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $from = (int)$matches[1];
            $to = isset($matches[2]) ? min((int)$matches[2], $size - 1) : $size - 1;
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $from . '-' . $to . '/' . $size);
        }
    }

    // Базовые заголовки
    header('HTTP/1.1 200 OK');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . ($to - $from + 1));
    header('Content-Type: ' . $mimetype);
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    
    // ETag для кэширования
    $etag = md5($path);
    $etag = substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
    header('ETag: "' . $etag . '"');

    // Заголовок Content-Disposition
    $disposition = strpos($mimetype, 'image/') === 0 ? 'inline' : 'attachment';
    $filename = substr($file, strpos($file, '_') + 1);
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');

    // Чтение и отправка файла
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return __('Cannot open file');
    }

    fseek($fh, $from);
    $remaining = $to - $from + 1;
    $chunkSize = 8192;

    while (!feof($fh) && $remaining > 0) {
        $read = min($chunkSize, $remaining);
        echo fread($fh, $read);
        $remaining -= $read;
        flush();
    }

    fclose($fh);
    return null;
}

/**
 * Форматирует размер файла в читаемый вид
 * 
 * @param int $size Размер в байтах
 * @return string Форматированный размер
 */
function getSimpleFileSize($size) 
{
    $size = (int)$size;
    if ($size <= 0) return '0 B';

    $units = Config::read('IEC60027-2') == 1 
        ? array('B', 'KiB', 'MiB', 'GiB') 
        : array('B', 'KB', 'MB', 'GB');

    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }

    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Рекурсивное копирование файлов и директорий
 * 
 * @param string $source Источник
 * @param string $dest Назначение
 * @return bool Результат операции
 */
function copyr($source, $dest) 
{
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    if (is_file($source)) {
        return copy($source, $dest);
    }

    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $dir = dir($source);
    if ($dir === false) return false;

    $result = true;
    while (($entry = $dir->read()) !== false) {
        if ($entry == '.' || $entry == '..') continue;
        
        $sourcePath = $source . DIRECTORY_SEPARATOR . $entry;
        $destPath = $dest . DIRECTORY_SEPARATOR . $entry;
        
        if (!copyr($sourcePath, $destPath)) {
            $result = false;
            break;
        }
    }

    $dir->close();
    return $result;
}

/**
 * Рекурсивное получение списка файлов в директории
 * 
 * @param string $path Путь к директории
 * @return array Массив файлов
 */
function getDirFiles($path) 
{
    $files = array();
    if (!is_dir($path)) return $files;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = str_replace(ROOT, '', $file->getPathname());
        }
    }

    return $files;
}

/**
 * Рекурсивное удаление файлов и директорий
 * 
 * @param string $path Путь для удаления
 * @param bool $preserveHtaccess Сохранять .htaccess
 * @return bool Результат операции
 */
function _unlink($path, $preserveHtaccess = false) 
{
    if (is_file($path)) {
        return unlink($path);
    }

    if (!is_dir($path)) {
        return false;
    }

    $items = scandir($path);
    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {
        if ($item == '.' || $item == '..' || ($item == '.htaccess' && $preserveHtaccess)) {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (!_unlink($itemPath, $preserveHtaccess)) {
            return false;
        }
    }

    return rmdir($path);
}

/**
 * Сохранение данных в файл в виде PHP-кода
 * 
 * @param mixed $data Данные для сохранения
 * @param string $path Путь к файлу
 * @param string $method Метод возврата ('return' или 'export')
 * @return bool Результат операции
 */
function save_export_file($data, $path, $method = 'return') 
{
    $export = var_export($data, true);
    $export = preg_replace(array(
        '/=>\s+array\s\(/',
        '/array\(\s+\)/',
        '/\s+$/m'
    ), array(
        '=> array(',
        'array()',
        ''
    ), $export);

    $content = "<?php\n{$method} {$export}\n?>";
    
    $result = file_put_contents($path, $content, LOCK_EX);
    return $result !== false;
}

/**
 * Создание директории с нужными правами
 * 
 * @param string $path Путь к директории
 * @param int $chmod Права доступа
 * @return bool Результат операции
 */
function touchDir($path, $chmod = 0777) 
{
    if (!file_exists($path)) {
        return mkdir($path, $chmod, true) && chmod($path, $chmod);
    }
    return true;
}

/**
 * Получение пути к файлу в зависимости от его типа
 * 
 * @param string $filename Имя файла
 * @param string $module Имя модуля
 * @return string Относительный путь к файлу
 */
function getFilePath($filename, $module) 
{
    static $imageExtensions = array('.png', '.jpg', '.gif', '.jpeg', '.tiff', '.ico', '.bmp', '.wbmp');
    
    $ext = strtolower(strrchr($filename, '.'));
    $subdir = in_array($ext, $imageExtensions) ? 'images' : 'files';
    
    return "/data/{$subdir}/{$module}/{$filename}";
}