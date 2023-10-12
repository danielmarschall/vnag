<?php /* <ViaThinkSoftSignature>
Q+LgiqdSfhwY9Jlcm6yUYFLgrqbkPTtbTn00IzB/vNJZ7UwGuMZBLm/3wdSqobOjO
aYwaWnxIqvl8IplgQf58a/wO1PS1Abz+T0yUL9bNL71JcJauTqNqNeSQUcPjDDn62
tF5FiEhtVYPhiWYdY8O7hF9xOIot9XBjN6KPXOaOqRFreKgeF9P/WNIzHSdnDaZDX
TP+tEvtCijPbcw+1n00Di0h5K/LBFXuItJvpeHmtew3XgBxQXedndOGA8ZVIo3tmH
/n7s5s+CZboe+h/Ds9BMsAcEKv++d+9bJuPd/YsmwRrB0j22KRfyJmKKm96UBL/bs
ORF5cb/f+Uqd86kpVk1MXY+CXARVNVrB1huyo7AZgvXePMkcTKSJBiHhfhNCjmrFy
g8SkllXByUiyceZazlvxWCRpzqGzjXGw8ZWWjVIASP0BhgxIDwhtGBADs0c9SuWDa
Y5mJ//E517rQm2uq9bQqmJ3JTPHB9r41VxO17hzlP9AZafo4ISLgiqghElKSvgcOy
8D1DYbVeYnoBK13GObzixhA+p+/Gs8IL8aiuFrffLU3aScwjvmNRA5YEVIM+QCyfX
cbkQ5++1sqdNJZO5MufwVRJEjidvZqrsVEVdT8Oztu47v+8pA/RMabOVkR8TMAZB4
PtzcM27uOUANXJlXbnyUQrA9qiyrklVY7mphdCrq2GpfaXWP5vYaRyV6BrooAJWSO
0Rl1nd2aIxjxb+tDWCgiXi9+bBTHAiqvj8jHSKlZqye+ECmdrsejBRqGKbeaws92v
L3H152X+5VG7kni4xQEO75w+gNifzN6fUJNnk6/aIv5sOhiQcXAaOL5p0tUPTlvmG
eerZGQGTGa5JW5Ns1zCiihoTpQ1VQ39gPntz3c7hTuCspMdSqB9nH7YylhRqt87Hv
KzBXsNLnHbwrPLN5n2V3dMgC9pbKTXZ4pEYkVvn4/prIpscDlKezS7NXMz98gdNbV
yAEItdnBK4zjUj8PnBKN579sU5+3NgkNRJrmsZJWjDDp5fwdpTTbHAbnixgfIKtI6
fptjBozyBjzK+eXrPUhvseHg3GytlNoX3BLBNis6Ya917D79qiGpJlMjL6WX+EXpR
+bYWChbMqJVxQC4z2Ui6PDtGj6v0UCiW5p3ag5zlJkJHVEUpbj1gFRVPb0LpJk676
+lKqKQIrkHdOHwIN4yr6vlL2j95tCI4NF0+QSjvEGAORJJsmr8RoX30S1g9GrSLNI
hhKaYpZ+wAteNYBIQ5b/O2F+tTnu4iw/Zc90jNtqGKP8S9Rgro4N6MDwk841jc7y/
biAkj6zKbFNfaUhIqjPh+355VUWhzoYowY0sGST+v7+6NzIGHIW7+eMzta1aKbnBj
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

	protected function get_local_version($path) {
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
		$cont = $this->url_get_contents($versionCheckUrl);
		if ($cont === false) {
			throw new Exception('Could not determinate latest phpBB version');
		}
		$json = @json_decode($cont,true);
		if ($json === false) {
			throw new Exception('Could not determinate latest phpBB version');
		}

		$version = $this->get_local_version($system_dir);
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
