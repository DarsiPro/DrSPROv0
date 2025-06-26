</section>
	<!-- End main content -->
<!-- Конец основного содержимого. Обратите внимание на использование элемента section: это чистая семантика, вы также можете использовать div.-->



<!-- JavaScript внизу для быстрой загрузки страницы -->

	<!-- Scripts -->
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.3/jquery-ui.min.js"></script>
	<script src="/admin/js/setup.js?<?php echo rand();?>"></script>
<!-- Два основных файла javascript должны быть загружены в первую очередь.-->
<script src="/admin/js/admin.js?<?php echo rand();?>"></script>


	<!-- Libs go here -->
<!-- Разместите необходимые библиотеки здесь. Обратите внимание, что иногда существует определенный порядок - например, 
рекомендуется загружать плагин tabs последним, так как нормализация размера будет работать лучше.-->





<!-- Template functions -->
	<script src="js/developr.accordions.js"></script>
	<script src="js/developr.auto-resizing.js"></script>
	<script src="/.s/scr/input.js?<?php echo rand();?>"></script>
	<script src="js/developr.message.js"></script>
	<script src="/.s/scr/modal.js"></script>
	<script src="/.s/scr/navigable.js?<?php echo rand();?>"></script>
	<script src="js/developr.collapsible.js"></script>
	<script src="/.s/scr/notify.js"></script>
	<script src="/.s/scr/scroll.js"></script>
	<script src="js/developr.progress-slider.js"></script>
	<script src="/.s/scr/tooltip.js"></script>
	<script src="/.s/scr/confirm.js"></script>
	<script src="111/.s/scr/content-panel.js"></script>
	<script src="js/developr.agenda.js"></script>
	<script src="js/developr.table.js"></script>
	<script src="js/developr.wizard.js"></script>
	<script src="js/developr.tabs.js"></script>		<!-- Must be loaded last -->

	<!-- Tinycon -->
	<script src="js/libs/tinycon.min.js"></script>

	<!-- Google code prettifier -->
	<script src="js/libs/google-code-prettify/prettify.js?v=1"></script>

	<!-- glDatePicker -->
	<script src="js/libs/glDatePicker/glDatePicker.min.js?v=1"></script>

	<!-- Hashchange polyfill -->
	<script src="js/libs/jquery.ba-hashchange.min.js?v=1"></script>

	<!-- CKEditor -->
    <script src="js/libs/ckeditor/ckeditor.js"></script>

	<!-- Tablesorter -->
    <script src="js/libs/jquery.tablesorter.min.js"></script>

	<!-- DataTables -->
    <script src="js/libs/DataTables/jquery.dataTables.min.js"></script>

<script>
<?php
    if (isset($_SESSION['message']) && count($_SESSION['message']) > 0) {
        // Уведомления
        foreach($_SESSION['message'] as $k => $v) {
            echo "notify('". $k . "', '". $v . "');";
        }
        unset($_SESSION['message']);
        
    }
?>
</script>
</body>
</html>