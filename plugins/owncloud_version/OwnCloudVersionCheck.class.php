<?php /* <ViaThinkSoftSignature>
IvzBH2MGR7iuY8ypKdaWeNlTFUDviDbO7/OB8VBORbHEEsLkTdH/PYxpmlvrI5KWl
NwVu87C8TziS10APkoVpPaMR/5UtGbU8dNEuBzMZOkH2sLSmjYqj8Eo+RswjNtFHc
nmn6lEmHHXYDibl+8ante/SL15CBYBaFS5ZEt96tZHz1z6v31QVpmOmSfMoKFlnhJ
G9mnmfJlplnUJuYk84LIgUe4rBrvU6bivOkkTNOg0MKQdcWDb6y1j20fojGbUWXkc
RzaKESzNS/cA8shEuaO4UwXifkuxwgzRpzGR19k83xLnzvboPmbqt9BWgtKrV09Is
UbEOEcczA6r0/phKLMl1svf6syhyPuEmmplyxzobiOgoclwUbTqNyJU06M78HkJZ0
Apjh3M56pKAxfJE0PLusZT6Fj2F2Ww0Y85rtF41NfWb4LmHFBiys+SL5eYZ8vSQEF
Dlot/K9qGb24UUWP0IiGrIL1b+0huGdb1L6msWVM4DMJDcA+L0afMfxKccQsAslfA
lOEq+PL3tbrcyWyxcmvnNopr0/8ZjLWr8Vc6B2oT7Y7SgqPeChsGAIR8k9tB26K9l
eCSC87JnHnJY98wc7acgoLAIdTI4Djfsp47/Ke21FLRtW6PbV20wzFcxEiECKT6U0
P2x8oWaRK1FD5UQpm0qoDV1G0R/GrkbjSgK5Or1OTeDF/cGL47LQy2Z9IUbmzc9o5
S47WI4+4UxBTgEluO5REwg7lBBh3T8o6VdpjWSwuES+tgvhBCc0eGhHfDAlwDkxaA
cKBdUyP0SPbc8OKQySmjmNCwFWsu6W/IbZDDWoyA1eZtJL5x5EY8sDGY3tEPPWZ6r
BdK/F9OocrLiAyqP3ExjGg895KlxEzF1udxxtUL/sjZcz31/MAkYylqCl2eOdUa6l
4iFv6hWyNnxdkEvHB0MIxHE/1JKYqXTckGiBz758ATyFxwz7B+Ln5H4Cck6DHP1e9
5VT33ScKyh0D4CQSd/MNe/JxvyjQEDdbpJDZ/Nm/kxjhkLkYvz4sQGdS/oxYCmLXM
xO2ESmZI+thCSdLz4sFRvikCQIPlU6VuxmsKXMoa1irESaDqcYfcVOIL8chUvikwQ
3GH8hNVV257SJN3MO8Nd2Fj/WpgIgV5TXRsfk/KujjnF62zkLc0eATo0vkwVlQ+9d
gtTHL4R40c+jy1u0jDRxvtkgJ5dIETpkvzpu0/thWRXIv+sTtTQ/a60S4ZkHGM3za
0GCX+lu83OpzrYkb0HTjrfA04k2SgvxtV1z0vqj5mPCSIEGx76LEApCHvPCl1QecK
ze+Jd6kZVOY/JFuU02o2iRx+7fYFxAHoXOmjZMfSlLAQ+/vvs7TA3hsbXMcihhLfg
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

class OwnCloudVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_owncloud_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local ownCloud system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'ownCloudPath', 'The local directory where your ownCloud installation is located.'));
	}

	protected function get_owncloud_version($local_path) {
		$local_path = realpath($local_path) === false ? $local_path : realpath($local_path);

		if (!file_exists($local_path . '/version.php')) {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'".');
		}

		// We don't include version.php because it would be a security vulnerability
		// code injection if somebody controls version.php
		$cont = file_get_contents($local_path . '/version.php');
		if (preg_match('@\\$(OC_Version)\\s*=\\s*array\\(\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*\\)\\s*;@ismU', $cont, $m)) {
			$OC_Version = array($m[2],$m[3],$m[4],$m[5]);
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Version".');
		}
		if (preg_match('@\\$(OC_VersionString)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_VersionString = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_VersionString".');
		}
		if (preg_match('@\\$(OC_Edition)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Edition = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Edition".');
		}
		if (preg_match('@\\$(OC_Channel)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Channel = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Channel".');
		}
		if (preg_match('@\\$(OC_Build)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Build = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Build".');
		}
		if (preg_match('@\\$(vendor)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$vendor = $m[3];
			if ($vendor != 'owncloud') {
				throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". It is "'.$vendor.'".');
			}
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "vendor".');
		}

		// Owncloud\Updater\Utils\Fetcher::DEFAULT_BASE_URL
		$baseUrl = 'https://updates.owncloud.com/server/';

		$update_url = $baseUrl . '?version='.
		              implode('x', $OC_Version).'x'.
		              'installedatx'.
		              'lastupdatedatx'.
		              $OC_Channel.'x'.
		              $OC_Edition.'x'.
		              urlencode($OC_Build);

		$cont = file_get_contents($update_url);
		if ($cont === false) {
			throw new Exception('Could not determinate current ownCloud version in "'.$local_path.'".');
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
			throw new Exception("Please specify the directory of the ownCloud installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		list($cur_ver, $new_ver, $channel) = $this->get_owncloud_version($system_dir);

		if ($cur_ver === $new_ver) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is the latest available version for your ownCloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
