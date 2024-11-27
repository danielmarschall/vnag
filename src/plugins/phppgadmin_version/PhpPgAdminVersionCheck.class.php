<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2024-11-27
 */

declare(ticks=1);

class PhpPgAdminVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_phppgadmin_version');
		$this->getHelpManager()->setVersion('2024-11-27');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local phpPgAdmin system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'phppgadminPath', 'The local directory where your phpPgAdmin installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists($file = "$path/libraries/lib.inc.php")) {
			throw new VNagException("Cannot find the phpPgAdmin version information at $path");
		}

		$cont = @file_get_contents($file);
		if (($cont === false) || (!preg_match('@appVersion = \'(.*)\'@ismU', $cont, $m))) {
			throw new VNagException("Cannot parse the phpPgAdmin version information at $path (version not found in file $file)");
		}

		return $m[1];
	}

	protected function get_local_fork($path) {
		$intro_page = @file_get_contents("$path/intro.php");
		if (($intro_page !== false) && (strpos($intro_page, 'ReimuHakurei')!==false)) {
			// see https://github.com/ReimuHakurei/phpPgAdmin/issues/27
			return 'ReimuHakurei';
		} else if (substr($this->get_local_version($path), -4, 4) === '-mod') {
			// ReimuHakurei adds "-mod" at the end. But it is not 100% sure that the fork is ReimuHakurei!
			return 'ReimuHakurei';
		} else {
			return 'phppgadmin';
		}
	}

	protected function get_latest_version($fork) {
		if ($fork == 'ReimuHakurei') {
			$git_repo = 'https://github.com/ReimuHakurei/phpPgAdmin';
			$want_method = 1;
		} else if ($fork == 'phppgadmin') {
			$git_repo = 'https://github.com/phppgadmin/phppgadmin';
			$want_method = 1;
		} else {
			$git_repo = 'https://github.com/'.$fork.'/phppgadmin';
			$want_method = 1;
		}

		// Method 1: Read version from lib.inc.php (version might be increased after release; unfortunately currently not the case)
		if ($want_method == 1) {
			$cont = $this->url_get_contents("$git_repo/raw/refs/heads/master/libraries/lib.inc.php");
			if (($cont !== false) && preg_match('@appVersion = \'(.*)\'@ismU', $cont, $m)) {
				return trim($m[1]);
			} else {
				$want_method++;
			}
		}

		// Method 2: Read version from HISTORY file (might not be up-to-date!)
		if ($want_method == 2) {
			$cont = $this->url_get_contents("$git_repo/raw/refs/heads/master/HISTORY");
			if (($cont !== false) && preg_match('@Version ([^\n]+)@', $cont, $m)) {
				return trim($m[1]);
			} else {
				$want_method++;
			}
		}

		throw new VNagException("Cannot parse latest version from phpPgAdmin repository $git_repo . The plugin probably needs to be updated.");
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
