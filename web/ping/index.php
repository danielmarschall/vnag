<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-11-18
 */

// This file (index.php) is intended to be called by your browser.
// If you want to access via Nagios/Icinga or CLI, call "bin/ping.phar" instead.

declare(ticks=1);

?><!DOCTYPE HTML>

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>Online ping</title>
	<meta name="robots" content="noindex">
</head>

<body>

<h1>Online ping</h1>

<?php

echo '<form action="'.htmlentities($_SERVER['SCRIPT_NAME']).'" method="GET">
Host: <input type="text" name="H" value="'.htmlentities($_REQUEST['H'] ?? '').'" size="40">
<input type="submit" value="Check">
</form>';

require_once __DIR__ . '/../../src/framework/vnag_framework.inc.php';
require_once __DIR__ . '/../../src/plugins/ping/PingCheck.class.php';

$job = new PingCheck();
$job->http_visual_output    = ($_REQUEST['H']??'')=='' ? VNag::OUTPUT_NEVER : VNag::OUTPUT_EXCEPTION;
$job->http_invisible_output = VNag::OUTPUT_ALWAYS;
$job->run();
unset($job);

?>

<p>Please note that this page also outputs an invisible <a href="https://www.viathinksoft.de/projects/vnag">VNag</a>
machine-readable part which can be read using the webreader plugin.</p>

</body>

</html>
