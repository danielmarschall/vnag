<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

define('OUTPUT_UOM', 'GB');
define('ROUND_TO', 0);

class VirtualMemCheck extends VNag {
	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwc');

		$this->getHelpManager()->setPluginName('check_virtual_mem');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks the amount of free virtual memory (real memory + swap combined).');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-w freeMemKB|%] [-c freeMemKB|%]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// In this context, when the user writes "-w 10GB" then they probably mean "-w @10GB". Make sure that the user doesn't do it wrong
		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_LT_X_BAD;
		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_LT_X_BAD;
	}

	protected function cbRun($optional_args=array()) {
		if (!file_exists($meminfo_file = '/proc/meminfo')) {
			throw new VNagException("Cannot find $meminfo_file");
		}

		$meminfo_attrs = array();
		foreach (file($meminfo_file) as $line) {
			$line = trim($line);
			if ($line == '') continue;
			list($key, $value) = explode(':', $line, 2);
			if ((!strstr($key,'Pages')) && (strtolower(substr($line,-2)) !== 'kb')) {
				throw new VNagException("meminfo shall contain Kilobytes!");
			}
			$meminfo_attrs[$key] = (int)trim(substr($value, 0, strlen($value)-2));
		}

		if (!isset($meminfo_attrs['MemAvailable'])) {
			$meminfo_attrs['MemAvailable'] = $meminfo_attrs['MemFree'];
			if (isset($meminfo_attrs['Buffers'])) $meminfo_attrs['MemAvailable'] += $meminfo_attrs['Buffers'];
			if (isset($meminfo_attrs['Cached']))  $meminfo_attrs['MemAvailable'] += $meminfo_attrs['Cached'];
		}

		$totalKB = $meminfo_attrs['MemTotal']      + $meminfo_attrs['SwapTotal'];
		$freeKB  = $meminfo_attrs['MemAvailable']  + $meminfo_attrs['SwapFree'];
		$freePercent = $freeKB/$totalKB*100;

		$this->checkAgainstWarningRange( array($freeKB.'KB', $freePercent.'%'), false, true, 0);
		$this->checkAgainstCriticalRange(array($freeKB.'KB', $freePercent.'%'), false, true, 0);

		$m = (new VNagValueUomPair($freeKB.'KB'));
		$m->roundTo = ROUND_TO;
		$freeKB = $m->normalize(OUTPUT_UOM);

		$m = (new VNagValueUomPair($totalKB.'KB'));
		$m->roundTo = ROUND_TO;
		$totalKB = $m->normalize(OUTPUT_UOM);

		$msg = "$freeKB free of $totalKB (".round($freePercent,ROUND_TO)."% free)";
		$this->setHeadline($msg);
	}
}
