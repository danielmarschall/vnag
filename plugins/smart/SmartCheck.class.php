<?php /* <ViaThinkSoftSignature>
q7QvHyotKOTpEs9fbuwcPzqwjSw69WwJkY5ah30oJKbmru7P2dq3T8e5Xz2WZygTE
JahCPsT59QVwK92IUQQDZwQ5QO1GBwhH9sQ2LoAy7W0X9iGLxwAQM1b0Lalfr22V0
cct1CBNw2Jyv/jQXDdMGgJ0lEv677xH+zizO86+WahYUW6WZ91dFGp6gw89pMK4QA
bvk6QnKmIrrfX/m6fA0SENoche/dBwFzSTSScpFCs6Xpy+aQNQUcGWyBKBePosQD0
YI2TAqf7rnOuN71xgNG0JzNJQf+aBonZeBkxA98Iui4r10ws4iapq7TklJaWoy423
iHUWx9PU+u5RqnthE98Dmcu/pJmQME8l/M4eSNFssUmc3Oxx3lcSMjyBmlTyOx9eA
24Fg5xaLH29nGP6XfuFdGrUUzkNZKWqG9jUQ1DBhSs/U3bFIABCIBnSB0kWlT18tP
c9WLveDfdZG56c8Sw9lKKQZh1Nx1fhPa3Kq/a/JqJnWv3161M4LKSsw9038Qdzqad
mYA0paiMPS5RLsJmktVn1NmTNudaW1QyLkwqKZAHuSof1M7/KOElZLu20ut4U/BRc
xWvtQOtgdwE5s79WIzCT72Ycv1OHuwfdrdABwBx+t3gM0nx+EjZhyhcYm/O4j1FMs
fvVnCkrl6wl1gJSJ5ADLvdaQFA9aGiH7UhBSEL77u6/962qshTaJ//OZhaWdoIIfn
Dx9iwMjTm/x6qeLgwcYUonp59/76ZPQlFYzjf+M/pcqZ8XManfd9fADrau5+15k2h
i18FVBi43Ef5rletQM2c39ZOBOlklILKxsQTNUWDjZCsSV7dVxJJq2ykcNruogppM
hpXPgLBye0dWqxeAixQEoLegUgwxI/X3MP5qJbfxzQfBZYo54yEMaml+T4cGU6Ctj
gkhxB05CMxaORs5f1dFmKBKKNvZ7pQw4g/GbRxhVX+uAdVf5e4oVFEYBNviCc4tGt
MEHiPhTEa5PJbfRgxiEfyKkSjDXJeHou5O8mQjD/01cw3VMhuaStcM99DaAVunBWi
24uD1lLhHXybvwIiJ3OwA/oOmwL47UZ1fsKFHwMfEb4bQ9SiwosESz2V6TZHlQjbi
1tIfhmrsxG88jtYpBPagTD09afluSRJAmEiMzl4k2+JAts3mvfGFTl5KsGGard3gq
zR5XlY52le3Oi0MFNQzXi+jeOJdWlIGbTb7z10rwrLemyGzFH4BPhwjV7gOlxpmE5
xFb/VeAeGCPIh5XxNq34S82EV80MMw2DK1l7T3440cTteSv4byAE2ZWo3/iH1lOXp
oUEulv7DBhHv1OZHbZsU9klfScZcQVJM+FmVqtK6krBZtx5p2a49N9kD0ZOn2lc6O
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
		$this->addExpectedArgument($this->argType = new VNagArgument('T', 'type', VNagArgument::VALUE_REQUIRED, 'type', 'e.g. for RAID devices "sat+cciss,0" for drive 0.'));
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
