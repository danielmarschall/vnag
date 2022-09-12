<?php /* <ViaThinkSoftSignature>
rpUdHNdO4FpccwYOBOlXhGElqJIl7T6SKi8+rz7lwKg1w7EpBqrn+bqF4gmmcT8JX
49NsMDcPftt9qc//I9CdiK7mGiFiR8ymWt/xCXQaIIAllAq8+8PHJfihot9z9fzLS
xJQ61eJfrU8aeHWl1FaeI193ESmzY2HzjvOt0W883EQDLxVDQ45sGu623SX1XuTFH
L/rkT6EUM5iWOqBfwho+1V2HZATk4sGB7ZMSLWUyGlXIagJOZQIPb1uQHA5G9zcW/
6yDVhXrKyHi7rApzDqYHaemEr5b9Qmaw4KJbWIFsqCWpWTeRRsI5hrnRiZwCm1yaE
9j6wGzCi5m9Yp2Xm36sPmhAG9An6c9wypIIyp3WV44ECFmpuTQqtqaj7MTorxztKs
yTPr7lcLSYzfqYcbsPUb6vLTXmFz71nq+aBRgrqXa6V/coIL84qpIzM4JCdDrUSNN
vVXolPoSu1XL1gA9Y+3X915vdVUfMSVUfLhOWqiZD+tmmlDZTvlFAq9kM2gjkzeRS
Tj7rJeIhkN39e9h17RhTBT50OQvJIpJUwLmAdorV1F/zWMDRK6LtYT00B+EYQ3SIR
FwZRlW7LMjNrbFj2r2ocBJGhkP1YQBrZakj6Qa50iB1dsS+3OZxtQ/jWP/VXFc631
vosPB4/u+GqryaRR3C/LpxMBypIVDk4J6LRh5EhUDjjNu9L1yIBOu0syHp6Wuojto
zDbD0Y13Ik3wrLBOz/J6Wu1/baN2CN/a2yaUlGKIqoUHijM909J1YVZDNdahssNIK
M9jK/AHfv/I6wB1IGH39+W9aFJNOnLu5lY3JQEAPuheaIxiN4/71fO2AOTiIdsC9h
jjvRukBnI9GAXx56awejGZ8DDhX+k2hJrx6N9G+tKCB7yGx2Wed91ZNGbIRJlWJQk
hFr+8rP60u/ySa2RDBCzwLbG/KUlvL1P5mGfU507t1shIZg0lZIxgZHYARgDRzOm4
jPCX3G77jxd4VubQ0YU9ip0LjiirAuY6JKpz6aqLnZxRnD5bpuRvyf++X0JOujPwZ
T0bFVHUdXJzIU/7GpO/nYbmdOkmf/z4zZZEGI9/6hMLLoZ1RtCKZ1/h8Z+CUMDNfE
R6e7RwuHeUHSo3q0XHL/OfcVZKlyzQkvp3UD4ftErEBhMolFAQQIG1og5v5h6EMOn
CkQPJ6RzSn0k/f3PiixXE0iH/X62LH0m4U9vg2+S700d/91N+R4Nawu3A/cqyCXZa
A3xQtlTg2E7o6RKFTqqWfHxk2rKZ9AWrft/chy+iVJxn1G8b+TwMPd6NBRnfah+77
JQiGbHpKHxv8OAJC58VPKVfjBqoOg9llTH5+b0eZ9owzF8d9UohQ/vgoCph7bk8q1
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-09-12
 */

declare(ticks=1);

class PmWikiVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_pmwiki_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local PmWiki system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'pmWikiPath', 'The local directory where PmWiki installation is located.'));
	}

	protected function get_pmwiki_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$c = @file_get_contents("$path/scripts/version.php");

		if (!preg_match('@\\$Version="pmwiki-(.+)";@is', $c, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = @file_get_contents('https://www.pmwiki.org/wiki/PmWiki/Download');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

	        if (!preg_match('@Latest <em>stable</em> release \(pmwiki-(.+)<@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the PmWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_pmwiki_version($system_dir);

		$latest_version = $this->get_latest_version();

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
