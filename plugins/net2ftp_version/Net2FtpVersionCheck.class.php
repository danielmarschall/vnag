<?php /* <ViaThinkSoftSignature>
lExJowQmx58MVA6MsmRUWBXI3PT7vScRq6WfY82k/2gX71+VIIIoAP7CR+RC2F9t1
gSxNUoNAtbwtJry6OSlnKl9Eg/RLmGUFFTuw9J7flX5zBnZwejquWSfvQM3AhkFLe
0zNLNhaD56gceVrquJzaXqIPdfCSfpPZU0Mo+FvrvbpfTUlMsbabb2Aesxy+Xr0JL
e2a97WXlUdeBKVZQANbjNzArtUgtvkkrIzZOenZ0LLmVvyYQ5oJ6Vlj/dymRyMCzI
tSJFr3e9XdCAyoNOwbjbOTaylYCZYR7lz6pU1gyUyrFrLaVsfMolZouX8t2HzJp1K
ew4GnJ52106GjjXynkMsvry4195S4XrWoWchbbK5FGKKCJXoJK5Mmn/b9IlUBTrQf
osJ9CWjI6K9iEVVNWr6UxyABOqfQokzbqTCf7Az/3kJcLqwk9oB2Y67NiwQgM0eek
nTZdO4KKzLvD3IYrKfhnzXJN4hUz+KE3AsMGXpmSy8ovDYNZtY/Rbs49K8ZlJGqSZ
P/WErF3laGosOnLAmosT8mdUM8pF54sH1Ve57lN4HdDH0Ro1qOWljAB9LridDxj/B
qp5ZTJ89sz/RC6XIVYBJ0rNfg41BhXIEQ3OcVQ2UddgySXUri0J/K/acgpwLa2DB1
8l+L7hxox97647hOybj2QWAtbg6l7CffU5sIdw/8fRnuorlmNdnKcqguFynpt3d2c
zKSRmjJdqXEQFMxnz5Toc6BBNRMjl1RLIjyImfiagdP15eDIRnH7ARn6KEmJSDWsR
nsAFXFavn4kc4mkIyf+hef9Dn86wGYP5lJITuFwwz3V5i5PDXwBgkB5Z29qXA1jkg
Bu3GwWKrASpaN6+VxNrZlvLot+BLFPbpDdpGl0hGYcUm2VhDmtE7ZJqILuLkTqc2t
bGOhtF6kDyyhKU5jaiMOUtq4x8lPhDCMXdLd5Jyf6pIUTJbKt5nnsG/R0avpVgacc
KpB3lrpLOF4lI3PyMxMuekoAhXmeRIUcH6fw/k25dfb71FSFiqXpDajYbZGbkSo1I
KKV2VbiAxzE0v6gKDvFNlSNbFwCVshxPHk0jx332qkNecgLKUGOgoXtL/3urjqbv1
M/40Ci+G+2Wb4uvQ6x8SJZ7f69OxyHr4h7Hrx+MthqoEPDuQKsixw63LjwPbzCXOb
ePysuJrIiy0IqxsUbLSiIBSW7AQkdwcHdmj39vuW+t/Swek5XK7CAu3+lY3LeMC20
BzgDvUlWohWq7EPTpY2hhjrGvs66sLRPIgbquXB3yo8dfzjHrfwYd3qhjLAUPvepW
PcL+UXgnr+ViOyRjfukNsfruULKvWWs0kaiGbWj5hU+IJoGo3tqK8xtf7ed8VZacu
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
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

	protected function get_net2ftp_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/settings.inc.php")) {
			throw new Exception("Cannot find net2ftp settings file at $path");
		}

		$cont = @file_get_contents("$path/settings.inc.php");

		if (!preg_match('@\$net2ftp_settings\["application_version"\]\s*=\s*"([^"]+)";@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[1];
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

		$version = $this->get_net2ftp_version($system_dir);

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
