<?php /* <ViaThinkSoftSignature>
wvt5JYOFKaJBLyUvgpwn2+bDeUf+WHsqBRn7PiRv1GfwB1OBVVJvLNeVYYsfX8yAC
EnvtdEO42wmpGEuegwcgvthOmc9ltwc3XADjReEZtDBtRJqWO9ZyszaH4/Z8cGbJP
5qlQuIsw3LNkJbJIq+DZl8AoaQmYMkSB7TxDgl8QkQNbkJyynRNYSOq6i3pSkEas5
Vr4vkFQrer9+XeHPF5tdGtw+hLSbu1Gq7x4jMIpaY0H32F0FrTUgS4UWkxO2jfzPW
fNkcwGQgBWDpLvno0iE52XSEMlrvpIOQiTCX96QXvBM6hNk3+uwmHG7fhhSUYoagX
Ol5jeiMyQxHG9Rq3eOL07/1IR/zOI6AHENGsdfb8t3Q2/BDoEAu+B2hFsdZ1U86og
AoaYXlMgfcB9CAaPgaq1sEV3ZOn+XNHblAKiV7tt5LVkp22TQpgCIbdTJButAl+J7
1uHYUlHwxNFotjKR1JXQD/JVD+bTOrDLbuDQcUYdbgNE+PkLsqchbQCcfFPjGGUhS
BiSi4Kica72NHF+t7dgJ5ltT+zjkQBcJdJpdgUMw7HobHNWxVhkrycVIwTN6BWan6
KsdGJHYLKoMUSnvfFFV0AJZz4v8S+KwrOVwLnGBMHPvnpf9I8qbu42nCcyKnIdXHk
UNmprr9vnuxb2Dii6tJw8ZW3uvfkv1hFPwiVvazM2Md6nCMaJwnw4iEOwqum6DaEj
YmW5F2jwx4mSqUZipp+RNxqTGfHLAO+L0x1n//RqIK+ByfVAC0QIaCIY3BtLiOdDB
G0zHY0uiDUirMbsjK/Vs6bfcCsVZmTrDLPJGHDaHQqfHkGCteEfsAS3KdrGF5t21E
q6SY+p5lJQOAls9GFwGKfAnSViYH/4CqSNms1o+DbkhcW7sh6/2ljjqxPxl8iVs5v
HLx6EvKlk2ZMotSFhXBpTVYDb0V+yyIitm19M/vk/v010gkg78GbcxLi0belHX9pJ
p359eqFMbtqyX5Eiyhml+IFTBqnaFBVGVo40cAPLBjZWrPjvSbmXH5cJekSn7Zlwj
wRi1Dnmt2WKfB8mxnAPx4SrSEHQRoRvL+6NaGiZsqbGp0ptyHtAJyA6jgSw3kkttd
i0+tn9KD1lXt3mMkI3SfRTtzd3u10fn6X2OP0+zYXhikLsWV4BQKPB+if72c4HLj+
4FF2gccV71qzCdQeMFllc0SLmeK1nXd3dMXL7jRuPDAjls1bfqJIwM6gJA2DnKqSN
8nT4vtZ906bEkKkwDIRO4wDctSxkjvTXPg5hFWa7pgkdmpbjcm5AzM5FfrVKdZYeX
WbX0s6oima0/lyv2LTr5NDe0+YRMLMB8uPCEqZJKKVcrAhB2zr11GBRpy3/ERels0
g==
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

		// TODO: For some reason, it does not work if "sudo" is added to the command section in icinga2.conf!
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
