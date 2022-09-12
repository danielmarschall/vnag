<?php /* <ViaThinkSoftSignature>
USMXmEZw3BxLtCMYVFw3pNRfut91obKVEaTFxBoaMCPTpOUfsqHIIwwwTfKc5C9Ij
uLzcD6jaorajF2zyS6qWSauACaw3jPpulkF66pSgETrNWAzaWlVanmll5gD0pE0j/
FTJ7GBD5+3qHz3aF5DPzncqP3l5G8AodOPDHvD15lM1e/nI4JAW0Pgsiot9AOpV0e
UBmi6uYOGgiuiLI3ewpyWY252aJsAhVlvRRHay23pKZkMpcZBQ2eqd4uv68s71iXd
YCpT5ApoRt+pDXHVg1yUvNsz+lIVentvSMXsKMvWfJpThAppoNIDRgbYcKodEn8Gf
0Smk5ldkuhryswCSO4RYHVW7XEF/ZEuQt2ID3Sx1AQBlJl6i+/eZ7fVTyNQtoezEx
BljGD3rYxPcJ8eIYoHJf26OamG9z/mm64iNBJ5Pf6paNazV2fqLZCPZNcQzbiYlzE
cNJPmC7nCib59enEopIQkdLnu6HFhTjVru+yCWT8V5b2zHE/u5kEclTVpdXwHeBvN
cDGrTe32lCA3nuN/5FFv/C1WH000p0oy2nxZoLNg9nAkCvJ/KWj6PKA4sW+zAPleB
mPOK+F413X37Poh7eRS17cqYegQYkezpegXIASLiKNo0Oj1C0nSzhHnBhLjDCnG6R
E3JAiP24H+WPxJsiW8W1qVtESwYER8A9NVYGHNWdHPaV9tyBwap9qLfUHXjfkTuJE
od0Z4ZSmxHIVAN53Sd5r0MceGJmI3Uk1L4FGdyIJd/peWNaWHroVsFtporI+XsjSV
Gk6KORxLERNtIAi4q0BVcBCjSeY/CP+o7UwkMzp1m3v+3xqP1YBO1gg/i5FGZRh5d
r7MRbXoVgsTWA9zYeSDFwdkFpUuc9GwdZN6lIFTWAYl8VAhG5UiUs7WAN5dj+Xfrx
DrXLXxFY5/BYd2ggm6G+LiVQenvsiMtdR5sQNsJRgJdtYvcOljfyiMK4OPdMsokIM
09FtFavsOVDhMJjnkr1UlzPC6tXS2b5sgE/xTbNtm0yV6ji6DSJnMjg0nyj0E1/vf
afqNx8l0Zqojhal8N02LqXMIZVxeESLwxqqS2mZtPQo9gPdz0Bgq+ce3BXnXohsXZ
F81JQYQoMoNCcH3WlaD+YSQnBxsKNb2qerGStGPEVsjLWJQ/1b3gwAvQvMjSvcDLc
G68YdiuJwf1Ipzo5MVlM6fWzR2JlyG96CXi2j8tqpeZsW42Q09A6liOR3jyY4dhbG
0wBsj34RTl+btsXE1xG2P3TwUDaMsZYO8QWfF+m+68piAZ53ZM2vXa9BJeTRl2kHo
Zc0uoIndnbFONE3K2Hi0FUMo25brvirV1VVSb2DW0hZbeXj4PDCtgY4tiMMEIo7vc
g==
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

class NoccVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_nocc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local NOCC Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'noccPath', 'The local directory where your NOCC installation is located.'));
	}

	protected function get_nocc_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/common.php");

		if (!preg_match('@\$conf\->nocc_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = @file_get_contents('http://nocc.sourceforge.net/download/?lang=en');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download the current NOCC version <strong>(.+)</strong>@ismU', $cont, $m)) {
			if (!preg_match('@/nocc\-(.+)\.tar\.gz"@ismU', $cont, $m)) {
				throw new Exception("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the NOCC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_nocc_version($system_dir);

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
