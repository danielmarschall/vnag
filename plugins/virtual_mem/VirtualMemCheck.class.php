<?php /* <ViaThinkSoftSignature>
qMJiodDpZAq5lmMr1YTFAzBz7y3WUrxNn6+gko5VdixCfUQEjrRySixRs61b8d1N1
MKEHjaL2mwgMYT7TsOisNeiTad8iUe4fDBzR7XnyX1LbvmOCDMbvRHwLR/LBf+N9R
kc3FwU0xmRHLpi5zJ2wwLCGwiurIVyXKoAUuYda8LIuJsg9XyfHiNFoJz91WnLuK8
FleyiTF7timGeMFMP/FYnIBmuBjWnG5kGk79naWFLML386W/abe5VllqfeQMFGkLS
BFlExWb5V6qaA/BO+0jheB2LOh6xNMkOEYvCdkKHWDpbdVG/W3gRsnE7cVJV7fKkB
Gt3nh89CS0XLswUDH3RWA/NCGBj+tc/QcIdFTezu4QSFaUO63OOPusEVrZZG80vUl
5vKIPtKakVJrix/EYzOBRC6fNRIakkB/q8k1UZD27Fa4ySQJgedJm6hIa7q+3Ln9M
X6083Cyeu5nMMqQy7emY9q5g6EPJugxO1GsxVegHN800coIlEipLCDANvr/+jOOb/
K9jf0j4+RhNUdSmXV3HTLvEKOnL6SsPCtFlo9wKOwUBfqrlBBTBV3A4DLtTD1Oc/6
wJKDPIf04yigCxw+Nn2oK9TZGH8KHXscTPxru6BIn07hZq9oGO5EyW2K2AISY4OjF
GaMAuns2jAQHPacRWWvc9gp67nk9TfPFStZYLWkSfzeSaAn1NEBncAriGWNOxGnpb
XAXKZ8EiCQNHrqPnDgObgkgx5/PxhxFoacVUzl9CWZWSiKmbbppomlVDWuPUqJx16
BNOvWu6H2oWq+sYav1cvTnZ4dpBKiMLNHLma8k6sA6UdSxuRujTSFll9cCVrDlj4W
MSYJfufXv4pD3Qp5SQLlXj70ioM5jAx04sRSNQg/BDPcdiTzOXGx64B/m0gDb6x4C
WVBrB0MMml4u3R8NFsCQpmDTAWVICAXdV5nNa8LcXrB5JQz6R9UFau1JuVN7ES6Yb
Ev1og+Jh3DzY5iumbVWUugAppBV7MdgHN50vIwFC3YrVIfUjSyC4nnr3JZ4uuQfqi
PY8NvY3RQ5J7avSt7Z67I5lz5swwrDro3iGk8a0tMGHWg8BDYwijuedici0fFpu8o
K1ss7Kmwpvuti9bk4DvYH4D8gUjN3s8H0fE3D+v6sPNg3FQXs64Ob8jptqEUKMRfz
XuIYbI89WgLV0TADxTm8kmDAJKOYW9xBQqdJh3UYo0Rwwkd7X3hvAUi8JojHWlizN
JsqWm+6wv+HfX4105MVNXbANk1w4Go5fJ2xSjXJdKQESoNjhipKgSk7mYbRcZON+Q
vLaHROFrQ/EK2lAmcDcJUKf+fK0JZGTm7+2xeLwE65jAvwtD40Dkw9JNy4AVo8k02
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-18
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

	private function getMemAttr($attrName) {
		$cont = file_get_contents('/proc/meminfo');
		preg_match('@^'.preg_quote($attrName,'@').':\s+(\d+)\s+kB$@ismU', $cont, $m);
		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		if (!file_exists('/proc/meminfo')) {
			throw new VNagException("Cannot find /proc/meminfo");
		}

		$totalKB = $this->getMemAttr('MemTotal') + $this->getMemAttr('SwapTotal');
		$freeKB  = $this->getMemAttr('MemFree')  + $this->getMemAttr('SwapFree');
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
