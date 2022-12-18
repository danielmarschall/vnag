<?php /* <ViaThinkSoftSignature>
kaYU5Ej07R4JWIYY77+1u7d9Z/suFEbhvcni+GKz0Y7j5W4RUkmYNGW72dwGJODn3
4cbFalcSOJ1X+6RSelabpkRbEAT7xJDYIsZKq4n28PrYYmVoHLgAlQpTC36iFRxqG
NsZuDj8iAOaBiaUlB30denRyvP6YYIdFa0hlHY4nK3eqptbS9F5PTxtjB1reHwTOK
F4kEhznuZY5POdGnrRNAlB4s5wc70Qqn6JiRtZHtKU1reX//Jdq3FP2HAc82P4izE
AZsmJbY5dQwa7YMWe4n9AcWHyXr08zbHqYCzCTgEmLgPv0Z4RsBXSMq9JfseHl19i
R8mCQF91/N1vMpnSRhmBEj5p331jHcCwe3w7eQHFJriT8zNosRkeBTYGEowNI3EcO
nYRgWfxdpBujU/Q1ES2P/te8efclnVdCGfhlOK21Y6qj9P45mZdnqS1C2YT498N3s
AYrl5q793aujZyZ+tZ6SzVTsb7EYYGXkjMmkqFsz1sLNe4Mwmku1gVo1dZKqF/j+8
tSl8RgHA5NUzu2m+Rhu14CnBQ36904vN1I/dpbAYnk+9tbCNywuPlPaJtpTpmPQtb
TyhZTdL+0SK90O4IhFcEugFBsw+b7LnaqOPw7GwOHXBHWM9xePZ6r0K49/0rwvMyH
1j0096b+EAw/hCOf3ic9cZh/rpO9IcOcOJI0zsR0Mta1dzujnzjY66VPt0TCYjWPu
chqcU2Re8YuSmIsmkHWOuEJRVDYE/h/2yHNdcdo2ev90DYIjP95UHi9ZxCKoJDEG2
aac0WcNMrxEFgmYM8p8v4Bg96x0o+Y16xxMc2T3NeT6E4HrZAx8Sq1hkl4+D0RRid
ibXuzxERmDu+DFeA1d+WRLCv0vIFaQHVVx79HiA/U1TdMqbvSv6TPMEX3etjwo9gv
4hZ4Io9npHprocFcLLPzgfCBmOGBinvmUGwnw+iVTTK08SVOTPPkT4sOdwiBgRysw
F3Bj2O2qwOOkH+1lscTogldQQMvLYQaL6K3RvUHNede2yxnjowjfZBFLdZQVZvvhH
cc1QuCqsrU9cmsUPSGd/hkvAlS1a2dk2TLPJTkchxb0Q/hEcl+zwYnW5ZMj8Uvbkj
TFnI0XV6upG4WTF53ewPR4dZMfdBzboMFglDFGg7MJY6tkZzwrxTAFmVQSGCUgcVn
rzL0NWyivR24OA8cQ2CRe3X4bmJQ4bHuZZf6dLLbbyJJV3x3QwFw1BQCUiJE1mBQH
ZfOqsdqwmvVH76R6DStD1BakkjqfB/Q4A2ghRxTqsESMj/KcC1Rj0YL/RbWQGKWb0
b+OYXtEHghPQPLH9nUU6wH0pi4zG80rFG1nrOQTYEf4PfyqkdSAa5jK0HTxLIASpV
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-12-18
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
		$cont = @file_get_contents('https://www.net2ftp.com/version.js');
		if (!$cont) {
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
