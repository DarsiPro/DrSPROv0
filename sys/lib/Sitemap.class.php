<?php
/**
 * @project     DarsiPro CMS
 * @package     Sitemap Generator
 * @url         https://darsi.pro
 */

if (function_exists('set_time_limit')) @set_time_limit(0);
if (function_exists('ignor_user_abort')) @ignor_user_abort();




class Sitemap {

    public $output;
    private $host;
    private $uniqUrl = array();
    private $DB;



    public function __construct($params = array()) {
        $this->host = $_SERVER['HTTP_HOST'] . '/';
        $this->uniqUrl[] = (used_https() ? 'https://' : 'http://') . $this->host;
        $this->DB = getDB();
    }


    /**
     * Создаёт карту сайта.
     * sitemap.xml должен быть разрешен для индексации поисковыми роботами.
     *
     */
    public function createMap() {
        $this->getLinks();
        $this->finalizeLinks();

        $this->output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n"
            . 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n"
            . 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

            foreach ($this->uniqUrl as $page) {
                if ((substr($page, 0, 7) !== 'http://') and (substr($page, 0, 8) !== 'https://')) $page = (used_https() ? 'https://' : 'http://') . DrsUrl::parseRoutes($page);
                $this->output .= '<url>' . "\n"
                    . '<loc>' . $page . '</loc>' . "\n"
                    . '<changefreq>daily</changefreq>' . "\n"
                    . '</url>' . "\n";
            }

            $this->output .= '</urlset>';

        file_put_contents(ROOT . '/sitemap.xml', $this->output);
    }



    /**
     * Удаление дубликатов и экранирование символов.
     */
    private function finalizeLinks() {
        $entities = array(
            '&' => '&amp;',
            '"' => '&quot;',
            '\'' => '&apos;',
            '<' => '&lt;',
            '>' => '&gt;',
        );

        $this->uniqUrl = array_unique($this->uniqUrl);
        foreach ($this->uniqUrl as $key => $link) {
            $link = trim($link, '/');
            $link = str_replace(array_keys($entities), $entities, $link);
            $this->uniqUrl[$key] = $link;
        }
    }



    /**
     * Построение ссылок на все материалы.
     */
    public function getLinks() {
        // Unique pages (Module pages)
        if (Config::read('active', 'pages')) {
            $htmlpages = $this->DB->select('pages', DB_ALL, array());
            if (count($htmlpages) > 0) {
                foreach ($htmlpages as $htmlpage) {
                    if ($htmlpage['id'] != 1)
                        $this->uniqUrl[] = $this->host . $htmlpage['url'];
                }
            }
        }


        // news, stat, loads, foto
        $hluex = Config::read('hlu_extention');
        $hluactive = Config::read('hlu');
        $drsurl = new DrsUrl;
        foreach (array('news', 'stat', 'loads', 'foto') as $mkey) {
            if (Config::read('active', $mkey)) {
                $entities = $this->DB->select($mkey, DB_ALL, array());

                if (count($entities) > 0) {
                    foreach ($entities as $entity) {
                        $hlufile = $drsurl->searchHluById($entity['id'], $mkey);
                        if ($mkey != 'foto' && $hluactive == 1 && file_exists($hlufile)) {
                            $hlufile = substr(strrchr($hlufile, '/'), 1);
                            $hlufile = explode('.', $hlufile);
                            if ($hlufile[0]) $this->uniqUrl[] = $this->host . $mkey . '/' . $hlufile[0] . $hluex;
                        } else {
                            $this->uniqUrl[] = $this->host . $mkey . '/view/' . $entity['id'];
                        }
                    }
                }
            }
        }


        // forum
        if (Config::read('active', 'forum')) {
            $themes = $this->DB->select('themes', DB_ALL);

            if (count($themes) > 0) {
                foreach ($themes as $theme) {
                    $this->uniqUrl[] = $this->host . 'forum/view_theme/' . $theme['id'];
                }
            }
        }
    }

}




?>