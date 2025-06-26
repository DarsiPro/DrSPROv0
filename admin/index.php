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

$ucircle = '';

$new_ver = @file_get_contents('https://darsi.pro/last.php?host=' . $_SERVER['HTTP_HOST']);
$new_ver = ((!empty($new_ver)) && ($new_ver == h($new_ver)) && ($new_ver != DARSI_VERSION)) 
    ? '<a class="usually_link" href="https://darsi.pro" title="Last version">' . h($new_ver) . '</a>' 
    : '';

include 'template/header.php';

?>


<!-- Main title -->
		<hgroup id="main-title" class="thin">
			<h1><?php echo $pageTitle; ?></h1>
			<h2><?php echo date('M');?> <strong><?php echo date('j');?></strong></h2>
		</hgroup>


<!-- Эта разметка необязательна, вы можете использовать простой <h1>. Просто добавьте его в дополненный блок под.-->




        <!-- Main content here -->
        <div class="columns with-padding">
            <div class="s6">
                <h3 class="thin"><a href="/" target="_blank"><?php echo $_SERVER['HTTP_HOST'] ?></a></h3>
                <?php echo __('Anti DDOS protection'); ?>  
                <input type="checkbox" class="switch medium disabled"<?php echo (Config::read('anti_ddos', '__secure__') == null) ?: ' checked'; ?>>
            </div>
            <div class="s6">
                <h3 class="thin">
                    <?php echo __('Version DarsiPro'); ?><b> <?php echo DARSI_VERSION ?></b>
                    <?php if ($new_ver): ?>
                            <p>
                            <?php echo __('New version'); ?>
                            <?php echo $new_ver; ?>
                            </p>
                        <?php endif; ?>
                </h3>
                <?php echo __('Cache'); ?>
                <input type="checkbox" class="switch medium disabled"<?php echo (Config::read('templates_cache') == null) ?: ' checked'; ?>>
            </div>
        </div>
<?php
    $hcharts = Events::init('add_admin_hchart', array());
    foreach($hcharts as $hchart) {
        $hchart = array_merge(array(
            'title' => false,
            'is_row' => false,
            'body' => null,
            'order' => 0
        ), $hchart);
        if (!empty($hchart['is_row'])) { ?>
            <div class="dashboard">
                <div class="columns">
                    <?php echo $hchart['body'] ?>
                    <div class="s3 s12-mobile new-row-mobile">
                        <ul class="stats split-on-mobile">
                            <li><a href="#"><strong>n/a</strong> new <br>accounts</a></li>
                            <li><a href="#"><strong>n/a</strong> referred new <br>accounts</a></li>
                            <li><strong>n/a</strong> new <br>items</li>
                            <li><strong>n/a</strong> new <br>comments</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php } else { $ucircle = $hchart['body']; } ?>
    <?php } ?>
    <div class="columns">
        <div class="s6">
            <!--  MATERIALS -->
        <script>
        $(document).ready(function () {
            
            // Build the chart
            $('#mpie').highcharts({
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: '<?php echo __('Materials') ?>',
                    verticalAlign: 'middle',
                    y: -30
                },
                subtitle: {
                    text: '<?php echo __('All materials') ?>:<br><b><?php echo $cnt_mat ?></b>',
                    verticalAlign: 'middle',
                    y: -10
                },
                tooltip: {
                    shadow: true,
                    backgroundColor: "#fff",
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
                    type: 'pie',
                    innerSize: '50%',
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
        <div id="mpie"></div>
        </div>
        <div class="s6"><?php echo $ucircle ?></div>
    </div>
			
	

<?php
include_once 'template/footer.php';
?>