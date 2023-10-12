<?php /* <ViaThinkSoftSignature>
Y47HtMofXduSMBqHYs0A2zUutszEFPtnTiplJnrXJJCTA4aIl3/mw1UwycL+xmcvG
eeO81pkefEWiXtWhoRqX4MIBUv7zZ7Yp+O9/uUs/l+++WvBHkEue++SyuJXgRlYOS
14NGZl2w4LJjLEw6RYFtq7UhHlojVrFfysBXgbzYyvMx+mbkstNLTwJZ34T2wJCAK
b3bYzIavozAoSBPiyq1LblDE+EMFbVEdHvRzNTNrcc7fVcDuZt3pzbHkXbJzAbIT+
8s5ZCwxrzuy+Q2kq4GCeiTUdpneq8UwWFqrM5pgSpjLP0MM8DZMVJeYcm5KWomOnO
HjGhU53upbQkMpadXJOU8eLm0XSD8WwxK6cWiZTGyS0Vg22Hepgv5HDobHWk3Dt7I
8ryhwdS2wtfvioWcabm95coxWIayQq/49bW/U0iBZCUlizpkLFVQiqFrPV7BpMN5G
tjdJBjR1Hg5E8ALpa19v4B6TCb/ujGTlZkVqQbNBhrTH1VuJ7UNSx9Lnt/Y3Jwss8
63HHSs1nG5cnCgpef0ojH20aLk7N7k7DwWMYovOlx/LpfmgK3iYTBo4UlwBVTJByk
nMK010KnruouDT5alpeZ/tvSYWzWakgqaOMCd6IaDhDt+v/RZXtXuvSOjV+tfX1nF
rATHyku3y4dH09uirH9AHuN1PfFi/mjXiOPlUjHdgdih/nTmKFxDJhi7qX1Cbrch5
L0Rq7nPTD9R4B7k8YwmDmMWAY/+XPTg0Y+J+ivCVnNkReqsEDLS/fMGu8VekBhxIh
WoivFYr62IdEMfDdK7usvVY3lA1xSS4X/bg9OixwTVyjyiZjYPGpilR4q5rB4LrDb
Jn8JuB2jFLFCAABeNbLd76Cz35CVhmNm1VetFhBYlVG7k/a7PZgH79GZDXRpzYCOP
mMCYYNgCJ1RDXZeeKsTD+gZ/VjAS/XvUgVCN/Du37EW/IsMPoOQNVKbWOEj3HYbwi
7wr4ebqMtjMxfikc1uUu2WIlnooYDF5NusE3y24Vok0sUv3qqZ9nDrI6tOG1wFUSx
CYyJVAUV0tgjOeixgzdseR7oi5pT8RjUHg3Y/EOHDMg3ZXPZiJzITQaprkB9Zu3Kg
v7ks3PZqxBRxYtCBsMHXmKsWavRBmL7NZP8ETk0SIXmhpK45LHh/LkD/8KktRQ8Gx
PRfqK+LWFmAOBfCABUt0ZsN6lA0u5U+ddOMl0sSTsSn7C3mCnTwmFGroGR0gWUQhJ
wJ4tagVYOJUxgFOa0TiNTttVrYTx+DPfjm7F6c3yjaTg/MKBf5eOKvNPlD22lnsNh
pkWe14wIwYN7nwVGe6+5IR6GpuYCvfQ0tMTg3NEwraHl0U/wtcGbraAgQQZWoTqNg
Q==
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

class Net2FtpVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_net2ftp_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local net2ftp system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'net2ftpPath', 'The local directory where your net2ftp installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/settings.inc.php")) {
			throw new Exception("Cannot find net2ftp settings file at $path");
		}

		$cont = @file_get_contents("$path/settings.inc.php");

		if (!preg_match('@\\$net2ftp_settings\\["application_version"\\]\\s*=\\s*"([^"]+)";@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://www.net2ftp.com/version.js');
		if ($cont === false) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.');
		}

		if (!preg_match("@var latest_stable_version\s*=\s*'(.+)';@ismU", $cont, $m)) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.');
		} else {
			$latest_stable = $m[1];
		}

		if (!preg_match("@var latest_beta_version\s*=\s*'(.+)';@ismU", $cont, $m)) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.');
		} else {
			$latest_beta = $m[1];
		}

		return array($latest_stable, $latest_beta);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the net2ftp installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		list($latest_stable, $latest_beta) = $this->get_latest_version();

		if ($version == $latest_stable) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
		} else if ($version == $latest_beta) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Beta version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			if ($latest_stable != $latest_beta) {
				$this->setHeadline("Version $version is outdated (Latest versions are: $latest_stable Stable or $latest_beta Beta) at $system_dir", true);
			} else {
				$this->setHeadline("Version $version is outdated (Latest version is $latest_stable) at $system_dir", true);
			}
		}
	}
}
