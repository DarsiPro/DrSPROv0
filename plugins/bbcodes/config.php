<?php 
$editor_set = array (
  0 => 
  array (
    'title' => 'WysiBB (светлая схема)',
    'editor_head' => '<link rel="stylesheet" href="{{ plugin_path }}/wysibb/css/wbbtheme.css" />
<link rel="stylesheet" href="{{ plugin_path }}/wysibb/css/ds_wbbtheme.css" />',
    'editor_body' => '<script>
var UsAgentLang = (navigator.language || navigator.systemLanguage || navigator.userLanguage).substr(0, 2).toLowerCase();
$.getScript("{{ plugin_path }}/wysibb/lang/"+UsAgentLang+".js");
</script><script src="{{ plugin_path }}/wysibb/js/jquery.wysibb.min.js"></script>
<script src="{{ plugin_path }}/wysibb/js/bbeditor.js"></script>
<script>
$(document).ready(function() {
	var wbbOpt = {
		smileList: [
		{% for smile in smiles_list %}
			{title:"{{ smile.from }}", img: \'<img src="{{ www_root }}/data/img/smiles/{{ smiles_set }}/{{ smile.to }}" class="sm">\', bbcode:"{{ smile.from }}"},
		{% endfor %}
		]
	}
	$("#editor").wysibb(wbbOpt);
	
	// Отправка сообщения по Ctrl+Enter
	$("#editor").add($(\'body\', $("#editor").getDoc())).keydown(function(event) {
		if (event.keyCode == 13 && event.ctrlKey) {
			$("#editor").parents("form").submit();
		}
	});
});
</script>',
    'editor_buttons' => '',
    'editor_text' => 'id="editor"',
    'editor_forum_text' => 'id="editor"',
    'editor_forum_quote' => 'onClick="quoteSelection(\'{{ post.author.name }}\');" onMouseOver="catchSelection(); this.className=\'quoteAuthorOver\'" onMouseOut="this.className=\'quoteAuthor\'"',
    'editor_forum_name' => 'onClick="$(\'#editor\').insertAtCursor(\'<b>{{ post.author.name }}</b>, \', false); return false;"',
    'default' => true,
  ),
  1 => 
  array (
    'title' => 'WysiBB (темная схема)',
    'editor_head' => '<link rel="stylesheet" href="{{ plugin_path }}/wysibb/css/wbbtheme.css" />
<link rel="stylesheet" href="{{ plugin_path }}/wysibb/css/ds_wbbtheme_dark.css" />',
    'editor_body' => '<script>
var UsAgentLang = (navigator.language || navigator.systemLanguage || navigator.userLanguage).substr(0, 2).toLowerCase();
$.getScript("{{ plugin_path }}/wysibb/lang/"+UsAgentLang+".js");
</script>
<script src="{{ plugin_path }}/wysibb/js/jquery.wysibb.min.js"></script>
<script src="{{ plugin_path }}/wysibb/js/bbeditor.js"></script><script>
$(document).ready(function() {
	var wbbOpt = {
		smileList: [
		{% for smile in smiles_list %}
			{title:"{{ smile.from }}", img: \'<img src="{{ www_root }}/data/img/smiles/{{ smiles_set }}/{{ smile.to }}" class="sm">\', bbcode:"{{ smile.from }}"},
		{% endfor %}
		]
	}
	$("#editor").wysibb(wbbOpt);
	
	// Отправка сообщения по Ctrl+Enter
	$("#editor").add($(\'body\', $("#editor").getDoc())).keydown(function(event) {
		if (event.keyCode == 13 && event.ctrlKey) {
			$("#editor").parents("form").submit();
		}
	});
});
</script>',
    'editor_buttons' => '',
    'editor_text' => 'id="editor" style="background:#333;color:#fff;"',
    'editor_forum_text' => 'id="editor" style="background:#333;color:#fff;"',
    'editor_forum_quote' => 'onClick="quoteSelection(\'{{ post.author.name }}\');" onMouseOver="catchSelection(); this.className=\'quoteAuthorOver\'" onMouseOut="this.className=\'quoteAuthor\'"',
    'editor_forum_name' => 'onClick="$(\'#editor\').insertAtCursor(\'<b>{{ post.author.name }}</b>, \', false); return false;"',
    'default' => false,
  ),
);
?>