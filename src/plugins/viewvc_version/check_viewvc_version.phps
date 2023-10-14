<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-12-18
 */

declare(ticks=1);

require_once __DIR__ . '/../../framework/vnag_framework.inc.php';
require_once __DIR__ . '/ViewVCVersionCheck.class.php';

$job = new ViewVCVersionCheck();
$job->run();
unset($job);
