<?php /* <ViaThinkSoftSignature>
gr/1vRpjKy5z4D+dglj2jnNc9DfOawz0AiEfTwiL0IS9Z7dSQ6oUwrnCCN539UfAQ
6a5F8T06WRO6yhFOQQaiJApX5Yt9NTb/X0hZ6pxRQ0/kg22wvpS048sEkNMvmxGjh
vV+53l+YnB5EtyXCVQ/ZRp8RnFeB90L421X2C7iyGNjbye1XmXyODvAj4TW7x2Jnv
x4ZUnecWHrHtn7szS8Z7FlbeB8K3O3lUuxdohv0Mu/e7Lga9YBu3x3cOjz54bNzIg
GVbg++iiwHNYvejK/kDk/BhRpjm4lB5AKnfhI73V/Kd4gam/MYS6JTIlvLUbdHmWU
rzmwnRSh1sTsTBjGuosUeMWKIkqrNroNCgWTmm4sQWP5ae4pB9wN0iRNvH2qtxg69
OWVAbjvkz+ws5aGgmEq6vsly6XUn96TY7ZCJrFns9nxAZeoGp6IT2aYOvcc8B83TR
Kd7HVg6yuZTJEDz0Coemh3AmqmwPQQjTxfOHbaC7pdA/oktybmJGUi5CwjZWyPZRo
g873PMfjkFhYpumOp8wKCU+3CqAqpxC8VYGdrULGfza6qDkSoeXRNnb+WYsbwwPFm
82Rcg4Kax1/7cOtTntaT0AGxLHYqUJoUC3cUZDn2SpeWUNI2GLFwA2Jn0XGVc5KLy
u63DACjmQsJF6ANjYW/URZvoR+kyjzWen5CtkUZeuC1feZfe3r4/7d4d3IEYqx+vK
/RYZaREq4TGgidyqwU3tNT9sMW1GU5jyHpSZ4mrqQLgDkmW9Q7x0VA3Ib4wlY9SKs
fBAt1o/Ds6Ik97wIgYnqD0MsZEpAS4MZqdWgFd7ifZ/mRE38oiejClHQWM2HYRxN+
mdbHCCg9lHGyPdyW9GIrHpwlJMuM+BA5W1l5hMUkBr4pmLzdCto9shoPVztirKyde
LhxID/wsbcOKhDqfSE72gKaELsu+uBBXbdPsnQFnsv5ptkUVzbH8+E6TmR+t3D0E9
JAiebXJ4HRHZk2nLS71nPjEHPSvm73cHMs+vpD2zLW1NQmQQbBTNx8+T0nwuWB+qC
y2c8O3obKFE5rK/Wz0476WPDs4HH93FF13o1SZuTKI5ZPxDUvTJNaWsQmRJKQefJf
hPlhd20axsMZnkqql3Xko4KjWhhXbU8yDv44unynmh1rL/MskdY9oF6dnjxUc4BZ+
z2E1Wda7dPQ8h06qJDEPqdzUoOmRH1sHMBMvP8pZeJTCWN9yJwytBTV2OTj22Xs4j
dNtBUlo0fGhIoycI3dwtEN2xUQMRb6dhN5e1Cn2xnkSaHtn7XGqJVB2II7rVqvgXZ
+7+d64dwLF2qk9+zDrnx4q8lFBPmjZkGH6Brj1t8Hc99qKfu/30GHPJai8/QpjO+A
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

class NoccVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_nocc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local NOCC Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'noccPath', 'The local directory where your NOCC installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/common.php");
		if ($cont === false) {
			throw new VNagException("Cannot find version information at $path (common.php not found)");
		}

		if (!preg_match('@\$conf\->nocc_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new VNagException("Cannot find version information at $path (nocc_version configuration not found)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('http://nocc.sourceforge.net/download/?lang=en');
		if ($cont === false) {
			throw new VNagException("Cannot access website with latest version");
		}

		if (!preg_match('@Download the current NOCC version <strong>(.+)</strong>@ismU', $cont, $m)) {
			if (!preg_match('@/nocc\-(.+)\.tar\.gz"@ismU', $cont, $m)) {
				throw new VNagException("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the NOCC installation.");
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
