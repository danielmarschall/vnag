<?php /* <ViaThinkSoftSignature>
1nbCL4jEjMIUirsnswcLvLOyfe4r5lC8uW+VWf4gmxQJkjsT6mBL7UJ1qkLyozyix
7BLkSW+00lytdAut4LORacPuO8vKTgPsbwYktrAON26lo3mTFpcoOriwPATDavd5d
F69Ma5FM6vDtucdiOhBM+jRQB+91dOjFCg05lNNzk9ZPazZ6HQR4roZUShRZxjR5N
8+C/ZtAxPHspZnKtcLCxKgeBuRxaIbm12XLnB1ol8fuSiVOL208y2a2bV+Ne0+/K1
B1qguJuZ71OgpHcp62TqtoX7cdzMbwqNvLTzvBzWeanbrs2ULKTbFSI+SvYfY4Z7j
J0MMurcUuiupxGLPElQbW7PwS/Yu5onMm7HpOPp6HdVxlyyWDTyJymVxuY+ouYctd
QnlAdVlLgkC7melQvF038ANRJt6Z/0IbxOGPp0gyo7VLcG6P2tylAoqn4w8MoqbKj
6024IA+/HILQhpnMjnH37QoSp2z1v6yrzXc2P0HvY8zh0SbvLWvMJqUyYaYkyfvT+
XvcXDHspoFAC759RWvRokzXvNhHX87ocTxmr/RZ+nxHHPXXhgpgl/nNPnRT+NRQJ4
4MYoQUZOBRrW6QOmKC7851das0OhFiDdGQTHdWTjx49Q1GnBJc2PeXhQ822jfK4g6
uamXBhjme+E24saVl970VKdYzcpkzkEv1GHMfs8H0qv9mcTSFidLXxT/5MEfXJh+j
BBI6oiCidY7rIygR+OuuYZRZ4/PInSVHyGNyt2lzWON3OGy6tDzEU1/soHU0SvMnE
nDBAATL7qxKOsM/FA2y8mn/n9n7bTpbDkIzmOLaxK2zHExx6HJuusKoSrca9DCNwJ
ZKqsj1TbX5C9K84f1IJ1XHZ2chYnaK9gUk6x7gWUYn9UtCjwlgHHuXtuYxNQv/6Fa
ZA02zyuC9YOn3d2iisanSglVlSbTb9eLobRgZhs/xe6wBdKqQN61inuWyrSD/tIKD
k4FhYR3zvUK7hoeEeZbdHdV68oeakKiDSSUpDtvYMrQar9u1xbcoSpmq8PJM7jjex
i4Mix+Yn63IkY/Nfcj6abeOdVMX+7rkjsB+xe8AQC1PTHig7bqnJElw0jTJEP83A5
OrZt3p7lWDKzAlIPXgDWAsBOWoCjVKx8WWQsxcOdpUzqv1K4VApCsVgKmXoOWeRVb
3kwvJHGR9C0qkUKEmYcpCI1cIPo+UmR35Ox9/im48jIXgA0SDs3vegLVECvoz00Fr
7zRfkvOq/0mnDZN/nKceGogyCBPmImZ2PU6wyDztvkfxT7EbE12T3dWKiw1sXM9L/
hHR8FZxhV4x3ZxqeyRHjKv0itK+vEBpTvCQX6kUMPn0WUi7A9Mj76LHCqlPAAIi2N
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
			throw new Exception('Directory "'.$system_dir.'" not found.');
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

