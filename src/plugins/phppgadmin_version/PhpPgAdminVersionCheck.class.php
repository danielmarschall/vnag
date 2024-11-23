<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2024-11-23
 */

declare(ticks=1);

class PhpPgAdminVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_phppgadmin_version');
		$this->getHelpManager()->setVersion('2024-11-23');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local phpPgAdmin system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'phppgadminPath', 'The local directory where your phpPgAdmin installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (file_exists($file = "$path/libraries/lib.inc.php")) {
			$regex = '@appVersion = \'(.*)\'@ismU';
		} else {
			throw new VNagException("Cannot find the phpPgAdmin version information at $path");
		}

		$cont = @file_get_contents($file);
		if ($cont === false) {
			throw new VNagException("Cannot parse the phpPgAdmin version information at $path (cannot open file $file)");
		}
		if (!preg_match($regex, $cont, $m)) {
			throw new VNagException("Cannot parse the phpPgAdmin version information at $path (cannot find version using regex)");
		}

		return $m[1];
	}

	protected function get_local_fork($path) {
		if (substr($this->get_local_version($path), -4, 4) === '-mod') {
			// ReimuHakurei adds "-mod" at the end.
			// Better would be a different kind of detection, see suggestion https://github.com/ReimuHakurei/phpPgAdmin/issues/27
			return 'ReimuHakurei';
		} else {
			return 'Original';
		}
	}

	protected function get_latest_version($fork) {
		if ($fork == 'ReimuHakurei') {
			$history = 'https://github.com/ReimuHakurei/phpPgAdmin/raw/refs/heads/master/HISTORY';
		} else if ($fork == 'Original') {
			$history = 'https://github.com/phppgadmin/phppgadmin/raw/refs/heads/master/HISTORY';
		} else {
			$history = 'https://github.com/'.$fork.'/phppgadmin/raw/refs/heads/master/HISTORY';
		}

		$cont = $this->url_get_contents($history);
		if ($cont === false) {
			throw new VNagException('Cannot parse version from phpPgAdmin website. The plugin probably needs to be updated. (Cannot access GitHub repository)');
		}

		if (!preg_match('@Version ([^\n]+)@', $cont, $m)) {
			throw new VNagException('Cannot parse version from phpPgAdmin website. The plugin probably needs to be updated. (Unexpected data downloaded from GitHub repository)');
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the phpPgAdmin installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		$fork = $this->get_local_fork($system_dir);

		$latest_version = $this->get_latest_version($fork);

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			if ($version == $latest_version) {
				$this->setHeadline("Version $version (Latest version at fork $fork) at $system_dir", true);
			} else {
				$this->setHeadline("Version $version (Latest version $latest_version at fork $fork) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version at fork $fork) at $system_dir", true);
		}
	}
}
