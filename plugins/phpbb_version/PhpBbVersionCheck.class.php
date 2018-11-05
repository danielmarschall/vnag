<?php /* <ViaThinkSoftSignature>
XTWdCCK0FEM0wejpeajPPdpwhBNotRfIAK4cUBRtt32pcQoxsNdP1sU8Zw38IEq2B
P1pK8ELVVyb20dUZGQouY42DJQaTE0RRDGSfKZVxsYWAdLyiRc1gWPVsj3fHFJHJO
jey16hbqsdiKLpwT8fHgqaCz0y87oeksLQOmGw1VXfbQECYz3XU1JGie+Mxbx/dZN
gMb3l3juVwrs9veDBU2Q0HyeVAsV1xebfm+AfKngIV0dBEFW+kkBAcFuwzfaLIlhT
C5Vt1mvs7ZVbrgFxybkTuPV0J7aHwqt9qrRUiJLCOT6Vd2JuMQisCjVecjKD6XSNq
2+ZQ/YOuzuQ8HtPm3zKZeyk+PjQ+LqcoRY0i8MEXyxkHwZbBIgwko00Nw9hzSbtE0
iQfDi/KtAYBr8bb3U8ufMgbRWWf3ayrDOAJ2wNVgt6CqZACtPncvdpEc0fTAMm5n7
oinbC81zCpOCza9+Wka1Rl3w6forL9vezAG10K+9tn7PyUFKTxJKEUY2agFtumywK
Dh2tLoODZyZ8mNF3/pUeCgZFzoYgRvdRHBqc4E3Bnqb3ffB1JSaHl0FizufFv1UdH
rG0EYXL7VARPHjDAXNi9EiPsudZP3XREcQpDSk01zqajMrA4s7PphLKigGcvW9nw5
BTlo8i10RWzQcSsIqQNP7wibr5TZUrkSVjRZZNOOXepdGiAHapKn9h2tTcYbQb8yG
5j4bGTzFQsD/9wJql+acM/sdwDR3Gn/j0765eioW3DUCp0QeTmHewerq85aL+T+GJ
A3cjFkk1yemgOVI9QlDFBrCe1iYnep+XxuN8vEDM/AXy1e122FCDAHACWFTE6qrbD
SF5Gr92vMoQKyKtTTDJRDjrAIkPtQzSSHeyHUqKAFN2xapEiyq6rbuSQvbBnQPp+z
DPkJUTV55U84JpRWmVEDe2TdUJcrJKRRGGY0kXZk75M+9L3UUDJljSzTemvMSD/kL
Fz0tGFHLzLguUZFlKMMQwsV/+WqQ13jl9FO8r7zmEG+9f3S2kqhQEyF+ZjmqBeSh5
vb6zOzQ5ZSyPMdVcdYtOnPhKAARA3/oj9T9IB0Ybji4bYs7u2cD00yxHJ/f9yR5CR
Y6T6CJHuY6qduzacD6t8wrigfLQtv2/K27hpdoTOQi6oqPCy1zbF9UY6N4JpBVHns
MDv7akwPTMNTB0tuYTIHH8rcQudrJfqXUXquTgL35k8KaB+IGtGlrGq0w9OtCA199
or1heFAvPzi6lGkjF5rftF/V4HpO4JZQ2lKY41Z2ZXGK75oryv2DL79h4cvdFdhR7
O88AHlwlcodGTMfaKa2o51scaVjjDirWe4lZb0uXXLHDr19dDeajObnBFnukl1FGa
Q==
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
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
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
			throw new Exception('Could not determinate current phpBB version in "'.$system_dir.'".', false);
			return false;
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
