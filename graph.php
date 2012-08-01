<?php
// This is the front-end. Metric info is taken as parameters and passed to fetch_data.php

// This should be something like AWS/RDS for RDS, AWS/ELB for ELB, etc.
// You can find more info in the CloudWatch section of the AWS Console
$namespace = $_GET["ns"];

// Metric to retrieve, this should be something like RequestCount, FreeStorageSpace, etc.
$metric = $_GET['metric'];

// Start & end times to retrieve statistics in, strtotime() compatible, default will be up-to-date info
$start_time = isset($_GET['start']) && !empty($_GET['start']) ? $_GET['start'] : '-12 hours';
//$start_time = $_GET['start'];
$end_time = isset($_GET['end']) && !empty($_GET['end']) ? $_GET['end'] : 'now';

// Interval for statistics, i.e. retrieve for every X seconds
// Must be at least 60 seconds and must be a multiple of 60
$interval = isset($_GET['interval']) && !empty($_GET['interval']) ? $_GET['interval'] : 300;

// Metric type, can be Average, Sum, Min, etc.
$metric_type = isset($_GET['mtype']) && !empty($_GET['mtype']) ? $_GET['mtype'] : 'Sum';

// Unit to return, check the API reference for all the possibilities
$unit_type = isset($_GET['utype']) && !empty($_GET['utype']) ? $_GET['utype'] : 'Count';

$dim = $_GET['dim'];
//TODO: handle empty lb name here
$id = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : null;
$dimensions = array('Name' => $dim, 'Value' => $id);
?>

<html>
    <head>
		<link rel="stylesheet" type="text/css" href="style.css" />
        <script language="javascript" type="text/javascript" src="flot/jquery.js"></script>
        <script language="javascript" type="text/javascript" src="flot/jquery.flot.js"></script>
        <script language="javascript" type="text/javascript" src="flot/jquery.flot.navigate.js"></script>
		<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/jquery-ui.min.js" type="text/javascript"></script>
		<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/black-tie/jquery-ui.css" rel="stylesheet" type="text/css"/>
        <title>CloudWatch Metrics</title>
    </head>

    <body>
		<div id='header'>
			<span style='float: left; width: 100%;' align='center'></span>
		</div>
        <div id='cw' style='width:100%; height:95%;'></div>
        <script type="text/javascript">
		
		jQuery(document).ready(function($) { 


		$('#thelink').click(function(){ $('#thedialog').dialog('open'); });

			// Dialog 
			$('#thedialog').dialog({ 
				modal: true, 
				autoOpen: false, 
				height: 600,
				buttons: { 
					"Ok": function() { 
						$(this).dialog("close"); }, 
				} 
			}); 
			

			// Update cloudwatch metric parameters from modal dialogue
			$( "#interval_slider" ).slider({ 
				min: 60,
				max: 14400,
				step: 300, 
				value: interval,
				slide: function ( event, ui) {
					interval = ui.value;
					getData();
					console.log(ui.value);
					$('#interval_slider > p').empty();
					$('#interval_slider > p').append(interval / 60 + " minutes");
					
				}
			});
			$('#interval_slider > p').append(interval / 60 + " minutes");

			// Get start time from slider
			$( "#start_slider" ).slider({ 
				min: -1000,
				max: -1,
//				step: 2, value: start_time,
				slide: function ( event, ui) {
					start_time = ui.value;
					getData();
					$('#start_slider > p').empty();
					if (start_time < -23 ) {
						length = start_time / 24;
						$('#start_slider > p').append(Math.round(length) + " days");
					} else {
						$('#start_slider > p').append(start_time + " hours");		
					}
					
				}
			});
			$('#start_slider > p').append(start_time + " hours");		

			// lets populate the namespace box 
			$.getJSON('metrics.json', function(data) {
				$.each(data, function(i, value) {
					$('#namespace_select').append('<option>' + i + '</option>');
				});
			});

			$("#namespace_select option").each(function(){
  				if ($(this).text() == namespace)
    				$(this).attr("selected","selected");
			});
			// Get namespace from dropdown
			$("#namespace_select").change(function(e) {
				$('#metric_name_select').empty();
				$('#metric_name_select').append('<option>Select</option>');
				namespace = $(this).val();
				// lets populate the namespace box 
				$.getJSON('metrics.json', function(data) {
					$.each(data, function(i, listOfDimensions) {
						if (namespace == i) {
							$.each(listOfDimensions, function(a, listOfMetrics) {
								$('#metric_name_select').append('<option>' + a + '</option>');
							});
						}
					})
				});
				getData();
			}); 

			$("#metric_name_select option").each(function(){
  				if ($(this).text() == metric)
    				$(this).attr("selected","selected");
			});
	
			$('#metric_name_select').change(function(e) {
				$('#dimension_select').empty();
				$('#dimension_select').append('<option>Select</option>');
				metric = $(this).val();
				console.log(dim);
				$.getJSON('metrics.json', function(data) {
					$.each(data, function(namespaceName, listOfMetrics) {
						if (namespace == namespaceName) {
							$.each(listOfMetrics, function(i, listOfDimensions) {
								if (metric == i) {
									$.each(listOfDimensions, function(a, dimensionName) {
                                		$('#dimension_select').append('<option>' + dimensionName + '</option>');
                            		});
								}
							});
						}
					})
				});
				getData();
			});


			$('#dimension_select').val(dim);
			$('#dimension_select').change(function(e) {
				dim = $(this).val();
				//console.log($(this).val());
				// Query fetch_data.php for flot data
				getData();
			});

			
			$('#id_name').val(id);
			$('#id_name').change(function(e) {
				id = $(this).val();
				getData();
			});	

			$('#mtype_select').change(function(e) {
				metric_type = $(this).val();
				getData();
			});

			$("#mtype_select option").each(function(){
                if ($(this).text() == metric_type)
                    $(this).attr("selected","selected");
            });

			$('#unit_type').change(function(e) {
				unit_type = $(this).val();
				getData();
			});

			$("#unit_type option").each(function(){
                if ($(this).text() == unit_type)
                    $(this).attr("selected","selected");
            });
	}); // end of document ready	

		// We want to pass the paramters from the URL to an AJAX call
		var id = "<?= $id ?>";
		var dim = "<?= $dim ?>";
		var namespace = "<?= $namespace ?>";
		var metric = "<?= $metric ?>";
		var start_time = "<?= $start_time ?>";
		var end_time = "<?= $end_time ?>";
		var interval = "<?= $interval ?>";
		var metric_type = "<?= $metric_type ?>";
		var unit_type = "<?= $unit_type ?>";
		var dim = "<?= $dim ?>";
        var plot_data = [[[], []],[]];
		
		// Flot Options
	    var options = {
			series: { lines: { show: true }, shadowSize: 0 },
			xaxis: { zoomRange: [-500000000, 1000000000], panRange: [], mode: "time", timeformat: "%d-%H:%M:%S"  },
			yaxis: { zoomRange: [-50000, 1000], panRange: [0, 10000] },
			zoom: {
				interactive: false
			},
			pan: {
				interactive: false
			}
    	};

		var plot = $.plot($("#cw"), plot_data, options);

		// Write the name of the metric to the graph
		$('#header > span').prepend('<h3>' + metric + ' / ' + id + '<h3>');	


		
		// Initialize the graph
		getData();

		

		// Query fetch_data.php for flot data
		function getData() {
			var dataString = 
				'ns='+ namespace + 
				'&metric=' + metric +
				'&start=' + start_time +" hours" +
				'&end=' + end_time +
				'&interval=' + interval + 
				'&id=' + id +
				'&dim=' + dim +
				'&mtype=' + metric_type +
				'&utype=' + unit_type;
			$.ajax({
				type: "POST",
				url: "json.php?" + dataString,  // probably it should by a ASMX amd not ASPX page?
				data: dataString,
				dataType: "json",
				error: function (json) {
					//alert('error');
				},
				success: function (json) {
						delete json;
					// Check for errors, like requesting too many metric points
					if (json.error) {
						$('#cw').empty();
						$('#cw').append('ERROR: ' + json.reason);
					} else {
					// If no errors exist, update the graph
						var plot = $.plot($("#cw"), plot_data, options);
						updateGraph(json);
					}	
				}
			});
		}

		// Update the graph with data retrieved from fetch_data.php
		function updateGraph(r) {
			plot_data = r;
			// Redraw the graph with the new data
			plot.setData(plot_data);
			plot.setupGrid();
			plot.draw();
		}
		// Set the update frequency, 20000 = 20s
		// There's not much use for making this faster as CloudWatch doesn't update often enough
		setInterval(getData, 8000);


		// We want to get data from the modal dialouge


        </script>
		  <div class=toolbox>
			<a href="#" id="thelink">Graph Settings</a><img src="./media/wrench.png" alt="some_text"/>
			</div> 
		   <div id="thedialog">
			<p>namespace</p>
			<select id ='namespace_select'>
				<option>Select</option>
			</select>
			<hr>
			<p>metric name<p>
			<select id='metric_name_select'>
				<option>Select</option>
			</select>	
			<hr>
			<p>dimension</p>
			<select id ='dimension_select'>
				<option>Select</option>
			</select>
			<hr>
			<p>metric id<p>
			<input type='text' id='id_name'/>
			<hr>
		   <p>resolution</p>
		   <div id="interval_slider"><p></p></div>
			</br>
			<hr>
		 	<p>start time</p>
		   <div id="start_slider"><p></p></div>
			</br>
			<hr>
			<p>Metric Type</p>
			<select id='mtype_select'>
				<option>Sum</option>
				<option>Average</option>
				<option>Minimum</option>
				<option>Maximum</option>
				<option>SampleCount</option>
			</select>
			</br>
			<hr>
			<p>Unit</p>
			<select id='unit_type'>
				<option>Count</option>
				<option>Seconds</option>
			</select>
		</div>
    </body>
</html>
