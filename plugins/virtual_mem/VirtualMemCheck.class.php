<?php /* <ViaThinkSoftSignature>
XdqA1AEsWl/tBsy3uppNSoRkapKPN0TaRG6nrsRGwK7u3QG0a9AdlGGtQw57I8Anb
PB1fKnyyZGFhB8JnoFK3TdoPEA0NVxN2CnMxghYUNjKMfJxbW/PmtDuOF4V+MAJk3
uriAEkzVBgIgXwQhZ0b8Xkr5x33U1dfKiuhhUlwlEOKIhS7jg4FT50CY2/cCYXW6l
y+ENDNMHtuPBP/dfq7/8CigPWIfnZDyN/QgaMy/kOGvx8L0K6LyK79ac1TXpStmrl
puaZYNZGj+TkTh6buDLJXzo8BBGWUMjGybg7La3G25b7CdRkGesL1hFMOeak1W+q0
NlaVCSlEYkkoARtdWyGMS7upY4vmLYuq8kPg6b9DVudZMXV4gRTkDu2s4xNsOamjE
Yu6kCFZ/2ZqgeWGWOMDWuE/gKvFIS7RfjPkwjeHvAHqW9wI8BtX53e+yKnE40LACo
tDQ+fqkS/4C2UJYOZcNVfGlORsxtymmwjCr6OnMdEdImFHmKqo7fKdeQ5cSkj3imp
qG2z6qLX8clbRqaJU1h+Qdc7i44KvN+/tGDsPvGemIzRvJk4WujpuVrztx5O0ebnX
uBd6OAGuHJlY3Dkd6B/Tx9/YbsOqUbe8KEJ6TFnQPW+V+njKdTuGmY9F62xNynd47
NuUZebnuAK4qAVxqMFpRAzW++20tbTMlGcE9EvdZkxKH/2Dl78cWxe8ZO7aagRZ3h
u7MKV8IDCzwReJqUv6OaUl6XGlviF9PDu+bTCvT+Lzs6f3txYvlJcSXuGT+G/0nyN
Wyzg49m9jSSAUT1TzHclS0bFWKQFnm7bzrXdNea37+GvFFSh22BkYNTBcvB9o+ZJF
Juy5DGIEEi+v2UP95BQo9PJDP81+WlR+lsgL1kEjZiprGkrzEso4OubQUuwGW2Xbb
bFDLSzyQgFiLnBUyEJ/9LwvcF8Vf2bfgTw72vHSovZFP+fOhD60fZoOq5K6NMzlm1
9TD/3aAZ6MayuBgr55aaPPYMSJuwsyjKqnILxa/YuwZwTsBKrWiz6Osy+PLjSXbfl
EnKIQ+FBiwqnPur9PTxOwtcYk2dCBknW728M4KYAsYQ7KAEwjqv6unD/uB7+xEk24
JTTG6pkQhuu3Hz2vf8deR1mgdmE2U8IySOMx5gw72uQFwP8B5dWh+eIcY3PmKvMTc
8ehGSqnmysjVpsblcjydEALpaRJ626stfmaDn5yZJlBmsplHfqMyFEIE5YiRUqATR
nAFF0vhlVv5OAcZf0G4PRq8akg4KuprSoIYR7F/Mo6cCLZVn3sinqAfkYadm/KeUx
IStZou4PvPRjNyenI2EgLmrqHsBaNeMT349346AQ1BZUJIaiEqJ3DaiscN+ZvnQ3o
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

		// When the user writes "-w 10GB" then he actually means "-w 10GB:~" or "-w @~:10GB", so these commands allow this notation:
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
