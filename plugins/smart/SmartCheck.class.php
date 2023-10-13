<?php /* <ViaThinkSoftSignature>
bNs6RDbDtvaplN+W0zEqFdF9ouhBy80juWD/zFr+tV5QgZ2n06p5jqHQLu+4xRaW9
A6jqIg4xVnvN6OKBqBvDHuvkQTCQX3SpHN7YF/aPPDvWPeEbb5HodC/aHAgkl/aBW
d8WDmR9tv0PuPWPLdWMwz/X9qYFc9rtVmTgR0Z2A5KQ0U/gjigJ6YN450hlKwn7v4
f/RBOqb+2AJv77/eN7QZXfJIRhPz6k/QQi3woMjNflOzcbSyaVhQkpHU1MIIUbUGC
Rty5Zs214LFSrBMGwVdIzZRl/ePlf/BvcvyBzrsfXwfGJKDJw/O6ezKYb4Vtu4DcS
dt5ThQ7ZZhE9uEj09lmQHSR3aZWQy4cZ/C2/CAw933MgdQ/uiEHbD4ldfSbJXM6Qq
Ef0iUSvVsVimqRrQmzE1MmgYb1oCjPJ+usAXsMfbi3r3hG1f+wlwkjRX0MarQZlup
VljpKJwvCXyYqm47UGGTaE/ajqNuYWxnASpmhoCO+NvbEz6DzmMTDlvsJF9oJoyrU
wyCzAij9adiK0FVnmAfB6jpyQskzP4RQkXKyIgKtzOPgGL3cSLAsBi9S2TPLr5CRX
EJChPEWFFKlDvGHjg7XoerclvHYyuNc2v2Xby4El3CusDH2oCHME/wBUFjFYn7OoU
2vhbsi1fx6lX5t3eNmY9J9N4sUms63xOTr/UoeYOrPA/jzl5675V2qOx7cGZjnyYp
YUipAnTepOt5FvnlW1i9TXMgRjT1Z62UJDJ5S7MLuntiwSxzyWSYE3NQ0CvwhlKQ2
HZ8+j4r5qMxLevxbOZQAPDY65BNPpWVeyM/gPE+uTuM/XGJCEYazJJQ/h/DbdxH7e
4eBYQfvKTajwICmr5Jvg0tDrAsSRBBuGpwQxCPzS+f17yJT51IoWA4r+TilQFn0tM
vk5CAgjjQdPMMk2/fyfE8ZV4izYIHPcMrNBYlo+RGuTQd6QkoTnb8sC2kc4oXoub9
tFevAhA/Rm3oVGWIwCSH3rVfeyK/4cgSeyKHsd8xI8K7+XRvVrCE4P49yzBj/xd0P
0YQaJqONQNSUsFpEBn3ce6OR1Hg3v5ueZm+ZUS6rYWjJXO8h+KYV3olyDpa9Y4Ub2
LfVQWMhNg+Z37Y087vB2AYsephcBamqbqL766cs/+Dw8vdIkVTnB4sSqOpbWtaiAl
eIoyqDQdg7A9LBZz+7yh3NIIrgcoJ7NbliPfMAzscd+rTj6iRzt/+iJfaSiDEymOJ
5NKibqHD9a0t+gw3h6ZZ5fx8Bz8520xrpH2dLDV8llXLce/j27B0/VqKF07QIpn6w
+Z5NHZZeVUredJlJhMOEyN1v5AUenSbT0si+u30f3m8UnALLbs3QfbZ2yniuRtITV
w==
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

class SmartCheck extends VNag {
	protected $argType = null;

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

		$this->getHelpManager()->setPluginName('vnag_smart');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks the contents of the SMART data and warns when a harddisk has failed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-T <type>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// TODO: also add a command to check a single drive   [ -d /dev/sda,/dev/sdb ]
		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argType = new VNagArgument('T', 'type', VNagArgument::VALUE_REQUIRED, 'type', 'Explicit drive type e.g. for RAID devices "sat+cciss,0" for drive 0.'));
	}

	private function check_smart($dev) {
		if (!`which which`) {
			throw new VNagException("Program 'which' is not installed on your system");
		}

		if (!`which smartctl`) {
			throw new VNagException("Program 'smartctl' (usually included in package smartmontools) is not installed on your system");
		}

		$code = 0;
		$out = array();

		if ($this->argType->getValue() != '') {
			// Note: Requires root
			exec('smartctl --all '.escapeshellarg($dev).' -d '.escapeshellarg($this->argType->getValue()), $out, $code);
		} else {
			// Note: Requires root
			exec('smartctl --all '.escapeshellarg($dev), $out, $code);
		}
		$cont = implode("\n", $out);

		$msg = array();
		$status = -1;

		if (stripos($cont, 'device lacks SMART capability') !== false)  {
			// At my system (Debian 9), I get exit code 4 (which is not fully accurate)
			$msg[] = 'Device lacks SMART capability';
			#$status = VNag::STATUS_UNKNOWN;
		} else if ($code == 0) {
			$status = VNag::STATUS_OK;
		} else {
			if ($code & 1) {
				throw new VNagException("smartctl reports 'command line did not parse' (code $code).");
			}
			if ($code & 2) {
				$msg[] = "Device open failed. It is either completely defective, or in low-power mode.";
				$status = max($status, VNag::STATUS_CRITICAL);
			}
			if ($code & 4) {
				$msg[] = "SMART command failed or checksum is wrong.";
				$status = max($status, VNag::STATUS_WARNING);
			}
			if ($code & 8) {
				$msg[] = "SMART status returns 'DISK FAILING'";
				$status = max($status, VNag::STATUS_CRITICAL);
			}
			if ($code & 16) {
				$msg[] = "SMART found prefail attributes below threshold";
				$status = max($status, VNag::STATUS_WARNING);
			}
			if ($code & 32) {
				$msg[] = "SMART status is 'OK' but usage/prefail attributes have been below threshold in the past.";
				$status = max($status, VNag::STATUS_WARNING);
			}
			if ($code & 64) {
				$msg[] = "The device error log contains records of errors.";
				$status = max($status, VNag::STATUS_WARNING);
			}
			if ($code & 128) {
				$msg[] = "The self-test logs contains records of errors.";
				$status = max($status, VNag::STATUS_WARNING);
			}
		}

		$messages = implode(", ", $msg);
		if ($messages != '') $messages = ": $messages";

		if ($status == VNag::STATUS_CRITICAL) {
			$this->addVerboseMessage("$dev (Critical)$messages", VNag::VERBOSITY_SUMMARY);
		} else if ($status == VNag::STATUS_WARNING) {
			$this->addVerboseMessage("$dev (Warning)$messages", VNag::VERBOSITY_SUMMARY);
		} else if ($status == VNag::STATUS_OK) {
			$this->addVerboseMessage("$dev (OK)$messages", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
		} else {
			$status = VNag::STATUS_UNKNOWN;
			$this->addVerboseMessage("$dev (Unknown)$messages", VNag::VERBOSITY_SUMMARY);
		}
		$this->setStatus($status);
		return $status;
	}

	protected function cbRun() {
		$devices = array();
		$devices = array_merge($devices, glob('/dev/sd?'));
		$devices = array_merge($devices, glob('/dev/hd?'));

		if (count($devices) == 0) {
			throw new VNagException("No SDx or HDx drives found");
		}

		if (strpos($this->argType->getValue(),'cciss') !== false) {
			$devices = array($devices[0]); // we just need a "fake" drive; the drive number is given as parameter to cciss
		}

		$count_total = 0;
		$count_ok = 0;
		$count_warning = 0;
		$count_critical = 0;
		$count_unknown = 0;
		foreach ($devices as $dev) {
			$count_total++;
			switch ($this->check_smart($dev)) {
				case VNag::STATUS_OK:
					$count_ok++;
					break;
				case VNag::STATUS_WARNING:
					$count_warning++;
					break;
				case VNag::STATUS_CRITICAL:
					$count_critical++;
					break;
				case VNag::STATUS_UNKNOWN:
					$count_unknown++;
					break;
			}
		}

		$this->setHeadline(sprintf('Checked %d drives (%d OK, %d warning, %d critical, %d unknown)', $count_total, $count_ok, $count_warning, $count_critical, $count_unknown));
	}
}
