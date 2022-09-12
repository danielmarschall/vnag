<?php /* <ViaThinkSoftSignature>
Jwv2QTTUkaqqNzdKRCyG3W5V7ue0Kdy89/G/x4LhQxUQUZP0Erf3P2EI8rxUdQ2uA
0OjWHGS0+1D6yEB8M/HEFCTRzHnMsl8zzo4Uiemi+ZzJytqtYi93HrR6SOWNzzblN
iFhmDvZs6+4cmH17wvwXutfabzKhUaVFNMavBdkko6sH62dgy3+U5o/qhqAFoAccR
FTvD3fMG9CSNnEGMVFcFjpKMIUWQjQj7bB0PA8EMawJlE7R9ydNJBLET0KMnMG6zD
LNF72ENl9mQ4tfLp1e9qYVJ68Lf2WGu0u4E1Vb2fDgT4c+d31WlmyrcgmqIfKM8hK
U5ht/SvmM5OT9OOR6cjZBHVN+ulsibzCYq1szcpKUSlLHiLh0B5keoSpJduGxxz98
7ECTN/s0VWYo4AzwvqSlEl1wmlStXxyM2ixZXcLcSjKvTZEIUQOfoSM2Vx9hFrIQX
eKJccTu92VTbN/E7Q+79sMA+ULM/FBixrsoghViu9jcwYaGlQ9mnhNAEOIVmROgcA
KkAHbhoCnBn5lImlVDpvfkaCTgv/SIMkG92gzmNHA/LUOhGFb/A3BQyqU8nhhGtrR
FKPBmZWw/0IvNliNWauxodL+jNZp46LOaV3AeDCC2Rfs39A9Aw6tuSaSxZH9XM++p
FIRpPUQkCJC04t+wTZPfLER1IRWmRBa3c+CMCIgtTZOm0RcAoS3etHf4kWd3L9+GU
D5OLe06C3FgWmlUBPfBTI6h9IQY2VdjxjCP+SvwjGY2Iy7AxBVszapt69YbSnjI6V
g0lHNGm4vtnDXtyZgBUVulVU0nO+JizB2LKtkfRxkBbjpeL/ZlOQBF7NNjUeGZo1H
WJl0xAQxHSIA4oGPOX2958fB0oAyHK+JOPx5AkQ4O+kvM72tte4eDUap1WDOMa7zU
aVzu43FzP3vXBKPsOm0n2cY+fDy56G/MX7hGQAIvriw9ajADxx43QjiLNOKS+N1HK
MNDzW12Ridr0Gnt5NGP8cA/hYZRlpy4kAPJFttA8TTbIdQZHhZaaQCBSfX45jSbPs
Mh9mJmjBvP3bXU4za3XrCxcK/aELxNvczp/UlmL5KDYMalA7arHhaIahLgN2sYNGh
2tzJywxNGVdWcRQJoFNRCbqRMcroP6ro/VAO4gELfq1RE7C3ZJqjcfwAno6XABi3I
PuR+LzADuh9yM+1xdYrpAmYfrMEYgBzyx05vn33GDRsaryHYtvkjNRrjBAujnYY0n
Obus4i/hVah6yUgURhprwARav1gPn4IaZbjV0C93CpIWvodwruNW1eTEMoPOw3Fl6
x4N1r5Q1uQpHkfJoOnEOSzUxVLa6kMYamF6fE9ckM55RkzJCX+ff4tUPzDGXhS6/x
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-09-12
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

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			if ($version == $latest_version) {
				$this->setHeadline("Version $version (Latest version) at $system_dir", true);
			} else {
				$this->setHeadline("Version $version (Latest version $latest_version) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
