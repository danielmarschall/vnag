#!/usr/bin/php
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-14
 */

foreach (glob(__DIR__.'/bin/*.conf') as $a) {
	$a = basename($a);
	echo "cd /etc/icinga2/conf.d/ && rm -f vnag_$a\n";
	echo "cd /etc/icinga2/conf.d/ && ln -s ".__DIR__."/bin/$a vnag_$a\n";
}
