<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

define('OUTPUT_UOM', 'MB');
define('ROUND_TO', 0);

class OpenDeletedFilesCheck extends VNag {
	protected $argDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwc');

		$this->getHelpManager()->setPluginName('open_deleted_files');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks for open deleted files (which require space but are not visible/accessible anymore).');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d directory] [-w warnSizeKB] [-c critSizeKB]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

#		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'directory', 'Directory to check (e.g. /tmp)Directory to check (e.g. /tmp)'));
	}

	protected static function check_open_deleted_files($dir_to_check = '/') {
		// Note: Requires root
		exec('lsof -n', $lines, $ec);
		if ($ec != 0) return false;

		/*
		$lines = explode("\n",
		'COMMAND     PID   TID TASKCMD               USER   FD      TYPE             DEVICE    SIZE/OFF       NODE NAME
		php-cgi     430                          oidplus    3u      REG               0,42           0 1502217042 /tmp/.ZendSem.uhCRtC (deleted)
		apache2     838                             root  150u      REG               0,42           0 1499023202 /tmp/.ZendSem.RFcTM9 (deleted)
		postgres   1060                      gitlab-psql  txt       REG                9,0     9291488   47189384 /opt/gitlab/embedded/postgresql/12/bin/postgres (deleted)
		php-cgi    1573                         owncloud    3u      REG               0,42           0 1499024339 /tmp/.ZendSem.2Qh70x (deleted)
		php-fpm7.  1738                             root    3u      REG               0,42           0  434907183 /tmp/.ZendSem.unGJqF
		php-fpm7.  1739                         www-data    3u      REG               0,42           0  434907183 /tmp/.ZendSem.unGJqF (deleted)
		php-fpm7.  1740                         www-data    3u      REG               0,42           0  434907183 /tmp/.ZendSem.unGJqF (deleted)
		runsvdir   1932                             root  txt       REG                9,0       27104   45351338 /opt/gitlab/embedded/bin/runsvdir (deleted)
		');
		*/

		$line_desc = array_shift($lines);
		$p_name = strpos($line_desc, 'NAME');
		if ($p_name === false) return false;

		$nodes = array();

		foreach ($lines as $line) {
			if (trim($line) == '') continue;

			$name = substr($line, $p_name);

			preg_match('@.+\s(\d+)\$@ism', substr($line, 0, $p_name-1), $m);
			$tmp = rtrim(substr($line, 0, $p_name-1));
			$tmp = explode(" ", $tmp);
			$node = end($tmp);

			$tmp = rtrim(substr($line, 0, $p_name-strlen($node)-1));
			$tmp = explode(" ", $tmp);
			$size = end($tmp);

			if (substr($name, 0, strlen($dir_to_check)) !== $dir_to_check) continue;

			if (strpos($name, ' (deleted)') === false) continue;
			if ($size == 0) continue;

			$nodes[$node] = $size;
		}

		$size_total = 0;
		foreach ($nodes as $node => $size) {
			$size_total += $size;
		}
		return $size_total;
	}

	protected function cbRun($optional_args=array()) {
		$dir = $this->argDir->getValue();
		if (empty($dir)) $dir = '/';
		$dir = realpath($dir) === false ? $dir : realpath($dir);
		if (substr($dir,-1) !== '/') $dir .= '/';

		$size = self::check_open_deleted_files($dir);
		if ($size === false) throw new VNagException("Cannot get information from 'lsof'");

		$this->checkAgainstWarningRange( array($size.'B'), false, true, 0);
		$this->checkAgainstCriticalRange(array($size.'B'), false, true, 0);

		$m = (new VNagValueUomPair($size.'B'));
		$m->roundTo = ROUND_TO;
		$sizeOut = $m->normalize(OUTPUT_UOM);

		$msg = "$sizeOut opened deleted files in $dir";
		$this->setHeadline($msg);
	}
}
