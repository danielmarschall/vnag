<?php /* <ViaThinkSoftSignature>
q6gx+eKOBlrjudngDLewBu36ohNJSKufLoPCxcLlswvaRLCe6fNoEPFIkS4FW748J
C+3c7H3HZx4oyqEztzYqaG53xpaM4jFKsRs4hYPsxLmOtXyiXUhk5bTeZzyGYQpTH
IquBkUBcworlVKxaTTdipcytlq0e9bTWMNd1vivtttYdpJd+UkiaQL/Mb/AwvU62Q
y27URiKSiLx356H9XQbZl7NvSxnrvDwcV/1+tAp/XpFh3F+BK88+y6hs7oNsN9D9H
c2dw3sSPH1b75HQTyDD8yyBxqGqxAEDYUNBEQPHnxYk2uLagolfiLXiyrUkJr8New
attBAECBmfFjXjOvhVz4ggPYKVjOL5i1cKviwsiHo5BFH7H5QECqUbGyHNm+Ce6IJ
5o5wveQG+JCzNjH1BifZZ/dNM26PbyVyOLiFok8dc/fkdLmbj1x5hYY4nvhL92QUT
83XBDzXV/2QIiiQTnwi89V3iOv4bfLEc9OcEDM0m+dKKrista+btJLw8u7NV4PXYE
1U/UBpH+NjJ6uJ9nitVfOYN8LgwerGhFN/WYxjiBgRdhbux2/YQvH2V0yX0+sNn5t
RLi45LRLM5VoCg56r6vhNaYRoYTeluSJ57FwMVong1JLv/kW07/wHQ0PBRs3tDEPf
JnatiJxYhhJKd04QdEfYuQj5o+51u3OIDMLNh/3IQtxM1La6I7dBA4vpq/o3+Tg5x
E5HouzDPelo8OTzB/7sVsFHIFah1BI3PoIcbNBKNuMz34KU3ZQAs8LnYDutTKLtEX
FOOhROidGochYXUG71guCH8byMo+tJ8ISzzNQCslvcbL2Xc3jlQwlsKtxB3bLJxs0
pWbJ73MY4w2lDUBtXPmF3Qdw1n4CoWs09EJAxYU1ZvtwftTtfIEOV3YODIC5QzZ1z
H6yDDvjPJ2WTLbIIXrsC00a0KM8aek8lHog9a/ypZKFZ+QEw5kWSVkp7hALM3jwxr
SNF6gnf0Rnmw6V6MltZI+MhfSYsb0YyINvhQaiI0hyeeIEMSyZZfMRhDCKmg09ee/
pkUD/d5lOQ0/Nb0fhdL4MCsR4X8YoG+OFgZ3D47Mvw9qpOw2m5SQonMVDJQFLH+qN
gSqlcoJbqy0ElBK1eUHP9aHUyMVugxR8v383ernTsUhBja76w2lJ1d4Cd9Gfh0ASK
O8ApnxCH4//R34u0DX5VIZHHQRZa3BoyjdsS+2FnEEcjZXYmT+O89+m0bjNqnMvkR
82YKssUPI1DW+OwoE06TQX0jyOkFmGEFnkJEOa6ScnjiCRLc4T65ABbK2xKTo65yl
S5zMY6/QmbNCoteoq9f9RelLyK6Ra3io8pjQemdyLBiHq+NySUK75dO7YxUuZJJsw
Q==
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

		if (file_exists($file = "$path/libraries/classes/Config.php")) {
			$cont = file_get_contents($file);
		} else if (file_exists($file = "$path/libraries/Config.class.php")) { // older phpMyAdmin versions
			$cont = file_get_contents($file);
		} else {
			throw new Exception("Cannot find the phpMyAdmin version information at $path");
		}

		if (!preg_match('@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU', $cont, $m)) {
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
