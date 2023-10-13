<?php /* <ViaThinkSoftSignature>
lVcMi5qSDkLUYGRizTEBBN/wfe0iXetbtF5/m/AB47STu6J6jJ1s3Q89hu0XEHcv3
20KxLns4ikt3f05Uj8OnB6yYrQ50xl8AXtSDcy+k4Iz/7AXLUB6z95gMx9BctAk5x
pWum1/0p7y98sZb7cVOZRJjC4LDW+YWXV8OQUdx4/KCKbTp8KL0HykKXff4hw9fU/
Yas1sbDNmUEEG0UwEFPON73sgJm/KimsLWYP8VSQ9W0nvaRWyDLtPtRXsA4ZZ5WXp
tYeMdhN2txOzZl3AFeTjbXbeSmNNh0orlY1i4TquzoK62SxPwMuzMEKpPWc0LJmxN
ev9rWB/ucB/DSkVFqbss0tRkECjHyZxzbT0tjCWgSQhIc0jQzjXQQoqJyIOnUVUJn
Y4S1rPAOdgijnD6apfnegmCQFhQrg7Fua4bURzJ3sMu7JT7QHiL1L3X+Xk+p14bto
ZbtGLMxEFnOvroEJ2iYmxJYB/9fSKQAtS2yrA4c67frHNZrkvw9u+dDiTJPc9JxpM
ufsTyoxTQqocrOJg3pzqlsAu0WmVO3UJG5OVpSjxH+4Wyk79cEELZNcctJaE2NMh9
gU3rzDyufa5nff0hyiku3qIGi4JdWFxX6ko2qh2kycuWWF1EqEW8ufORlwDw1S4Pd
iAkwMsmuMccRYtY60D0GZkYzcC4glIv3sD0KszTuTl4mlJtCiYuAA2CmBut6Hb9S4
IUr0DSc6tAAXZI4zJScONeAfQn0mH6yEqz55atAT4KJwyMWL4DoO6IfdiKRsvq9Rs
Qrf7oK2zjym2EZaiVjo2jx6VIh9B4Mbf8efcHkdq5EC7LsYcyPiF02P4XYDA8zcn9
jLcrpbCgz/ZLSAdM7AzWLh0WYxaBiqfbtt13aGapkoKFxIL0YlINCXt/GAlwpyalj
jzbjoGB/BrUibPa+6FLhY2wEJ/jgpg0VJRn5TPsyuBYnt4+YrzkIQ4EIFm1dbEwt9
U+ONNO4xjqVrvcTgvfTpGHw0gcU8XmqsKnWmA8obc4I7zXco9WBnl1fTCL1XRNfHd
IaOfxgVtE6dKbqBKGw/KEttb0vpFyw3jPGvyyVmQOkS2eluc7GgutlG7hpPennheZ
Yxt5XJV2P+RxkZdO7kruACpwtkMn9qMTuCL8eWIgfJ3ddT8z7H5j1F92wavSYhYVK
FJ3IO60q7xAFEF23CYoQW0zJBORbVR5+9ncrR49fa/938sRQf41IrZZzoXVHZU4T5
ar7gdPg5DR7CytfkRlFZzJ2KBHwGHX6nTppEUxF9KgsDVOGzPYxQVs3vSqvH4yelc
A+oVPffLPjCVp7jZ1Ht/L519ZG6oHJum/r2eeE39JH15q0Ab/AL3kCrwkyVAPxXUA
g==
</ViaThinkSoftSignature> */ ?>
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
		$this->getHelpManager()->setVersion('1.0');
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
