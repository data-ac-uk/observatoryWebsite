<? $chartid++; ?>
<script type="text/javascript" src="http://www.amcharts.com/lib/3/amcharts.js"></script>
<script type="text/javascript" src="http://www.amcharts.com/lib/3/pie.js"></script>
<script type="text/javascript" src="http://www.amcharts.com/lib/3/themes/light.js"></script>
<div id="<? print $chartid; ?>" class="chart"></div>
<style>
.chart {
	width		: 100%;
	height		: 300px;
	font-size	: 11px;
}
</style>
<script>
var chart = AmCharts.makeChart("<? print $chartid; ?>", {
    "type": "pie",
    "theme": "light",
    "dataProvider": <?php print json_encode( $stuff ); ?>,
    "valueField": "v",
    "titleField": "k",
    "depth3D": 15,
    "outlineAlpha": 0.4,
    "balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
    "angle": 30,
    "groupPercent": 1,
	"exportConfig":{	
      menuItems: [{
      icon: '/lib/3/images/export.png',
      format: 'png'	  
      }]  
	}
});
</script>
