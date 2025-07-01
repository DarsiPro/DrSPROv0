<?php
/**
 * Менеджер шаблонов для DarsiPro CMS
 * 
 * @project    DarsiPro CMS
 * @package    VpsViewer class
 * @url        https://darsi.pro
 * @version    1.1
 * @author     Петров Евгений <email@mail.ru>
 */

class Viewer_Manager
{
    /**
     * @var Viewer_Loader Загрузчик парсеров и ресурсов
     */
    protected $loader;

    /**
     * @var string Название текущего макета (layout)
     */
    protected $layout = 'default';

    /**
     * @var object Парсер токенов
     */
    protected $tokensParser;

    /**
     * @var object Парсер деревьев
     */
    protected $treesParser;

    /**
     * @var object Парсер компиляции
     */
    protected $compileParser;

    /**
     * @var array Дерево узлов шаблона
     */
    protected $nodesTree;

    /**
     * @var array Данные маркеров для замены в шаблоне
     */
    private $markersData = array();

    /**
     * Конструктор класса
     * 
     * @param array $loader Конфигурация загрузчика
     */
    public function __construct($loader = array())
    {
        $this->loader = new Viewer_Loader($loader);
        if (!empty($this->loader->layout)) {
            $this->layout = $this->loader->layout;
        }
    }

    /**
     * Установка макета (layout)
     * 
     * @param string $layout Название макета
     */
    public function setLayout($layout)
    {
        $this->layout = trim($layout);
    }

    /**
     * Рендеринг шаблона
     * 
     * @param string $fileName Имя файла шаблона
     * @param array $context Контекстные данные для шаблона
     * @return string Результат рендеринга
     */
    public function view($fileName, $context = array())
    {
        // Получаем содержимое файла шаблона
        $fileSource = $this->getTemplateFile($fileName, $filePath);
        $cached = false;
        
        // Специальная обработка для main.html
        if ($fileName === 'main.html') {
            // Добавляем базовый CSS с версией для кэширования
            $fileSource = $this->injectBaseCss($fileSource);
            
            // Вставляем панель администратора
            $fileSource = $this->injectAdminBar($fileSource);
        }
        
        // Вызываем событие before_view
        $fileSource = Events::init('before_view', $fileSource, $fileName);
        
        // Парсим шаблон и замеряем производительность
        $start = getMicroTime();
        $data = $this->parseTemplate($fileSource, $context, $filePath, $cached);
        $took = getMicroTime() - $start;
        
        // Записываем информацию для отладки
        DrsDebug::addRow(
            ['Шаблоны', 'Время компиляции', 'Кеширование'],
            [str_replace(ROOT, '', $filePath), $took, ($cached ? 'Из кеша' : 'Скомпилирован')]
        );
        
        return $data;
    }

    /**
     * Вставляет базовый CSS в шаблон в приоритетном порядке:
     * 1. После последнего <meta> в <head>
     * 2. Перед первым <link> в <head>
     * 3. После <title> в <head>
     * 4. Перед закрывающим </head>
     */
    protected function injectBaseCss($html)
    {
        $cssPath = '/.s/scr/base.min.css?' . filemtime(ROOT . '/.s/scr/base.min.css');
        $cssTag = "\n" . '<link rel="stylesheet" href="' . $cssPath . '" />' . "\n";
        
        // 1. Пытаемся вставить после последнего <meta> в <head>
        if (preg_match('/<head[^>]*>.*?(<meta[^>]*>)(?!.*<meta[^>]*>).*?<\/head>/is', $html)) {
            return preg_replace('/(<meta[^>]*>)(?!.*<meta[^>]*>)/is', '$1' . $cssTag, $html, 1);
        }
        // 2. Пытаемся вставить перед первым <link> в <head>
        elseif (preg_match('/<head[^>]*>.*?(<link[^>]*>).*?<\/head>/is', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $linkPos = $matches[1][1];
            $linkTag = $matches[1][0];
            return substr_replace($html, $cssTag . $linkTag, $linkPos, strlen($linkTag));
        }
        // 3. Пытаемся вставить после <title> в <head>
        elseif (preg_match('/<head[^>]*>.*?(<title[^>]*>.*?<\/title>).*?<\/head>/is', $html)) {
            return preg_replace('/(<title[^>]*>.*?<\/title>)/is', '$1' . $cssTag, $html, 1);
        }
        // 4. Вставляем перед закрывающим </head>
        elseif (preg_match('/<\/head>/i', $html)) {
            return preg_replace('/(<\/head>)/i', $cssTag . '$1', $html, 1);
        }
        // 5. Или просто в начало документа (fallback)
        else {
            return $cssTag . $html;
        }
    }

    /**
     * Вставляет панель администратора в шаблон
     * Без проверки существования файла (по требованию)
     */
    protected function injectAdminBar($html)
    {
        $adminBar = "\n" . file_get_contents(ROOT . '/admin/template/AdminBar.php') . "\n";
        
        // Пытаемся вставить после открывающего <body>
        if (preg_match('/(<body[^>]*>)/', $html)) {
            return preg_replace('/(<body[^>]*>)/', '$1' . $adminBar, $html, 1);
        }
        // Или просто в конец документа (fallback)
        else {
            return $html . $adminBar;
        }
    }
    
    

    /**
     * Выполнение скомпилированного кода шаблона
     * 
     * @param string $source Скомпилированный код
     * @param array $context Контекстные данные
     * @return string Результат выполнения
     */
    private function executeSource($source, $context)
    {
        $context = $this->prepareContext($context);
        ob_start();
        eval('?>' . $source);
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Подготовка контекста для шаблона
     * 
     * @param array $context Исходный контекст
     * @return array Обработанный контекст
     */
    public function prepareContext($context)
    {
        $return = Events::init('markers_data', array_merge($this->markersData, $context));
        return $return;
    }

    /**
     * Получение содержимого файла шаблона
     * 
     * @param string $fileName Имя файла шаблона
     * @param string &$returnPath Ссылка для возврата полного пути
     * @return string Содержимое файла
     */
    private function getTemplateFile($fileName, &$returnPath = null)
    {
        $returnPath = $this->getTemplateFilePath($fileName);
        return file_get_contents($returnPath);
    }

    /**
     * Получение полного пути к файлу шаблона
     * 
     * @param string $fileName Имя файла шаблона
     * @return string Полный путь к файлу
     */
    public function getTemplateFilePath($fileName)
    {
        $template = getTemplate();
        $path = ROOT . '/template/' . $template . '/html/%s/' . $fileName;

        // Проверка существования файла в текущем макете
        if (file_exists(sprintf($path, $this->layout))) {
            $path = sprintf($path, $this->layout);
        } else {
            // Использование макета по умолчанию, если файл не найден
            $path = sprintf($path, 'default');
        }

        // Нормализация пути
        $path = preg_replace('#([\\/])+#', '\\1', $path);

        return $path;
    }

    /**
     * Парсинг шаблона
     * 
     * @param string $code Исходный код шаблона
     * @param array $context Контекстные данные
     * @param string $filePath Путь к файлу шаблона (для отладки)
     * @param bool &$cached Флаг использования кеша
     * @return string Результат парсинга
     * @throws Exception В случае ошибки парсинга
     */
    public function parseTemplate($code, $context, $filePath = '', &$cached = false)
    {
        $key = md5($code);

        // Предварительная обработка сниппетов
        $this->loader->snippetsParser->setSource($code);
        $this->loader->snippetsParser->preprocess();

        // Проверка кеша
        if ($this->loader->cache &&
            Config::read('templates_cache') &&
            call_user_func($this->loader->cache['check'], $key)
        ) {
            $sourceCode = call_user_func($this->loader->cache['read'], $key);
            $cached = true;
        } else {
            // Загрузка парсеров, если они еще не загружены
            $this->loader->loadParsers();
            $this->tokensParser = &$this->loader->tokensParser;
            $this->treesParser = &$this->loader->treesParser;
            $this->compileParser = &$this->loader->compileParser;

            try {
                $this->treesParser->cleanStack();
                
                // Разбор токенов
                $tokens = $this->tokensParser->parseTokens($code, $filePath);
                
                // Построение дерева из токенов
                $nodes = $this->treesParser->parse($tokens);
                
                // Компиляция шаблона
                $this->compileParser->clean();
                $this->compileParser->setTmpClassName($this->getTmpClassName($code));
                $this->compileParser->compile($nodes);
                $sourceCode = $this->compileParser->getOutput();
                
            } catch (Exception $e) {
                throw new Exception('Parse template error on '
                    . (!empty($filePath) ? h($filePath) : 'Undefined') . ':'
                    . $e->getCode() . '. ' . $e->getMessage());
            }

            // Сохранение в кеш
            if ($this->loader->cache) {
                call_user_func($this->loader->cache['write'], $sourceCode, $key);
            }
        }

        $output = $this->executeSource($sourceCode, $context);

        // Замена маркеров сниппетов
        $output = $this->loader->snippetsParser->replace($output);

        return $output;
    }

    /**
     * Генерация имени временного класса для шаблона
     * 
     * @param string $code Исходный код шаблона
     * @return string Имя класса
     */
    private function getTmpClassName($code)
    {
        return 'Viewer_Template_' . md5($code . rand());
    }

    /**
     * Установка маркеров для замены в шаблоне
     * 
     * @param array $markers Массив маркеров
     */
    public function setMarkers($markers)
    {
        $this->markersData = array_merge($this->markersData, $markers);
    }

    /**
     * Метод для совместимости со старым API
     * (Заглушка, не выполняет никаких действий)
     */
    public function setModuleTitle()
    {
        // Метод оставлен для обратной совместимости
    }
}