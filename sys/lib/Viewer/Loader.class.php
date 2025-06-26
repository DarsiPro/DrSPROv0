<?php
/**
* @project    DarsiPro CMS
* @package    VpsViewer
* @url        https://darsi.pro
*/


/**
 * Class Viewer_Loader
 */
class Viewer_Loader
{
    /**
     * If isn't set, will be used "default" dir in current template.
     * Example: /template/current/html/MODULE/filename.html
     *
     * @var string
     */
    public $layout;

    /**
     * Used for change "[~ ID ~]" to URLs.
     * If isn't set, "[~ ID ~]" won't changed.
     *
     * @var object
     */
    public $pagesModel;

    /**
     * Used for parse snippets.
     * If isn't set, snippets won't parsed
     *
     * @var object
     */
    public $snippetsParser;

    public $rootDir = 'default';

    public $cache = false;


    public function __construct(array $params = array())
    {
        $this->layout = (!empty($params['layout'])) ? $params['layout'] : 'default';
        $this->snippetsParser = (!empty($params['snippets_object'])) ? $params['snippets_object'] : Register::getClass('DrsSnippets');
        $this->rootDir = (isset($params['root_dir'])) ? $params['root_dir'] : 'default';

        $this->pagesModel = OrmManager::getModelInstance('pages');

        // Cache
        $cache = new Cache;
        $cache->prefix = 'template';
        $cache->cacheDir = R.'sys/cache/templates/';
        $cache->lifeTime = 86400;
        $this->cache = array(
            'check' => array($cache, 'check'),
            'read' => array($cache, 'read'),
            'write' => array($cache, 'write'),
        );
    }

    public function loadParsers() {
        if (!property_exists($this,'tokensParser'))
            $this->tokensParser = new Viewer_TokensParser($this);
        if (!property_exists($this,'treesParser'))
            $this->treesParser = new Viewer_TreesParser($this);
        if (!property_exists($this,'compileParser'))
            $this->compileParser = new Viewer_CompileParser($this);
    }
}