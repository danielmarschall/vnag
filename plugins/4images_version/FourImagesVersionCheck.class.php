<?php /* <ViaThinkSoftSignature>
muCA1BNAx3t4UMIIzqCeb9w0THNUjzz6c/qmRy6zLtsiwU3WE0nJFpHwi6R1B3jhr
ivN4x+RGN9wo2YCpenFDreN0+k4oHsxsqz68eiaiH2+1Z5bKXMayUNG97mFdbWxIx
At1VWBRG4GSXnAbkX3ubNhQw1e2WptqpT4SufS5KcGaaI/MxwRrBdjheyENnNahSF
njM4LHw6R2On+P5RCML9aReiYLECYhZPi/WlqOI7aj46Qfu5g29s6qxfKEc3HWGoX
kJi706o9G4j/nffo16EBrgpjLMQxO7z7nhsbbaucABnMuDu50ViFmJRMi6j0ys5SY
wrLuoa3VqGL73Gxy7agJgF/4+35Q4LArCyhIIg7xmtOmC+vKIDlJD9cmUpIBT3th9
Sxqzl5uMkoqZRpBzOtTIKFX/p4I6VbJSjroHVcCO5rUbwzXZDshdLdbULXTOR8/dJ
s3aqOPpPbMasblpVc6oRfePS1Wy1agYBl5kbZErJeYr2ZerhsAdwvkoXjM14Sk6Ol
9evRdSbdGDVK3x1zJSvoW4yE+tImN7zCnlqCc4AD2u8EWjDXCGfhkaAzbbqf8Geo6
TSJQm3YUJWfZFP0y7SYfyzeYt7ZVHyLcUNxowRsAfYndXn2700HqJP1pDtmWEVcnJ
pbie9BwDkasWbgCXypXcRAItnHDndYIyxxCTYfXamZIrhmQ40aJow1WYEpDzj1TI9
vmFTXh0ciY9/rvMATofiUOCgyn6k2BukNFND3oFr9M/FsPx6UFnmjprphQdQSvcIu
+VnrnQRRRCwvJRHqjvbZVjN8/aluTPWib5+ITMW0PdrfzXPgZbzLjpBFLg1nHtC7d
xFQ/8OyCibCe0+SY0nLyq/TFBbSBjaK96kXjOT8xp5aVyGiSrXm8wPh1WtlOaJhrj
5cNP0si/rd7vmLzyxC/eplqddVyfbKVNZRyjbcyyg1D5Wsg9uhp+sgnMSPad1O6yw
44rfbtnyT+imUWXoqU4h3B+h21BYIFnYqvJtA/UfloimomfvNxjqiwX0oHuYII3Dv
/b14JGXGaps/PaxRvQjxEHOvVZhU5Eus9piYihgQxhq6/B5A6d1Om7iZsyIkOvX+F
45WuazvoEc3gs3c6F8EmGY1i4vdSJ6ws9VueJSyyE0hPePGV7vJQ92EduP/j2Yna6
cOcNP0W26gNlTcQO2G0K4zm5ZBI5RhocRsTKHxm9XQIA2+KgjvpEupb5R+Sqt7NKP
DfMtFiTSoPkCaB1Qgc5JDdJqXlpOm4dlT9OCryAqMvcBJSDchNM+/u7gbk/P5zBZH
Gpn0BcCkX409jH7Z1l5rMKTwOkInPxD1XkUnXO55HMR2/m+TDlk3BnA6fgIau2eaj
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

	protected function get_4images_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/includes/constants.php");
		if (!preg_match("@define\('SCRIPT_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = @file_get_contents('https://www.4homepages.de/download-4images');
		if (!$cont) {
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
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_4images_version($system_dir);

		$latest_version = $this->get_latest_version();

		if ($version == $latest_version) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}

