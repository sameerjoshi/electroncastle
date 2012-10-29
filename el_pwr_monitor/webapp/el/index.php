<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Live electricity power meter</title>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script type="text/javascript">
		
$(function () {

	// Update period in seconds
	var updatePeriod=5;

	// Time interval shown in the graph in minutes
	var timeWindow=60;

	//------------------------------------------------------------------
	var xmlhttp;
	if (window.XMLHttpRequest)	  {
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}else{
		// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
   }

	function formatUnits(watts) 
	{
		var units=['W', 'KW', 'MW', 'GW'];
		var unit = 0;
	
		while (Math.floor(watts) > 1000){
			unit++;
			watts /= 1000.0;
		}
		
		var str=""+watts+" "+units[unit];
		return str;
	}
  
	var chart;
	var firstSampleTime;

	function requestData() 
	{
		$.ajax({
			url: 'power.php?now',
			cache: false,

			success: function(point) {
				var series = chart.series[0];

				// Start shifting (removing old samples) the graph to show only 
				// samples for last 'timeWindow' minutes
				var shift = (point[0]-firstSampleTime)/1000.0 > timeWindow*60; 
	
				// Add new point to the chart
				if (series.data.length > 1){
					var str = "Usage now: "+formatUnits(point[1]);
					document.getElementById("myDiv").innerHTML="<h2>"+str+"</h2>";
					if (point[1] != 0){
			            chart.series[0].addPoint(point, true, shift);
			        }
				}

				// Schedule another update
            setTimeout(requestData, updatePeriod*1000);    
			},

			error: function(result) {
					var str = "You are offline. Trying to re-connect...";
					document.getElementById("myDiv").innerHTML="<h2>"+str+"</h2>";			  
	            setTimeout(requestData, updatePeriod*1000);    
			}
		});
	}  
	
	
	function prefetchData() 
	{
		document.getElementById("myDiv").innerHTML="<h2>Prefetching data...</h2>";

		$.ajax({
			url: 'power.php?last='+timeWindow,
			cache: false,

			success: function(points) {
				// Assign the received data to the chart
				// The data is in format [unix_timestamp_ms, power_watts]
            chart.series[0].setData(points, true);
				var str = "Usage now: "+formatUnits(points[points.length-1][1]);
				document.getElementById("myDiv").innerHTML="<h2>"+str+"</h2>";
				
				// Store timestamp of the first received sample
				firstSampleTime=points[0][0];
			},
	
			error: function(result) {
				var str = "ERROR: "+String(result);
				document.getElementById("myDiv").innerHTML="<h2>"+str+"</h2>";			  
            setTimeout(prefetchData, updatePeriod*1000);    
			}

		});
		return;
	}  
  

	function chartNow()
	{
   	chart = new Highcharts.Chart({
     		chart: {
				renderTo: 'container',
				type: 'spline',
				marginRight: 10,
				events: {
				  load: requestData
				}
			},
			title: {
				 text: 'Live electricity power meter (last '+timeWindow+' minutes)'
			},
			xAxis: {
				 type: 'datetime',
				 // Resolution on the x axis.
				 tickPixelInterval: 100
			},
			yAxis: {
				 title: {
				     text: 'Power (Watts)'
				 },
				 plotLines: [{
				     value: 0,
				     width: 1,
				     color: '#808080'
				 }]
			},
			tooltip: {
				// This will appear when you hover above the graph in the chart
				 formatter: function() {
				         return 'Time: '+Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) +'<br>'+
				         'Power: '+Highcharts.numberFormat(this.y, 2)+' Watts';
				 }
			},
			legend: {
				 enabled: false
			},
			exporting: {
				 enabled: false
			},
			series: [{
				 marker: {
				     radius: 2
				 },
				 name: 'Power (Watts)',
			}]
		
		});
	}

	$(document).ready(function() {
		Highcharts.setOptions({
 		   global: {
      		useUTC: false
         }
      });

		chartNow();
   	prefetchData();
   });
    
});
</script>
</head>

<body>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/themes/gray.js"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>

<table width=100%>
<tr>
<td width=200px valign=top>
	<BR><BR>
	<a href="power.php">Raw samples table</a><BR>
</td>
<td>
	<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
	<div id="myDiv"><h2>Usage now: 0 Watts</h2></div>
</td>
</tr>
</table>

</body>
</html>





