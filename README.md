## cloudwatch-livegraph 
An API for generating json, and UI for generating graphs from AWS cloudwatch metric data. Graphing done with [flot](https://github.com/flot/flot).  Enhancements welcomed!

![simple graph showing requests to an ELB.](http://i.imgur.com/WFV3Q.png "simple graph showing requests to an ELB.")


## Usage

To generate json:
<pre>
localhost/cloudwatch-livegraph/json.php?ns=AWS/EC2&metric=RequestCount&start=-8%20hours&end=now&interval=60&id=i-baba23342&dim=InstanceId&mtype=Sum&utype=Count
</pre>

Go generate graphs:
<pre>
localhost/cloudwatch-livegraph/graph.php
</pre>

## Requirements
PHP SDK for Amamazon AWS must be properly installed and configured with your AWS credentials. [aws-sdk-for-php](https://github.com/amazonwebservices/aws-sdk-for-php.git)

## Notes
Combinations of the metric parameters are tricky. Querying AWS for them takes an enormous amount of time. Currently the data is stored in metrics.json.


