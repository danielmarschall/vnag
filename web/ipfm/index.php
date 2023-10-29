<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-29
 */

// This file (index.php) is intended to be called by your browser.
// If you want to access via Nagios/Icinga or CLI, call "bin/ipfm.phar" instead.

declare(ticks=1);

define('USE_DYGRAPH', false); // Slow!
define('ALLOW_HTTP_PARAMTER_OVERWRITE', false); // true: Allow the user to set their own ?w=...&c=... etc.

if (!ALLOW_HTTP_PARAMTER_OVERWRITE) {
	$_REQUEST['L'] = '/var/log/ipfm';
	$_REQUEST['l'] = '10TB';
	$_REQUEST['w'] = '6TB,8TB';
	$_REQUEST['c'] = '8TB,15TB';
}

?><!DOCTYPE HTML>

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<meta http-equiv="refresh" content="300; URL=<?php echo htmlentities($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>">
	<title>Traffic monitor</title>
	<?php if (USE_DYGRAPH) { ?>
	<!--[if IE]>
		<script type="text/javascript" src="dygraph/excanvas.js"></script>
	<![endif]-->
	<script type="text/javascript" src="dygraph/dygraph-combined.js"></script>
	<?php } ?>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<meta name="robots" content="noindex">
</head>

<body>

<h1>Traffic monitor</h1>

<?php

require_once __DIR__ . '/../../src/framework/vnag_framework.inc.php';
require_once __DIR__ . '/../../src/plugins/ipfm/IpFlowMonitorCheck.class.php';

$job = new IpFlowMonitorCheck();
$job->http_visual_output    = VNag::OUTPUT_EXCEPTION;
$job->http_invisible_output = VNag::OUTPUT_ALWAYS;
$job->run();
unset($job);

?>
</body>

</html>
