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
		$this->getHelpManager()->setVersion('2023-10-13');
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
		if ($data === null) {
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
