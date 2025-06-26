<?php



// properties for system settings and settings that not linked to module
$settingsInfo = array(
    /* HLU */
    '__seo__' => array(
        'hlu' => array(
            'type' => 'checkbox',
            'title' => __('On HLU'),
            'description' => __('On HLU: info'),
        ),
        'hlu_extention' => array(
            'title' => __('Extention HLU'),
            'description' => __('Extention HLU: info'),
            'onChange' => "if (this.value == '') {this.value = '.htm'}",
        ),
        'hlu_understanding' => array(
            'type' => 'checkbox',
            'title' => __('Hlu understanding'),
            'description' => __('Hlu understanding: info'),
        ),
        'hide_method' => array(
            'type' => 'select',
            'title' => __('Hide method'),
            'description' => __('Hide method: info'),
            'options' => array(
                '0' => __('Hide method: 0'),
                '1' => __('Hide method: 1'),
                '2' => __('Hide method: 2'),
            ),
        ),
    ),


    /* SYS */
    '__sys__' => array(
        'template' => array(
            'type' => 'select',
            'title' => __('Template'),
            'options' => @$templateSelect,
            'attr' => array(
                'id' => 'templateScreen',
                'onChange' => "showScreenshot('" . get_url('/template/') . "')",
            ),
            'options_attr' => array(),
            'input_suffix' => '<a href="' . getImgPath($config['template']) . '" class="gallery"><img width="200" "id="screenshot" src="' . getImgPath($config['template']) . '"></a>',
        ),
        'language' => array(
            'type' => 'select',
            'title' => __('language site'),
            'options' => @$langSelect,
        ),
        'permitted_languages' => array(
            'title' => __('Permitted languages'),
            'description' => __('Permitted languages: info') . (isset($langs) ? implode(',', $langs) : ''),
        ),
        'site_title' => array(
            'title' => __('site_title'),
            'description' => __('site_title: info'),
        ),
        'title' => array(
            'title' => __('Title'),
            'description' => __('Title: info'),
        ),
        'meta_keywords' => array(
            'title' => __('Meta-Keywords'),
            'description' => __('Meta-Keywords: info'),
        ),
        'meta_description' => array(
            'title' => __('Meta-Description'),
            'description' => __('Meta-Description: info'),
        ),
        'cookie_time' => array(
            'title' => __('Cookes life'),
            'help' => __('Days'),
            'description' => __('Cookes life: info'),
        ),
        'redirect' => array(
            'title' => __('Redirect with main page'),
            'description' => __('Redirect with main page: info'),
        ),
        'start_mod' => array(
            'title' => __('Main page'),
            'description' => __('Main page: info'),
        ),
        'max_file_size' => array(
            'title' => __('Max size attach'),
            'description' => __('Max size attach: global info'),
            'help' => __('Kb'),
            'onview' => array(
                'division' => 1024,
            ),
            'onsave' => array(
                'multiply' => 1024,
            ),
        ),
        'admin_email' => array(
            'title' => __('Admin E-Mail'),
            'description' => __('Admin E-Mail: info'),
        ),
        'redirect_delay' => array(
            'title' => __('Redirect delay'),
            'description' => __('Redirect delay: info'),
        ),
        'debug_mode' => array(
            'type' => 'checkbox',
            'title' => __('Debag mode'),
            'description' => __('Debag mode: info'),
        ),

        __('Materials on the main'),

        'sub_news' => array(
            'type' => 'checkbox',
            'title' => __('news',false,'news'),
            'value' => 'news',
            'fields' => 'latest_on_home',
        ),
        'sub_stat' => array(
            'type' => 'checkbox',
            'title' => __('stat',false,'stat'),
            'value' => 'stat',
            'fields' => 'latest_on_home',
        ),
        'sub_loads' => array(
            'type' => 'checkbox',
            'title' => __('loads',false,'loads'),
            'value' => 'loads',
            'fields' => 'latest_on_home',
        ),

        'cnt_latest_on_home' => array(
            'title' => __('Material per main page'),
        ),
        'announce_lenght' => array(
            'title' => __('Annonce length on main page'),
        ),

        __('Saving images'),

        'quality_jpeg' => array(
            'type' => 'select',
            'title' => __('Quality image(JPEG)'),
            'description' => __('Quality image(JPEG): info'),
            'options' => array(
                '100' => '100 ('.__('Quality good').')',
                '95' => '95',
                '90' => '90',
                '85' => '85',
                '80' => '80',
                '75' => '75',
                '70' => '70',
                '65' => '65',
                '60' => '60',
                '55' => '55',
                '50' => '50',
                '45' => '45',
                '40' => '40',
                '35' => '35',
                '30' => '30',
                '25' => '25',
                '20' => '20',
                '15' => '15',
                '10' => '10',
                '5' => '5',
                '0' => '0 ('.__('Quality bad').')',
            ),
        ),
        'quality_png' => array(
            'type' => 'select',
            'title' => __('Quality image(PNG)'),
            'description' => __('Quality image(PNG): info'),
            'options' => array(
                '9' => '9 ('.__('good compression').')',
                '8' => '8',
                '7' => '7',
                '6' => '6',
                '5' => '5',
                '4' => '4',
                '3' => '3',
                '2' => '2',
                '1' => '1',
                '0' => '0 ('.__('without compression').')',
            ),
        ),

        __('Other'),

        'IEC60027-2' => array(
            'type' => 'checkbox',
            'title' => __('Use IEC60027-2 standart'),
            'description' => __('Use IEC60027-2 standart: info'),
        ),
        /*
        'cache' => array(
            'type' => 'checkbox',
            'title' => __('Cache'),
            'description' => __('Cache: info'),
        ),
        */
        'templates_cache' => array(
            'type' => 'checkbox',
            'title' => __('Template cache'),
            'description' => __('Template cache: info'),
        ),
        'raw_time_mess' => array(
            'title' => __('Raw time posts'),
            'description' => __('Raw time posts'),
            'help' => __('seconds'),
        ),
        'use_multicategories' => array(
            'type' => 'checkbox',
            'title' => __('Use multicategories'),
            'description' => __('Use multicategories: info'),
        ),
        'comments_tree' => array(
            'type' => 'checkbox',
            'title' => __('Tree-like comments to materials'),
        ),
        'allow_html' => array(
            'type' => 'checkbox',
            'title' => __('Use HTML in messages'),
            'description' => __('Use HTML in messages: info'),
        ),
        'allow_smiles' => array(
            'type' => 'checkbox',
            'title' => __('Use smiles messages'),
            'description' => __('Use smiles messages: info'),
        ),
        'smiles_set' => array(
            'type' => 'select',
            'title' => __('Smiles set'),
            'description' => __('Smiles set: info'),
            'options' => @$smilesSelect,
        ),
    ),

    /* SECURE */
    '__secure__' => array(
        'antisql' => array(
            'type' => 'checkbox',
            'title' => __('Use antisql'),
            'description' => __('Use antisql: info'),
        ),

        __('Antiddos'),

        'anti_ddos' => array(
            'type' => 'checkbox',
            'title' => __('Use antiddos'),
            'description' => __('Use antiddos: info'),
        ),
        'request_per_second' => array(
            'title' => __('Max count queries(ddos)'),
            'description' => __('Max count queries(ddos): info'),
        ),

        'system_log' => array(
            'type' => 'checkbox',
            'title' => __('Logging actions'),
            'description' => __('Logging actions: info'),
        ),
        'max_log_size' => array(
            'title' => __('Max size log file'),
            'description' => __('Max size log file: info'),
            'help' => __('Kb'),
            'onview' => array(
                'division' => 1024,
            ),
            'onsave' => array(
                'multiply' => 1024,
            ),
        ),

        __('Other'),

        'autorization_protected_key' => array(
            'type' => 'checkbox',
            'title' => __('Use autorization protected key'),
            'description' => __('Use autorization protected key: info'),
        ),
        'session_time' => array(
            'title' => __('Time of session in admin-panel'),
            'description' => __('Time of session in admin-panel: info'),
            'help' => __('minute.'),
            'onview' => array(
                'division' => 60,
            ),
            'onsave' => array(
                'multiply' => 60,
            ),
        ),
        
        'used_https' => array(
            'type' => 'checkbox',
            'title' => __('Forced https use'),
            'description' => __('Forced https use: info'),
        ),
    ),

    /* COMMON */
    '__rss__' => array(
        'rss_lenght' => array(
            'title' => __('Max length RSS annonce'),
            'help' => __('Symbols'),
          ),
        'rss_cnt' => array(
            'title' => __('Count materials in RSS'),
          ),

        __('Use RSS for modules:'),

        'rss_news' => array(
             'type' => 'checkbox',
             'title' => __('news',false,'news'),
           ),
        'rss_stat' => array(
             'type' => 'checkbox',
             'title' => __('stat',false,'stat'),
           ),
        'rss_loads' => array(
             'type' => 'checkbox',
             'title' => __('loads',false,'loads'),
           ),
        'rss_forum' => array(
             'type' => 'checkbox',
             'title' => __('forum',false,'forum'),
           ),
        'rss_foto' => array(
             'type' => 'checkbox',
             'title' => __('foto',false,'foto'),
           ),
    ),

    /* Sitemap */
    '__sitemap__' => array(
        'auto_sitemap' => array(
            'type' => 'checkbox',
            'title' => __('Use autogeneration sitemap.xml'),
        ),
    ),

    /* Preview */
    '__preview__' => array(
        'use_preview' => array(
                'type' => 'checkbox',
                'title' => __('Use preview'),
                'description' => __('Use preview: info'),
        ),
        'img_size_x' => array(
                'type' => 'number',
                'title' => __('Width preview'),
                'description' => __('Width preview: info'),
                'help' => 'px',
                'grid-width' => 's6',
        ),
        'img_size_y' => array(
                'type' => 'number',
                'title' => __('Height preview'),
                'description' => __('Height preview: info'),
                'help' => 'px',
                'grid-width' => 's6',
        ),
    ),

    /* Watermark */
    '__watermark__' => array(
        'use_watermarks' => array(
            'type' => 'checkbox',
            'title' => __('Use watermark'),
        ),
        'watermark_type' => array(
            'type' => 'select',
            'title' => __('Watermark type'),
            'options' => array(
                '1' => __('Text'),
                '0' => __('Image'),
            ),
        ),
        'watemark_min_img' => array(
            'title' => __('Min watermark size'),
            'description' => __('Min watermark size: info'),
            'help' => 'px',
        ),
        'watermark_indent' => array(
            'title' => __('Watermark margin'),
            'help' => 'px',
        ),

        __('Watermark (image)'),

        'watermark_img' => array(
            'type' => 'file',
            'title' => __('Watermark (image)'),
            'input_suffix_func' => array('DrsImg','showWaterMarkImage'),
            'onsave' => array(
                'func' => array('DrsImg','saveWaterMarkImage'),
            ),
        ),

        __('Watermark (text)'),

        'watermark_text' => array(
            'title' => __('Watermark (text)'),
            'input_suffix_func' => array('DrsImg','showWaterMarkText'),
        ),
        'watermark_text_font' => array(
            'type' => 'select',
            'title' => __('Font'),
            'description' => __('Font: info'),
            'options' => @$fontSelect,
        ),
        'watermark_text_angle' => array(
            'type' => 'select',
            'title' => __('Degree turn of text'),
            'help' => __('Degree'),
            'options' => array(
                '315' => '315',
                '270' => '270',
                '225' => '225',
                '180' => '180',
                '135' => '135',
                '90' => '90',
                '45' => '45',
                '0' => '0 ('.__('Without turn').')',
            ),
        ),
        'watermark_text_size' => array(
            'title' => __('Font size'),
            'help' => 'px',
        ),
        'watermark_text_color' => array(
            'type' => 'select',
            'title' => __('Font color'),
            'options' => array(
                '000000' => __('Black'),
                '008000' => __('Green'),
                '800000' => __('Maroon'),
                '000080' => __('Mazarine'),
                'FF0000' => __('Red'),
                '0000FF' => __('Blue'),
                '00FF00' => __('Lime'),
                '808000' => __('Olive'),
                '800080' => __('Purple'),
                '008080' => __('Teal'),
                '808080' => __('Gray'),
                '00FFFF' => __('Aquamarine'),
                'FF00FF' => __('Pink'),
                'FFFF00' => __('Yellow'),
                'c0c0c0' => __('Silver'),
                'FFFFFF' => __('White'),
            ),
        ),
        'watermark_text_border' => array(
            'type' => 'select',
            'title' => __('Color text border'),
            'options' => array(
                '000000' => __('Black'),
                '008000' => __('Green'),
                '800000' => __('Maroon'),
                '000080' => __('Mazarine'),
                'FF0000' => __('Red'),
                '0000FF' => __('Blue'),
                '00FF00' => __('Lime'),
                '808000' => __('Olive'),
                '800080' => __('Purple'),
                '008080' => __('Teal'),
                '808080' => __('Gray'),
                '00FFFF' => __('Aquamarine'),
                'FF00FF' => __('Pink'),
                'FFFF00' => __('Yellow'),
                'c0c0c0' => __('Silver'),
                'FFFFFF' => __('White'),
                'none'   => __('without border'),
            ),
            'onsave' => array(
                'func' => array('DrsImg','saveWaterMarkText'),
            ),
        ),

        __('Other'),

        'watermark_hpos' => array(
            'type' => 'select',
            'title' => __('Horizontal position watermark'),
            'options' => array(
                '3' => __('Horizontal position watermark: 3'),
                '2' => __('Horizontal position watermark: 2'),
                '1' => __('Horizontal position watermark: 1'),
            ),
        ),
        'watermark_vpos' => array(
            'type' => 'select',
            'title' => __('Vertical position watermark'),
            'options' => array(
                '3' => __('Vertical position watermark: 3'),
                '2' => __('Vertical position watermark: 2'),
                '1' => __('Vertical position watermark: 1'),
            ),
        ),
        'watermark_alpha' => array(
            'type' => 'select',
            'title' => __('Transparently watermark'),
            'description' => __('Transparently watermark: info'),
            'options' => array(
                '100' => '100 ('.__('full Not transparent').')',
                '95' => '95',
                '90' => '90',
                '85' => '85',
                '80' => '80',
                '75' => '75',
                '70' => '70',
                '65' => '65',
                '60' => '60',
                '55' => '55',
                '50' => '50',
                '45' => '45',
                '40' => '40',
                '35' => '35',
                '30' => '30',
                '25' => '25',
                '20' => '20',
                '15' => '15',
                '10' => '10',
                '5' => '5',
                '0' => '0 ('.__('full Transparent').')',
            ),
        ),
    ),
);

if (isset($rss_modules))
    $settingsInfo['__rss__'] = array_merge($settingsInfo['__rss__'], $rss_modules);


// Модули, настройки которых не обьеденены под один ключ.
$noSub = array(
    '__sys__',
    '__seo__',
    '__sitemap__',
    '__preview__',
    '__watermark__',
);