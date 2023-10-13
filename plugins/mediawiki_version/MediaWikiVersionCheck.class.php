<?php /* <ViaThinkSoftSignature>
Lme5HcTC/7qI1KUKY012sAKAHRs+Xe2raUjKRYPkpBP0vUbn70cyw8Lql37CmVH4h
9eJ9Gs3tCYDZdslW42Rh/WNFVy4hMsyhLUzwvp8pks//pqoAgv3fVDit1x5z8NxIP
5EWxsAEOWp1ty2aNF/lD9aJHGjq+IZb7fMq5PLWoFMWQGukZovbI+dVBkhiA/2AMZ
r2xie9GESJlyAVfhDtQEiMFSpgpJvbd2SmzJoJg+3aLfpVj6Ro4HF/7KY+kjJxFR4
rDmK5lmyQqfuwUfG0cqOnQQjODi/LCFSOgF7hyboMT+wAztkMAHhiSWrWd2jgy9Mk
PV1L7ktcyoS+RwFGAJznRG4PGAb7kE4ygS7CMjbh/yGrerOhdsFgI0URBx6UE2Dgh
URlefSxPODIxt7ol39bN7IDJHjz+z1xPDKP9VgACuaNue/oZfrc7gyAjvWJrIahLe
/tPsrn0HURYLZflCSy7VtUKpMtu4RSAKOaM2DJRWR7XAC7LoQWQg672JtK4rHzMX1
cEKlCM28C+JRTd+FH5V3iLOoLjgGrGQr9coZPJ2r8s8pGNic1L/d2x5YUFWSwv74H
WJuMe8zQ8f541RKFZllU4OueISitWVUd1KYLHEpxa/ioDDXnpH3xD4ILDhrwng67B
1eabFPtNNHgahtvePAzRlmE+hOKZitOsEOI8bB2IbYAyYbluaj69b8JOWDpWVWiqW
n9g/bXhQu//7CY+X+9p6Lsm5mwtQp2syKe29MSuOYRT3eBRFDs0D1sBLREiivoX1v
1U6vDHHndtPq1UeaNB3UHxM3Wdmc0X3VDDcGn3qPnl6x4jQSB1Gp9QCIpYprNOLyd
RCVnPwy+qgfU97iEEpDxduJRSaQrmr6KximkE3GrFHxp3j8fwHGA/ryvNK9CRXII+
tetvf1f60+gEPsrmxziPZgVLGfko5lmugItSXFpS4xdyrtk/pUSHE3cG0Ke2yt25M
f8jGG0nQLLsaUA+6zbPBfMCP3niT1OzoHV4domlqak7KOpmqbjlJoEHrEptDQGnB4
It41TI37j/7qIfVAJkkpTAWFsOuLd5dwiPBp6JMF7apf/n8rnpXWNDAT8N0NeIGpN
B9lBll/jtnSBVn9hD6zQBt4MkP62gVevLfA54gRqjTAeb+JDM8E4aeQ60n0p3P7lO
PhOPQa2K13nyXaYxL0ogu634jdiN4B8aBlK8UUWFzEE5VFNTIg7tr7bEHd+MeadCt
YUKMq0372Bgp0B3cqfnTC+946xsJYnMUGnqM4A/haK6ttiIpf5w/0O9+uRIf8Fixr
5blWfXw2HvRuIz2uAoiu+Tk3luxH/SHazJ3FiXgV0TEEzo8/GPH8cHFlFnsKXJB7L
Q==
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

		$cont = @file_get_contents("$path/includes/Defines.php");
		if ($cont !== false) {
			if (preg_match('@define\\( \'MW_VERSION\', \'([0-9\\.]+)\' \\);@is', $cont, $m)) return $m[1];
		}

		$cont = @file_get_contents("$path/includes/DefaultSettings.php");
		if ($cont !== false) {
			if (preg_match('@\\$wgVersion = \'([0-9\\.]+)\';@is', $cont, $m)) return $m[1];
		}

		throw new VNagException("Cannot find version information at $path");
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
				throw new VNagException("Cannot access website with latest version");
			}
		}

	        if (!preg_match('@//releases\.wikimedia\.org/mediawiki/([^"]+)/mediawiki\-([^"]+)\.tar\.gz"@ismU', $cont, $m)) {
			throw new VNagException("Cannot find version information on the website");
		}

	        return $m[2];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the MediaWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
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
