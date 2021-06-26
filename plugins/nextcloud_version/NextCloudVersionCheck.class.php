<?php /* <ViaThinkSoftSignature>
iHvcQ5LzNAer/Ap18FaL5bM2842CWMdX5Gald3rv3jnW8Dl69p7W0T7cUWEPrM39v
ZUoVTv+XcWGY4v0L1rTx1UOq68z/vGW9dzCLuPEytVjT3SRojx0qhBOZZHkhGgXKA
EEsEKomsdteMo0aMMq6EiM9HDYLVJXnjkzD9QavXNcXP0B52ChK/DL3zhAVan83p9
P8zkkRY399qTfoM8IP2vEadUafsYtJewWvxgi/L6U3ogBPTMEF9Ba9iEWBmZCfbRH
upSkg1aAetrtAs03HFVpKNb2gC1LUCcwvRT8E06yjU3+9vFSfl4YhXiFuMeSIOaFB
H/ZW8kB+sRwkZQoohBrikbXkgj4mJANfqNx7lRvWBLWoJ349vvh+iJdFIIIoOurc7
Vlc7MKSRSZ2CNMcTLHLT8pZ5RVdzO2If5iU5diHGWSlX9beliwuZpfJQ86nFOSdIY
V65Z0U2hlamxeXbAVWYZbAZ4QlYyl7JlJ32QBcKAMgK5DVc83ZRInPXS/0prvrCDf
h17iTLL27hdx8mXSEgmQgFMBS2fyV6+vJxQpdbwUAZTfIcGXyLPKUgXn3jypSzBt4
n4a63YcSJWL3iBjQpj1SUYhTCrZ3HlR8OUZ5NRpcUcTwt7TTkNvV3+NAj+LvrAUyv
iWK4y7U86l2cLO0b/NHHE7BYZkQhnPCjTjR2tKeRAI63A4+2sor8jtaHGCXCGas59
VHYgwbpOJWe3igTv4n0wshGdeO9VgM0EbpwrgIq3zEzbrl1QbfxO3k8TBRWb8wB/F
c0zbXJOZqYXdd9eZ6hjxLu/AFjpPJC43PqcVuKHX6Vp3Qwod3zebQxbYl4ajmV3EO
Q0nAHJKQbrZ/edxOeYA9RhRP9LUp49fbf8e+GjdEcFqkiAPDjtPhPVPi+fBsx+MfQ
UxF+ZmDgjrxaDeTWGuCjv2BJ+q1uGIBkRN0eo+70cRNaaSppxxPED6DxXEA3sw/us
OZyvMER1xvTPkLauaNmxZ9zj7nBiyHEO8Gp58XNIkKrNyh1YBYckRmJLCG+x3qP4M
WD0tZHuUvLWiPDhUYSVKuVq0qjZOsn9qcTgkV/BXSmnPEPutXyZYShnA5/0RlPaq4
XvrbTSHCvviXvJr/rgDtE+7llx6l+PLujtgm1v049sp+CHNy2312NIC73/HSKWU1h
GYzKuWt8WOWmVPGNlaQSFEeIkYfZwEp/Og2+QN9TgQsYNcVYBTmo+E7gfgDfj1Lcf
YDmXLnZJFOAqVMFlHbiX9/rn3VzSwVHNiIOU1qS+9uYezXbKzjnWTvODfv0gS7Neg
/MbDtsRQWG7UMpTPT96oGjGKxPtuBvNg6YwDxNopSG3AfKwLzVI6imvcMhCug9znS
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

		if ($cur_ver === $new_ver) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is the latest available version for your Nextcloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Nextcloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
