<?php /* <ViaThinkSoftSignature>
Os6Vigdt4/PI0epPcVQLiFdxgBhWiu8NEIPIqX9z3giSygs8qTKj6ySf+t3QkS9Hr
DckIspa6rb9LPdgsWzIen0c7AdZIktyvnr0aRJytTvsEi39htzI9065rPoMTYzdNd
UrunPx0DJkr3GxKOFpEv9BiqNdQJVQQTmrfa5+73DoBcJ1Rgd/LqeBa8YPYlaYe4U
3JbEdd3ZBSNxAv4b/qk0S4b8d1HVZWslpqCdOxJmaQM0xqCmkDPRtBIoVKsdTQ0NN
CGcM5lmo/eyk+N1HNtpIZxrkfOil8OAy1Nhfq90f8IQJiolOn9/+SWqfwtPADb2jC
LE7Ed1jgcAHfDbzD4E7NfBWL3dREiLBwnZbbdLV4EwojAhpPyRTBT9CQk0HbhF2Zs
EEZMEzV/ebWQ1/S8xjC+cDIw4I0nKCg1/di50e07yP1Dkoau5aUiS9FcY5BhAkW1i
PdgJz6MrtQv7t9aqhf6XeF8PCEVbkbdckzIWMJTYP1IC8DDwBzvIQ6Ss8g+YlWsgx
fHw1ZKSF5YSnkgrxzAOnzAWNk9/68OKwEe5PzLLCqyktWw0+E4FGHiXIkVY8jyc41
5Lg/2iE6t9k4KLmq1uk29DnNmeydujMjaUoT8+NetCPKPQT9hQtClliw9rPVlHawa
XqpBsHamsz3ZWEQfF+P+jKJj9azgB9pMv52nYEEwuWOUp78aAWEDgtT0MeOb1JXk6
kBmekIj208tOcrHH0qQr/03Z3jEMoMgGfa1IX0JEK7v/lJWbPSkT4PdxgefGjWKcC
Zosc7WsjUfasiSN/Qr08DjV2hEBbYg9j2o6zl3JqjGr8bkCmIlewl9II8u2Ioc6vL
bGRnLtbiGvHAOkPhGYwWG2Y6oLyrQQQzHsSTvPARN1zqCPjlkCntGW3tH4hg+aFvN
R932hgONDUtxH5W0Azqq0UgE8tA7r4HWwPbN81UT6trJXvOg3CCT5Gz0CJm4vc1xV
BG1ez3NwYVVECH7I5pjRI87xBhb0GoKJGK6oAf4zzSLIhCGhWeVc2iJmGyoLkzImG
2g7AI9ddpqqTcEqYusnhc5HaNccaq91ABLtBZ44zpIaqT+YBEigQ9G2AhwcA+L3In
4zj9wvjiM8kbgyi6g9JsUncPi6+9KoZbMpVtX9vOX5FnUuUZ2ktzH1tDp2ZkuUM1d
AhXTFR21ZnD9Ji2LPs6piFGOQEwwjPwImV/Xd1KkNC+E4UkfKZn8IEP5eG6FvL4p9
KY8nmjQixlnAH1SlQD50PWXWSEhW6JW+Zr5VFwVnBEgHFb2mXqqeQE/s6JdQCVi4K
3H+twnvPtn2rCnOZ2OpeTPqGiJ1JEt/cLdNl2a3MD6tduHuvlWE7fTMnO2poU0BDh
w==
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

class ViewVCVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_viewvc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local ViewVC system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'viewvcPath', 'The local directory where your ViewVC installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/lib/viewvc.py")) {
			throw new Exception("Cannot find ViewVC settings file at $path");
		}

		$cont = @file_get_contents("$path/lib/viewvc.py");

		if (!preg_match('@__version__\\s*=\\s*([\'"])(.+)\\1@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[2]; // e.g. "1.3.0-dev"
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/viewvc/viewvc/releases/latest');
		if ($cont === false) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. A');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. B');
		}

		return $data['tag_name']; // e.g. "1.2.1"
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the ViewVC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$local_version = $this->get_local_version($system_dir);

		$latest_stable = $this->get_latest_version();

		// Note: version_compare() correctly assumes that 1.3.0-dev is higher than 1.3.0
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
