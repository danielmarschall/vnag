<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

class NextCloudVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_nextcloud_version');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Nextcloud system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'nextCloudPath', 'The local directory where your Nextcloud installation is located.'));
	}

	protected function get_versions($local_path) {
		$local_path = realpath($local_path) === false ? $local_path : realpath($local_path);

		if (!file_exists($local_path . '/version.php')) {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'".');
		}

		// We don't include version.php because it would be a security vulnerability
		// code injection if somebody controls version.php
		$cont = @file_get_contents($local_path . '/version.php');
		if ($cont === false) {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing file version.php.');
		}
		if (preg_match('@\\$(OC_Version)\\s*=\\s*array\\(\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*\\)\\s*;@ismU', $cont, $m)) {
			$OC_Version = array($m[2],$m[3],$m[4],$m[5]);
		} else {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Version".');
		}
		if (preg_match('@\\$(OC_VersionString)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_VersionString = $m[3];
		} else {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_VersionString".');
		}
		if (preg_match('@\\$(OC_Edition)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Edition = $m[3];
		} else {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Edition".');
		}
		if (preg_match('@\\$(OC_Channel)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Channel = $m[3];
		} else {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Channel".');
		}
		if (preg_match('@\\$(OC_Build)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Build = $m[3];
		} else {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Build".');
		}
		if (preg_match('@\\$(vendor)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$vendor = $m[3];
			if ($vendor != 'nextcloud') {
				throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". It is "'.$vendor.'".');
			}
		} else {
			throw new VNagException('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "vendor".');
		}

		$baseUrl = 'https://updates.nextcloud.org/updater_server/';

		// More information about the paramters, see https://github.com/nextcloud/updater_server/blob/master/src/Request.php
		$php_version = explode('.', PHP_VERSION);
		$update_url = $baseUrl . '?version='.
		              implode('x', $OC_Version).'x'.
		              'x'. // installationMtime
		              'x'. // lastCheck
		              $OC_Channel.'x'.
		              $OC_Edition.'x'.
		              urlencode($OC_Build).'x'.
		              $php_version[0].'x'.
		              $php_version[1].'x'.
		              intval($php_version[2]); // Last part could be something like "28-2+0~20210604.85+debian9~1.gbp219f11"

		$cont = $this->url_get_contents($update_url);
		if ($cont === false) {
			throw new VNagException('Could not determinate current Nextcloud version in "'.$local_path.'". (Cannot access '.$update_url.')');
		}

		if ($cont === '') {
			return array($OC_VersionString, $OC_VersionString, $OC_Channel);
		} else {
			$xml = simplexml_load_string($cont);
			if ($xml === false) {
				throw new VNagException('Could not determinate current Nextcloud version in "'.$local_path.'". (Invalid XML downloaded from update server)');
			}
			$new_ver = (string)$xml->version;
			return array($OC_VersionString, $new_ver, $OC_Channel);
		}
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the Nextcloud installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		list($cur_ver, $new_ver, $channel) = $this->get_versions($system_dir);

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is the latest available version for your Nextcloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
