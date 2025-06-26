<?php
/**
* @project    DarsiPro CMS
* @package    RSS Generator
* @url        https://darsi.pro
*/



// Для красивого отображения в браузере
// необычные отступы реализованы с той же целью
header('Content-Type: application/xml; charset=utf-8');


if (!isset($options)) $options = array();
$def_options = array(
    "model_instance" => ucfirst($this->module),
    "bind_tables" => array('author','categories','attaches'),
    "query_params" => array(
        'limit' => Config::read('rss_cnt', '__rss__'),
        'order' => 'date DESC',
    ),
    "field_lastBuildDate" => function($records) {
        return strtotime($records[0]->getDate());
    },
    "fields_item" => array(
        "title" => function($record) {return $record->getTitle();},
        "description" => function($record) {
            $announce = PrintText::getAnnounce($record->getMain(), '', Config::read('rss_lenght', '__rss__'), $record);
            $atattaches = ($record->getAttaches() && count($record->getAttaches())) ? $record->getAttaches() : array();
            if (count($atattaches) > 0) {
                foreach ($atattaches as $attach) {
                    if ($attach->getIs_image() == '1') {
                        $announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number(), $record->getSkey());
                    }
                }
            }
            return $announce;
        },
        "author" => function($record) {return $record->getAuthor()->getName();},
        "link" => function($record, $sitename) {return $sitename . get_url(entryUrl($record, $this->module));},
        "guid" => function($record, $sitename) {return $sitename . get_url(entryUrl($record, $this->module));},
        "pubDate" => function($record) {return strtotime($record->getDate());},
        "category" => function($record) {
            if (count($record->getCategories()) > 0)
                return $record->getCategories()[0]->getTitle();
            else
                return null;
        },
        "enclosure" => function($record, $sitename) {
            $images = array();

            $atattaches = ($record->getAttaches() && count($record->getAttaches())) ? $record->getAttaches() : array();
            if (count($atattaches) > 0) {
                
                foreach ($atattaches as $attach) {
                    if ($attach->getIs_image() == '1') {
                        $images[] = array(
                            "url" => $sitename.get_url($this->getImagesPath($attach->getFilename())),
                            "type" => 'image/'.substr(strrchr($attach->getFilename(), "."), 1),
                            //"width" => $size_x,
                            //"height" => $size_y
                        );
                        break;
                    }
                }
            }
            return $images;
        }
    )
);

$options = array_merge($def_options,$options);
if (!empty($options)) {
    $options["query_params"] = array_merge($def_options["query_params"],$options["query_params"]);
    $options["fields_item"] = array_merge($def_options["fields_item"],$options["fields_item"]);
}


$cache_key = $this->module . '_rss';
$cache_tags = array(
    'module_' . $this->module,
    'action_rss',
);

$check = Config::read('rss_' . $this->module, '__rss__');
if (!$check) {
    $html = '<?xml version="1.0" encoding="UTF-8"?>';
    $html .= '<rss version="2.0" />';
} else {
    if (!isset($this->Cache)) $this->Cache = new Cache;
    if ($this->cached && $this->Cache->check($cache_key)) {
        $html = $this->Cache->read($cache_key);
    } else {
        $sitename = '';
        if (!empty($_SERVER['SERVER_NAME'])) {
            $sitename = (used_https() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '';
        }

        $html = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>' . h(Config::read('title', $this->module)) . '</title>
        <link rel="self">' . $sitename . get_url($this->getModuleURL()) . '</link>
        <description>' . h(Config::read('description', $this->module)) . '</description>
        <pubDate>' . date('r') . '</pubDate>
        <generator>RSS Generator (DarsiPro CMS)</generator>';


        $Model = OrmManager::getModelInstance($options["model_instance"]);
        foreach($options["bind_tables"] as $table) {
            $Model->bindModel($table);
        }

        $records = $Model->getCollection(array(),$options["query_params"]);


        if (!empty($records) && is_array($records) && count($records)) {

            $html .= '
        <lastBuildDate>' . date('r', $options["field_lastBuildDate"]($records)) . '</lastBuildDate>';

            foreach ($records as $record) {

                $html .= '
        <item>';

                $html .= '
            <title>' . h($options["fields_item"]["title"]($record)) . '</title>
            <link>' . ($link = $options["fields_item"]["link"]($record, $sitename)) . '</link>';
                
                $cat = $options["fields_item"]["category"]($record);
                if (!empty($cat)) {
                    $html .= '
            <category>' . $cat . '</category>';
                }

                foreach($options["fields_item"]["enclosure"]($record, $sitename) as $enclosure) {
                    $html .= '
            <enclosure
                url="'.$enclosure['url'].'"
                type="'.$enclosure['type'].'"
                '.( isset($enclosure['width']) ? 'width="'.$enclosure['width'].'"' : '').
                ( isset($enclosure['height']) ? ' height="'.$enclosure['height'].'"' : '').
                ( isset($enclosure['length']) ? ' length="'.$enclosure['length'].'"' : '').'
            />';
                }

                $html .= '
            <description><![CDATA[' . $options["fields_item"]["description"]($record) . ']]></description>
            <pubDate>' . date('r', $options["fields_item"]["pubDate"]($record)) . '</pubDate>';
                
                $guid = $options["fields_item"]["guid"]($record, $sitename);
                if (!empty($guid)) {
                    $html .= '
            <guid>' . $guid . '</guid>';
                } else {
                    $html .= '
            <guid>' . $link . '</guid>';
                }

                $html .= '
        </item>';
            }
        }
        $html .= '
    </channel>
</rss>';

        $this->Cache->write($html, $cache_key, $cache_tags);
    }
}

echo $html;

?>