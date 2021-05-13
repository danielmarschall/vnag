<?php /* <ViaThinkSoftSignature>
GnWfHSoN8khDxWVPk1WtNNb9cya8l+VjuXNZoxpwryMvcMbodkqUTH5PF4+IauISp
NeDx6OCgFyr8OxWDFI13S827OTOITl9ySe7zQTCY5Ky3rZFomCd3KokM2vlwwtg8I
CFmNTh7eFvPionKy9orplx+746S8YgRIr9z5kozb17AgDxRjdzXNj9v80aRyRDiUB
EQ9Z/MAFmSF3Ki6MQOwLGHd+N3XWA7B+/XRlT3XgZ1OqmRabnljWV+g70ynE5gpLc
oD/pHFQRJwv5O9s9+q7bgeYgPG8shO8Ire53OHgdW168bn7PhahlP95kYwgfkjWSc
ENR7znWzN4wNHwvn4K/DaDBITibnjeSuHyGX2YUmGOKlnp1J3snx7+egXeOeAq5hW
93hBXtXTfSUyekp+oMdJgYzbBesas8bDcqRYQfICh8njmCXvigoGUGTd+5Niv46S4
EguTkX9OMDWJK1ijnBnQsRL4YXaPcK3JPi9tpX30ozKms9zitwsAKBa9jKwn0wHyx
BvpREKMMpAuqGOYD7CVpwJ1/El7bvc3lnP3L+Lm7NHYk+27AKkchYkziPi/XUqTBZ
JHKZumfENALE2opVeY4dvRZtTq0HORaX+qZ8mw944T5g9FM9kHafYwjiZakRP97Zn
R1JnBFQjdlYMF5q7G59WFixNcVuFFA9SC1GNbdZl8EAFI/7RkCpbBNKsXd4RnS3hk
6yBb+tsVg39B/Qh3HrjaRTw5B/RBkEtDDWfnzWwNIz3qJ/PyobAkXwqZqy4yDPwHV
TEKrSSNazI4dZ6unWVX+sqovlvJNtSv+O2/l532t4dhS6T79iXAf0lC1LHEo0jz64
YKBr7wnrG/BPr619KAnO7JUt4S6ab3OSIN2zYdIBVh8wrYPdpvc6WVpKGuhENGlEN
Va2jIWLfEeo6mhn2BbrNAM9l2gXB8Sm8XRppuWiZUno0CPkNKoq991B3TTAHdNwv5
vDO7QzrMOXzdT7X08+Eb74BIRds9Uslvye2vLNIrzNmj2b2FWab2benvjx+aTIWOP
4nMmve3mVmPU3NbaSsul1Mp9lfiqcvgOXoNSd0+7neV+dVVz8QKDlyGDaDjZ99JQ5
jUMqRHl1tqoMuCFKoJwrjOL5U6xtip+3L7o+rHbd6yzjPB9tKBOY2HQhwpy9gQkw8
72LPID+KkBzc5+LD6v8gYxs1deBuV5t7HKGAfx1q3+P6b/iW4nfE2VX2vbgFUv0vC
QtZvuMLD4KYgXyv41WCDgfAIUW1No4WWkr0dQQjhmx+m1rTOQMiz6fCJ06TzJTFQ/
NLpKercAnfh51VqOtxHeMaxBcYBkKX1V3QiIOYrCG8fXbklFnKMgoLL/cJHd4v4Sq
w==
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
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_phpmyadmin_version($system_dir);

		$cont = @file_get_contents('https://www.phpmyadmin.net/home_page/version.json'); // alternatively version.php
		if (!$cont) {
			throw new Exception('Cannot parse version from phpMyAdmin website. The plugin probably needs to be updated.', false);
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
