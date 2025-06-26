<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/

include_once '../sys/boot.php';
include_once ROOT . '/admin/inc/adm_boot.php';

/* current page and cnt pages */
$log_files = glob(Logination::$logDir . '/*.dat');
$total_files = (!empty($log_files)) ? count($log_files) : 0;
list($pages, $page) = pagination($total_files, 1, '/admin/system_log.php');



if (!empty($log_files)) {
	$filename = (strrchr($log_files[$page - 1], '/'));
	$filename = substr($filename, 1, strlen($filename));
	$data = Logination::read($filename);
}




if (isset($_GET['clean']) and $_GET['clean']==1) {
    Logination::clean();
    redirect('/admin/system_log.php');
}




$pageTitle = __('System log');
$pageNav = $pageTitle;
include_once ROOT . '/admin/template/header.php';
?>


<div class="row">
<div class="col s12">
    <a class="btn" href="?clean=1"><?php echo __('Clean system log') ?></a>
    <div class="right"><?php echo $pages ?></div>
</div>
<div class="col s12">
<table>
    <thead>
        <th><?php echo __('Date') ?></th>
        <th><?php echo __('Activity') ?></th>
        <th><?php echo __('User') ?></th>
        <th>IP</th>
        <th><?php echo __('Information') ?></th>
    </thead>
    <tbody>
    <?php if(!empty($data)): ?>
        <?php foreach($data as $line):
                //for coompare old version
                $color = '';
                if (!empty($line['user_status']) && is_numeric($line['user_status'])) {
                    $group_info = ACL::get_group($line['user_status']);
                    if (!empty($group_info)) {
                        if (!empty($group_info['color'])) $color = $group_info['color'];
                        $line['user_status'] = ' <span style="color:' . $color . ';">' . h($group_info['title']) . '</span>';
                    } else {
                        $line['user_status'] = ' <span style="color:#F14242;">*</span>';
                    }
                } else {
                    $line['user_status'] = ' <span style="color:#F14242;">*</span>';
                }
                
                
        ?>

        <tr>
            <td align="center"><span style="color:green;"><?php echo $line['date'] ?></span></td>
            <td><?php echo $line['action'] ?></td>
            <td><?php echo (!empty($line['user_id']) && !empty($line['user_name']) && !empty($line['user_status'])) ? 
                    '(' . $line['user_id'] . ')' . h($line['user_name']) . '' 
                    . $line['user_status'] . '' : 'Unknown'; ?></td>
            <td align="center"><?php echo $line['ip'] ?></td>
            <td><?php echo (!empty($line['comment'])) ? h($line['comment']) : '--'; ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td><?php echo __('Empty') ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
<?php
include_once 'template/footer.php';
?>