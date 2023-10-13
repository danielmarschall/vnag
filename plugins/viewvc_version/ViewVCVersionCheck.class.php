<?php /* <ViaThinkSoftSignature>
TDF5pajkBmVclzZlc0Mqz3y4QzLmlLQH7/1OwuO0mDvrBIICenmgyTBAOxMXYrdCC
F36A/6YxsrSNws2EgNU4FWm1LZuMeo4vRbT7Vz8tgxnaBmc2uxBW84QChRFIF6VSO
TYG2ebNkcbUuYaCm36ryk5zyygeDzwop4msrd8yrQ3HqLW2/Xas3Rjs/1hBOxxwSh
bsZ6deXEAO71BCxA8QsmHjJbMH9x0iaiF+7wbvXeFL2iepw3tpSE9n2HA6rGfd3xn
gZtzMDoN8RMUfBbbx0txj9S26rgnQsmwHcYz+D+TNO/0CMplLNpZJgXyE5W2RaR5M
JX4uF4859t26Btc4A1muXCydMlWUHSjDURpagoOPCGuK7SyiD5Iucly2Z+JCkbiFW
hR84YmPvCKo4rIIA0M2NnNEtSxngRvB39Cnu86e0MSvFPUFfrSkU/94nv92pKw053
crPDsmAPSkeqkRPHM/muuNhWHWFpxxM9RuLZHVNAovQDh+rHzLGn7qmgzNsJ/f2+H
Z/2Xo5b2VjyfB8pQZQIXukA4Ikrtl1R4ah0z3fyeufuMiVcXBUOIdmtBIle5SlrsH
Eq8hpVL/qvxjhNgtD/tXNGKn9ib43FAnknE+mJi1hUDCduvOVCd4IaZgCvIbIhSEt
Q8Xx5nw4EWNl7AOYEYh1jzOn04/lZCFmjM0EyufrMj93Um6WPyXvROOwH6GHEKaSo
cavkpMYBsrb1YFHv0+RF7Z7PoGQR6XEM/3XKH+jTsVVQaVI8DoC/JFY6WPACcq9Y4
ykCW3b0MioLKXs/rU7NYEcJ360j20XnxXgR/GrMCLWwCdXJTDIOK0BdGiwpN6i2m2
n1zEAp0Kn/lKeKHf6GY0g2kT6oXBXuflm+rfDBoSU0hd0Ux0dYCSlcqg2GdfpcDfo
e99plg1NahSqUA4DWTz0ChlBv6GTtDhJ0QWaq+dQvUuW9bhoOPi/FNwe1xv3OuBf+
opz6e51fFEpaeHG5Y+Qa9D7CrHk0z88QJWXCgCdWNboGwNaJ7pCleL9AOz5RR53Y2
Fk9neknuhFv/PUfePhQZd8mhxBgbzlRpN1v8enDY4bY+A695Vmz6Lx3XWvb8os1aO
CIkperWfF336r+lj3fDGDHnlqXYVrOKgiBSFBqtQZ8l4xmcxE/On8el2B95LTY4hE
D4KKRPTBqanx75Ajgv0o5FVHtNripTFA2PXOv9NaEjqkvckBCHnLOcUUSfT4GCqe/
EZwmgNGc8eQ6Jf09Lr4B7ppmUFD/mEx/wsaQrVwFNclFtOhJi6Y1KDBqq/I9tmB0P
31b1a4OfVuclmipIyFphLOhAkKYD7l4kpuaJ2i4ij+OAfBikVcZr+sVliDrKQhWvG
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

class ViewVCVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_viewvc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local ViewVC system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'viewvcPath', 'The local directory where your ViewVC installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/lib/viewvc.py")) {
			throw new VNagException("Cannot find ViewVC settings file at $path");
		}

		$cont = @file_get_contents("$path/lib/viewvc.py");
		if ($cont === false) {
			throw new VNagException("Cannot determine version for system $path (cannot read viewvc.py)");
		}
		if (!preg_match('@__version__\\s*=\\s*([\'"])(.+)\\1@ismU', $cont, $m)) {
			throw new VNagException("Cannot determine version for system $path (cannot find version string)");
		}

		return $m[2]; // e.g. "1.3.0-dev"
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/viewvc/viewvc/releases/latest');
		if ($cont === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Cannot access api.github.com)');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new VNagException('Cannot parse version from GitHub API. The plugin probably needs to be updated. (Invalid JSON data downloaded from api.github.com)');
		}

		return $data['tag_name']; // e.g. "1.2.1"
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the ViewVC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$local_version = $this->get_local_version($system_dir);

		$latest_stable = $this->get_latest_version();

		// Note: version_compare() correctly assumes that 1.3.0-dev is higher than 1.3.0
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
