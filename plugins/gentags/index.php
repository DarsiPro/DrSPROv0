<?php
class gentags {
    public function common($params) {

        $conf_pach = dirname(__FILE__).'/config.json';
        $config = json_decode(file_get_contents($conf_pach), true);

        $str = "$('" . $config['text_obj'] . "').val() + $('" . $config['text_editor_obj'] . "').html()";
        $ignor = "$('#" . $config['ignore_obj'] . "')";
        $length = "$('#" . $config['length_obj'] . "')";
        $repeat = "$('#" . $config['repeat_obj'] . "')";
        $keywords = "$('#" . $config['tags_obj'] . "')";	
        $ignoring_hide = "'" . $config['ignoring_hide'] . "'";


        $script = '<script src="{{ plugin_path }}/index.js"></script>';
        $id = 'id="' . $config['tags_obj'] . '"';
        $button = '<input type="button" value="Сгенерировать теги" onclick="gen(' . $str . ',' . $ignor . ',' . $length . ',' . $repeat . ',' . $keywords . ',' . $ignoring_hide . ')">';
        $start = 'gen(' . $str . ',' . $ignor . ',' . $length . ',' . $repeat . ',' . $keywords . ',' . $ignoring_hide . ')';
        $default_start = 'if (!$(\'#keywords\').val()) gen(' . $str . ',' . $ignor . ',' . $length . ',' . $repeat . ',' . $keywords . ',' . $ignoring_hide . ');';

        $Cache = new Cache;
        $Cache->prefix = 'template';
        $Cache->cacheDir = 'sys/cache/plugins/gentags/';
        if ($Cache->check('gentags'))
            $template = $Cache->read('gentags');
        else {
            
            $markers = array();
            $markers['length_id'] = $config['length_obj'];
            $markers['length_val'] = $config['min_word'];
            $markers['repeat_id'] = $config['repeat_obj'];
            $markers['repeat_val'] = $config['min_repeat'];
            $markers['ignore_id'] = $config['ignore_obj'];
            $markers['ignore_val'] = $config['ignoring'];

            $template = file_get_contents(dirname(__FILE__).'/template/index.html');

            $Viewer = new Viewer_Manager;
            $template = $Viewer->parseTemplate($template, array('gentags' => $markers));
            
            $Cache->write($template, 'gentags', array());
        }

        $marker = array(
               '#{{\s*script_gentags\s*}}#i',
               '#{{\s*input_gentags\s*}}#i',
               '#{{\s*button_gentags\s*}}#i',
               '#{{\s*start_gentags\s*}}#i',
               '#{{\s*default_start_gentags\s*}}#i',
               '#{{\s*forms_gentags\s*}}#i'
        );

        $replacements = array($script, $id, $button,$start,$default_start, $template);

        return preg_replace($marker, $replacements, $params);
    }
}
