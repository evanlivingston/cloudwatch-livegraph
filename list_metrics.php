<?php

/**
  * These functions are intended to retrieve or generate lists of metrics. 
  * Retrieving all metrics for an AWS account takes a considerable amount of time.
  * It is probably best to construct a complete list (metrics.json) and update it only as new 
  * metrics are introduced to cloudwatch.
*/
require_once 'AWSSDKforPHP/sdk.class.php';

$cw = new AmazonCloudWatch();


// Get the metric namespace for which you'd like info. e.g. AWS/EC2
$namespace = isset($_GET['ns']) && !empty($_GET['ns']) ? $_GET['ns'] : null;
$function = isset($_GET['func']) && !empty($_GET['func']) ? $_GET['func'] : null;



//-----------------------------------------------------------------------------
// Metrics have names that are pretty descriptive such as RequestCount or NetworkIn
function list_names($namespace) {
	global $cw;

	$metrics = array();
	$next_token = true;

	// Amazon limits the number of results, and there are a ton of results.
	// as long as we receive a NextToken, lets loop
	while (isset($next_token)) {
		$has_next = $cw->list_metrics(array('Namespace' => $namespace))->body; 
		$response = $cw->list_metrics(array('Namespace' => $namespace, 'NextToken' => $next_token))->body->ListMetricsResult->Metrics->member;
		foreach ($response as $item) {
			array_push($metrics, $item);
		}
		$next_token = $has_next->ListMetricsResult->NextToken;
	}
	$dimensions = array();

	// Lets filter all the parts we don't care about.
	foreach ($metrics as $metric) {
		if (!in_array((string)$metric->MetricName, $dimensions))
			array_push($dimensions, (string)$metric->MetricName);
	}
	return $dimensions;
}

//-----------------------------------------------------------------------------
// Metrics can be accessed by a quantifier such as InstanceID or LoadBalancerName.
// Here we can retrieve a complete list of quantifiers, or dimensions.

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
// Here is an attempt at constructing a complete list of metric names related to their 
// dimensionsfor a given namespace.

function list_all($namespace) {
	global $cw;

	$metrics = array();
	$next_token = true;

	// Amazon limits the number of results, and there are a ton of results.
	// as long as we receive a NextToken, lets loop
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
// Convenience logic for vieiwing results in the browser. 
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

