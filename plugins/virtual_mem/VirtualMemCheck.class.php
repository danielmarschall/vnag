<?php /* <ViaThinkSoftSignature>
c1wGuhv9GU+clZ9207sxwFamirQi5OPT+4J/Rir1QS5uJosEcvuOX+ZqWEX03ZUxT
t1LeyccWq0r4NJhhLzroMdBqrI/5wYJlttbZxgMVqODvjnQqWbSljb/FtQbGCgOmZ
1TjrART4ukk/l9ELnJJuTx5xmUOODuKiMKJcIYV5U+a3Ig9u4fcGpYKM5fwpep5p9
lJ+Xibt/UESgPB4bAoAxJE4szOZ31cN9DxVohoIUoQ1V+ZYdLu6ZeNAQ122QNHYk4
S1yQf+nRqTc0dVKS7bcrgYrWaX6GbkxYq18TY2w2KuuYWEjBSoxI8a0+S6XydHfGq
fYhG0L1mLRND6Zdutx9WDNr0gfKvEE8djezOcUH5RzVFzLK71wAKqoi9JyNkNLHds
u7ptuWGnvNNfM+vSDiRr0e+80fKha3jP8ViWdEZMxEfNUIPjFqj0UQM8n9pk8utoF
SpmVzFHxG8VijCT0KdMo+kID5LG2Ra22I8UKj7JWmkTMX0etFcyYNvYybJmz1p8Mx
930UkzAFx+hq1TH9zgcPD0Y6hmJs3cGZDk8Lq5Ls5AZKuqFZanZrCy61t4hNG3sZ7
fkWe/I71TlMAWsuZygo0kYv3EqPBaJgCAYfB1Xr98FBoslpNPg3Bn8znmK3SBCmnM
o/ViDGz955lTX26WwtbsXtXsU5fdJ3f072O8n9+Kjab1NwZptJllTPlQjwZT9VOnb
G2/n7HyOT7luSrqXfz8q6ZRWKr47nqXTw+jIEWeft3HzpCqdO8aLBm4h90FvkrXn6
nGjTt802hG3b0DFXZq5brzs4+Tp3y4ghkGvlBF/q5vm/e4DdXFXgMCQ8Ndk9sWzG0
ywYLD0bdVGIorqVuAM70UwD769s5+UXs3I1puRL3gXpk47FHQYKMTPrNgvmbbY3GM
bBnkhLb/exmCBtR0F8XA+nCF9nnocuixVGhaEJBoXHhM28cB1mVJV+zP5Mx1vztCd
PCUd/m86xmoTC62Zs1NK4E0rbJXoaKI0SEzT+QK4JG9NpoS+JDKLPiQ3p/uSt0mcM
HJTOpY09QobYczE/nTd9OJ6fZX19C1YHufpHDowvqvHItwrmZ8y1s4J9PtoAjK9Z9
vyjEGPexHZtdnUYeFqDtYur6v+D0v4TqVsDTn2SUDyR1EjDRmfCDtMhOsHy55W/xT
kDN/rt+RCzCthUJK1cfOcvEnfi9sAmDQBT2Hqbbe8kmcL5myZiGYsLq7oCpQOSYVm
9DpwNTbakHSn7cxGMWkwdhNATjMc2EAH92snZAzhZOOqTGtJoDDPm01mnLFf4Olt7
zl6CMM/UpkjAfBmUoVbIAxORSbUQBPCtrCpg1BCfWNhFbTIfxJSNWnzsrqw0eD3j0
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-05-19
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
				throw new Exception("meminfo shall contain Kilobytes!");
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
