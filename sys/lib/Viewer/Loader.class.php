<?php
/**
 * @project    DarsiPro CMS
 * @package    VpsViewer
 * @url        https://darsi.pro
 * @version    PHP 5.6+
 */

/**
 * Класс Viewer_Loader - загрузчик и обработчик шаблонов
 * 
 * Отвечает за загрузку шаблонов, их кэширование и подключение парсеров
 */
class Viewer_Loader
{
    /**
     * Имя используемого макета (layout)
     * Если не задан, будет использована папка "default" в текущем шаблоне
     * Пример: /template/current/html/MODULE/filename.html
     *
     * @var string
     */
    public $layout;

    /**
     * Модель страниц для замены плейсхолдеров "[~ ID ~]" на URL
     * Если не задана, плейсхолдеры "[~ ID ~]" не будут заменяться
     *
     * @var object
     */
    public $pagesModel;

    /**
     * Парсер сниппетов (кусочков кода)
     * Если не задан, сниппеты не будут обрабатываться
     *
     * @var object
     */
    public $snippetsParser;

    /**
     * Корневая директория для шаблонов по умолчанию
     * 
     * @var string
     */
    public $rootDir = 'default';

    /**
     * Настройки кэширования шаблонов
     * 
     * @var array|false Массив с функциями кэширования или false, если кэш отключен
     */
    public $cache = false;

    /**
     * Конструктор класса
     *
     * @param array $params Массив параметров для инициализации:
     *   - layout: имя макета (по умолчанию 'default')
     *   - snippets_object: объект парсера сниппетов
     *   - root_dir: корневая директория шаблонов
     */
    public function __construct(array $params = array())
    {
        // Установка макета
        $this->layout = (!empty($params['layout'])) ? $params['layout'] : 'default';
        
        // Установка парсера сниппетов (если передан) или получение из реестра
        $this->snippetsParser = (!empty($params['snippets_object'])) 
            ? $params['snippets_object'] 
            : Register::getClass('DrsSnippets');
            
        // Установка корневой директории
        $this->rootDir = (isset($params['root_dir'])) ? $params['root_dir'] : 'default';

        // Получение модели страниц
        $this->pagesModel = OrmManager::getModelInstance('pages');

        // Инициализация кэширования
        $this->initCache();
    }

    /**
     * Инициализация системы кэширования шаблонов
     * 
     * @return void
     */
    protected function initCache()
    {
        $cache = new Cache;
        $cache->prefix = 'template';
        $cache->cacheDir = R.'sys/cache/templates/';
        $cache->lifeTime = 86400; // 24 часа
        
        $this->cache = array(
            'check' => array($cache, 'check'),  // Функция проверки наличия кэша
            'read' => array($cache, 'read'),     // Функция чтения из кэша
            'write' => array($cache, 'write'),   // Функция записи в кэш
        );
    }

    /**
     * Загрузка и инициализация парсеров шаблонов
     * 
     * Метод проверяет наличие парсеров и при необходимости создает их экземпляры
     * Поддерживаемые парсеры:
     * - tokensParser: парсер токенов
     * - treesParser: парсер древовидных структур
     * - compileParser: компилятор шаблонов
     * 
     * @return void
     */
    public function loadParsers() 
    {
        // Инициализация парсера токенов, если еще не создан
        if (!property_exists($this, 'tokensParser')) {
            $this->tokensParser = new Viewer_TokensParser($this);
        }
        
        // Инициализация парсера древовидных структур, если еще не создан
        if (!property_exists($this, 'treesParser')) {
            $this->treesParser = new Viewer_TreesParser($this);
        }
        
        // Инициализация компилятора шаблонов, если еще не создан
        if (!property_exists($this, 'compileParser')) {
            $this->compileParser = new Viewer_CompileParser($this);
        }
    }
}