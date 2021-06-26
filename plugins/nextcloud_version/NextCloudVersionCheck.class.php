<?php /* <ViaThinkSoftSignature>
gX9e3ZsTr/5gCndjoXkh5Y65OgXPOPRob9TUwW2ssq+YdiQl3g/A8t35kb23dX6WN
fU0aZTP1S2DBLLaf0I6CiKrli99a9KsHjA7kH4rmjed5RFwqBoRbUxJTFvt4X0EkX
8k/sIUmdES8kVO+JTfIlUB4ZFjVlc89bTWQaeagXkE58f8Fhr+Up9GvQJ2eJDEUpv
Zfw7kHZ+odkkiK5L+aCP+Mwl+A044R/e8SnBMf3ng7PKwLRoGu9d2iRAEdAIUzZWz
RAxmnyVqsjtUUxSPrCp+3WOmzdR1hWLZm+BJvpGOTjuICKXxOatyIsBR76mh2ndri
PrD9y8NLdI/azsEiJWZgCJr8h1R2/SWF1ndiC5nshT2sZBAQLdSQ/zvcbIdHtnibq
63KEDcR/m3V657gVmT1UAAM7JtcTh2x9GDz5ZWAg7Vcb+q54s9A6NNEXaHOc3XMHq
aCGuf1EYZyaQeTiyPkgJHwtH4rawwu4K+keDm+loS/KQ9btuC0tDLcupwJZn9qREo
2YJ2IwKaNzRj7DNkIG7ASwqwcbWQSx2RqUWm9zG0D5NIx+Jc97A19v3p3HaplGH5K
+1anSj87rtxENA+n1L+8ZbvI4SZ0txLxS2sJwYr/0Pv2gWUxUdoVhmNPRWAKnQM8w
12TqndyF4ls5KamJemkz3RSiYLWH67sW1jBi+QYu/VGcf15vOmlHMQemf+TEly1sA
zn7BQsxGS5Wosux4fToyLNKNvXlaWiuo6Q1SACRTns9rKnX9atFrnydO1NHaA1WkA
KgEQolI7fL2ggz+6veuyAbHnpMvp26essnXApwC604HOFW7IB4lrnZCyk8UHtGixD
xy7bHKXV1jPGXkN/AUADY3Avq1/6N65GAAPNoHnKrlGVjDJyI7H/KMiTPASzJzJ0e
XFpmgWSHmQfPMNv0dFwjE8siQyF2FwGz4bYb4FbfLheUhEZRcGtZniAZMHT4zPAK4
+Mp61QsX/79a1NBg3gmPtk1cziK6UyhuD2BubUm3y4MXANDxZhe0mw0UxKQtW0XDL
yXBDmGL8D3yPdmafx4UublOjvfAJLZDjYENPoaDhH8ZCowcdSbe7NZKsCtNWsRh+a
7kEUZ2wEtCWiajyOq4piR9fRO4uVb+4Umdw+UaG6kKlCbuV4jkC48EQUUt56c19zK
WopXwCSa2GZioCz5vUzNoeNtdjJDMXgW+Af/PbV4qBRZkv3vYJ84Z8fFDZrGqUnpU
5mLlK8KuaGLg3nZLXsNYgepTXPqvtTvC9Xps/HNsUBiHBcKNcff8iqOl6cWZlBCl1
hOWTlTqhzBLCLnXKxnvX+yG9c2chKna94ZAPgX7w95k5vX8+xOg9nfwAIfjZkm83C
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-06-26
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

	protected function get_nextcloud_version($local_path) {
		$local_path = realpath($local_path) === false ? $local_path : realpath($local_path);

		if (!file_exists($local_path . '/version.php')) {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'".');
		}

		// TODO: this is a security vulnerability if an non-root user can control the input of version.php !
		$vendor = 'unknown';
		$OC_Version = array(0,0,0,0);
		$OC_VersionString = 'unknown';
		$OC_Edition = 'unknown';
		$OC_Channel = 'unknown';
		$OC_Build = 'unknown';
		include $local_path . '/version.php';

		if ($vendor != 'nextcloud') {
			throw new Exception('This is not a valid Nextcloud installation in "'.$local_path.'". It is "$vendor".');
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

		$cont = file_get_contents($update_url);
		if ($cont === false) {
			throw new Exception('Could not determinate current Nextcloud version in "'.$local_path.'".');
		}

		if ($cont === '') {
			return array($OC_VersionString, $OC_VersionString, $OC_Channel);
		} else {
			$xml = simplexml_load_string($cont);
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

		list($cur_ver, $new_ver, $channel) = $this->get_nextcloud_version($system_dir);

		if ($cur_ver === $new_ver) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is the latest available version for your Nextcloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
