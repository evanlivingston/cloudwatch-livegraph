<?php

require_once 'AWSSDKforPHP/sdk.class.php';

$cw = new AmazonCloudWatch();

$namespace = isset($_GET['ns']) && !empty($_GET['ns']) ? $_GET['ns'] : null;
$function = isset($_GET['func']) && !empty($_GET['func']) ? $_GET['func'] : null;

function list_names($namespace) {
	global $cw;

	$metrics = array();
	$next_token = true;
	while (isset($next_token)) {
		$has_next = $cw->list_metrics(array('Namespace' => $namespace))->body; 
		$response = $cw->list_metrics(array('Namespace' => $namespace, 'NextToken' => $next_token))->body->ListMetricsResult->Metrics->member;
		foreach ($response as $item) {
			array_push($metrics, $item);
		}
		$next_token = $has_next->ListMetricsResult->NextToken;
	}
	$dimensions = array();

	foreach ($metrics as $metric) {
		if (!in_array((string)$metric->MetricName, $dimensions))
			array_push($dimensions, (string)$metric->MetricName);
	}
	return $dimensions;
}

//-----------------------------------------------------------------------------
function list_dimensions($namespace) {
	global $cw;

	$dimensions = array();
	$response = $cw->list_metrics(array('Namespace' => $namespace))->body->ListMetricsResult->Metrics->member;
	foreach ($response as $dim) {
		if (sizeof($dim->Dimensions->member) > 0) {
			if (!in_array((string)$dim->Dimensions->member->Name, $dimensions))
				array_push($dimensions, (string)$dim->Dimensions->member->Name);
		}
	}
	return $dimensions;
}
//-----------------------------------------------------------------------------
function list_all($namespace) {
	global $cw;

	$metrics = array();
	$next_token = true;
	while (isset($next_token)) {
		$has_next = $cw->list_metrics(array('Namespace' => $namespace))->body; 
		$response = $cw->list_metrics(array('Namespace' => $namespace, 'NextToken' => $next_token))->body->ListMetricsResult->Metrics->member;
		foreach ($response as $item) {
			array_push($metrics, $item);
		}
		$next_token = $has_next->ListMetricsResult->NextToken;
	}
	
	$metrics = $cw->list_metrics(array('Namespace' => $namespace))->body->ListMetricsResult->Metrics->member;
	$all = $cw->list_metrics(array('Namespace' => $namespace))->body->ListMetricsResult->Metrics;
  	$listOfMetrics = array();

    foreach ($metrics as $metric) {
		$metric_name = (string)$metric->MetricName; 
		$dimension_name = (string)$metric->Dimensions->member->Name;

		if (!in_array($dimension_name, $listOfMetrics[$metric_name]) )
			$listOfMetrics[$metric_name][] = $dimension_name;
	}
    return $listOfMetrics;
}
//-----------------------------------------------------------------------------
if ($function == 'names') {
	//list_dimensions($namespace);
	echo json_encode(list_names($namespace));
}
elseif ($function == 'dimensions') {
	echo json_encode(list_dimensions($namespace));
}
elseif ($function == 'all') {
	echo json_encode(list_all($namespace));
}
?>

