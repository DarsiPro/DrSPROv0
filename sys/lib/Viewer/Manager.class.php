<?php
/**
* @project    DarsiPro CMS
* @package    VpsViewer class
* @url        https://darsi.pro
*/


class Viewer_Manager
{

    protected $loader;

    protected $layout = 'default';

    protected $tokensParser;

    protected $treesParser;

    protected $compileParser;

    protected $nodesTree;

    private $markersData = array();



    public function __construct($loader = array())
    {
        $this->loader = new Viewer_Loader($loader);
        if (!empty($this->loader->layout)) $this->layout = $this->loader->layout;
    }



    public function setLayout($layout)
    {
        $this->layout = trim($layout);
    }



    public function view($fileName, $context = array())
    {
        $filePath = null;
        $cached = false;
        $fileSource = $this->getTemplateFile($fileName, $filePath);
        
        
        
        //print_r($fileName);
        // Добавляется после первого тега <link один раз
        if($fileName == 'main.html'){
            $fileSource = preg_replace('/(<link.*?>)/', '$0<link rel="stylesheet" href="/.s/scr/base.min.css" />', $fileSource, 1);
        }
        
        
        
        
        
        
        
        
        $filePath = str_replace(ROOT, '', $filePath);

        $fileSource = Events::init('before_view', $fileSource, $fileName);

        $start = getMicroTime();
        $data = $this->parseTemplate($fileSource, $context, $filePath, $cached);
        $took = getMicroTime() - $start;

        DrsDebug::addRow(
            array('Templates', 'Compile time', 'Cached'),
            array($filePath, $took, ($cached ? 'From cache' : 'Compiled'))
        );

        return $data;
    }



    private function executeSource($source, $context)
    {
        $context = $this->prepareContext($context);
        ob_start();
        eval('?>' . $source);
        $output = ob_get_clean();
        return $output;
    }



    public function prepareContext($context)
    {
        $return = Events::init('markers_data', array_merge($this->markersData, $context));
        return $return;
    }



    private function getTemplateFile($fileName, &$returnPath = null)
    {
        $returnPath = $this->getTemplateFilePath($fileName);
        return file_get_contents($returnPath);
    }



    public function getTemplateFilePath($fileName)
    {
        $template = getTemplate();
        $path = ROOT . '/template/' . $template . '/html/' . '%s' . '/' . $fileName;

        if (file_exists(sprintf($path, $this->layout)))
            $path = sprintf($path, $this->layout);
        else
            $path = sprintf($path, 'default');

        $path = preg_replace('#([\\/])+#', '\\1', $path);

        return $path;
    }




    public function parseTemplate($code, $context, $filePath = '', &$cached = false)
    {
        $key = md5($code);

        // preprocess snippets
        $this->loader->snippetsParser->setSource($code);
        $this->loader->snippetsParser->preprocess();

        if (
            $this->loader->cache &&
            Config::read('templates_cache') &&
            call_user_func($this->loader->cache['check'], $key)
        ) {
            $sourceCode = call_user_func($this->loader->cache['read'], $key);
            $cached = true;
        } else {
            $this->loader->loadParsers();
            $this->tokensParser = &$this->loader->tokensParser;
            $this->treesParser = &$this->loader->treesParser;
            $this->compileParser = &$this->loader->compileParser;

            try {
                $this->treesParser->cleanStack();
                // Get tokens
                $tokens = $this->tokensParser->parseTokens($code, $filePath);
                // Get tree from tokens
                $nodes = $this->treesParser->parse($tokens);
                $this->compileParser->clean();
                $this->compileParser->setTmpClassName($this->getTmpClassName($code));
                $this->compileParser->compile($nodes);
                $sourceCode = $this->compileParser->getOutput();
                //pr(h($sourceCode)); die();

            } catch (Exception $e) {
                throw new Exception('Parse template error on '
                    . (!empty($filePath) ? h($filePath) : 'Undefined') . ':'
                    . $e->getCode() . '. ' . $e->getMessage());
            }

            call_user_func($this->loader->cache['write'], $sourceCode, $key);
        }

        $output = $this->executeSource($sourceCode, $context);

        // replace snippets markers
        $output = $this->loader->snippetsParser->replace($output);

        return $output;
    }




    private function getTmpClassName($code)
    {
        return 'Viewer_Template_' . md5($code . rand());
    }



    public function setMarkers($markers)
    {
        $this->markersData = array_merge($this->markersData, $markers);
    }



/*
    public function getTokens($code, $filePath = '')
    {
        return $this->tokensParser->parseTokens($code, $filePath);
    }




    public function getTreeFromTokens($tokens)
    {
        return $this->treesParser->parse($tokens);
    }



    public function compile($nodes)
    {
        return $this->compileParser->compile($nodes);
    }*/

    // для совместимости со старым вызовом
    public function setModuleTitle() {
        
    }
}