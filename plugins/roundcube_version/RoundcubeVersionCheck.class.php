<?php /* <ViaThinkSoftSignature>
OpbezgMkPVeK+pfO9zIJJPwMOQ4+s630AKA2J/Ty+RAdDwFg0cUT39SNttpTBpK+a
y9RxTZ11ln3kvaw6NbOHSPswfOZX9+jl8IhgpzQzDE+ha0JA0WFrDv342DgcWgap7
hWCbucAj/9aOpPT3j5tBxsUs8FON297kr5dQwtSmHcsPIauu2tsaAOOUdDouEO8Ua
xPrpdQ6oN5tpiBlWh5RBTn+19N0ra96MUXi02Tk4+6axIQ8xrkeTNb8LLkQM/h3J7
oVndkbnF/xVrtMxjz7pB+0IxiTAREM6cRBfVsrMawyPWp075iqiVuea3XCy2Di1rW
3hbfWg0QfYX63ft4Ag7HyxANs9NMcNEDU3tdotae+Rs7W8L7c7Gu1+Pc6wTjX1gBV
Y0am+pP0Uceug2sR7nCi6Ew9ff6XQE9DUy7/GkQsxpRhxs/2UpuHHpiygvl1DhUrm
vBbDmfKSN3QTdKaTVJ3ym19MyvU/KAgHPsLX2ekDUGKIwc32vUUBvYVJvMdoavI+P
+1YKEg/oRveZuEaWvpyu/AJIlhFrd9/ZaLenyC5KpiTB+1+x3vvbVkcvOAZydmCOl
jHS0M2ep33yl5qSBjv6gdiwkaX6ABStaZNNcyiPbG7CBhbkdTlePrwSQ6GpV5F6F1
rs1mnAhFHxNK/BC0BtOMEU25xmODEf5o7Fs88sce38CaIEQ60WNX2ntMl4Fj0nDVj
nIb8tAk04h8uXPs+ZMWS5Z18OimkzGUaiig8Fr8cgi5OHdq7653/KrdyLc/y2gu+J
SrLzvQ505M0CtxRkQq1bBOw8gdeDfQfsYebwkz5t/AstIDQBaTNBbT2sgSAjtNz4k
AssiRulBzQW2usaVk2wcJWZgsZAkDCih2l2oVyrEUi1ZyG04L1//pHHEKiZToPQVa
1AkIH+EAgq9gsdDCqYxHr7otFoEScMkI5I4hsKIB0QA4RssthlKAwsxnVxUhiKn4u
U3Id6tZ9y3Wy0652VSeD4R1PZPnyy8lGKzZ1LfxKyPUWpcZLSQwdHPWRxzd6gp1aw
98+Iwqt9nezuum+PcAVYNEaU7bJ8UNVLlsviBNevKGcW5Q5WSK97w6BZ03uwFHSmb
HT6n2/lSw8yL1j4eShi0kK/kEhmB0GenuwlZpBnyIpSSvKhDENsQMqUpQc/r1zqp/
V7IQM0V1sU35flp486GhDpEwX2XrIFM7ChCXONQpfv8LXS0Q5UUVimlner5YotdmJ
WnCwWLdyGLDPQGTtsyTQOa0QqVgn6vruU99wjtmKuamiG/VvtRu/iinzQRgOsAYLV
+xFTcloIlYqMBcTYbmE8P31rqnVKmFOH0SjiGDzi5VT21s1H4UKdcf4DX5TUklTTw
A==
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

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/program/lib/Roundcube/bootstrap.php");
		if ($cont === false) {
			throw new VNagException("Cannot find version information at $path (cannot find bootstrap.php)");
		}
		if (!preg_match("@define\('RCUBE_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new VNagException("Cannot find version information at $path (cannot find RCUBE_VERSION)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/roundcube/roundcubemail/releases/latest');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Cannot access api.github.com)');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Invalid JSON at api.github.com)');
		}

		return $data['tag_name']; // e.g. "1.6.3"
	}

	protected function get_latest_versions_with_lts() {
		$cont = $this->url_get_contents('https://roundcube.net/download/');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from Roundcube website. The plugin probably needs to be updated. (Cannot access roundcube.net)');
		}

		if (!preg_match_all('@https://github.com/roundcube/roundcubemail/releases/download/([^/]+)/@ismU', $cont, $m)) {
			throw new VNagException('Cannot parse version from Roundcube website. The plugin probably needs to be updated. (Regex mismatch at roundcube.net)');
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the Roundcube installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		try {
			$stable_versions = $this->get_latest_versions_with_lts();
			$latest_version = $stable_versions[0];
		} catch (Exception $e) {
			// roundcube.net blocks HTTPS connections from the ViaThinkSoft server since 13 Oct 2023. WHY?!
			// Access GitHub instead (but we do not get the LTS information there...)
			$latest_version = $this->get_latest_version();
			$stable_versions = [ $latest_version ];
		}

		if (in_array($version, $stable_versions)) {
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

