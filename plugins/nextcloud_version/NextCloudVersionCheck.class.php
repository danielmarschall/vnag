<?php /* <ViaThinkSoftSignature>
xYW/6C/wvjTZL6NWDg36CNbd8VUdQ+nizmR3DZoL1h5ilQel2IFUN5YWMqw4Tigvf
ksHsVSaLyt0z5J3jEtgSQmg5odNKw74JH5npY2xfnfeoHbFMQlDdFY4xsZh51JW9t
xmQWV0B0qASrYDJjZdejxG92UWf+ZrsuOa+3D5t9qZnQsQrIb7dm0daBEY2+bVdx0
elCVFpdQ/MUcAZI7MKJoPRxbmQpZQiBJRAq1aR6G09srBOZCkzR62x8pVOiFDE/4B
Y4eo/ZTzcolD97seWeqGODnRFdL7rzMUY29iPZlvk0I6s5KWoWJm1bGMeRqX06vRC
mZp6bDpaTAoVe1Rh1laJn2+KjLeupsnKCcQncdyy1Y5m9rs+PEia3+ZFRgDmz8p2B
ClpX1Ko969jtAe2oYQn82rhnbv+w1cSUJyPnn76FmvShXNC7DuFPWl3rPdEjFUXRe
jZD6HAKumND2IAokTU8P/Mn0Xoqj3gpu23RVByOAU9PHrD4M4vfTPLxmiYvyrqBVs
rJSvaURUJs4RFvZGlBGjmsRWjOtn6J/1iXiTHk3197IruG/laaBzoBZhOzWv2MV7I
xddtrr9blabIHkngnwD5XtO40x8P5kKH9qc4l8vBl1MGNvN1BJ2BJPV6X4IrVSmJ5
sz3SCgXJkEVFth/KZCc+6DtD/bze4gj9Ad212vTkPMsxS0NTALDd6nLdExjj5TewQ
B0YLmZsn+hltPTbxod6ukis3UTE92GUsN35K/DNyJcfJ/cebbQlcMhcAojLRkUNTq
1W6M2m24l6CGwkUPM1w0e3RlyQA5TS2PjX8TRX91spRXY8hD8fpNHL9+GOzbEIyIy
73yeoOGHeB1hSnD4H2LSs3dwl1189PyuNl2jJNVa6cLPy3R1Yixz4q29XkS60Oxpg
lGme9nlW2S4yJJLB/VWStEXNeCOfTvKIHmTZSEQ1RvxttRBkcjMoJqZbaKpwW8rVB
PVaw07SCi7IKXWiosY9hMvHbnLU0GkbgkxW09bXw4LkTLwYHEfbGGsBe1qS1IGF7/
JzfPxj7RX0tDZX5wuolJEZb+rer76VJD1wNdzcpbTAAdu/bf3Ptwblx6eIFQw6+YN
/eOs1YC4VAUedKGh1FruSU5wZiJJDizP/+33OapudvTaRlK6/AEdSc5JnP5GsFQah
1CzAU0WekFhFP51SmSMsefVjC3AzWSDJUTa4E+e8SsyYOkHCDAZmcI8wL+KMsbNLs
0QMHW9GwY+9BP+iGMFBMklBWEFkKkPx/QfbqGW0JB4bjcgB3UVHMAHbJ5+DlN9SZ7
EUqh5dIkoaQ8FwKIIIa8qw7GCN6F96IKZ+3O80RVSm6ZPDSdd/Z+qgPUg06La0Xme
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-09-12
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

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is the latest available version for your Nextcloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
