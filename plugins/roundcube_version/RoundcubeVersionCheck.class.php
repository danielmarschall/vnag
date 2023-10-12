<?php /* <ViaThinkSoftSignature>
L2cLimf5MKzRpuLa3QN3ZXwPjg1yIeFbWqIvkJGaSwRxKCg/LL/E2yEQHXu1mHH/8
kBIyH2DQ/9fSsSlcNOt4mJfZQ6Ij6999+3AYLR/IGbDTfug1+zvwYwHx4zRSQS2fo
PSHsUL6lAp2G7s+PhLAZywm+vlz1AGHQmXrmPkD8WVlxX9dNpeyGmVpQh+I8+LcRf
WDdLjeW3MphaOWSh6/MQxG60BZEJHjDpGcPiJm38FRBq0l5nH/2pEV0XkRwOsKHbx
hHLc5scufol/6b5GYAG0aiKrbml4mSO41J8r3k0eVdeeXpYJzUR2yDYacOxas/HKZ
+7G7DsgXpY31ELTHIf52h9PIils+DYM2ZG5oGYn85jhqLGAKgd2ZHSTw7ZZ1AZkwY
g+7dZmRZ6Z0QgFTA0eOTpQx61KWxQTFfhEqchtrmeL1xhOG3KPVaNfDJFIqeZV8HJ
5spNP0rer2gaB9PkbfWAz9yM7hXV7qCSkv1vKD1iwOG+JjeYLbEhHPntx8Hgoxx6K
VEg+F0WUbK0nUYuUMso3alMciLB0igIF+Uf4KmJmpSEKIFezbg2A54ADOf22pnjHJ
rOcmKWOlbfNKFeTqqvadj0KYrYWzCfZkGhFc9xwJs+jh48mUAb+LtQOEejJH7l1cn
2jnqLJgd0yvnSRc+wTSEWuoFLqA0MiDcfmY8NbtFuXMGiisFk30NSDgHDxbsLjcR1
pSr0w6iuD6fLa0f07B8DgH0YnZSIP6tJazL7Ho32eXUqeHkryg1b9fzzZmaD0/ISh
bi5OXffJGXog1qvhF9Ga6nQ4qwYtHthOtQh9hgg9VhRtNN9XoHIE//JZWvUCVNhVz
00g8GOCAiYvobxndpXy9N6D7mA4B0SBkhPuW0UXLOC9MazQIXV9Sqx5kkF3l46tZv
akC7lQyB15W/+vIiVScs3p7r2CC6vh6D8WZH/nBPyetbK8afXkOg0ZfZJznauyt+y
VvXpPBGJjA1UYAmLmcyjkMFY4j0qkY+sRmPTXAVPE1H9GbK2GSNlR2amB98SNxBit
fch507ub1jAAS+a8M3p69Hb9Mv9ljrQpanbabN01rFTuI2yToxTBqdPgUpejKnVpz
1XjeghVz6ODfDnpyzPhlHzgF2fLj3YZ1SiWVHq+Oeni3uUfSI60Ps0IBQb8MI0+Sj
nJ9w+9y7AuyruCuE0duli9wzNGJOqHYPLmOAxv780dnkFkFK3JUZowaJbpEJAauZ3
2kybuyOSwa+hYX4jonikPuQoIKKFZAOEMVUvJK4Rswq2H8jb3y28XHrisPm2f0thM
iAWxG+mm7Fam9DYtIRKzJNKNrYyB1NBMnHa3ev5bxrhCkma9Zj04re8tAZUNSj3Sy
A==
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

class RoundcubeVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_roundcube_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Roundcube Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'roundcubePath', 'The local directory where your Roundcube installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/program/lib/Roundcube/bootstrap.php");
		if (!preg_match("@define\('RCUBE_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://api.github.com/repos/roundcube/roundcubemail/releases/latest');
		if ($cont === false) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. A');
		}

		$data = @json_decode($cont, true);
		if ($data === false) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. B');
		}

		return $data['tag_name']; // e.g. "1.6.3"
	}

	protected function get_latest_versions_with_lts() {
		$cont = $this->url_get_contents('https://roundcube.net/download/');
		if ($cont === false) {
			throw new Exception('Cannot parse version from Roundcube website. The plugin probably needs to be updated. A');
		}

		if (!preg_match_all('@https://github.com/roundcube/roundcubemail/releases/download/([^/]+)/@ismU', $cont, $m)) {
			throw new Exception('Cannot parse version from Roundcube website. The plugin probably needs to be updated. B');
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the Roundcube installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		try {
			$stable_versions = $this->get_latest_versions_with_lts();
			$latest_version = $stable_versions[0];
		} catch (Exception $e) {
			// roundcube.net blocks HTTPS connections from the ViaThinkSoft server since 13 Oct 2023. WHY?!
			// Access GitHub instead (but we do not get the LTS information there...)
			$latest_version = $this->get_latest_version();
			$stable_versions = [ $latest_version ];
		}

		if (in_array($version, $stable_versions)) {
			if ($version === $latest_version) {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
			} else {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Old Stable / LTS version; latest version is $latest_version) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}

