<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

class GitLabVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_gitlab_version');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local GitLab system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'gitLabPath', 'The local directory where your GitLab installation (version-manifest.txt) is located.'));
	}

	protected function getInstalledVersion($dir) {
		$version_manifest = $dir.'/version-manifest.json';

		if (!file_exists($version_manifest)) {
			throw new VNagException('This is not a valid GitLab installation in "'.$dir.'" (version-manifest.json is missing).');
		}

		$cont = @file_get_contents($version_manifest);
		if ($cont === false) {
			throw new VNagException('Cannot read version-manifest.json from GitLab installation in "'.$dir.'".');
		}
		$json = @json_decode($cont,true);
		if ($json === false) {
			throw new VNagException('This is not a valid GitLab installation in "'.$dir.'" (version-manifest.json has invalid JSON data).');
		}

		return $json['build_version'];
	}

	protected function getServerResponse($version) {
		$opts = array(
		  'http'=>array(
		    'method'=>"GET",
		    'header'=>"Referer: http://example.org/\r\n" // Important!!!
		  )
		);
		$context = stream_context_create($opts);
		$url = "https://version.gitlab.com/check.svg?gitlab_info=".
		       urlencode(base64_encode('{"version":"'.$version.'"}'));
		$cont = $this->url_get_contents($url, 1*60*60, $context);

		if ($cont === false) {
			throw new VNagException('Cannot query version.gitlab.com for version check (Version '.$version.')');
		}

		if (!preg_match('@>([^<]+)</text>@ismU', $cont, $m)) {
			throw new VNagException('Server version.gitlab.com sent an unexpected reply (Version '.$version.')');
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the GitLab installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$cur_ver = $this->getInstalledVersion($system_dir);
		$status = $this->getServerResponse($cur_ver);

		if ($status == 'up-to-date') {
			$this->setStatus(VNag::STATUS_OK);
		} else if ($status == 'update available') {
			$this->setStatus(VNag::STATUS_WARNING);
		} else if ($status == 'update asap') {
			$this->setStatus(VNag::STATUS_CRITICAL);
		} else {
			$this->setStatus(VNag::STATUS_UNKNOWN);
		}
		$this->setHeadline("GitLab currently installed version $cur_ver [$status] at $system_dir", true);
	}
}
