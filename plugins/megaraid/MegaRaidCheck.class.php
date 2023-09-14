<?php /* <ViaThinkSoftSignature>
Tc+nyWiSG99g+rn5Kxz4Qq1U8OIq3W83DIzZN42wndPJit2lZMknPYjt/OUTmtTcv
u/8ef2+7k7VFr6pzqlVi2lfG9FcRwSl0BSXfs+b5Aw0y4YVdM3k0IE+XJlOmJKhuO
TrNHq6OCIoA4bJlSOgJ02PSy2LCReAsEQfwfCsQy+j1WAm0Pno8PB/PbBhxGUoO3c
f1O/DbJh17NfcQNzfulUBFYwGx3mBnNg5JqE4H435y/A5irelloX4GgZJjq6YJu8p
rzgLCHgaDdAlbypK3YFxlQWmMI8yfUwaaX3DDT276lBeA7TjSj0iwQu1VnSux4vmb
AjHGkoexDa6JG7qHoNGG8LF5JTQ+35f4hcTwUWwkWts5N3yQ+xOP6MzBoL0oFtQZS
s3amlKuiWmtk3Ft45hnoq0KepEGgMFnEdz1AmeZ+HJc1gYTuPmhF0XGq8bBPhA0GL
OIqo6AIhBzAcpFyad6Ag+Wkq3OEmpDv+5NjR1Fd/p6968oGVTPjPCjyw122PxfqzJ
C3fGEQWwKisoMOwg/4wDJP8bUDOgeb3U5OGYJ/p4dHI5Em2vHCAHJk8fEWpaMFk8j
3h+3+8RnVxllVx3qeIpdoMEeHZn5olToa+5yM2puf9aHbJD9DB+g68zLLjRH5May6
fkGKlJ2GiIX0+JUy2PCnhI5lpwweBA75ADAZLfcmgxJNSsZYOYE1RwcfdCRMU7wA2
BAD+A3PR31LZpAy5mPtStCwlMO2W3BcLv2bZGpNnExoAiL5YqoPhVGMF4Xer3SxlP
uxFhzAq0RbG8fXGr9w+c+gh2hV/LO0G0qwJV9mBmlg2ZBUO8HN6Wi+Oe/NfZg3IOo
z7cSzkWdmfY6m1PwKcDIae+EAnfqr9jN3CImE7Yt8pdSwCC4aEN3d+/TQtrimOlxT
94fg0z0+5mgve7AEQaieINSlDkJAARaYQVQvRrYryMmhjjSYQCXuMjXYu2ZOTvtgy
wbNfOWPT6EKAyxRiL8X2R6L5kSozABxonkKycrXX6uEel+MwVNs6ymS0czt8skInk
8+Q2KxH6PDjYU4iRxtSpCRhwCBC9vgrHGNi+sgx+EN7aU7/bgCN/7ZjxFJYPYn0mR
2rezG9g0sWospPHhMetHf170FQXdS4OCLxIfcqwrGXEIb7nNQl1hXrw2Gg1cFEGtN
XJOOeTSszuPBH70JvqH+dRaZB44pyF0pMbcyWD3lJi6DIckzgHQQg/DiIgjLLRX5v
g+E81ICL86W+q3sEhbtFc0XEFpOBdB4TRxS02vAZP0pyUPTX1P3YIcguS12E+yzwT
VkeVK2fFuhi03QuBiJIMnBlP7g5MwtMyxjuLcT8P7BTH5GnlLrv0idVO00maYnv9Z
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-09-14
 *
 * Changelog:
 * 2023-09-13   1.0   Initial release
 * 2023-09-14   1.0.1 Executable path is now configurable
 */

declare(ticks=1);

class MegaRaidCheck extends VNag {
	private $argCmd = null;

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

		$this->addExpectedArgument($this->argCmd = new VNagArgument('E', 'executable', VNagArgument::VALUE_OPTIONAL, 'executable', 'The path to the MegaCli64 executable.', '/opt/MegaRAID/MegaCli/MegaCli64'));

		$this->getHelpManager()->setPluginName('vnag_megaraid');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks the controller and disk status of a MegaRAID controller (using MegaCli64).');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
	}

	private function megacli_logical_disk_status($adapter='ALL') {
		$mock_file = __DIR__.'/status_ld.mock';

		if (file_exists($mock_file)) {
			$out = explode("\n", file_get_contents($mock_file));
		} else {
			$cmd = escapeshellcmd($this->argCmd->getValue()).' -LDInfo -Lall '.escapeshellarg('-a'.$adapter).' -NoLog';
			$out = array();
			exec($cmd, $out, $ec);

			// DEBUG: file_put_contents($mock_file, implode("\n", $out));
		}

		$drives = [];
		$cur_drive_id = '???';
		$cur_drive_name = '';
		foreach ($out as $line) {
			if (preg_match('@Virtual Drive: (\d+)@', $line, $m)) $cur_drive_id = $m[1];
			if (preg_match('@Name\s*:([^\n]*)@', $line, $m)) $cur_drive_name = trim($m[1]);
			if (preg_match('@State\s*:([^\n]*)@', $line, $m)) {
				$drives[] = [$cur_drive_id, $cur_drive_name, trim($m[1])];
				$cur_drive_id = '???';
				$cur_drive_name = '';
			}
		}

		$drives_ok = 0;
		$drives_fail = 0;

		foreach ($drives as list($cur_drive_id, $cur_drive_name, $cur_drive_status)) {
			if (strtolower($cur_drive_status) == 'offline') $status = VNag::STATUS_CRITICAL/*?*/;
			else if (strpos($cur_drive_status, 'degraded') !== false) $status = VNag::STATUS_CRITICAL;
			else if (strtolower($cur_drive_status) == 'optimal') $status = VNag::STATUS_OK;
			else if (strtolower($cur_drive_status) == 'initialize') $status = VNag::STATUS_WARNING;
			else if (strtolower($cur_drive_status) == 'checkconsistency') $status = VNag::STATUS_WARNING;
			else $status = VNag::STATUS_UNKNOWN;

			if ($status == VNag::STATUS_OK) { $drives_ok++; } else { $drives_fail++; }

			$cur_drive_hf_name = $cur_drive_id . (!empty($cur_drive_name) ? " ($cur_drive_name)" : "");
			$msg = "Logical drive $cur_drive_hf_name: $cur_drive_status";
			$verbosity = $status == VNag::STATUS_OK ? VNag::VERBOSITY_ADDITIONAL_INFORMATION : VNag::VERBOSITY_SUMMARY;
			$this->addVerboseMessage($msg, $verbosity);
			$this->setStatus($status);
		}

		$drives_total = $drives_ok + $drives_fail;
		return "$drives_fail/$drives_total arrays with problems";
	}

	private function megacli_smart_disk_status($adapter='ALL') {
		$mock_file = __DIR__.'/status_pd.mock';

		if (file_exists($mock_file)) {
			$out = explode("\n", file_get_contents($mock_file));
		} else {
			$cmd = escapeshellcmd($this->argCmd->getValue()).' -PDList '.escapeshellarg('-a'.$adapter).' -NoLog';
			$out = array();
			exec($cmd, $out, $ec);

			// DEBUG: file_put_contents($mock_file, implode("\n", $out));
		}

		$drives = [];
		$cur_drive_id = '???';
		foreach ($out as $line) {
			if (preg_match('@Slot Number: (\d+)@', $line, $m)) $cur_drive_id = $m[1];
			if (preg_match('@Drive has flagged a S.M.A.R.T alert\s*:([^\n]*)@', $line, $m)) {
				$drives[] = [$cur_drive_id, trim($m[1])];
				$cur_drive_id = '???';
			}
		}

		$drives_ok = 0;
		$drives_fail = 0;

		foreach ($drives as list($cur_drive_id, $cur_drive_status)) {
			if (strtolower($cur_drive_status) == 'no') $status = VNag::STATUS_OK;
			else $status = VNag::STATUS_CRITICAL; // unsure if there will be a "yes" or any other output

			if ($status == VNag::STATUS_OK) { $drives_ok++; } else { $drives_fail++; }

			$msg = "Physical drive $cur_drive_id: SMART alert? $cur_drive_status";
			$verbosity = $status == VNag::STATUS_OK ? VNag::VERBOSITY_ADDITIONAL_INFORMATION : VNag::VERBOSITY_SUMMARY;
			$this->addVerboseMessage($msg, $verbosity);
			$this->setStatus($status);
		}

		$drives_total = $drives_ok + $drives_fail;
		return "$drives_fail/$drives_total drives have SMART alerts";
	}

	private function megacli_battery_status($adapter='ALL') {
		$mock_file = __DIR__.'/status_battery.mock';

		if (file_exists($mock_file)) {
			$out = explode("\n", file_get_contents($mock_file));
		} else {
			$cmd = escapeshellcmd($this->argCmd->getValue()).' -AdpBbuCmd '.escapeshellarg('-a'.$adapter).' -NoLog';
			$out = array();
			exec($cmd, $out, $ec);

			// DEBUG: file_put_contents($mock_file, implode("\n", $out));
		}

		$battery_status = '???';
		foreach ($out as $line) {
			if (preg_match('@Battery State\s*:([^\n]*)@', $line, $m)) {
				$battery_status = trim($m[1]);
			}
		}

		if (strtolower($battery_status) == 'missing') $status = VNag::STATUS_WARNING;
		else if (strtolower($battery_status) == 'optimal') $status = VNag::STATUS_OK;
		else if (strtolower($battery_status) == 'failed') $status = VNag::STATUS_CRITICAL;
		else if (strtolower($battery_status) == 'learning') $status = VNag::STATUS_WARNING;
		else if (strpos($battery_status, 'degraded') !== false) $status = VNag::STATUS_CRITICAL;
		else $status = VNag::STATUS_UNKNOWN;

		$msg = "Battery Status: $battery_status";
		$verbosity = $status == VNag::STATUS_OK ? VNag::VERBOSITY_ADDITIONAL_INFORMATION : VNag::VERBOSITY_SUMMARY;
		$this->addVerboseMessage($msg, $verbosity);
		$this->setStatus($status);

		return $status == VNag::STATUS_OK ? "Battery OK" : "Battery has problems";
	}

	protected function cbRun() {
		$headlines = '';
		$headlines .= $this->megacli_logical_disk_status() . ', ';
		$headlines .= $this->megacli_smart_disk_status() . ', ';
		$headlines .= $this->megacli_battery_status();
		$this->setHeadline($headlines, true);
	}
}
