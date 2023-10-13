<?php /* <ViaThinkSoftSignature>
SJsIrfinSzsYbyQO+IHwJkShVGvRRbKXBSTuyO+b058nk32JwGIh6fEo7tkWBAtS0
3e9479eHecdKr4IYhYwPF02Z/JUO3xt4E4rhevEtATx9XgJgV+bu4mMxrYZPxShUx
NjEr9DYGYlfJgY+XgK1bwjGjIHyBpO4Eh1QovgJ3I2mWEiYaX3S/vuw+KGsa063uT
tsyBWIMgn+XTN5bJWz6jqgOKGnsKVTSeEpre78uCloLFmI0wXp8MNfBfesSs40zK0
trqCF8yp3NitzwUtnecwQxbncp8iVGU9bO8EoD3BUy9Ohv8GINCQjtnuuAx9YFVlD
++wb8yN3p3NsLw8r5nu0DCPbfPxJALC7UiBcZ/z7m6CO4rbsaV1yfSaX5jDrAvCtd
TQPBIcb/V+HcVgikGSbZkBxTv8+R27nyv3csGPIOu5AQMBhtDbNAJphDYhmFXxqVw
Ww91POe3UqBao/cxDbsAZRL7RJ26L1J4d5n8qEd6DJH03uV6SW/EjPieLaGgZ7mhY
4ifQGqTHjZmQuLs1S1aFL6F4oT7UG/6nmBT7zyd4Lcpa1wRjO1egAC3OxDCrYJ8wB
fJWzqLd9gT5utmvx91I83N65evE4LcqS5uGjED48s0uGZ3g4YDFySwlxqI0mclDGZ
8Mf4c4vTjzB0tzFgTKiYlzR27PQQ+7g0VTGKl1SKwBt8mXRI3iQwkvk/QUXWhvR71
y0hoCVxlC4cq91brH8LornfDDHQhyVydrkAvl55uXSv8xq7RhlY+Pp784CkNAemKs
t8HfHWtfXN7aXXiV0wtq3+fNE1wykSnz/onYFNw269JNTZgW4llFZgY8DWpOu/B3Q
m0j5DxatYr571TByTWH+1dLPOga2VaBYhJonAlaVf1FNF9IRhsZoENIDUq/7ej/uw
G5XWbRVX4GZ32CLWo8NTup6VtriZdscUf+Erw0H7Xw5gOJoSCzOy3/8dFmoIWQyx1
ZsejNS4tLZf52gOg1XuF01ZRUyMX8X2XW8AUPd2b8Fu3cObSuUDpYgNGRK54INMjO
VXSsrOZOpo9ybNQ2h6OrJndA1UJtrThowVxn55DsfSAQ4AaI7ZLztGRMSmWk6c5P3
iNSa2U9Xy5fV9neN1T5BibyP64aFWkR+gZFC9Xxgdl/YQiPEmorxU98JtDwtsBR5J
FdMfIzsrreiXs9rbrTeX3sw0T4Ag2hOrQuoBuZy5TDoh8Gjc8bebfcALNR88sktN7
WMEA8T3uxhE13LI3Pu3iM6iWw+6+gNGmBA9kC42jQvN7s5S+7CEA9PyDVI0CiFn5p
uzEa/b8BTISIhb6dQzM0yUs2vk+PXFAa5muIGMGmf+dUbPuC+BXbGRic81xKDdYDU
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

class FourImagesVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_4images_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local 4images system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, '4imagesPath', 'The local directory where your 4images installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/includes/constants.php");
		if ($cont === false) {
			throw new VNagException("Cannot find version information at $path (cannot read constants.php)");
		}
		if (!preg_match("@define\('SCRIPT_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new VNagException("Cannot find version information at $path (constant SCRIPT_VERSION not found in constants.php)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://www.4homepages.de/download-4images');
		if ($cont === false) {
			throw new VNagException("Cannot access website with latest version");
		}

		if (!preg_match('@<h2>Download 4images (.+)</h2>@ismU', $cont, $m)) {
			if (!preg_match('@>Current Version: (.+)</a>@ismU', $cont, $m)) {
				throw new VNagException("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the 4images installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

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

