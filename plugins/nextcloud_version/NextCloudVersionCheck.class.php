<?php /* <ViaThinkSoftSignature>
cMU48trGvkbMlzRpC9CObsZaTJQLSX+W3WA0s/ipwluSIf5e1oPV/8hsf/xQ8Jdoy
md+onDcvBYBvQRK/eb9NblY+OVHpQyBTmeFabsnWFzAL4cR6IDUh7Wi7Mv8z30tIu
bHIGsE1zP8M02Sm1Cy/LEAHrUSzTum4vCZ0Ur7IgWqVJyvmwyLS3yMUu2ozk1fii6
PjlciD5Oe+DiNsVERv5/YtirDychwrUz4lPOWnZQebZsMEmNHCWZbWree1WYCV4u1
b7JaROxuZRfghe3vxTigLrDG2emmKanw6+W9dfaQEfsQDF0kn2UNYquj+IjmRWkQN
bTLJzV2SJwr+ayyIWjJXuKTS+OtMI2PEFvqC1WdGC7HSvlE68fNXZcZzuS2dxUGeO
IiSfmD6eKfHs5JtPQrY910QgvqGFtd8Rdvfi9KUrXUP2YXuEiL/A3+vXoGg2eseEd
aDjte0ZFJratQXBR3tp/1+lXGwkRv+1016kqprH9jaqubbTz98waLllyxBjG19mZD
S85CFfL6kPiWInCdMSRsytG4h5T4w3CZsPTwnP0MOekO1j0eXncoeCMVr4AO5j6wl
4jdv4NAO0o2YJrmDaC6gV8N0aGlCArC1RRk49fExUFyHMOuO99ZY4gqyp04JhQzrX
9Bgj7sa3zsnrSAfCCgBf9o62OZUUypiuRNrfpj+DLYyaeIe2MUBhlFFOWEN1XLbev
5zvWLFJyfcw+b/iJSViKu8PTyHJgu5ZBCMhyJ2WD7AX+wwPKFjFI7f5CF+eZj/n4Q
LDzc7LvTjQ/01hIGNZNRvGsyJ1YvVkrfZrIpFmE+X77IQRhpRoQv0gyb0sutQ/bAF
qeQyb449/UZk8hXBxlYi6AxUQhFPx1jNCG8q2jCKDPkRo5BrHRsORNFKlQiQKHuWi
zrm26GiJRUhryn4AScMwWBXDYMLV5i+C5zYOnfI4SMQJurgPHr3lhELHTB6C6IeKL
s1kVHLbPqTCNr3dWK46gtGGoMwvjEDTcPyiEOxERZuYZ/BNxl6P+G0E+oNeBQPVbG
9e7vi7SQeZrEhqKn8gNQx5DQyOdP7PsBmHJG8th6Tr77YktIE+yVRR8rxj0tHvqrY
ppdYUl/rIpj1LyYS3MyOXneekAn/rnEOe0lGM5sIM4/pI4oK03snEY9ngD9X7xNm3
lUWz/1tWfJFYolMVQOdEfHU5rvavSJASlUihm9tcuIM+jAJDSF+UMR7sQ+n46VaaB
2u/aP50UMrtU1FVoooXeB6uSH/EGesTmYREA13UjmVz3gIB4QT9m/XVZ5zkRVMleM
b9sCS5/IGoiyhR3ge17fVPUmRsdI7maW5t+v5FxM45qv2t0hFGEfoBFY03oi+Mjdr
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

class NextCloudVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_nextcloud_version');
		$this->getHelpManager()->setVersion('1.0');
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
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'".');
		}

		// We don't include version.php because it would be a security vulnerability
		// code injection if somebody controls version.php
		$cont = file_get_contents($local_path . '/version.php');
		if (preg_match('@\\$(OC_Version)\\s*=\\s*array\\(\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*\\)\\s*;@ismU', $cont, $m)) {
			$OC_Version = array($m[2],$m[3],$m[4],$m[5]);
		} else {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Version".');
		}
		if (preg_match('@\\$(OC_VersionString)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_VersionString = $m[3];
		} else {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_VersionString".');
		}
		if (preg_match('@\\$(OC_Edition)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Edition = $m[3];
		} else {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Edition".');
		}
		if (preg_match('@\\$(OC_Channel)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Channel = $m[3];
		} else {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Channel".');
		}
		if (preg_match('@\\$(OC_Build)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Build = $m[3];
		} else {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "OC_Build".');
		}
		if (preg_match('@\\$(vendor)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$vendor = $m[3];
			if ($vendor != 'nextcloud') {
				throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". It is "'.$vendor.'".');
			}
		} else {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". Missing "vendor".');
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
			throw new Exception('Could not determinate current Nextcloud version in "'.$local_path.'". A');
		}

		if ($cont === '') {
			return array($OC_VersionString, $OC_VersionString, $OC_Channel);
		} else {
			$xml = simplexml_load_string($cont);
			if ($xml === false) {
				throw new Exception('Could not determinate current Nextcloud version in "'.$local_path.'". B');
			}
			$new_ver = (string)$xml->version;
			return array($OC_VersionString, $new_ver, $OC_Channel);
		}
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the Nextcloud installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
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
