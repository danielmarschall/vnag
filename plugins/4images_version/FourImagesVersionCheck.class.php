<?php /* <ViaThinkSoftSignature>
UzghgdkH2mWbh17KeuX+tCGeGd4f9DmMtoRV1S0L+qpVQ+3khc8X9mMqxd1pNwzG/
+FnU1vAhHRT2sOcWVGsWThIBZtSIbdy+/HoAhqB94nAqwMIzwrOCwOnB+2EbbkWPv
TQImz8/wxd+Bh3sKpefgxZa1soa5fsLib/Ag2xDL4swEJ3HZRwPqB/su+I7zbMmVH
Aks5P0MnfZY1Jtj1yXaFilaTkUDDqaN8AOEjbA9qa7mxa79zhLq1weDLTeprYUMWq
R3jO6ohiNgIEddRhmvCC3n5HMiIfyUtJHwpS/+4VLBqejpMmEP9cTeCabS8HZO1SD
AJvmIa6rhPdA6gYw0eSI4LzkMKohgykrO6/i1o4+6Uo4INpe7foJClnRNboPLHdr8
TniS7Xz7Y8/EK+RRQx9dy1AQEzJ3zE30y3Hj3SvAPzlVs2SjTz5+tmVlsqAp092lQ
isZsjCJK4YKno39MhP3CiNEcN7Yx98KCYwDmEWSlTbYgTCMuo96CEAdObLCOtrdip
GnttI5xLfLip6xE5w9C6oXtBx5mEqa1+k1VIVDljQ6ft2ug35w5n8T4qmDvphgtYD
FxlzMRpLKTWzUEh/BxWYJDDhvDcpz9aOS7MX5NRx0Nsh9TAiYzT7WgcJmdU92qAJB
sYH02IV8X4HK52DkEYWsNn/uvbTLtIrRJPB3QyKgiZr1xDzG2/xM+nmyNCPuc1tuL
UvTiE+mHF3EUGItKpWuio7jwisPcSiERaJkzzmlSyEgks7GO0R727G4LN6aU6bfZd
NFcUn8wNApIrSpY9QWCZR4SLlbI+KLc8dV98x29WqBZtVfPqHaLb1x0A8tdwzkbzi
XlPDTSoq41Vp3Ac/048LTBq7sqRgQfeXOpOykhe9GwbyIa4r245cpO9ecSqhIC+Hk
/jvS1DL6X8JDBQfb+jlG0Etolvgm8wXrPqUbBXOjXrZdL6Llh5ds7swIetoWvPMXI
Dj+7vk/SYhUA2esmDt5h13MZIlxiyCQ3WimVhqzy4ugNBHFdCkoaXsCm0gCy1rLmO
kV8CTAW6zEhBCrgGHhaaGMV0RTc6ygnkVjPyZ3c6p6bxlc3DJgJ7E1dDNOqWH/mzO
n61R6pl7ISJ9/Js0FJkL5WcvCbjao2QuJD00gyGsJpOt3WfCCHGjif/VWZo4M9Y/K
GDyE+Lwq2690k+K2876Y+rdXoopjhLv6GYXXrjPhUTRMtzXkK5tiVjRzLMN5T2fvC
gWLFxi5J1qM4DV4AGLF245144Stsa3j558edqgWEK4CVTsHGT1YiQFF0omFVlG9IT
cMeslXPMLRwpyx2Z/UoMBiTsc+7lazwlZ/w9RutNd7pRWA1E3GcIC4AO4NPLQ/1nW
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
		if (!preg_match("@define\('SCRIPT_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://www.4homepages.de/download-4images');
		if ($cont === false) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@<h2>Download 4images (.+)</h2>@ismU', $cont, $m)) {
			if (!preg_match('@>Current Version: (.+)</a>@ismU', $cont, $m)) {
				throw new Exception("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the 4images installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
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

