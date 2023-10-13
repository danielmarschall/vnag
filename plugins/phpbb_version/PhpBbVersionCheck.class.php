<?php /* <ViaThinkSoftSignature>
MUPXG6vL9JioMaRfRc3zp3JGGHwaqjaAYeFbI8+lHpt8PoGtKQgp0YInngx8JaSlQ
nH0uTLeLmJGjSxc37BCxSuisNrYO2DkHGpIUfx37Qvo82uUhd6q4AYl+WOh1kRgEH
y1vwejbwwyR/K825roWiQ8D4gFprcJ0/Es7uyMFRnHbulg+F2epESceAKnWOzOIYY
jD4B34AcYxjvl9KppU1i+l55/wgxr2VlhOQGeEN0aJjHFTrLB+LdMftrSwCJlnQGZ
lHQ4FiEvMKYHXHNLsga/weiTdjP9Xtjmg1S4/dA/dhq7VFUXv043xkOSjR7KL+BRN
Po3crLkxXHLQoNl9xrE+fRV99F8cLmKSD749OwWrAdazKkwXILSxFOAvMF+h64Jcp
m6KBaDxDp56bpMsxj1+UYksb67ljCkwOgXfZ6lWuZr6leEDpo8bQmY53iggNVCOwU
j0gIX1yDN1RvkXX9yp8xwpLuaJmfRIJ58/FtfJ9YnG0P25MI9ozNYTeQ+JN96LQ7e
JgWgTGLEEq/geau/r18CzW39PL1+9qy19VYab/dcapX75ZpRBq9XgeMU6sP8Fdt+v
zNBedk+UnvOxWXJUohtLIBKArw0/cO9sW8CtqIO4MVctFAry8hqMXU0qpMf4EBbZF
/PwK0jax74ynKSXeyp3lAJfcy3EJVjKWtFonbHvlbMr/MvTtfDHuOpu/hRxRZhppm
nhQxUF8fqfHpxHpCo6B28XBQHfQSCkhWEahpsCoNXt91WGWLSpYwB508MijucNaWS
1qM7D84P03CPbDDJGLZU4/V++foeiM9R2QcHaq6SdImcLfRRBbWQbR9bHRw1OHOoA
X/piaytbGhJvOpbqLdpCeXtNP+bLz5lwC9dKFehPhY3Y1GG2FClpwabiRqk9jGQXL
55mLIEPrN7yHMh/ITMlMRVQed8liJT2BDpF7ZbeCI6rOaczRSoNTMdsw/9k46Cc1Y
qAXb1C0Wp5TUl3AOs6Y3cLgRqLZ+3LZ568YwiJvo5Yf+PpE6J/5iow2c6prxVjHqA
uxaNy4EzfvD6j3BpB1/y5IzbzsCmRefFMyRVeAl5VSSk97zrJ+uS5woEbT5684HVO
U11S5+KHkubSOz+4UJgBmndGT9cppIOFXaOy/ioWAMN8tAqKmo6Q12j1fFirh6r4Y
MS8eHTS+QmSlPE/CMl+8/vlbeKKFtWpjPb6RX4E3FwkQi7xLcsoqXz0iWRVyEdG+u
SD+kM/qF8d0VepED0J5xwHa9W04Hr4MIpYMB/47isd/QP+gx/Zo+AANzkg1PzgrHL
FKp6AVbsjfbysvP8wC70+2jKNAgpSG1LMFqRr6HfPqb/waxawAw/YDt+c2mKRykDB
g==
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
		if ($cont !== false) {
			if (preg_match("@define\('PHPBB_VERSION', '(.*)'\);@ismU", $cont, $m)) return $m[1];
		}

		// Version 2 support
		$cont = @file_get_contents("$path/docs/INSTALL.html");
		if ($cont !== false) {
			if (preg_match("@phpBB-(.*)_to_(.*)\.(zip|patch|tar)@ismU", $cont, $m)) return $m[2];
		}

		throw new VNagException('Could not determinate current phpBB version in "'.$path.'".');
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
			throw new VNagException("Please specify the directory of the phpBB installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		// 1. Checking the main system

		// See also the original version checking code at "phpbb/version_helper.php"
		// More information about the JSON structure: https://area51.phpbb.com/docs/dev/extensions/tutorial_advanced.html#extension-version-checking
		// (Note: We should check regularly if the fields 'eol' and 'security' will be officially implemented/described)
		$versionCheckUrl = "https://version.phpbb.com/phpbb/versions.json";
		$cont = $this->url_get_contents($versionCheckUrl);
		if ($cont === false) {
			throw new VNagException('Could not determinate latest phpBB version');
		}
		$json = @json_decode($cont,true);
		if ($json === false) {
			throw new VNagException('Could not determinate latest phpBB version');
		}

		$version = $this->get_local_version($system_dir);

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
			$cont = @file_get_contents($ext_json_file);
			if ($cont === false) continue; // TODO: throw exception?
			$ext_json_client = @json_decode($cont,true);
			if ($ext_json_client === false) continue; // TODO: throw exception?
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
