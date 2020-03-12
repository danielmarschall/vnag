<?php /* <ViaThinkSoftSignature>
eqUk8Qv5K8hX8WxJgxbENCqVzJJoy0A0HTqnXHFhek/uPLrsQ46mh2QnL/42GCX6W
VdQVhaNjrzwxA7xM6zsLDktr6tadog/ayNq60usmt0BUXNxLeAa9x3D5FcUSHgnk0
MnyyqwXJGLUyIBJVoGLYV4EQoQleJXZVV61qReSjgWqKm7Dy+Uzsqfz/FT6LMjgjw
qAhr02Geht+Ya6WIV7eVNYl99CNM6yit0XCYPS+ategNlwRFvpNZOTV8DWhtTx1AP
khaJq2bqdPIIrdR39dv8uZ4BbMqlk0ShZ1xM9cTVPeOVtQKwocdsP/ANavzCUTvIR
9pWud+qGTE31Im5ZV1b9YU4KNv/3wOj4vOLLVA7FerrcL5UBAPTUZOOVGExW6FpoY
qnVMyNOJR3zx9yN9bWaSjbbbCNYnREA8L2UY6g8VIhPugVY6UmhHD1H9s7S2N7vtC
AIsKeWGWKEh755CRpadkqX64LpvYuEm2HeQmvFxtpcAN3mw5P6mqloPBtmHlDvJW2
S3CbXkxkJaYf/UuFFobug2zTv8m0a6+0SPlt9EpFNh8PCXXW4vkiDVUwSbG+D3/Pt
fZBI2MYhMTG0Lt8HwXKxhFsYySZ3gGq4Go22nM++Ywffyl0WY2R2Rr2LQehdRzyTL
aNUoWbmeaYEghpRBbTzXi+w3c1z8qd9hicP8sTWxWGfymlLm2x74NerKqgzMnvElV
n6vO2rR9SyI5pxJIPY6wMCBquNZh93edwn5qo2SGqZlsB4ADdg0FdRV4vTeoUQbPq
N4RrweV2LF65cO+IZzrXN/srhzWTtN9j7uXCAmDEsb0uWOIoawbSXSZeiNUI8vDCY
Q7ErXb7382yC2Lrjy7NVeL7fyQA1qRBDtuAbj8/uyqUXsdzPrbS2jFWZj5gewVTob
5f8z3k5pYS2EQKwFfc1K/wM4oo/ti+4pdtR9/vNba7KDoDe9gb0LjqeYERCwqMIU4
HCJID5vDXrA37MPT93u51Uy++rVpN8qxDYVCi7sZHZlMmkuowYEVsDWIf1gtFEA3p
wpJuCV/hkY5tjl3bFXOS9Ds9rQcTcuvN2DFCsMdjIfhgrFnP+Ikow32JUms1Ys2ZR
N+Jzlld4e8hkoljkCU2J5OEzdb6gHc/wWBr0myonkfKMCN9J6x/WnZFRVmYbQnt7w
HpfIC0kNm1HvGzryHtCaTI1cADPwpNN9Aq1AqDkXht+RRzOFYlM9kF/hVR3TMGoOW
Kda3sUa1oJ+hasSD1g3a7DeqZ71E3JqCOpgivU0sHOkcQ1GwCsNyyaINXo3DXDa3+
l9LUhOFixOuOYgTfMWiSVTt8x3m7M5EUtR6Jek6JVdkclyaPyeQOGN13LtMDzyqc1
Q==
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
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
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
