<?php /* <ViaThinkSoftSignature>
FjPoJmd4Q5+yJRzZt4FvBkVzDOwgeHFuKiqEvpXwJ5MHmkZH2YMsSFb7qKCJRus5Z
8PDQ8fm6MhDtXJ7N6hiI/v2tEe2zxr13q0e5ATcg6hDtZrvxks6QNwxa2Qb14q4en
tYShvmESYxJAO0IJkZglEFVRyovxH2zm6YgkNbMhf9IReupg2A2TvWdxvyAYXtn4e
/L18YW84bH2BaF6e7csex4U66yVbciMZERor6aDDQTtw32Dy1f+4X75jqkcEuYsy+
rgmsAhJFOAeghSRRtbA87y26wpP8l/ima9PmmIMc7zsVJ8iuuNd5Px169m23KohpW
8WKX8CuIhaaVNfa3UQM2yuM/Kj6cvYCNldouvMtGUpyXyX5ZIOM2b8aaEoDHXAtbC
SzSGeTMCDXWFsAoygwQ97fYAoXvzPXnYylIbMpLCZ5EoUyb7raF3x/6asPU7uVIWj
y8kXL9VoBb1Dbr04drAmMzqTrH/37/+aymJ/KN7XbPwKtLbayDoBcQ/0Kt+5zVLFC
Afe7r9IAKPLb76upMFxzwc4NE2IKe08a/Vis925gKki++Z55XuJtmz1UbBb0NqFdb
MtxlFje7sAUHBjw+w5vvfWrKP18vkJlmQU7humm731fzwkqgr/LpFQpTCw6MD3CzW
iZtVsLre1Pw4AYe5QkM33u0b1RioOI/KsRKYk6FZodK/nDU1v/EKE5Ixa0JcvEDUw
/vPt9Vx3gPF8XUcM5mt5oqzr6R+HnozBbl5mLTCZCAtXExTxpSNhc0JLMfZKLWxwt
lHqQrlNBVMXf9KjcI0yRreh3Ir4VTbXjWvkD+8owA+I3edNfj9ZNiT1cC5lwFM35W
o0jpqa0nV02pyM+p/anlQtQ8mlPppv4kPv0yCuOL+WbJu7ilqPusyFYmKdVBYN91L
zqBHyrddZI0uzf1Ki22GibIrfXSWx24COQFPY9ZtkuHYg6Tuh5yISfOwb2fh6A0zl
Aw7J5xDU0ChQh1AWO+sly+ytIG0/dhAqXmyN0IdRnj/GsZddMm9iczdzXM6SAUFI9
roosg36lsk2J1Xrl1feRsuPdYdhkJexYhJybAf3Qs0IvbPXbb1UQ9PW8vi1DXXh9G
gL7aG6YqLnzTxeSW6uah/tHoc4GfAxWNq2M6OEPte8KWS6hJI3YURLaeKbc7X5zt6
5s1JuuwWeLqF76jjaDy2Gq857qT8fBNqx7p1b3X4DbxpNcFdMQiJrXBB8BdygFKEL
AcRnNZKmjc7+XBo4HoElAnXc3H9xflbq+F5Y5360R52b9uw17hp4TdelAzut10uOK
FFGGSHEVYDau3FAn3iq2dlL4AwDWcSt/myudVuaFNeoVFSpKxMwiBpb+EhFGBRoj4
w==
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
