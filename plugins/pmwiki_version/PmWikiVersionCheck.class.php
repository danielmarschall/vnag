<?php /* <ViaThinkSoftSignature>
bHBXiUbQ+0PJcOOwAjToPOSaMw/KmBJUBDG1ycVbkSZMrjz6WzZ8OLcdBd9EyaUKC
tXQoz0Os3/V8G2T6osN16+DLj7jUwBFdYCbCYt9j95eZ1eSKNNyZtAHtOEVKCdtBC
rOwsFHdiRrHe3tvz2SmFkHFrGT53RxbwpztVfXsdsf+Xz7zBvchiQvzNi7phFTBcv
WUgNsGG8FbSEAHatnJNeUr3VVkEkeWdTjvP8S6x8Wmh9KREPtIBUeOWPfOl46j1lR
VaYVRulJVp8hTxIpciRYyHvgm6MqZuDq+iAEkD4axNhtqbkd9TXQ0jmSnFxHGWhup
7mNMT0DPruaeb/yMKVJwVxq6Whdz2+i+N2lSfyZ1FX+4XL0PzWaMM7aQqtLqhSTBr
U7vVxX6HOjPVrnyckxPZv4rBcd+9fnZFGIbUIybirMymkBPznmG/hmtzPoepztSf2
eGehncIopPfhLUI0MQhzA+7aF6EJYwyv/3FrmbQf9HUKs+f+0vvxQBR+k1hAD3/Qe
zQ2BK/or4iNm2wF+eancLYOKQm8VsBrsNtnQKDXIQ3iDChs0nJghQDbrMjbVtZKEu
TFKqmQ7v8zPeVmwsiRAYUQ8COASUYLt4sQUlPRG6C3TGFwRdk1Pgq9Slb+uYhs/IK
ot9sI46O7XUDGM5AjvNW3zHWX/ySxSClRwN50JwL1TG+5VBwY5UaoNbFz3cLrRhEC
M3pNHtMVPZ011d6kUwttGzK4LF3Jopj46uw2lNB9TS82bsQBAbgy5If7IVZx1jsX2
S/5zl+JRGCDQbEphfK2PuS0cowbv9apcpEgdK2nWN2mEewx7k3y8/EGFxuiqqqgWE
5OvKpXr1uuAOhrrVHDQW5cs+jdhHDHYNixAV58Ut5qCjGPWKagLJsEYqlwkel1rIY
yE2lB9+bmqMdBJXcPOuA5067J2DhTBIPUvrRAt9B4EfwV+PYL73CgUVnpnXVEyKqe
zM5SlHnb/6W++xRCF3ySn7qr4StonzI5PBraQAT27zUKuTcLEUV4SUB+h9gGWaP7c
Ho2wJMgjZ7eG9twks/X60t/cdhCPJqbnmnAiYsDYuMNBz+LEH7uJw/qJh4B1qZfUB
TPRlpGSvoPjZyGiMXlpTgpTN/PP7zQBGM9P6HOwPlIRhhHaS1zoms7P+OsdPj1Fxv
4fpQ5MX7b8zHuqj+Wn+FGAqZWK4hJ82ry10k26t+pqzevjZTIpb5vfpHbAX+rBzr3
UBwdciatgG/DF1TWmCFMaebGfi7gwRSPJY9WMcUujF7DbL48P109SKtuHFGItj98M
PPOShotaIAcbEuSQjRvuJ78XctuCwqsi3dimAzRXaN/91YhTSP6+hy00OHdkpRZPc
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

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$c = @file_get_contents("$path/scripts/version.php");

		if (!preg_match('@\\$Version="pmwiki-(.+)";@is', $c, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = $this->url_get_contents('https://www.pmwiki.org/wiki/PmWiki/Download');
		if ($cont === false) {
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
