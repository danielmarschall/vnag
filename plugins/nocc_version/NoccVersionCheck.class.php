<?php /* <ViaThinkSoftSignature>
YhhRwJvSCq9ZIMij3FMlw3RoGYWMmIotnzcKX1mPvLuhjGWoOGYHieG8Ob06sUjAY
duZGeaepTnUTG/iNuXbiO4Ae0TCrsrZrcwsC53XZ6xch+MbLVPmwS7RayUsm4PoEt
exzZBXDzoxu9Yk0lvX9FUC8UORJCvx9TO98uSxVZREWGQMYFAL/64lqm4i/PxOUWL
7cQt6Ue3W9PtjV7liTUSlQlPaMiu3crAHF5MZxGLGkiuk6BsObutf6foZy5f0n2mU
d6Ouyq+eev4MNf/StbnpdiNKv+TJtHcUF6naPCArJGcTFkKFQ31UpaTljIdE2+qmo
vb44zqNvcQ+V5xir2/5FK87yvDFtumrn6RC1BV7skDLmxOIHlcCnOLAv5vMnLOqP+
PhDnLi7iACs4Kpbnuok45lHpmFyjewMZ/IoOo/1qX4Mxh3yIB9SUe9EdLtx0viegY
V+p+154+EAQRoZVt2n2/F11tkqIBRPnFKxZtx8zPjyUtN5pNYOrTaCvAB5YG0gP1J
gV+A1QcHxufn8+Dkhe5vqxq1FHvB//YpqPgjhSO13yi+2PaSlrkcfqFHTvIjwhdRU
hJ6V8zamyCqkrXej68/vAo05q7oproUOW4C2wLUe+Ew1m+vdsBZNs3gktkRXewFyZ
NUd+OZl7yvRKSEDROGDnYjMFj7LDXEaaRK7ZHR3Z2I2BQeh1nf5gKveX1gVvLYgKu
KlfrNnFZLgr4HLalxRRcucFkfWLLs97qWoNV+/qiRnSDNXESVGNF1lZA2O9uhiAuD
BSMLmWMD1wfBIsscG0LbwZBBk/jvqHN9Z/ZKNNqp0a9EpPKuN5KM6Kae0qy5y9lci
4O0Ifu95eA7T1XQvrY+EdqMUJ1U3XM+x3ut/S6LtwBTA9k5SsmuxwGA8rFqIvMABq
oNzstw8PX1xsOy8sQEjEe27L4wAQlfkF5QlbT2xgwxxAM3pLCelzbA0MXfRIBRXTg
BW1ibsDJxiQktapIkc/yMYlXVveRe1YyGY+oPjoSfEPvFgxJgsDCnuP8bTY3BHDoT
Y10ThdjaiL26cQq/Y/mZ/en9L+IewGeLbC9c11U8N41IvywyQxuTLraqHN85qDGVO
ibIEzToi4HAJXorbHXkemfJrIPS7JrUTEwDUiO+Xrys+L9oOZl5EUHYUc+GebaMm3
nzcSkDjxGfC+44WnDvYDOdEMPtGGjH40fhhawlHO1K4+DlHNLbmBMBCoE5KaHkanN
jeLh5pvNkMrJWK/1dfr5moDftU/glkHwq2srUs7/8xZU5AqcbBWQSZNztT7p58UwZ
Kf6hlHOQYdY1dujII1sj2vGqKn1O+57twJpfJ/dEmEzDcii/SFml8zViWZHUG4A7d
g==
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

	protected function get_nocc_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/common.php");

		if (!preg_match('@\$conf\->nocc_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = @file_get_contents('http://nocc.sourceforge.net/download/?lang=en');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download the current NOCC version <strong>(.+)</strong>@ismU', $cont, $m)) {
			if (!preg_match('@/nocc\-(.+)\.tar\.gz"@ismU', $cont, $m)) {
				throw new Exception("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the NOCC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_nocc_version($system_dir);

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
