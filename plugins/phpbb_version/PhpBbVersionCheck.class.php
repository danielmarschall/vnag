<?php /* <ViaThinkSoftSignature>
URC5lyHF7B7Z/hCr2eAcYLDKai5MDWwA6NK9uL37gGSh0pfGDEss7/vvlTbfFJPDo
/4zYu0T4mgcqPs8kKgLRmT4A/Hn0BlQRcaG5MV2r+ypF37avL06BYfsed8DwxXOVM
ENZ4fTolmbKBGaQ+0BXGgkO9TnXnXP/A1LuOUZj6v0PGrnI6wfyJ8+a0de1lAvXZh
WxKqN4RcHkbqEQy71Ox5XCS6Wl8ZDp2/uPyoyh8FM1c/XJalnzVTaAkJwjsP3Vtqd
qVv7QURFmetfkOqqvtNm4N5XGlNhd2/M2QolAJNzjVL4ZLd4hNB2dGlxo7eNag/+r
100zpq3/ZVJ65YKMfDXAAteHS/k/ajHO6bmh171pVj2cq5NlBqIPm7zk3dIeHhu1f
WhW6tV6aZu7ChK9waZ1i9G2g4FStOLzQdRrV9rqOqLjfXB4hyfXP9SIwPn+sZ+VxU
x4+PW9QCZxc16JW6tyik9MFqjmwD5cttzoAwa/EKvuoxXeMz+aIsqXNxv8YfgCUXN
dyZwWUBb9UTbsVpEZTU8wXNhLwYzLGKuv9t/xWEGr/+bknaEwiMxhqzt9j9P+Blhx
vw+0V2z6NtX/VOJJ/kaJFPS+U0Zj0M96QB/EUPKzuxtU4MpqyyKxrv4d3rWoH+I2i
G5Q4TI58+8UNBKG/UZZz6adF0VZ8TwuRxDL07USEgqkIV96NAzTguVhBYRmn/OROU
qUnfB16vvDI0qXpT/tMuthHWQK/qDkxF6JiBRBGQGSl+a1SBW7peEc7yQvgs94Prn
eTuisoHyZ6o2UY8iYCulOTiYAjLd6Ob45O0ggu3U2Y8e+avoJc7vZ3ix4AzsFPtLZ
q1QTSLakzPD6yKwSnr6WHk72R/VlWo3nGF5I53e+32tyqN9bgMol6BxGu6fMx/nAZ
581nqLgqhI/+WstBmQYysVJXkt2rCowgrtU35ErZD9tKwWC7pBGwJrH2zF88y8nxz
YmtfL5zdHVU0tY2wStySbWPC77+RWLTRRiNmDmM4xAWiJ53VsKBQiXO+/Ee1XieL/
s1qGqyUp3ZhWgqZSur1X3MxFnzt50g+VZUJOIYL4/8Zxn/jxwa+SQDazvsnxCQQFC
9Q43pB304ik4Ps0BBmcUzOIGy3UIzWeWl8p2nLFCJ9UmGoQcwfCAc/1zWdoBnHyNM
n/0Ody4uqN0qzo9z9B52YJzrTApC2m0EcbpUVCwfWWV0e8UGhw1Y3rR0ovPOHU53g
wxGtDzQoFySXk2RgxVxWn5DIa8B4gH94aCbQ6C4Msew1iHkYINWp4TUfbyGQDQLNQ
1ICXmi0Zs3lXHQsjHt/uTb/rjftdUivbKo0V+1AnW/jo7fzegS6Vv5+3A8TdBaa6d
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
 */

// TODO: Should we also warn if a newer major version is released?

declare(ticks=1);

class PhpBbVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_phpbb_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local phpBB system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'phpBBPath', 'The local directory where your phpBB installation is located.'));
	}

	protected function get_phpbb_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		// Version 3 support
		$cont = @file_get_contents("$path/includes/constants.php");
		preg_match("@define\('PHPBB_VERSION', '(.*)'\);@ismU", $cont, $m);

		if (isset($m[1])) {
			return $m[1];
		} else {
			// Version 2 support
			$cont = @file_get_contents("$path/docs/INSTALL.html");
			preg_match("@phpBB-(.*)_to_(.*)\.(zip|patch|tar)@ismU", $cont, $m);

			if (isset($m[2])) {
				return $m[2];
			} else {
				return false;
			}
		}
	}

	protected function isVersionCurrentStable($json, $version) {
		foreach ($json['stable'] as $major => $data) {
			if ($data['current'] == $version) return true;
		}
		return false;
	}

	protected function isVersionCurrentUnstable($json, $version) {
		foreach ($json['unstable'] as $major => $data) {
			if ($data['current'] == $version) return true;
		}
		return false;
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the phpBB installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		// 1. Checking the main system

		// See also the original version checking code at "phpbb/version_helper.php"
		// More information about the JSON structure: https://area51.phpbb.com/docs/dev/extensions/tutorial_advanced.html#extension-version-checking
		// (Note: We should check regularly if the fields 'eol' and 'security' will be officially implemented/described)
		$versionCheckUrl = "https://version.phpbb.com/phpbb/versions.json";
		$cont = @file_get_contents($versionCheckUrl);
		if ($cont === false) {
			throw new Exception('Could not determinate latest phpBB version');
		}
		$json = @json_decode($cont,true);
		if ($json === false) {
			throw new Exception('Could not determinate latest phpBB version');
		}

		$version = $this->get_phpbb_version($system_dir);

		if ($version === false) {
			throw new Exception('Could not determinate current phpBB version in "'.$system_dir.'".');
		}

		if ($this->isVersionCurrentStable($json, $version)) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
		} else if ($this->isVersionCurrentUnstable($json, $version)) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Unstable version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version (Old version!) at $system_dir", true);
		}

		// 2. Checking extensions

		$total_extensions = 0;
		$unknown_extensions = 0;
		$old_extensions = 0;
		$current_extensions = 0;
		$check_errors = 0;

		$ext_json_files = glob($system_dir.'/ext/*/*/composer.json');
		foreach ($ext_json_files as $ext_json_file) {
			$ext_json_client = json_decode(file_get_contents($ext_json_file),true);
			$extname = $ext_json_client['name'];
			$version = $ext_json_client['version'];
			$total_extensions++;
			if (isset($ext_json_client['extra']) && isset($ext_json_client['extra']['version-check'])) {
				if (!isset($ext_json_client['extra']['version-check']['ssl'])) $ext_json_client['extra']['version-check']['ssl'] = false;
				$versionCheckUrl  = $ext_json_client['extra']['version-check']['ssl'] ? 'https://' : 'http://';
				$versionCheckUrl .= $ext_json_client['extra']['version-check']['host'];
				$versionCheckUrl .= $ext_json_client['extra']['version-check']['directory'].'/';
				$versionCheckUrl .= $ext_json_client['extra']['version-check']['filename'];
				$cont = @file_get_contents($versionCheckUrl);
				if ($cont === false) {
					$this->setStatus(VNag::STATUS_WARNING);
					$this->addVerboseMessage("Extension $extname : Cannot reach update-server (Version $version)!", VNag::VERBOSITY_SUMMARY);
					$check_errors++;
					continue;
				}
				$json = @json_decode($cont,true);
				if ($json === false) {
					$this->setStatus(VNag::STATUS_WARNING);
					$this->addVerboseMessage("Extension $extname : Cannot reach update-server (Version $version)!", VNag::VERBOSITY_SUMMARY);
					$check_errors++;
					continue;
				}

				if ($this->isVersionCurrentStable($json, $version)) {
					$this->setStatus(VNag::STATUS_OK);
					$this->addVerboseMessage("Extension $extname : Version $version is latest stable.", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					$current_extensions++;
				} else if ($this->isVersionCurrentUnstable($json, $version)) {
					$this->setStatus(VNag::STATUS_OK);
					$this->addVerboseMessage("Extension $extname : Version $version is latest unstable.", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					$current_extensions++;
				} else {
					$this->setStatus(VNag::STATUS_WARNING);
					$this->addVerboseMessage("Extension $extname : Version $version is outdated!", VNag::VERBOSITY_SUMMARY);
					$old_extensions++;
				}
			} else {
				$this->addVerboseMessage("Extension $extname (version $version) does not have any update server information.", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
				$unknown_extensions++;
			}
		}

		if ($old_extensions > 0) {
			$this->setHeadline("$old_extensions extensions require an update", true);
		}
		if ($check_errors > 0) {
			$this->setHeadline("$old_extensions extensions can't be checked (update server error)", true);
		}
		$this->addVerboseMessage("$total_extensions extensions total, $current_extensions up-to-date, $old_extensions outdated, $unknown_extensions without update information, $check_errors update server errors", VNag::VERBOSITY_SUMMARY);
	}
}
