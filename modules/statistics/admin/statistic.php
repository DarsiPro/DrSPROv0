<?php
/**
* @project    DarsiPro CMS
* @package    Admin module
* @url        https://darsi.pro
*/


include_once R.'admin/inc/adm_boot.php';


if (isset($_GET['grfrom']) && !empty($_GET['grfrom']))
    $grfrom = strtotime($_GET['grfrom']);
else
    $grfrom = 0; // 1970-01-01 (начало эпохи)

if (isset($_GET['grto']) && !empty($_GET['grto']))
    $grto = strtotime($_GET['grto']);
else
    $grto = time();



// get data for charts (from arhive)
$grfrom_str = date("Y-m-d",$grfrom);
$grto_str = date("Y-m-d",$grto);
$Model = \OrmManager::getModelInstance('Statistics');
$all = $Model->getCollection(array(
    "date >= '{$grfrom_str}'",
    "date <= '{$grto_str}'",
), array(
    'order' => 'date ASC',
));

// get data for charts (today information)
if ($grto >= time()) {
    if (file_exists(R.'sys/tmp/statistics/counter/' . date("Y-m-d") . '.dat')) {
        $statsToday = unserialize(file_get_contents(R.'sys/tmp/statistics/counter/' . date("Y-m-d") . '.dat'));
    }
    $entity = \OrmManager::getEntityName('Statistics');
    if (isset($statsToday) && is_array($statsToday) && count($statsToday) > 0) {
        $all[] = new $entity($statsToday);
    } else {
        $all[] = new $entity(array(
            'date' => date("Y-m-d"),
            'views' => 0,
            'visits' => 0,
            'other_site_visits' => 0,
            'bot_views' => array()
        ));
    }
}

if (!empty($all)) {

    $json_data = array(
        'views' => array(
            'x' => array(),
            'y' => array()
        ),
        'hosts' => array(
            'x' => array(),
            'y' => array()
        ),
        'robots' => array(
            'x' => array(),
            'y' => array(),
            'pie' => array()
        ),
        'referers' => array(
            'x' => array(),
            'y' => array()
        )
    );


    // get data for charts (from arhive)
    if (!empty($all) && is_array($all) && count($all) > 1) {
        foreach ($all as $item) {
            $__date__ = date("m/d/Y",strtotime($item->getDate()));
            $json_data['views']['x'][] = $__date__;
            $json_data['views']['y'][] = (int)$item->getViews();
            $json_data['hosts']['x'][] = $__date__;
            $json_data['hosts']['y'][] = (int)$item->getVisits();
            $json_data['robots']['x'][] = $__date__;
            $json_data['robots']['y'][] = array_sum(array_values($item->getBot_views() ? $item->getBot_views() : array()));
            $json_data['referers']['x'][] = $__date__;
            $json_data['referers']['y'][] = (int)$item->getOther_site_visits();
            
            
            $bot_views = $item->getBot_views() ? $item->getBot_views() : array();
            foreach($bot_views as $botname => $botviews) {
                for($i=0; $i < count($json_data['robots']['pie']); $i++) {
                    if ($json_data['robots']['pie'][$i]['name'] == $botname) {
                        $json_data['robots']['pie'][$i]['y'] += $botviews;
                        break;
                    }
                    $json_data['robots']['pie'][] = array(
                        'name' => $botname,
                        'y' => $botviews
                    );
                }
            }
        }
    }


    $endDate = end($all);
    $views_on_visit = ($endDate->getVisits() > 0)
                     ? number_format(($endDate->getViews() / $endDate->getVisits()), 1)
                     : '-';

}

$pageTitle = __('Statistic');
$pageNav = $pageTitle;
$pageNavr = '';
include_once R.'admin/template/header.php';

?>

<script>
// Very Big Shit. Oh... I am sorry my god.
function necessaryAction(el) {
    location.href = el.href;
};
</script>

<div class="row">
    <ul class="tabs center">
        <?php if (($grto - 172800) >= $grfrom): ?>
        <li class="tab">
            <a onclick="necessaryAction(this);" href="?grto=<?php echo date("Y-m-d",$grto - 172800) ?>"><?php echo '&laquo; ' . date("Y-m-d", $grto - 172800) ?></a>
        </li>
        <?php endif; ?>
        <?php if (($grto - 86400) >= $grfrom): ?>
        <li class="tab">
            <a onclick="necessaryAction(this);" href="?grto=<?php echo date("Y-m-d",$grto - 86400) ?>"><?php echo '&laquo; ' . date("Y-m-d", $grto - 86400) ?></a>
        </li>
        <?php endif; ?>
        <li class="tab">
            <a onclick="necessaryAction(this);" class="active" href="?grto=<?php echo date("Y-m-d",$grto) ?>"><?php echo $grto_str ?></a>
        </li>
        <?php if (($grto + 86400) < time()): ?>
        <li class="tab">
            <a onclick="necessaryAction(this);" href="?grto=<?php echo date("Y-m-d",$grto + 86400) ?>"><?php echo date("Y-m-d", $grto + 86400) . ' &raquo;' ?></a>
        </li>
        <?php endif; ?>
        <?php if (($grto + 172800) < time()): ?>
        <li class="tab">
            <a onclick="necessaryAction(this);" href="?grto=<?php echo date("Y-m-d",$grto + 172800) ?>"><?php echo date("Y-m-d", $grto + 172800) . ' &raquo;' ?></a>
        </li>
        <?php endif; ?>
    </ul>
    <table class="centered">
        <?php if (!empty($all)): ?>
        <thead>
                <th><?php echo __('Watchers') ?></th>
                <th><?php echo __('Hosts') ?></th>
                <th><?php echo __('Views on visitor') ?></th>
                <th><?php echo __('Views on robots') ?></th>
                <th><?php echo __('Other site visitors') ?></th>
        </thead>
        <tbody>
        
            <tr>
                <td width="150"><?php echo $endDate->getViews() ?></td>
                <td><?php echo $endDate->getVisits() ?></td>
                <td><?php echo $views_on_visit ?></td>
                <td><?php echo __("All") . ": " . end($json_data['robots']['y']) ?><br/>
                <?php foreach((array)($endDate->getBot_views()) as $botname => $botviews) { ?>
                    <?php echo $botname ?>: <?php echo $botviews ?>;
                <?php } ?>
                </td>
                <td><?php echo $endDate->getOther_site_visits() ?></td>
            </tr>
        
        </tbody>
        <?php else: ?>

        <tr>
            <td align="center"><?php echo __('Empty') ?></td>
        </tr>

        <?php endif; ?>
    </table>
</div>




<?php if(!empty($json_data) && count($json_data['views']['x']) > 1 && count($json_data['hosts']['x']) > 1): ?>
<div class="row">
    <script src="<?php echo WWW_ROOT ?>/admin/js/highcharts.js"></script>
    <script type="text/javascript">
    function plotTimeAdapt(data) {
        for(var i=0; i<data.length; i++) {
            data[i] = (new Date(data[i])).toDateString();
        }
        return data;
    };
    $(document).ready(function(){
        var Stats_data = <?php echo json_encode($json_data); ?>;
        $('#Views_hosts').highcharts({
                chart: {
                    type: 'areaspline'
                },
                title: {
                    text: '<?php echo __('Views and hosts') ?>',
                },
                xAxis: {
                    categories: plotTimeAdapt(Stats_data.views.x),
                    tickmarkPlacement: 'on',
                    title: {
                        enabled: false
                    }
                },
                yAxis: {
                    title: false
                },
                tooltip: {
                    shared: true,
                },
                plotOptions: {
                    areaspline: {
                        fillOpacity: 0.5
                    }
                },
                series: [{
                    name: '<?php echo __('Watchers') ?>',
                    data: Stats_data.views.y
                }, {
                    name: '<?php echo __('Hosts') ?>',
                    data: Stats_data.hosts.y
                }],
                credits: false
        });
        
        $('#Referers').highcharts({
                chart: {
                    type: 'areaspline'
                },
                title: {
                    text: '<?php echo __('Other site visitors') ?>',
                },
                xAxis: {
                    categories: plotTimeAdapt(Stats_data.referers.x),
                    tickmarkPlacement: 'on',
                    title: {
                        enabled: false
                    }
                },
                yAxis: {
                    title: false
                },
                tooltip: {
                    shared: true,
                },
                plotOptions: {
                    areaspline: {
                        fillOpacity: 0.5
                    }
                },
                series: [{
                    name: '<?php echo __('Other site visitors') ?>',
                    data: Stats_data.referers.y
                }],
                credits: false
        });
        
        $('#Robots').highcharts({
                chart: {
                    type: 'areaspline'
                },
                title: {
                    text: '<?php echo __('Views on robots') ?>',
                },
                xAxis: {
                    categories: plotTimeAdapt(Stats_data.robots.x),
                    tickmarkPlacement: 'on',
                    title: {
                        enabled: false
                    }
                },
                yAxis: {
                    title: {
                        text: "<?php echo __('Watchers') ?>"
                    }
                },
                tooltip: {
                    shared: true,
                },
                plotOptions: {
                    areaspline: {
                        fillOpacity: 0.5
                    }
                },
                series: [{
                    name: '<?php echo __('Robots') ?>',
                    data: Stats_data.robots.y
                }],
                credits: false
        });
        if (Stats_data.robots.pie.length > 0) {
            $('#RobotsPie').highcharts({
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: '<?php echo __('Robots') ?>'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: "<?php echo __('Robots') ?>",
                    data: Stats_data.robots.pie
                }],
                credits: false
            });
        }

    });
    </script>
    <div id="Views_hosts"></div>
    <div id="Referers"></div>
    <div id="Robots"></div>
    <div id="RobotsPie"></div>
</div>
<?php endif; ?>





<div class="row">
    <form method="GET" action="">
        <div class="input-field col s6">
            <input class="datepicker" id="ffrom" type="date" name="grfrom" />
            <label for="ffrom" class="active"><?php echo __('From') ?></label>
        </div>
        <div class="input-field col s6">
            <input class="datepicker" id="fto" type="date" name="grto" />
            <label for="fto" class="active"><?php echo __('To') ?></label>
        </div>
        <div class="input-field col s12 center">
            <input class="btn" type="submit" value="<?php echo __('Show') ?>" />
        </div>
    </form>
</div>

<script type="text/javascript">
$(document).ready(function(){
    // for selects date in fields
    $('.datepicker').pickadate({
        selectMonths: true, // Creates a dropdown to control month
        selectYears: 15 // Creates a dropdown of 15 years to control year
    });
});
</script>

<?php
include_once R.'admin/template/footer.php';
?>
