<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

class RoundcubeVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_roundcube_version');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Roundcube Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'roundcubePath', 'The local directory where your Roundcube installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/program/lib/Roundcube/bootstrap.php");
		if ($cont === false) {
			throw new VNagException("Cannot find version information at $path (cannot find bootstrap.php)");
		}
		if (!preg_match("@define\('RCUBE_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new VNagException("Cannot find version information at $path (cannot find RCUBE_VERSION)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/roundcube/roundcubemail/releases/latest');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Cannot access api.github.com)');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Invalid JSON at api.github.com)');
		}

		return $data['tag_name']; // e.g. "1.6.3"
	}

	protected function get_latest_versions_with_lts() {
		$cont = $this->url_get_contents('https://roundcube.net/download/');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from Roundcube website. The plugin probably needs to be updated. (Cannot access roundcube.net)');
		}

		if (!preg_match_all('@https://github.com/roundcube/roundcubemail/releases/download/([^/]+)/@ismU', $cont, $m)) {
			throw new VNagException('Cannot parse version from Roundcube website. The plugin probably needs to be updated. (Regex mismatch at roundcube.net)');
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the Roundcube installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		try {
			$stable_versions = $this->get_latest_versions_with_lts();
			$latest_version = $stable_versions[0];
		} catch (Exception $e) {
			// roundcube.net blocks HTTPS connections from the ViaThinkSoft server since 13 Oct 2023. WHY?!
			// Access GitHub instead (but we do not get the LTS information there...)
			$latest_version = $this->get_latest_version();
			$stable_versions = [ $latest_version ];
		}

		if (in_array($version, $stable_versions)) {
			if ($version === $latest_version) {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
			} else {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Old Stable / LTS version; latest version is $latest_version) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}

