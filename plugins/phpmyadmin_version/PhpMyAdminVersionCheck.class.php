<?php /* <ViaThinkSoftSignature>
INj8GHKi/MYGHSHlhnJTyKdQEKnHl1bdkvxhTv3oLplJtJije4a7tidEPZqawrFw0
/wniyLDaIptRhe+x+OStEKG7OckSocxRuxK9PQciJOs+/aE4srSrxKW/mZP8v7gAm
iuvgRXnoo4VfnNTfAQFLiVcUSRE+Gku4zmPGSHKTKzIWz/IYGeJqc5E6oG47Ult37
0FirA6ZTpxEYfD+oc156jqcbF1TmJjQsZewR+n8YFrCLoTq4XpwUtVtfGlHgAimQI
UFgPphkdI6QvrhpNw4lyT1VoHuutzaK6o/q6PdbUfluzwuL0oTll4Sex/AjTmWs4q
zns+n2/fL6X1L60dqKqYp8oQW2WDw1TEtKdMoPaedjA1TOxvF750SOA22ggv8w3zB
TyCcAhxag0ZmwIqVYBukASoUNa+YNG8HPaT2kTmRgdqkMUiHZGfaPuvJmGHooVoae
pyzT4lsGT243Vlm/QKDpGwLfygPKg2RB+lE/VEFP5oRIxVPH+BpFu3jZTlj47Rlm8
mlpR+5D5iiJYVDzx8JV1sT9lAbP/z552laHjX4p8UXk9MY/0B9UDqb8QFcvJiXoGK
+sFpSyAMNJHwJHXkA0UIq4RFFnCBDu5L8lf9crIpE6sGx6dhrSQGAx8WO+ziYXhN7
ytCSNLxqt0KNuZfStFw/xf19g2OqN4zv4orimUMBs9qBQ2sDqbWLynqa3MRBPjn4S
5oR9lTXFtPXSCnnwDmqacbaCci9w/CslQIFoER7QO57cz3AFvNMDfdBIjxdEcFii7
P+mqFn5nhQE0VdvkkkUW0gdicFJVxT24WPvbIdFII3eYmKNznmEVXcCF5FtrZKmyb
hpu8GnliOYW1/uL+dQeSEcAgiAaoSg8RihwPvqP8wVNqMR5RXsM3PmIGJqall9ipd
eVdeMHrNnV3dn55LVwbgcJbLvCGOA7HQ2Afaiwz/I5TsFoMwKKPZUu3ot7LhYasYN
568Z0OSc/ryiriboW4vmQrbQBr7zv7mZZXN0ZcdJLtWNCS9smf9ahKiE1VB7YqqiZ
qF0v4arH3ShdKwHK7SircPyAc4XE6bBNjd2Lt54G52lfbuTejgPNdk00RxsjwX1i4
5iL+gnZ1YH/C7cnXNjPRseGQIwEz55VDexhIO8KZ/GsjPtCSSwPZZ1XLwWV7T6WVo
zIZYOl0Y8nogn9tOw9KyzZJDe993J2of/jP6uICMsT4UgjMPu/D4M/1mIpjbwZzPt
mqUKk5wea4a4JKVActG5/UmdoUw5RNlbXOmnWzRTg5isex4E1ORzHQrcjEQDgwia4
r0k0p2pHueH7bFbIJpBvDLlR/a1zeb26jQk73y27edYh/beQI2WYZWiQarkaR7f/D
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-05-13
 */

declare(ticks=1);

class PhpMyAdminVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_phpmyadmin_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local phpMyAdmin system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'phpmyadminPath', 'The local directory where your phpMyAdmin installation is located.'));
	}

	protected function get_phpmyadmin_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (file_exists($file = "$path/libraries/classes/Version.php")) { // Variant 3 (5.1.0+)
			$regex = '@VERSION = \'(.*)\'@ismU';
		} else if (file_exists($file = "$path/libraries/classes/Config.php")) { // Variant 2 (5.0.x)
			$regex = '@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU';
		} else if (file_exists($file = "$path/libraries/Config.class.php")) { // Variant 1 (very old)
			$regex = '@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU';
		} else {
			throw new Exception("Cannot find the phpMyAdmin version information at $path");
		}

		$cont = file_get_contents($file);
		if (!preg_match($regex, $cont, $m)) {
			throw new Exception("Cannot parse the phpMyAdmin version information at $path");
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the phpMyAdmin installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_phpmyadmin_version($system_dir);

		$cont = @file_get_contents('https://www.phpmyadmin.net/home_page/version.json'); // alternatively version.php
		if (!$cont) {
			throw new Exception('Cannot parse version from phpMyAdmin website. The plugin probably needs to be updated.');
		}

		$json = json_decode($cont, true);
		$latest_version = $json['version'];

		if ($version == $latest_version) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
