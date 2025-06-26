<?php

$json_data = array(
    'dates' => array(),
    'views' => array(),
    'hosts' => array()
);
foreach($all_stats as $day) {
    $json_data['dates'][] = date("m/d/Y",strtotime($day->getDate()));
    $json_data['views'][] = (int)$day->getViews();
    $json_data['hosts'][] = (int)$day->getVisits();
}
ob_start();
?>
<script type="text/javascript">
    function plotDate(data) {
        for(var i=0; i<data.length; i++) {
            d = (new Date(data[i])).toDateString().split(' ');
            data[i] = d[2] + ' ' + d[1] + ' ' + d[3];
        }
        return data;
    };
    $(document).ready(function(){
        var st_data = <?php echo json_encode($json_data); ?>,
            div = $('#ds_chart');
        $('#ds_chart').highcharts({
            chart: {
                type: 'spline',
                backgroundColor: null,
                width: div.width(),
                height: 265
            },
            title: {
                text: '<?php echo __('Views and hosts',false,'statistics') ?>',
                style:{color:'#fff',fontWeight:'normal'}
            },
            xAxis: {
                tickLength: 0,
                gridLineWidth: 0,
                categories: plotDate(st_data.dates),
                labels: {
                    style: {color: '#fff'}
                }
            },
            yAxis: {
                title: false,
                minorGridLineWidth: 0,
                labels: {
                    style: {color: '#fff'}
                }
            },
            legend: {
                itemStyle: {color: '#fff'},
                itemHoverStyle: {color: '#607890'}
            },
            tooltip: {
                shadow: true,
                backgroundColor: "#fff"
            },
            plotOptions: {
                spline: {lineWidth: 4}
            },
            series: [{
                name: '<?php echo __('Watchers',false,'statistics') ?>',
                data: st_data.views
            }, {
                name: '<?php echo __('Hosts',false,'statistics') ?>',
                data: st_data.hosts
            }],
            credits: false
        });
    });
</script>
<div class="s9 s12-mobile" id="ds_chart"></div>
<script src="js/highcharts.js"></script>
<?php
return ob_get_clean();
?>