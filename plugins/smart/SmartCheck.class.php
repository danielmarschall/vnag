<?php /* <ViaThinkSoftSignature>JzjmNSphjJTRHMsIRHh892nocNxwZ2Es+ZcviS31H/rsieUvZmhp9cok7T2tEye/QwVbEbouz4zSVd9vP3Pvq37NJoqHC6s0zttRWeBrXKqkBpJnucsuT1M+OTkxdxu+CvXupKWifcXjUpQF+yxg+rPji/oaF5V8r4BZK7/GgZUyvovpuXSn2e0w91PtkrE7j9Weq8LS1SqcYeTh4AeozQC+GjWrrzzW6w90hDM1b4HSp+S5KcBQOTRY1h1umJp41XNeYb7/sPkmCsIbpf4YuzUFg4v7q9epYH/qwnfRI3Ll0jrFNYdGHFbgPGApmNkklNf7hSpWMAB1LaUh/4i/gkxceyHA8xt9i2pdHJIRiHoxzUFyIXevst0GOneUoeEUdLAyG3xCYxuai85ZvzeffkKQCVbEkQu/vitQUW6JiE3yeOMJFF9mEwPbHFC7uYEpnbV98FEsegNrxdM8vscinKmr/cGg/P2j+BjPVT+Uag+7ps5xxcmSHFIHZuD0VoKnyyDAyzOs8YhloJq0P8ovBTcJyvmj4Qzcs2UaM4WKmkFbFJhos2DxFxoPfh+DfiLVQJaO57npZOkbjX86+ann5OjkgJRgMDc4EFbfApfK1t4L57FTNJwEWIlmUl2X5k0LOXiLl0ShFGgpqfAIUvB+K7TQbEh3wRdqSuUhTigUqL/aEFygygueEPh2DhIPc//QENU22fSMcxT+Ud+uNJWgXqxCg6NxjyshWTNwvIFWX14LtqauL80TUhXnYZZVtq4tbCNqb3DAxIvqK+F1aTbgQnb/JIq+4ntHjR9t2Jp22zQ+EEzdgIabG9YRfJNYLggwgUFrlmmFht0sVJVCl4dLPo/CuK6VVTIj1PLSIKpa0mnt+cadUMrJVY1kogJHCmyzLpiwj4sff483YaH5gcOqAdJ1NlvnDJvhxZLV4JSIvuk8dGCdcuaa2HJfoVOxBFUguXB5wHsgTvOT7Ia8WpqbF6SJDfH2YqoUsAiifCUxi1C/NHffQ3mZc4ko7nzdTVHJgtfaPT1kkFscH2ZV6GZzUdJx0wcSr9e7d2zDCElP7R1rF01aiHCGlMOfdC4GnzxYH5MCgwBL/GNMN9qJmBK4sDFwJ6PiuJUlz20NlVlm/rwV0SfVaMHMtDZ7/L+ynva8W13SfsQeYhWoJxH8Um0dlvf5DG+Aq4mzMbCShHxG9zIrNSFfmYrKuh5ldot+/9FN6b4WTP7Ej1D0l55+1fcwBrXwxl8w9+awryEhXzuRtxuewnkNopqv+U3SEPlqK5nyZt8GwQZpqw1DzdGiA7jY7yAx/fTMeMs4ypVpxQJzt3Sct/BUkKHSkhSY0UrHyP2k4k29VGhwaAqZHBChglawvw==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-17
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

		unset($out);
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
