<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2024-04-29
 */

declare(ticks=1);

class SmfVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_smf_version');
		$this->getHelpManager()->setVersion('2024-04-29');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local SimpleMachinesForum system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'smfPath', 'The local directory where your SimpleMachinesForum installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/index.php");
		if (($cont === false) ||
		   !preg_match("@define\('SMF_VERSION', '(.+)'\);@ismU", $cont, $m))
		{
			throw new VNagException('Could not determinate current SimpleMachinesForum version in "'.$path.'".');
		}

		return $version=$m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the SimpleMachinesForum installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}


		$versionCheckUrl = "https://www.simplemachines.org/smf/current-version.js";
		$cont = $this->url_get_contents($versionCheckUrl);
		if (($cont === false) ||
		    !preg_match('@window.smfVersion = "SMF (.+)";@ismU', $cont, $m))
		{
			throw new VNagException('Could not determinate latest SimpleMachinesForum version');
		}
		$latest_version = $m[1];

		$this_version = $this->get_local_version($system_dir);

		if (version_compare($this_version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $this_version (Latest version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $this_version (Latest version: $latest_version) at $system_dir", true);
		}

	}
}
