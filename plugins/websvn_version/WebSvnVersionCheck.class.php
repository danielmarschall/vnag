<?php /* <ViaThinkSoftSignature>
fqkUXo8Mk3LMsdasFOvKADgY4gxxGTLbE4cM7AU1uo+PpfVGfzGKeXsOUYoPivmg7
pYb4KCOneJ5ifN35ujcauLXaaAvthxv8staBS/hHck3SWFCMCoYB5Y+7FYQyAupS1
bqjrkwBWHhjB1RpzheGenurqG/Cbn6vB0XlC9uE2AN52b/kmctIKdsgKfy6ojPfm1
NMOfJ3x7O1Z4+NPla0Ga+ezSScZjARO9jMBYN76FdDpioaa2t1J69rN4Ce7rj8A0v
d0+n1hLlXuIC9PBH3hzVdDbBTdkWDTlIjwr1Hr8+dmyqU7s24ww+55GrAjKNHOr4N
lwyatuMn4bBCT/vsdGZLAsaeC60ug9gHfjGvqXfTykUmVgMJNs+zSy7d5HLivoFsL
lQG1ioDxRr59BFF+St2a+rDjWLlafd7IayZbvUrGwGMeX50r9vEIo7gd7YZk2yyh0
Y+pQ3fENCLkl71COn0aYSRgHWMwL+IZwYV6/MZTC48pKO2U+EYY1GB9OrSMnPTVed
lLS/rJaBi9MEJina93RfHif16A6mn2wy2HkUrFg0FCgXsTEUlDBGlowmYFqG87LCG
vQf6pC+AUCZYCN52nkrwwEJGEgcwRQLq8IHuxrKMI+4HjBuSbdD3qSIl4RZYsJRAL
l62kcZpaF3a9t3oC1kBEiahiLjh7qIkFcTPKJm0Bi1iNAYaApO4qJHHvhT7MuimKV
mtAKlbFThGBIs9qHCQi0VzFwOHjKgFE8rGtA8EQGhs2MD5KdxV1JY4yr9Uj2fcgoJ
7TCOIejx5loBNSi4kaCCN4B4bye3B6fdptCtss/szKjiv4hftWycMartrxCHIHcUZ
FXTYLmDhmzp2fu85CZZ8tqHD/2GECGdqSQRjuWSXpmikzUAUNXnGFTMewHGuzOtiK
Vjhap3cydy5FfeyTxQunwdtiNM/xD+z9s6XICNgM1ur5rt1yGmtA8jJnao0okIBF8
yEjfW2NfK1nTeI6pvmcmWC0G2kHfuZrVnT7kxkzSBd/KDUhbKvYbNkWGQAKNeqMH7
AFYui87Iu4Rf8A9Pvt4unFSzCCiJ0mJO1//re/0IUWCIh8wjLejwPIpYcKDXBngf0
EF1IIbS76kfZ//mDcvh9k5ViCEeMcSSPKwQ6jiOqI2u0bal7IWWhbmwakbEwmE0ZJ
EIumubK3UL2+RKwUNGJ33yrK05VtQmkpqqa4KHvj5QVvnCpSXJu84+dTpW0TMAmKq
6Sh8HDFZIpqiBPUCa3Tug//WNf5xsYLxSrVl9YqsMiGBsPIM4f4J79uB672Azs0lE
1vb4opB5kNRk+u5+EPkY7rddApLDMh55Fda+37CC0NpCXdoo/vwnwXi8Q+4dQxmsw
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

class WebSvnVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_websvn_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local WebSVN system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'websvnPath', 'The local directory where your WebSVN installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/include/version.php")) {
			throw new Exception("Cannot find WebSVN settings file at $path");
		}

		$cont = @file_get_contents("$path/include/version.php");

		if (!preg_match('@\\$version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[1]; // e.g. "2.8.1" or "2.8.1-DEV"
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/websvnphp/websvn/releases/latest');
		if ($cont === false) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. A');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. B');
		}

		return $data['tag_name']; // e.g. "2.8.1"
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the WebSVN installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$local_version = $this->get_local_version($system_dir);

		$latest_stable = $this->get_latest_version();

		// Note: version_compare() correctly assumes that 2.8.1 is higher than 2.8.1-DEV
		if (version_compare($local_version,$latest_stable,'>')) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $local_version (Latest stable version $latest_stable) at $system_dir", true);
		} else if (version_compare($local_version,$latest_stable,'=')) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $local_version (Latest stable version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $local_version is outdated (Latest version is $latest_stable) at $system_dir", true);
		}
	}
}
