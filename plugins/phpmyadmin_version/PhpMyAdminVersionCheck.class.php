<?php /* <ViaThinkSoftSignature>
cyqSbtYKaD5MUhgAs4Z9sDuPHiH1rAb4gJIjlA3nWH3uT/M+CveQvXYoQW3kLyXD9
uM7k4o4CGPpjyJnflqluGqtaXx5x7dKC96hWM8OqWixpv65hE+oISA5Dap9bSy2RQ
2KJsSY4Pw1Rp2QrbZo7f8z2eGVUkmonbRwmDWd3iy9G2FCHvmuEd84YAtDmCB/tY0
yG+w5ilEMZPe+DYOPhOiAA02qWPlTotF27HaMKBWOANCNc4EfD8WB+/rkdv8VvFqT
d1TiDYWr17tcohafgt/D9jGqkn4798lEGesd9kIzHBVv7IlrNz3HLmNsMQpnn4fbP
KmxFuwxBlcxLbODiHGL75dXG6rlf1luXFJqbdb6+GqBMozImeeK/9LZV9NDl0nIcS
5EDN1OvZm6R8W9AYCjOYvWC4CrZSEzNcwDF5sGtDHHmNme/TLiWVH/E1Krzz5LRHw
UdJgvlFzOrGxvlJVhuEWaHbEGhv2YoTPYOzAZMuQe231faOV/0wuaPsV6FYCusb9/
Ykb/IFAEqXbw0mUZac2IRkAmy8jTg4FXmMbD6EXcR5zgMRi1I+ZeuN7hKesOmme0W
bvbjcO8K+f2pm4TK7t214KteqZ//tUW4nqGaSvMCb1Qakir6gjF62TvqTQD2PRvU5
0pqWr6X3OrbbVGhrz2lE3WldW/lXDBk/4UgA3TKpDUoQpXSz7O7SNTkyCZrAqBLqB
5mIKz+8HumNOVhTkufWyyORNWpkjz8l+x7mdaj4c3Ennpfa5+WwMx/5DPvVNkasqI
9cb0qF3ZDfxVYKnGpJFV6cNtyw49mh3MtU4i6LXYWj/PajTeleGBZOUW7VxdhOIqp
AS3orn9AeQZKEEG2JwSm1ITMQ2KFxEoFItt+D9CMOwu7/zw0J4ErHFbU/eQUBwWJy
t5oS1FjhhgO8RGCeOdJGd5of7wlUvFQD4NgaJ/aurFd5pTUS1riwDjoJoWvxBZPNf
8n+wRBg21iVhpQBvofvhcxw4enGSXSh8T/M6IekHP0vrq0wEsNFsg5rLGEx72fH6k
l80RCRLaVz8jXxeoMzLARuVqpmLO4b23pMkhsCLEZAuaNceQVgEM80/LV06qRGHEO
RA9xvDWiGCIEYgp7zJMSeuRRsa/zP49mbj5GcBbMQ7r0iAkyrQJR26V6/erYU5phr
YO8A80zKAVPaCL4GrGrDcm39Q+3J7RPyVI7XSrws/u4nhHkvJ+9BH4K+BF6+ykCwv
1vdrcxB+FFcvhsqFNiUNviCN4MdIyunpR2EaWIp8rCYN9LQVkrbczJmBTsXjznexz
cQFyZGH9F/2sPeoluE6oA+HrVA9jzJdgByeAY6S67pUZn4s8gfVRZvIvER7HQ/6hu
Q==
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

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (file_exists($file = "$path/libraries/classes/Version.php")) { // Variant 3 (5.1.0+)
			$regex = '@VERSION = \'(.*)\'@ismU';
		} else if (file_exists($file = "$path/libraries/classes/Config.php")) { // Variant 2 (5.0.x)
			$regex = '@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU';
		} else if (file_exists($file = "$path/libraries/Config.class.php")) { // Variant 1 (very old)
			$regex = '@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU';
		} else {
			throw new VNagException("Cannot find the phpMyAdmin version information at $path");
		}

		$cont = @file_get_contents($file);
		if ($cont === false) {
			throw new VNagException("Cannot parse the phpMyAdmin version information at $path (cannot open file $file)");
		}
		if (!preg_match($regex, $cont, $m)) {
			throw new VNagException("Cannot parse the phpMyAdmin version information at $path (cannot find version using regex)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://www.phpmyadmin.net/home_page/version.json'); // alternatively version.php
		if ($cont === false) {
			throw new VNagException('Cannot parse version from phpMyAdmin website. The plugin probably needs to be updated. (Cannot access phpmyadmin.net)');
		}

		$json = @json_decode($cont, true);
		if ($json === false) {
			throw new VNagException('Cannot parse version from phpMyAdmin website. The plugin probably needs to be updated. (Invalid JSON data downloaded from phpmyadmin.net)');
		}
		return $json['version'];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the phpMyAdmin installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		$latest_version = $this->get_latest_version();

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
