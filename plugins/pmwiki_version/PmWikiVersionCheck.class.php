<?php /* <ViaThinkSoftSignature>
NYkLKM16RHNYfXmjgdkPJRDFv60IevURDpDXhislrri20CeCNbL85l/s8qTzwi3B3
DBka6P0CaPrhOx1eBOTJ1/DgFMP8peQnG/VE+Riq35tzS5m0rX7b8zAt0f14HmuB4
cpLY91ZtLUEb+KM+rKLu+mn+oXHbUo8+Tti7MAOp+GxR2mBPHJFgW3NgfaFdmBWiY
MQiVUcpo4+Jc3iLUkMEHvUpspOJLWwXdl5iznN98AvboiMd30qujSZDcFXvyRgsJv
BMhw8k/snsHTBvsfsDsaluVNGIK97Vuj70fRUeYxhWgLwuZogHu6T4LL0rpk7sMuN
BM3kZJ8/7o9rP0JiKuEsGchn04sr5DmOL6k3W1DN/BwhUEEjqAqfKD1X06fkXlNeb
BXOK21kKvjOk3EdUlEAWH+xiMf5eFAYNl4EjKPNwRMZXF4j2oU3VLBqjWWJtUfIfi
7HaxyNMafm0d0coVgqoTJfnpXGVjPDVsRKJvKhRemjP9orra9Uxi9y3ONKAgB0ekW
FeoTps5GyOZCRuUPnJ9dAQpz//COJX/AXG8qDYsVxx9CMyEyIvb5dcFF3RVbsYvNN
i1TpvlVMgcTAzWAmXm0EQ53+Q823Kk3IuYUTUj/W6Azzz5SR6wFjwUumRF0EKsgBW
zuh7xcXGrVoqmel+qb0vRLWC39sLtxcFEDDPsDHKOS5CsoRT+CbZuLujh6fOz+PMc
H42WIFPDKVLw/t2v3GFNxwMNCS3B1zazEGzRhuAkZH2tl8+i6jALKalhS9YUpEIrW
ctW//ddZycxhRcmiRJABCZvKNDNEbWGu2FGvBDyA2tpsNwtrLHl5EzJV/UqusV853
eMVoCz+QA170yxOgjos91Bizs/AIJnlYTOqXDaJQLA48qwCW+SqDJtnuvVVDwaRPY
4G138y45OVyg9tEihatiTupXtI30DoAadPZSx2pdEIbCegKrfX5CicjJseIegqTdh
Dfk6LrTfZLlZH7I3bqTbent8ksfY5HUVBhYDHYxe8WuYaH/JjoQR+OetX4Oc4H7TL
02J+8ZlRtbPC/RiJBiCvkrfdIKfEgPuAe7tJcak/qkOX732n9YbG57FGkLN8O1UL5
Zq5JGoraJCPQ7n0DQFVJE6czbagE8qRsIg8vcojkggm3keP1lHLD9d9iCFXxmq3nR
t48A0i4MOoviV+9b6flfEBzXc2E8QN0fe+7Y/3zBASpBp7y/93dvPJHXHUqQy/3IA
YE9Pis9ln8iBIlgXycu0PAeeHZgAOoXwxmZci/etLAiSJ3Uy/lfriyBBg7bR0U554
OofVYwCyRyh9V46ifqhpYVdsqpFllmyiZGdJgm0CfIcAFiEjXAwljH8i0wU+hEHza
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2020-03-12
 */

declare(ticks=1);

class PmWikiVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_pmwiki_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local PmWiki system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'pmWikiPath', 'The local directory where PmWiki installation is located.'));
	}

	protected function get_pmwiki_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$c = @file_get_contents("$path/scripts/version.php");

		if (!preg_match('@\\$Version="pmwiki-(.+)";@is', $c, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = @file_get_contents('https://www.pmwiki.org/wiki/PmWiki/Download');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

	        if (!preg_match('@Latest <em>stable</em> release \(pmwiki-(.+)<@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the PmWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_pmwiki_version($system_dir);

		$latest_version = $this->get_latest_version();

		// TODO: We should probably use version_compare() instead of string comparison
		if ($version == $latest_version) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
