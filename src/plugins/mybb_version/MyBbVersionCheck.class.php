<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2024-04-29
 */

declare(ticks=1);

class MyBbVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_mybb_version');
		$this->getHelpManager()->setVersion('2024-04-29');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local myBB system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'myBBPath', 'The local directory where your myBB installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/inc/class_core.php");
		if (($cont === false) ||
		   !preg_match('@public \$version = "(.+)";@ismU', $cont, $m) ||
		   !preg_match('@public \$version_code = (.+);@ismU', $cont, $n))
		{
			throw new VNagException('Could not determinate current myBB version in "'.$path.'".');
		}

		return array($version_code=$n[1], $version=$m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the myBB installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}


		$versionCheckUrl = "https://mybb.com/version_check.php";
		$cont = $this->url_get_contents($versionCheckUrl);
		if (($cont === false) ||
		    !preg_match('@<latest_version>(.+)</latest_version>@ismU', $cont, $m) ||
		    !preg_match('@<version_code>(\d+)</version_code>@ismU', $cont, $n))
		{
			throw new VNagException('Could not determinate latest myBB version');
		}
		$latest_version = $m[1];
		$latest_version_code = $n[1];

		list($this_version_code, $this_version) = $this->get_local_version($system_dir);

		if ($this_version >= $latest_version) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $this_version (Latest version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $this_version (Latest version: $latest_version) at $system_dir", true);
		}

	}
}
