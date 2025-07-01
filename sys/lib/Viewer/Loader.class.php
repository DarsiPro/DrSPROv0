<?php
/**
 * Загрузчик и обработчик шаблонов для DarsiPro CMS
 * 
 * @project    DarsiPro CMS
 * @package    VpsViewer
 * @url        https://darsi.pro
 * @version    1.0
 * @author     Петров Евгений <email@mail.ru>
 */

/**
 * Класс Viewer_Loader - ядро системы шаблонов
 * 
 * Отвечает за:
 * - Загрузку и инициализацию парсеров шаблонов
 * - Управление кэшированием скомпилированных шаблонов
 * - Обработку сниппетов и плейсхолдеров
 * - Управление макетами (layouts)
 */
class Viewer_Loader
{
    /**
     * Текущий используемый макет (layout)
     * @var string
     */
    public $layout;

    /**
     * Модель страниц для обработки плейсхолдеров [~ID~]
     * @var object|null
     */
    public $pagesModel;

    /**
     * Парсер сниппетов (вставок кода)
     * @var object
     */
    public $snippetsParser;

    /**
     * Корневая директория шаблонов по умолчанию
     * @var string
     */
    public $rootDir = 'default';

    /**
     * Конфигурация кэширования шаблонов
     * @var array|false
     */
    public $cache = false;

    /**
     * Парсер токенов
     * @var Viewer_TokensParser|null
     */
    public $tokensParser = null;

    /**
     * Парсер древовидной структуры
     * @var Viewer_TreesParser|null
     */
    public $treesParser = null;

    /**
     * Компилятор шаблонов
     * @var Viewer_CompileParser|null
     */
    public $compileParser = null;

    /**
     * Конструктор класса
     * @param array $params Параметры инициализации
     */
    public function __construct(array $params = array())
    {
        // Установка макета
        $this->layout = isset($params['layout']) ? $params['layout'] : 'default';
        
        // Инициализация парсера сниппетов
        $this->snippetsParser = isset($params['snippets_object']) 
            ? $params['snippets_object'] 
            : Register::getClass('DrsSnippets');
        
        if (!$this->snippetsParser) {
            throw new RuntimeException('Snippets parser not available');
        }
        
        // Установка корневой директории
        $this->rootDir = isset($params['root_dir']) ? $params['root_dir'] : 'default';

        // Получение модели страниц
        $this->pagesModel = OrmManager::getModelInstance('pages');

        // Инициализация кэширования
        if (!isset($params['disable_cache']) || !$params['disable_cache']) {
            $this->initCache();
        }
    }

    /**
     * Инициализация системы кэширования
     */
    protected function initCache()
    {
        try {
            $cache = new Cache;
            $cache->prefix = 'template';
            $cache->cacheDir = R.'sys/cache/templates/';
            $cache->lifeTime = 86400;
            
            $this->cache = array(
                'check' => array($cache, 'check'),
                'read'  => array($cache, 'read'),
                'write' => array($cache, 'write'),
            );
        } catch (Exception $e) {
            $this->cache = false;
            error_log('Template cache init failed: ' . $e->getMessage());
        }
    }

    /**
     * Загрузка парсеров шаблонов
     */
    public function loadParsers()
    {
        if ($this->tokensParser === null) {
            $this->tokensParser = new Viewer_TokensParser($this);
        }
        
        if ($this->treesParser === null) {
            $this->treesParser = new Viewer_TreesParser($this);
        }
        
        if ($this->compileParser === null) {
            $this->compileParser = new Viewer_CompileParser($this);
        }
    }

    /**
     * Очистка ресурсов парсеров
     */
    public function clearParsers()
    {
        $this->tokensParser = null;
        $this->treesParser = null;
        $this->compileParser = null;
    }
}