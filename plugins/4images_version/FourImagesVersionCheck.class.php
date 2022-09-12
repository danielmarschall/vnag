<?php /* <ViaThinkSoftSignature>
lAkMAIwL5Eb71IHJLCJVhuS1bw1OUs8gYlJMpZhoPHf6dARxLpWHATIIiIzdP9qsX
LYl24sYg3jpuSUCwTyJlJp3BZn9D3pJPQrU7AI02eBd/9h5Mz7nXxExIfOUKamtYC
RS82nKHZhKO8j24sVfQclA0I6zWEOzBMmpRoL3r2RkTNJl9EtbgGS85UnRVFOkx68
V0P5mCGIoqbrvdQgGKxsk4b8T3+x9M7L7NZTNimQQgSQ3KfehELyVPxAPJUs+LGBn
+hTdAZWG7I++aiBGpzB6kWZ4WRdYn8Sd4OhIiiS3hRxKOUhxJWGLjbtBRLVR0GFUw
UihDbY4jnts6vx+wFzd4/DGi1jlIyJZIKENgPsOnoz3mizSlFJOxKYTBzL5CFTes+
AdpF4jbh+i+r3elB6llvUfTUxji7VxFPUNlgr3A+OfNU07SNh532Hxw1TLA29zUaL
n2ng2h23TW06qlVW+D+7x7yU+7UieBvpt+m8kX3a9Y8O44ZOenpxYthw/6htw2RqJ
0yhzLDfklZSs4cDgY58PqTmxRYxpuzN9A94ef+Gw3D5subQY8VXmNouPPsYAopJLQ
KA66wCvI0ceg3qbID6dIFZ32XlLLcIV6vAtc5hFhJh+uWbuP0r3t3IBfxmCDCbqVT
rX8aCG4KoDujMXdDNdgL3LWcWdbyiiADo/CNIOtYlNv8ePhVmX6dpGToOfhEoGVCO
MzuaQGt3jQ6W3lDyaMqxQuPSREdhGFWLiolTfxECayaLWcy8hQ8AQxMA55d9V3x6K
FZUycEjLe1HoASe7dXPVsMCg+iuw8/A1BDZQXSulNq86NVOrnQrXmJFUrBd+Wy7mk
MeFl8Leckma3kSh2IYgcuhUn67vE1bXaLHEs1PYvn91LPoROKodDYhyYSuHDHTgMe
/B4nCqwkL3oUGlNsMcvXchwTFy6PWEMuw2+O87MIPlbNRdS6PVwIvRu5+SXDMYxgi
X+QyexhefP8HIcDzHdtK8Q9ATLfrhsSNhyYHnFG2Q1E2a8eR0Gg9IUYgUjaCnp0WP
o1tvBqQoNxuM8FwtJEaDLLJYGI8SM7pRj1bNtekOoNrC2EWmATpXpawSEhVD/4FGh
tsfXhp3uGM/7mtnQgBS6N2rBN1zJ0qTcT1RxTmU9CcY2odrmAcI7cRIoBphdkaE8N
v5IA5ODOpT9F/ZMmo38FPrKLSNGbcVWIjhBht3tq8EVK3+sO0CjUgnBX46vVkQtzZ
3coIm9KhY6VPW8//Wugby2nG3hOFqMEPYVfCfUNj+7Fsw0gWTnhvpZITKjW+hjccC
64d8LOmGGCYpRSn89mItof6I0EDvXR99Sd6eMEuQGLyijFcBbWQePuiwda1sDNf9U
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
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_4images_version($system_dir);

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

