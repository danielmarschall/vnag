<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2026-04-16
 */

// TODO: Besides the total size of the files, also have a warning/critical range of the number of opened files
//       So, we have two performance data entries:  Size and Count.  And two warning ranges, and two critical ranges.

declare(ticks=1);

define('OUTPUT_UOM', 'MB');
define('ROUND_TO', 0);

class OpenDeletedFilesCheck extends VNag {
	protected $argDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwc');

		$this->getHelpManager()->setPluginName('open_deleted_files');
		$this->getHelpManager()->setVersion('2026-04-16');
		$this->getHelpManager()->setShortDescription('This plugin checks for open deleted files (which require space but are not visible/accessible anymore).');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d directory] [-w warnSizeKB] [-c critSizeKB]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

#		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'directory', 'Directory to check (e.g. /tmp)Directory to check (e.g. /tmp)'));
	}

	protected function get_deleted_open_files($min_size_bytes = 1, $dir = '/') {
		$results = [];

		foreach (glob('/proc/[0-9]*/fd/[0-9]*') as $fdPath) {
			$target = @readlink($fdPath);
			if ($target === false) continue;

			// only deleted files
			if (strpos($target, '(deleted)') === false) continue;

			// cut "(deleted)"
			$realPath = preg_replace('/ \(deleted\)$/', '', $target);

			// only specific directory
			if (strpos($realPath, $dir) !== 0) continue;

			// Get size via file descriptor
			$size = @filesize($fdPath);
			if ($size === false || $size < $min_size_bytes) continue;

			// Extract PID
			if (!preg_match('#/proc/(\d+)/fd/#', $fdPath, $m)) continue;
			$pid = $m[1];

			// Get process name
			$cmd = @file_get_contents("/proc/$pid/comm");
			$cmd = trim($cmd);

			$results[] = [
				'pid' => $pid,
				'process' => $cmd,
				'fd' => $fdPath,
				'size' => $size,
				'file' => $target
			];
		}

		return $results;
	}

	protected function cbRun($optional_args=array()) {
		$dir = $this->argDir->getValue();
		if (empty($dir)) $dir = '/';
		$dir = realpath($dir) === false ? $dir : realpath($dir);
		if (substr($dir,-1) !== '/') $dir .= '/';

		$files = $this->get_deleted_open_files(0, $dir);

		$total = 0;
		$verbose = "";

		usort($files, function($a, $b) {
			return $b['size'] <=> $a['size'];
		});

		foreach ($files as $f) {
		    $verbose .= "PID {$f['pid']} ({$f['process']})\n";
		    $verbose .= "  {$f['file']}\n";
		    $verbose .= "  {$f['size']} bytes\n\n";
		    $total += $f['size'];
		}

		$verbose .= "TOTAL: $total bytes\n";

		$this->checkAgainstWarningRange( array($total.'B'), false, true, 0);
		$this->checkAgainstCriticalRange(array($total.'B'), false, true, 0);

		$m = (new VNagValueUomPair($total.'B'));
		$m->roundTo = ROUND_TO;
		$totalOut = $m->normalize(OUTPUT_UOM);

		$msg = count($files)." opened deleted files in $dir with total size $totalOut";
		$this->setHeadline($msg);

		$this->addVerboseMessage($verbose, VNag::VERBOSITY_SUMMARY);
	}
}
