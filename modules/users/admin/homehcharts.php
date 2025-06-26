<?php
ob_start();
?>
<script type="text/javascript">
$(document).ready(function () {
    // Build the chart
    $('#upie').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: '<?php echo __('users',false,'users'); ?>',
            verticalAlign: 'middle',
            y: -30
        },
        subtitle: {
            text: '<?php echo __('All cnt users',false,'users') ?>:<br><b><?php echo $cnt_usrs ?></b>',
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
            name: "<?php echo __('users',false,'users'); ?>",
            colorByPoint: true,
            innerSize: '50%',
            data: <?php echo json_encode($groups_info) ?>
        }],
        credits: false
    });
});
</script>

<div id="upie"></div>

<?php
return ob_get_clean();
?>