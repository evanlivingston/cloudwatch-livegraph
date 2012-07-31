<?php

require_once 'AWSSDKforPHP/sdk.class.php';
$cw = new AmazonCloudWatch();

// Amazon works in UTC
date_default_timezone_set('UTC');

// This should be something like AWS/RDS for RDS, AWS/ELB for ELB, etc.
// You can find more info in the CloudWatch section of the AWS Console
$namespace = $_GET["ns"];

// Metric to retrieve, this should be something like RequestCount, FreeStorageSpace, etc.
$metric = $_GET['metric'];

// Start & end times to retrieve statistics in, strtotime() compatible, default will be up-to-date info
//$start_time = $_GET['start'];
$start_time = isset($_GET['start']) && !empty($_GET['start']) ? $_GET['start'] : '-12 hours';
$end_time = isset($_GET['end']) && !empty($_GET['end']) ? $_GET['end'] : 'now';

// Interval for statistics, i.e. retrieve for every X seconds
// Must be at least 60 seconds and must be a multiple of 60
$interval = isset($_GET['interval']) && !empty($_GET['interval']) ? $_GET['interval'] : 300;

// Metric type, can be Average, Sum, Min, etc.
$metric_type = isset($_GET['mtype']) && !empty($_GET['mtype']) ? $_GET['mtype'] : 'Sum';

// Unit to return, check the API reference for all the possibilities
$unit_type = isset($_GET['utype']) && !empty($_GET['utype']) ? $_GET['utype'] : 'Count';

$dim = $_GET['dim'];
$id = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : null;
$dimensions = array('Name' => $dim, 'Value' => $id);

$response = $cw->get_metric_statistics($namespace, $metric, $start_time, $end_time, $interval, $metric_type, $unit_type, array('Dimensions' => array($dimensions)));
$d = array();


// Lets return an errors
if (isset($response->body->Error)) {

	echo json_encode(array('error' => true, 'reason' => (string) ($response->body->Error->Message)));
// If no error exist, return data
} else {

	foreach($response->body->GetMetricStatisticsResult->Datapoints->member as $point) {
		// Create an array with all results with the timestamp as the key and the statistic as the value

		$time = strtotime($point->Timestamp) * 1000;
		//$d[$time] = (int)$point->Sum[0];
		$d[] = array($time, (int)$point->Sum[0]);
	}

	// Sort by key while maintaining association in order to have an oldest->newest sorted dataset
	rsort($d);
					

	// Knock off the newest value
	array_pop($d);

	echo json_encode(array($d, array()));
}
