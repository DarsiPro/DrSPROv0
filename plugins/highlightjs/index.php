<?php
class highlightjs {

    public function common($params, $hook, $fname) {

        if ($fname != 'main.html') return $params;

        $adding =  array(
            '<!-- Highlight.js -->
            <link rel="stylesheet" href="{{ plugin_path }}/default.min.css">',
            
            '<script src="{{ plugin_path }}/highlight.min.js"></script>
            <script>
                $(document).ready(function() {
                    $(\'pre code.bbCodeBlock\').each(function(i, block) {
                        hljs.highlightBlock(block);
                    });
                });
            </script>
        ');

        $adding[0] .= '</head>';
        $adding[1] .= '</body>';
        return str_replace(array('</head>','</body>'), $adding, $params);
    }
}
