<?php /* <ViaThinkSoftSignature>
FJRVnIdhB1D3LlEHa+sUsf8GetU/KYLo8RBGo0FxQ1JKQmjSNbvmLswrjaPoGodu+
5tylc9Oh0UGXodM+I4tEgInSIA7tCESSvMdRS3+I1rgX9xDdlMRoHuZwIFpMRxfcJ
hzDzGPf0NPpCfupiaxlHK8ZWkDshwNKbUalNQrONKddv1JjGV6e1tp9qJZH+kDopo
qJvZFkVcvbFNDYX7/GvgD8qZW77bJA4wPmLjTsqUIllnHdxso3byAPtwpLuDW3139
aFD8SUKHZV2J4clj/NmdJ7Fl1h1u435c/5IQbShKBFDXh68QHsPfxqStOjHUwWMx3
vwKH6yyBXidC2e5O5Vc4MNjayYbffXtGg8GEnQUEqHyDhR5mgxTEprx5KphfgNDxb
/Ba7hPa34ZacAkX6LNNjcCreE34Cz06g45hq4lPZm2ierAsljJOAAAU165mw5nUxR
aOcFLge8TIN4lzCmY4sVLCRm3cNZxUlU7GIcojgRIf5MbsYeFFGO+B1vMX0cWlwYR
3BK1EC4996Yw5av4UanvOOA7CAyQSY3Ype3yPeJuohjdJDmlt5498RtlLCEzt0+G6
246P6olAOXRMPrK2ul82d5/VeA+vPsiiQrPRFHdfkviQtDJcOXpGPf+Mry4UMevjn
jMLM+tPg1wFxqhdOIKuJDQfm9CLnAQqbq9nyXd8nNmZ++0yV9zQhW6WxPBWHebueh
5HliPdexsFU0PjC885qlQldZvwUHZUqDrSY6G7UnwCNUZeGIHlItxxAb2yyqeaSCK
xSrBlX29Pj0OSgG52+ceDAEMS718lLc9ujIibg3YmHmpQRHALINw5Ie/OlyN3R/qq
9Eg5NXyGgPXo425xJerxNdrzKsHNLd3zdYrSA7jIFaOp59G7aN9oBf+f1St+9MxZy
no6fNBQJRX54x36E3PyeyENjV2yY2Os97agvZADbpjpsdheoTYjEZT5FG+gdg722W
zNffSrbAR6VnldTHUK7LlevS6n+vc9mEZB+rRGXy+njqh0VmR+oJti4InshgDGrJF
RwxfdFxSYu6msy/TqO9h70Ixs5C/ALeC5jEEJmScRN1CLLiuwNMdyBEMnmDmU1I6a
Sg6hgIvBRCyDTOwokFSApy0Zq+25OhmD9p5bY3eyxKaACscuLuwpv1G/4zIGbSJWv
DU6/M5UBiKUl6ceDFKMs2Dtizi6ZnvbwYWi2t7fB6HaTPEtXSef52hFXpPgB/VNLw
zF/9mATFKWWhpFtaenVQP3UvAhj+Gvp8ZbNIGyBl48Ww8BYpJY5Lu+AJeMj4SFDX0
LoJ8f4BCol5mabN46lQZgupmMhxs1vQDWxRQtpXAabY/v/04DwOL2rXUmC8yMa8PS
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

class Net2FtpVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_net2ftp_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local net2ftp system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'net2ftpPath', 'The local directory where your net2ftp installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/settings.inc.php")) {
			throw new VNagException("Cannot find net2ftp settings file at $path");
		}

		$cont = @file_get_contents("$path/settings.inc.php");
		if ($cont === false) {
			throw new VNagException("Cannot determine version for system $path (settings.inc.php not found)");
		}
		if (!preg_match('@\\$net2ftp_settings\\["application_version"\\]\\s*=\\s*"([^"]+)";@ismU', $cont, $m)) {
			throw new VNagException("Cannot determine version for system $path (application_version setting not found)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://www.net2ftp.com/version.js');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from net2ftp website. The plugin probably needs to be updated.');
		}

		if (!preg_match("@var latest_stable_version\s*=\s*'(.+)';@ismU", $cont, $m)) {
			throw new VNagException('Cannot parse version from net2ftp website. The plugin probably needs to be updated.');
		} else {
			$latest_stable = $m[1];
		}

		if (!preg_match("@var latest_beta_version\s*=\s*'(.+)';@ismU", $cont, $m)) {
			throw new VNagException('Cannot parse version from net2ftp website. The plugin probably needs to be updated.');
		} else {
			$latest_beta = $m[1];
		}

		return array($latest_stable, $latest_beta);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the net2ftp installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		list($latest_stable, $latest_beta) = $this->get_latest_version();

		if ($version == $latest_stable) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
		} else if ($version == $latest_beta) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Beta version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			if ($latest_stable != $latest_beta) {
				$this->setHeadline("Version $version is outdated (Latest versions are: $latest_stable Stable or $latest_beta Beta) at $system_dir", true);
			} else {
				$this->setHeadline("Version $version is outdated (Latest version is $latest_stable) at $system_dir", true);
			}
		}
	}
}
