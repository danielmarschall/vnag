<?php /* <ViaThinkSoftSignature>
tCxWODVQjpp/mnpKKNqXZG1LyUti7FjIhQIkV0I7YHQb5pKovjP1jwQMLMrtdKt8j
fQEvKt1KGkeUcZfnQ1nU/7rXZ62zVgioXw35IodxQahtohyXErvCCAQ2e4Kh5UKMD
R5sC13rlgK2EGHwLJbLt6yQL2jc6sDuV3QyT/ggNTsGtOjSsQXHCSu0XywMEabcO0
ESELIgGC9w4T7nPbXFKx8c49j1ZFfJZeqFVnSpVXxj59Qpn1WS07fRndrC3hzFlX7
ds3q9Db/k74qPhzmvx9dZFFXLLhnP0l7GLVkmSBLiyO/wl7WBjIklL+ZTfFiyLMqb
B14YZfULwH2z/Y226IrcTt+fiz3muuNjLeIirfhlvv54pTn3a/8C0yp0qNGaINghX
LlWUxm7Jw0O5vLl991LHstIqZEr6uacFNF3pCyrHRTBuvUpBs1Wa+xLNQdRBfj9hd
7gJhVpYB1bJXFFx5XVAHmCGehPvQb36r2dnX2p9pD7qC4gmEZJgsYSomsc9STN064
dl5CKg5lDW8ZKew7D1rNKN47eoms92IWvHxkkgWYHGWCOKlZV2Ia5HtJYHsWjSz59
pEaJ8+LLA9hL9cRHQ/iMp9d6roihJrjPYm8dkvBHntSTSoX50XFUHiOsgANnauOf+
MuFhVFgdYyxPtTqcEhj9+cbP0G4C1CUgRZz9wSNc6n9qEOLYp4QbeD13wGic81BJo
tGI0p/eDhIewo4z4vPvDmquVisdvdZ/vIVXQcN9so9AwANqmaJaqhGyEFfh97uowD
S4SHOLcx195V42G5yo2x+nvkcDS0zewT832VG+mc96lrr0sWGNKndO42gtR0X54Q/
Ly3c8MgEK8mu11J+Yz7R0G/OkL2nPEjPSdKP/2lM7t8zyLevIAI8O4vMAeSu3iChQ
DAzvDB4ZL5FhhQkujATeEKj+zdz1AvY9AgLSLB/20fYnb0B+dEf4STeGOojsQ9Y62
+LwkLd4Qm9etkXElZMsP9lY9C3pTzDb7HBGvRgqgI3B5BOXV1SuBOEsdHKO2q+eQK
2oB9f6ILFjiY927T7E8qFjAEpwLyKs6NgEvmhcIJzpWl8MdW/N68BN18h3DErrj6F
FNOAQmncJioW66h5vkBukmw/Gxwmq4LhKyBrFH0GsJ4kJ46jtsVqQgWyClTODlcyl
KNlxvUVaqneVyEQJ57+QXrfZx0p3cOT8UiZD6TsngfXa4vu82ZDQi7p25LMwd0xfn
ToWOh0AdSXLS9QtL1dK3X7dJrwLSeFuVFTIGdvn5WuOmgon4QndNpYBxE9L2BJUL7
/kGYskQXghIAE2vr7oQGNSeEPGb7WAKb4+LMd6jpvVMDIK26tnL+eXz2TURMyE037
A==
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

	protected function get_mediawiki_version($path) {
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
	        $cont = @file_get_contents('https://www.mediawiki.org/wiki/Download/en');
		if (!$cont) {

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

		$version = $this->get_mediawiki_version($system_dir);

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
