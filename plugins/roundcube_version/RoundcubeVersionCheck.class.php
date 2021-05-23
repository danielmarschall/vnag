<?php /* <ViaThinkSoftSignature>
M5s14jZt1LxgtSqICA+mTNwD5gyp7BoQcIZLnI+/UJKBBOucFhN1ZlsZMEnuTBCkD
2EiOsnpjFqE9AzQA2UyVikHkI+ORmA6NDFEIalPk3hVe2oIC16AFsUPJaJgNix3WG
/P8uTsuYrdfmMnrcHJufyIhrA9CZproNE/3FdWrRCaRVDGRPmvV4H1Ge0p79oLgAP
G/9ExglsCV0aBdn/43H3ckhAoovBHfTyepXk45K2++mEF+DIyZhtOa1F3bWRRmVQL
pT19P96zNcsQSU6IivybRc+dHOwYA6tvGBJftRtBbgkCN/W33FF/YDRAdIRPNIKES
QOE5XkovdNtpJ+N+SgejYDQGpo9cGD5LVPRWZezXqcj/5DCGh69iYJ3iZrAMYz/v/
i/f41m6fl+Bv/A9KKKgz5OtjPfiFEIbgBEp1DdnrBHBt6aeF0OLR8Mi97jiV+Q7JN
QzKyEHTU9T28ie1rE+DVMzukzNPXEULPeGqz0QGpOBFQ7KVu3HbQ5nve9cXUhqKH2
K7pBrmj6t1Owl+oaX3rIZ0JQHBtZEGbdt+0KxGIqcHh0eYggZiYm8ZMTB8jydjKpv
lhvAwRlgWDaQ/i/4wNLJDRNgd7O6TPItNTROw6XKxL9GOzP5wKK6ZNEcStp1rUdI1
rYiqoKhybsiyzmmsoF1AuSt4ya6IYQkV0aOCadpEX33LfzjbeOyM22SNf7FC50QM1
mo/O7MA5rR5n2bkXe24fhJ9T40jhLvrO8KCDXeLK5v6W/4hfnx6ICx+g9+bHO4d2Y
0Ij9Ck13oaHmmVNkvbFV24DbIYicyCXJZoXSQSGAs2h7Z8TLKpWHMPtzWNTS0ZuDy
Erd9kdkpxX0Yr6tkGmSWMX18qV3gVn3DfXTUrG2Lrzq9eGkAqViVXPmCLpbn9EG/7
Sl0T3XEApLOsJIFCQgIFz1ZqfvkVcyxCX/P8yTa77rz2okroypAw2AHFasw1kYbpb
yt3TmSDp/H90watdIAs6cvzaQZSPT8sBYr2K85n89vzoznU4YdTc2SawEv8/3lPvw
WyoDr+A3myYPG0eSwDZLPqzOylaXCu41Rs9Rm8waYplX87HiNz1dw+vJyfkEqW9hh
kBoMYkBICZuCZ13edXvgutALbJgY6ddmzPqLD50ReBO8ORhWlnq/wrbLJM4WN9h9y
sBN73TLRlfmfmTF1WKpQu54KnFhj8Vu0SgNk5LCP59lT4a83VVRMMr37sbsBRH+Ye
E3nQm/+5lQh9U0M2hpUQqkt1IgImfN/ZTN5MYFCvVMsOJ6H0NRAdBmY3Pl1fwy2Xg
bMvmHc+olwQxf8Ep6bh/yi/+f+X8wLsPvpUbLhCWXeJOOrzOYQwrY9idSuUk6Xsav
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

class RoundcubeVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_roundcube_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Roundcube Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'roundcubePath', 'The local directory where your Roundcube installation is located.'));
	}

	protected function get_roundcube_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/program/lib/Roundcube/bootstrap.php");
		if (!preg_match("@define\('RCUBE_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the Roundcube installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_roundcube_version($system_dir);

		$cont = @file_get_contents('https://roundcube.net/download/');
		if (!preg_match_all('@https://github.com/roundcube/roundcubemail/releases/download/([^/]+)/@ismU', $cont, $m)) {
			throw new Exception('Cannot parse version from Roundcube website. The plugin probably needs to be updated.');
		}

		$latest_version = $m[1][0];

		if (in_array($version, $m[1])) {
			if ($version === $latest_version) {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
			} else {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Old Stable / LTS version; latest version is $latest_version) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}

