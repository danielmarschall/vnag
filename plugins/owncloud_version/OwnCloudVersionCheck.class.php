<?php /* <ViaThinkSoftSignature>
x8VDWLNVIBuTV+Bwn57lv0/eS256StHnC4d4Jdcpmn2mhOKU9INdgEdQWaRVsqPdL
v7ML2ZSeGQTO7E3rHtXvlhbFikllomsC8MiR1Ja+LogZR8VHpnAE29g6l+llwQYBq
F5UoDQQUHkxVf6fA2wcGfvzt3CjS4Ab1wcGerspjywQVDWHNFuTXYB7VzziAchJBv
dgTbzsW0LuNK9M09ve8szm8mYdQ3DbUo9kOOudmrP7jVm3FPowFcFQyI+XVooNwxq
0bt5HWWdn3iklgCdbK4I0PfFom7IIRmWLQcn+O9Xb6hsJNNMwzBtFRt6KqjrOJnFm
ENr0cMMDMt4rZlSLVP7MQL9X20/L8Sx6x9lSLe3L0kjRsQk5OtlzeP15ntjd4wYak
rDIj/nUQ/Fejkv210s6DJTtPFLCiVN6UlMfooL1Kp439HYfSko0QFTK3Szc7shsDg
QOWcEnheheJ4pUm0GsvSvikKz+MVMyygp0FLxe4j7/Dt8hR6y2sbC9X3RffRh+OHK
916LMzW0zowM8lc5+Orw5iZHpk5hFj19p2TN9cAzWWleGLWWBWFZgqIZVPbLgV5pM
tVRHHGTitZ68yU6U0fcDbafd4Fs4BIACMuAo3aR27PZJMKYWphLhwbhfH1jUQ+a4p
sxGCViiUSy+Fi2rl9T2glCKxwQmzZk3LQwbX37Cf+bCUV4u+x/vFrHPdKbbipP9XU
gjzXnWk/rytrXtiVJgk0R/nWq6KuzvMeivvQuPcJvtjbNWeSa/CCI33oBGfBWqOWf
tHYZ2ygQ/gkyD6Sjls5+3G1M6m5a3evgib4hSqXWIo5rmDtilSZ3pUOFpcsGZ9O1j
cMgVq78ErkEVJonNwoz1L2de6ArqF5IflG6vqDCwmm/iJNazwth1mem4S/O8HFMVZ
PVCslXsikJSFiN7EthHA4iGrMvZnVpT0OBaHSXcIRWiB6goIiKC7GgqiRx5Ho+rC+
h4DiSJ1lI6AbwWI+ATkGlLzLvjVb3ESmDXSisBVE1cMrh3TK/MJQ8XRRRfwNxzYT0
EOC1nZ/RSgjrilOqJbRlmXAqIv0vTSbIFpyYolOZjMVDZ9IluWy6sCErfuR/XHXYa
3oSEAlmpCzMkberpuVWCRcbLDMI+jjtx1J5SKn/IVMsNyB4ygi5YCuvqKxRge8rdq
GByaLxhO2ziDQGAK08+PHCLD19n9APbtyplEiX6wA5Mv2F2rEenuSbIAyXCxC4WnD
dyHau/7F2y4uyt6F0eD8PmtFYDtjH0RIGFtwqnmh61JChfxymXogN7Ee7bMqWJFe3
Ksd0egFhulWY9ox4KdDpUXDETfM3pIYnHKI7FWe2/4zqdUgA7sT/14ZuCdqXQJmsP
w==
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

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is the latest available version for your ownCloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
