<?php
/**
* @project    DarsiPro CMS
* @package    Template redactor
* @url        https://darsi.pro
*/

include_once R.'admin/template/header.php';?>



 <div class="with-padding">
            <span class="button-group large-margin-bottom">
				<a href="/admin/settings.php?m=__sys__" class="button icon-gear green-active"><?php echo __('Common settings'); ?></a>
				<a href="javascript:void(0)" class="button icon-palette green-active active"><?php echo $pageTitle; ?></a>
				
				<a href="javascript:void(0)" class="button icon-thumbs green-active">Icons</a>
			</span>
			<?php include_once R.'admin/template/tmpls_htm.php';?>
			
</div>



<?php

include_once 'template/footer.php'; 