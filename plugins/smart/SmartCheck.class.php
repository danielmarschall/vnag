<?php /* <ViaThinkSoftSignature>
R+VZLh3GAQ/vs4fO8eyTRn1IEqD7XS9dR7YFDqJM3h944GnPQKym1XAkDpiQkswyq
Dtmsg1r43c/tkzPexDMu8YLjKC2RzT0arj86HzJgeBSoQ4W1ncmmPZ7zsP/2TGJSS
5rLft8xT2/XcUAi02RaHstg+j3Q6f8+wgcWTj4hg/R/uQaPoR7re27Zwx1+xsH2er
hq7qo6oEf4kHno2gcLDyoQmUJbL2N7M0xfvrFPLoSSPuIL3EwtF9Cp3LZ3HXkUUnV
Bqbfnyv59ZKzVcpn9spZZKobiQJFNHbtAc3z61cTilNSvO4/5LhSk17CXtM4kjfLU
n1ZLgRqNmuiKNv+wPk/H4mM3VPc9DE67VRR6V+HZNr1PEdb9DbSiYgbmCONqs7lX9
y09sojvRztAemua0M44er/STKnmKpIGigmmMQ4ZBYh4XD34IfLYwVeX2ErK2mnBhg
DTbsKymyKxBVsDBv2Z7GvpZb3lxIFtZ5cz/NlVAfe5sKkmvO6J2VlaSzka0cl3kj/
bZuNVjuRwNdD31otYbOkTE69ARnRH2RwqVD927+Bne5OQOTy4w3Tuz3SqJesbAavx
pGwnmTCQoez+viiZqQBB6B55xBEEzsEVWgHiUmllgmz8xZMxEdNYfcw4H8T8oybLE
hSQBicFDoZpO8kNSRoU31Q3RDg9l1AW8JyRHxTlV767/JmyFmgvMiKSxZDbvjuzW/
i0dL1wurRTdfB2cXmfMQhXjl4t7rFzI9rIs4YMSDPDgZEjehYh7twrBZXelinDrrm
HQkQtRvz2IUGL/5KzXo03Ul7uz098X+uxlL6RMbE+8pYZJp3NuF/G9NXo8pzELlrt
FhP337/C7ASC6ua5OW9SzZ+EECCcaHWJjG7zZqFgBhzLNjakbA4a4iZHSztWsO4q4
xyKTOBzL+dtM04ERwiGiCU+yjPcuYjmDmPEZXTtCENUdtDDEm77OvunTI04aFqZST
XO00CNcCCByM8Q7m8qb06Od/4bjo79qhNPjHx4wYBTgLhYYKoQRTv3MyZY9BDgIE3
/UPyHa0h2UPhS6IjqLoIuU3WZRh+kKJMExeTNWJ+F9yVvmpDlK2pPNo279g8H7jD6
Dp5aSDwdwSqGE9mrMgwdDL2qjun8kWg0s69snfXSaI5qSTyoIjVmGIiSlNtdw8VsX
gW/wO07WOUv+r5qsHkVZgSFKZxeGCETPKyGmGjgy9yibFyDWwoeK2NMijgJlO0QPR
d02HfMGFQ30TyAnuVDkmZf1a02czFQ/YVQbu1Hq3siQ+gqkCC8ticsBRWux6dLYFL
cYNvfVYj3c0IwBmxUNtNzFvSZLdJOAkKenk7ODGgrHSZarKNWOl6wkb3IYdNFefgM
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-05-13
 */

declare(ticks=1);

class SmartCheck extends VNag {
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
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ (no additional arguments expected)');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
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
		exec('sudo smartctl --all '.escapeshellarg($dev), $out, $code);
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
