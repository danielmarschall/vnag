<?php /* <ViaThinkSoftSignature>
qBxM3hf7eDc1Bnpj3F8RPxCUO03EEJRctdD2zTJzOHjpro6bzMkwZAazBhQ0jPJ1o
0FOqTplQZ/6KWucpji02AFtSvW6OMf3clG8w+14Wxlzi+vVfxL5ZZMeRv9qTwQb4k
88pugBiQ8ldR/tq+P4R8nOo5vzV6DIaSUPZfHfzdvxl4w9m/pMgChD7oe5Hx4CtWU
2NLIv/qmJs23OGnZbf3CfNkvpJ7mjtia1Te/4olEa+NT2AOaib1f3IjRc0mjLVn9p
ARdkMZSkDMUmtSgAVIXy40b5Xx65FmKD0iuG9pV+MOlYboMuaKGqmMAfDbcCNQz2E
inrQJ2lHsm6B10ykzE5pATdR93Hpu4Xzw/LydLjLP6vtarVHZm8Qv1rELR/prPpMY
4V8b4KtoqcrMoHxnDlL+YgbwB/66FKXqLvM6ytuCwJu0baKSi3SnIC39RsHP51D6h
rGl9/mLq7X+PVu9ah5EFMeqXZykDaGYAsUvsEn3VYdF1h0EVpsfdlW90dnRolAC0N
8mOU30F+7GEAJxDXpDt4Sw2FrHv0ESb8QoOA+HagUuCA1D4X1pcaWHMMv95QChTwf
MfhFzRVNitNrdgTBB2T1m773stskWt63FdsFdIU+cga84zV3AFi4GzqsaFq7uH/eD
TTkoWGQcuKKmMafeP4QeEAzFarsMeVCnCxub5XTZNhU7h4Asrej9BhGNinVx9nFRO
tuk5bIh1kFI0OzN5JRDRsSgZkxsG9T5V8r2rGm5fTcy+MqKMP0JVyib7MpVid+9wi
QOH0h1CTkHkZ0ffqxAI5muCOzQrUzJ8SuG0krIBgHWE/nVakFcYYw+lR8/G93gUif
V+PRHSVyMeGIPmZBfDiEngCrRDDrBR/DaW5PXkhw22+T9xRmTShwcTTMPIwTVKzdf
KpHyx3TmF52xbfLH156Tai25QdTECakC7ffEln77/KtDx3GeD8tipvU5HJx/GSc+a
hTuCAdN4oX/m1vewEvKIJ0YaMr83F0KAJyqkuglVIKv3OnRL1lRswHj0Lwo3OWYDy
xi8uFIkmp47iFqSSIsMV1aPW5A4q0f/O8WswzaR20iKp9NDC/uaSAxrZvfkqs7lxy
GvR71hHqe1CQO0DN2oxZG8+beKF1k3PDzptIfEA77f+Ctc8/IXl8Y2E8ISQ/JMjqf
akMiYDVBQHV15JlQyG/EblqJOhOuOtOG+ZT7Dorx8l8sT5PCK+s3YxuyfQps6CaO+
WGjb9zViLO9lMWQGQ+WUfAfppw4Je5R5ZlS225+HW21jdcvy2wdtRnSjYYOTGIU4D
a/yXo6xSu9hhSiIUdio+Ib0Sg5n1Mc8Sx1Ih/6DCrno2SplyOK1m+QAU5He0o/iLs
A==
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

class WebSvnVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_websvn_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local WebSVN system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'websvnPath', 'The local directory where your WebSVN installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/include/version.php")) {
			throw new VNagException("Cannot find WebSVN settings file at $path");
		}

		$cont = @file_get_contents("$path/include/version.php");
		if ($cont === false) {
			throw new VNagException("Cannot determine version for system $path (cannot read version.php)");
		}
		if (!preg_match('@\\$version = \'(.+)\';@ismU', $cont, $m)) {
			throw new VNagException("Cannot determine version for system $path (cannot find version string)");
		}

		return $m[1]; // e.g. "2.8.1" or "2.8.1-DEV"
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/websvnphp/websvn/releases/latest');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Cannot access api.github.com)');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Invalid JSON data downloaded from api.github.com)');
		}

		return $data['tag_name']; // e.g. "2.8.1"
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the WebSVN installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$local_version = $this->get_local_version($system_dir);

		$latest_stable = $this->get_latest_version();

		// Note: version_compare() correctly assumes that 2.8.1 is higher than 2.8.1-DEV
		if (version_compare($local_version,$latest_stable,'>')) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $local_version (Latest stable version $latest_stable) at $system_dir", true);
		} else if (version_compare($local_version,$latest_stable,'=')) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $local_version (Latest stable version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $local_version is outdated (Latest version is $latest_stable) at $system_dir", true);
		}
	}
}
