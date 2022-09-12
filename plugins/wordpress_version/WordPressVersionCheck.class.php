<?php /* <ViaThinkSoftSignature>
a5D298rMx3/qA9ukmfMkxQf4wri7C1GCHanryxaFj+57d4OjKrBALddgP6VyXNkSm
DI8Y+4Y5Da256sr6AXw+VlL98Z+k8cVCtA4yqRJh+rD17SnkswDvQXeUbJuTzJahw
4BVhjzgmVsIE88FAA66gSzj8saBEfWMAymFrdxHn9CK4HZa+JBADLmLo9Oc8FlrnS
+OVQ9ctEeCRG+CWoF//2j35SfmN3ivnrvM6tuZ0ZiLOM5JRNSOpeiOpmrLqlqWVz0
LC6yZ28naCLVyeYf4lwhFXIK0W7KhE2YP3EaUr5Ekd/1sGpr9tQK3rQ+bjvheemTt
JFKTorIRqzdQt2BzymnyenkTVc9KV51mXSZvXJyDN3Er8YIge3CnTjbtw0HagZ0QE
4f2FHLYnID3ntwdPGf3kHbSo554CHkgSw2w52ieuCCkf6qNyRohAYfwdRFYar6l6x
/kJU1nOTdUVAwJLq9HoOvVSeEVM/DTo0CzJDzgjR9KPF7DoiOfHQPakBZnsp1papR
lJunUkkScFcFfMMp9P05lHThZP9tWTEYuSVyPtuKwrras1TrLohSwE092m67D/efb
qtTebeokxICDnhZ66ovDcGp69nC2eqB8M0K+Uh9il6zYlbaUDCVq0yCDT1VHXpxRj
y9ewpQidf3q5Ci8J/Wr67HerQQHKugmrbZlXOmzaB9zppGOhvzCuYCARQj5LPeVz3
Ep/NQ2M0XH3ustgEip+ybbeTJeYHCzbSlhRZr33Z1oJoMuHDI7g6x0kbc6G9t5fVG
GTKqZVhW8SLYzOTi+kwy80+JpFUxfy9743fZA5LhPVPNIkuIzX6NWLtsSD/geqHPf
OX2sdEK7lVTInaK4FVjnbMFn1Q3X4O6F22APlZcX3p3BHcaX3cYg5ZVre8Jt1cNGL
3JrNzwj15rnZ6KlyfSs6xR2Jr7JVuVvnjT9/aCFWwQ48haRe1j4jfBqNPKHV2wJfo
o1oJq4kPPQfQIJrbAMQlTR/l5z4VwLbT0CWKKu59SO1+l2y6gL4Qh2FXhYgnSIPx2
ig7qHr5NAYFLf/Msw0uqsCH6Xevdc030jvqRXr9l+OhdxM1XfnOPCDAudq/+16t7T
JZMF6XwsjQF+D5YUlU/Hz7fmShkiQ+S5fhIy9gfA1n0M4X2s4/bS6GP3edTPFfKpV
ZE1EKloQB5SwPXd3iGQRmGA6Lj8TPg7G20NVDBpQACKsHrxc3ULhP0MMnw3PdhHcN
6mbZkeRiJyJ7dPVwF+XB8Xmd03hZXRR6/xbcy3/3zW373IeFLZ63cU45EHJMM6B4l
plUjYSAqqVehnWDRpAsBCkqJi3z8Mqr9/88LcEXBZgDk4Pe+csbEQaL919EN+ux5n
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

class WordPressVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_wordpress_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local WordPress Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'wordpressPath', 'The local directory where WordPress installation is located.'));
	}

	protected function get_wordpress_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/wp-includes/version.php");

		if (!preg_match('@\$wp_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = @file_get_contents('https://wordpress.org/download/');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download WordPress ([0-9\.]+)@ism', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the WordPress installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_wordpress_version($system_dir);

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
