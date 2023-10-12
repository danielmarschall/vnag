<?php /* <ViaThinkSoftSignature>
KLQMkPW4o7zx674hFhk10B/LYxV/kdtFQre2WriSVPxQi/olKlHbxrJorvH9dIWfc
tyDGq8bXKHpHGne0KXEfL2/VqgFqZfgKIBtt+RptLxMZ7JtJmhn32YBC3yYMJSanD
PHlvnyzv+9jj6De5gjfeVjJEpDrz4Vji3s3unZG5sRuF1WCtMW+f5OpxC+IzfLgDZ
lGbrhXGxObTb8/ho0KkcCBYg8v1A5emxWhNxbnvLCHwIwTzxbFcop9u88hlJGoMec
j5BJ98cyg16WYVnie67NQ+MIZqUHILN6TLG08PetoGuYEIwfjtRr5a2Vc8lFztcpF
OfCJlyQL9Zg+yhfF3K/t3ZEIxoRmxMVxPceI7FGff1Rpr2Fj/wQtiuO0s/9S/IyRv
3S0Z64y3BOlAamSwiT00Vu0m9fZ6tS05cEoxKmiVBbuDlZoa+KMfxvl9bzWLr0E9T
DB/Ib90JoP9+/5Vw62g1PY2SSg8SikdvLPUFKfBa/etyZiwOaJ8TKUz0coFIS4bOA
umUITN1vmZoVB5DHYa+eKZIJ+YY6Nsp4L675QdHxSKi2XdEi+Vrlq3ndt14mIs1Ai
f1tctDt4+YrPo/0JuATApm6201jaL45sEaDlNi2/6Km1v2m1im+lUY6VrDlMzyV9W
LSLqlKAKMdnOPkVcHkYtD3DS92+bkS46dMuZtFdG3Q0Ia1eyb30QO35KQqFo++H/c
3Yjx4GXUo9MJHgIoh0JyBCyaDtlo3PposuG3soS2c+37q27CtDfx8zLWvTQeqI69e
8rPyZ8HMatXF3750QNCcODVD3mvTJJuXdJaiLr6IGgWaSinuiqJZaMXSSE8eMWea3
/69ViMld2tkEuBeZAE2Cay+fHFMK6+RSeCOGO1xUTBlBDFHbRJlXAl81EVfCuBzqu
5o/a3Y+CgARGsmIjMNutFlYgdOFSZKKIRxUuGBCJqQ6H0xKqtsrRJUAmFpMop2HCh
+P5WD7iIUgP9bgoI7gMJqTlCOqqCT/upxUkx9pRDEGcy59JLn+3Yb9gV0ffG5OQH7
waSmvKz3vIyVHowCxeU9ZZju2ou5rMpip6Q5gjNO0tDpagH7Sj8oeYcRuk2KJOZ9m
KSkThtscqVbjZLS4UYUpvioA/WQ4ad+k7LZOTs4LfDPXDsu5e1lKaO8j3dBWgr+Th
YZnRIravq6mNmfjmcJD/S9BpY4fqsjZRpT3Rf0GGkZX5dtPxKlzILu3A/5PlCJTNz
ja5LZxO0P5sJBnx2YLmaNYxWTJILhR0VtPJejYazXjYhYX5iMX4f+pvALwm+gcBIV
U9sNdc9z0cPxjiIWwPEXSRZZx3BwGc+YYDeQio9HxgvEOlBuCaU8TjPFPcfQFYIsy
g==
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

class MediaWikiVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_mediawiki_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local MediaWiki system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'mediawikiPath', 'The local directory where MediaWiki installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$c = @file_get_contents("$path/includes/Defines.php");
		if (!preg_match('@define\\( \'MW_VERSION\', \'([0-9\\.]+)\' \\);@is', $c, $m)) {
			$c = @file_get_contents("$path/includes/DefaultSettings.php");
			if (!preg_match('@\\$wgVersion = \'([0-9\\.]+)\';@is', $c, $m)) {
				throw new Exception("Cannot find version information at $path");
			}
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = $this->url_get_contents('https://www.mediawiki.org/wiki/Download/en');
		if ($cont === false) {

			// The server replies to some older versions of PHP: 426 Upgrade Required
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://www.mediawiki.org/wiki/Download/en");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
			$cont = @curl_exec( $ch );
			curl_close($ch);

			if (!$cont) {
				throw new Exception("Cannot access website with latest version");
			}
		}

	        if (!preg_match('@//releases\.wikimedia\.org/mediawiki/([^"]+)/mediawiki\-([^"]+)\.tar\.gz"@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[2];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the MediaWiki installation.");
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
