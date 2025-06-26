<?php
/**
* @project    DarsiPro CMS
* @package    Admin Panel module
* @url        https://darsi.pro
*/

@ini_set('display_errors', 1);
include_once '../sys/boot.php';
include_once R.'admin/inc/adm_boot.php';

$Register = Register::getInstance();
$DB = getDB();

$pageTitle = __('Admin Panel');


$cnt_for = $DB->select('themes', DB_COUNT);
$cnt_news = $DB->select('news', DB_COUNT);
$cnt_load = $DB->select('loads', DB_COUNT);
$cnt_stat = $DB->select('stat', DB_COUNT);
$cnt_mat = $cnt_news + $cnt_for + $cnt_load + $cnt_stat;





$new_ver = @file_get_contents('https://darsi.pro/last.php?host=' . $_SERVER['HTTP_HOST']);
$new_ver = ((!empty($new_ver)) && ($new_ver == h($new_ver)) && ($new_ver != DARSI_VERSION)) 
    ? '<a class="usually_link" href="https://darsi.pro" title="Last version">' . h($new_ver) . '</a>' 
    : '';

include 'template/header.php';
?>



<!--************ GENERAL **********-->
<div class="row b15tm">
    <div class="col s12">
        <!--<h5 class="light"><?php echo __('General information') ?></h5>-->
        <table>
            <thead>
                <tr>
                    <th data-field="id"><?php echo __('Current domain'); ?></th>
                    <th data-field="version"><?php echo __('Version DarsiPro'); ?></th>
                    <th data-field="isddos"><?php echo __('Anti DDOS protection'); ?></th>
                    <th data-field="iscache"><?php echo __('Cache'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo (used_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' ?></td>
                    <td>
                        <b><?php echo DARSI_VERSION ?></b>
                        <?php if ($new_ver): ?>
                            <p>
                            <?php echo __('New version'); ?>
                            <?php echo $new_ver; ?>
                            </p>
                        <?php endif; ?>
                    </td>
                    <td><i class="small <?php echo (Config::read('anti_ddos', '__secure__') == 1) ? 'mdi-action-done green-text' : 'mdi-image-texture grey-text' ?>"></i></td>
                    <td><i class="small <?php echo (Config::read('cache') == 1) ? 'mdi-action-done green-text' : 'mdi-image-texture grey-text' ?>"></i></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


<div class="row">

    <!--************ MATERIALS **********-->
    <div class="col s6">
        <script>
        $(document).ready(function () {
            // Build the chart
            $('#materialsPie').highcharts({
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: '<?php echo __('Materials') ?>'
                },
                subtitle: {
                    text: '<?php echo __('All materials') ?>: <?php echo $cnt_mat ?>'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y}({point.percentage:.1f}%)</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: false
                        },
                        showInLegend: true
                    }
                },
                series: [{
                    name: "<?php echo __('Materials cnt'); ?>",
                    colorByPoint: true,
                    data: [
                        {name: "<?php echo __('News cnt') ?>", y: <?php echo $cnt_news ?>},
                        {name: "<?php echo __('Loads cnt') ?>", y: <?php echo $cnt_load ?>},
                        {name: "<?php echo __('Stat cnt') ?>", y: <?php echo $cnt_stat ?>},
                        {name: "<?php echo __('Themes cnt') ?>", y: <?php echo $cnt_for ?>}
                    ]
                }],
                credits: false
            });
        });
        </script>
        
        <div id="materialsPie"></div>
    </div>



    <?php
    $cards = Events::init('add_admin_homecard', array());
    
    usort($cards, function ($a, $b) {
        return (int)($a["order"]) > (int)($b["order"]) ? 1 : (int)($a["order"]) == (int)($b["order"]) ? 0 : -1;
    });
    
    foreach($cards as $card) {
        $card = array_merge(array(
            'title' => false,
            'is_row' => false,
            'body' => null,
            'order' => 0
        ), $card);
    ?>
        <?php if (!empty($card['is_row'])) { ?>
    <div class="row">
        <div class="col s12">
        <?php } else { ?>
        <div class="col s6">
        <?php } ?>
            <?php if (!empty($card['title'])) { ?>
            <h5 class="light"><?php echo $card['title'] ?></h5>
            <?php }
            echo $card['body'] ?>
        </div>
        <?php if (!empty($card['is_row'])) { ?>
    </div>
        <?php } ?>
    <?php
    }
    ?>
</div>

<script src="<?php echo WWW_ROOT ?>/admin/js/highcharts.js"></script>

<?php
include_once 'template/footer.php';
?>



