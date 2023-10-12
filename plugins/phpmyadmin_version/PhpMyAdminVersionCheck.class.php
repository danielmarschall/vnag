<?php /* <ViaThinkSoftSignature>
zvqiPMHMNM4INK6Y/IBoEzPNOYtVgeI0+dmhSC5e5Uh72rP69jMsJe9VIffB7tFBM
pS4m/OTwpBhmpJkDSh+lGP8pF/pz/9YhEtCf5qbRJYeu+We6bEx8atpCH7k8TP3JN
4hs5M5QvAsJEsgrSXkTPJYZc9Uhe1GaWL3I71h/Vrk5zvytRuj9RGj5UVVQZU1kup
tdenDjw4zE3t2mqPH1Mj4ageNgJz8B0DQFxtFRnvr48DLAlPzT8LsmdtDkEqRKMFk
pZPP1nXc1rllYBBVmTVB9Cp1JJUFMMtaimHvwIWxvSa/3TSzA96X+/pcmZmLMVgjJ
NugoMMO3KBuVxDfKP3eBU467rvtYfHnGrCBkmyq2KnU57A27b7+eb0q3pxX3Tb5LS
XDYeEaALnYvsRbbYuGn+DtQIREjGDybbAz6sKR+beoUngCn0EDGJiD9tceGAdhwgP
B+aXaV0phYEjojqAauaUhbnW2Ozccnm2I91LYnsr9g7C5Q+8GB0EzRwbPgr5IoCah
mS9OvapVKeliaCVw/WLR1NYQDRMTyjf/gSTNC7Ns2D8tRpnz46IIPM1EBr3aJU+MB
V8AUNU6pCVyHMWkiH15pxlozW3WF4et0TLltdbGVlchE53ahsOb7JKmL3KelUIzmf
df7z8r5LB+ux/o0GSW/ylu1Bc8tniJD3EF9jSd1KT1dcQSMkKr49em983Xo/AwI0I
LA4f62fLYn90vtzU+0thoepA8RJ3URo7ruQxrX/Jl3viD6YmUNByQYmqb8VGvQ2kr
gti1aifQy/e8RS+prXMP+PzGQ15YW9/8Ue03CfAIAobZ9OT0QCiDBc2fxmHmOEGIt
oEpvvy/q+2njA5dVOLi3/CeVUPZ8jeEDa5/0LPU145gdKwNA9L6SU74ok6AE+BN4w
R0i4sY/2wCP9ycIfu3/XNWODVpJXRtJYoIG/RgH5k+6r613CSCrBFaIOETQQdrxcW
YQbiB+PHl0nn7m4QT7nQ4uIVpoBUVpl+lcZUC8zphPSx0pt2ks08qkpvJqsf+Ij1h
cv1BFYVuMc2a5LdGJAG4/BquLuDjq55Vqr2t4w4c43zl6VgXnP5Rpl4OWkqbilRaN
jdp/ZH7vaiWk6jrAjUWMbT3UWpy4J49VI0p4CWP4Mv7SDQt3U85d1uJrEvKds1MRD
v+aXp7GwIcAkTVx+sgKCf3CGrXGzBwkB6eGDSk+bg+AMPJtEag/nsdgQEcZRfIgF9
g5sgyJlo3Wze02/D0YXkCR/WtmYmRM17hxAU1RagCCEV21fpqLdwl7VIhtmfFzBtj
q/qDiC/BExUUFgkUMbiKf7bBk+bPLjM/GT45eg+BPipUZWB5wk9H4D7hxWAdNNkvU
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

class PhpMyAdminVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_phpmyadmin_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local phpMyAdmin system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'phpmyadminPath', 'The local directory where your phpMyAdmin installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (file_exists($file = "$path/libraries/classes/Version.php")) { // Variant 3 (5.1.0+)
			$regex = '@VERSION = \'(.*)\'@ismU';
		} else if (file_exists($file = "$path/libraries/classes/Config.php")) { // Variant 2 (5.0.x)
			$regex = '@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU';
		} else if (file_exists($file = "$path/libraries/Config.class.php")) { // Variant 1 (very old)
			$regex = '@\$this->set\(\'PMA_VERSION\', \'(.*)\'\);@ismU';
		} else {
			throw new Exception("Cannot find the phpMyAdmin version information at $path");
		}

		$cont = file_get_contents($file);
		if (!preg_match($regex, $cont, $m)) {
			throw new Exception("Cannot parse the phpMyAdmin version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://www.phpmyadmin.net/home_page/version.json'); // alternatively version.php
		if ($cont === false) {
			throw new Exception('Cannot parse version from phpMyAdmin website. The plugin probably needs to be updated. A');
		}

		$json = @json_decode($cont, true);
		if ($json === false) {
			throw new Exception('Cannot parse version from phpMyAdmin website. The plugin probably needs to be updated. B');
		}
		return $json['version'];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the phpMyAdmin installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		$latest_version = $this->get_latest_version();

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			if ($version == $latest_version) {
				$this->setHeadline("Version $version (Latest version) at $system_dir", true);
			} else {
				$this->setHeadline("Version $version (Latest version $latest_version) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
