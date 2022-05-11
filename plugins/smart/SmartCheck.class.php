<?php /* <ViaThinkSoftSignature>
MxM5IDmxLm4AvC7HzDxuwcSLYq57hKAe+rOy3+J8f3xVocgSffeNKPryAUV1XIDUQ
GsPaei87WN2j37pzhpMbxs73vTDip4vC5jQzYFurtVyC4JDZ3E4HKI2/X9w94FlI9
hjEvJ7hJ40i0YFRYhSIfDNqAnpvuWvcSZ4jADiakjVf3YQqfbcjazFX5gyRXH4F73
Ig+PHVFmhv5lc77x8sZqPggLIdxCCXWtuwPhwr95DTIYMLieIGlKO1uoytcDq9geI
EM7DWp7JPqECj8Z42jR+5Q4tt+EikyJ1/XX5eHDDI9e3p0bboq3l1SIXr9WQG5xtv
60EN4EGqCfJdLqDI29ETmH28JTOFReWkEr/Dcb9C569yeOWUi4L9h12BdKT5gxjOL
GXCHUfmrwss6Fl0s6EYmN03YEkCDsvHRna15Kf59J1r9h1yGrmpeBNLlS5+aHR81/
KYqeDF+ZMLmk2u1T4EpMVJ+QvLaGc4/a79vniZWZK/HRhIbV74WVu5yqGEzJ0dwij
QYDjUwRcz8GdfzBkEpUpIasRqWdKSgTAxKgW5GLFEXqJ4g9xsMmr2CD5dpDOBhwLQ
pHdI6WDGLp1NSts6hWyi7Yv8D+PYC07HMB4Lsk5b03eJ4BdGpyPTFpMKtMgBpCb9Q
oLU/E+j7Gv4CTJ8HEkg/oX8FGWs/EI1NpPwFa5m1Jb/CDvWc5d/tDUmxed14yxUAe
vbJbSkUYiULq7bl6nBwGLsaUbL+yrKHxh1ojl2e+/l8YlZAIkW702IZ9lukQ0HNzf
MHzho6z0UnAior+Y/C8wrGohDvbstBYErkq2MZLLotWy4y265AOi1UvnJXUBefDJH
j7M9UIkM8kycyplqyWCAWAfjF+yrMXWz+PH6lJm1N5ya0KoEfpLlRTlUcWkZEqOb4
B6z3AEoNzSmnvF62D0SQcvfY5idHhlOeyAVCCxr6ElHPTrh2TaQHg729OlaIRulvJ
+KX4hqIbJYHgXtLdQk4bLm+FE3GmgqGb7nAy0QQ/fv6ouHv3Dx3JZvYW1NSSCDSju
R6yJukTvuiWbwI4VgEDNk3sgqDHJHMg368OiRGSaHhdsssu5TIqA4jR/CxGj8VxOZ
9dtuoRlXQF16nSdzvx8YcI08c7jiYTffLo0AdYA8SGAqgc3qV2YKT1R61XiaG4N95
LaM8m4dquy1rJVRb+st4oph51o1cWHm5agHtEKitGOhxVbXE/AWHMiha3BMB9VJUi
FJKLq8yEQpoUEB45knhFn3/3XZUeWCj5TFy+IawBLfgLWpv9JvTCvHqFQ3DFW8jpS
OWgW4c7SOTM6yH5uExIRCqp/vbVaNcu0HU/RVU50UrdkkwQu6h+40+7EKFQHie/bZ
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-05-11
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

		if (!`sudo cat /proc/cpuinfo`) {
			// To make this work, please run "visudo" and add the following line:
			// nagios ALL=NOPASSWD: /usr/sbin/smartctl
			throw new VNagException("You don't have SUDO privileges. Please run 'visudo' and allow the command /usr/sbin/smartctl for user ".get_current_user().".");
		}

		$code = 0;
		$out = array();

		if ($this->argType->getValue() != '') {
			exec('sudo smartctl --all '.escapeshellarg($dev).' -d '.escapeshellarg($this->argType->getValue()), $out, $code);
		} else {
			exec('sudo smartctl --all '.escapeshellarg($dev), $out, $code);
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
				throw new Exception("smartctl reports 'command line did not parse' (code $code).");
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
			throw new Exception("No SDx or HDx drives found");
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
