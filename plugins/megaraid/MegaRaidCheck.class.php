<?php /* <ViaThinkSoftSignature>
jq/ej1RUUthsz1FZirM8duHgne60T2KTbQhO51DfGBEjFOFE85A87oHVBm2AuqjwL
5FpCdrGNPxR0bSOOrVg5Sa7sgg/L2x/c/XltvFiWd5PCp3xX7mH5Q2g3G3IZtRoYB
aNOxv4dadjGHr4tqw2y211Nb9/dsSm0K7+zRXu5jJGki7T6mrVSc0FnYNE+1IL56A
Pz55VEuqzIhGfpe9W80mzx7j8naQTk9l50sF5MrYNhe7sypVpaqPzdWuvCe2xJ3x2
ui1Z1hZ0tNHcGpyqtB2SYj20E7du9q8ApCS530YSLjvOlttnDQOM8FCEiBJMaqo3K
ZAjRgUeiS8WRwfyIg5FaT+4fojL/zYxW/bbpFeA/5s5IFIGCY6zIt24eaUbryd8tr
EDa2uj5yDS6Hbg7p+W71kZ393OH8YKcHUEdY3i9ZigkognQfpU427IzqbG8AyQVNc
SnZI69IomUgPQan7oRB8EoW1JSOjTjoG/uOdJTtr5S2L+Jn8VwScAvnBubwUEFi/K
7odB2qIyba6M7vB7c1ZuvrZIpncUPCaEeheo49u0hcF9Uq6Ot4HCeuiA/ks9omQm5
dzpjdmPYk1ESWGT2OEnnLNHDHdKc2zppYlUSEVqoK4wSW+Mz9dOJejuM4WzFD+2N0
YlaNG0Xxc6622ToU89iAEc1pnjdObl9HhIneLDygoqy+1KP/kp8rJCz+aKz/vznoY
qt+P3W51tGq53ee0fvhscILuHjLLTlWUhCUizKXIz8xaksM368lQUwPyX/Lt05DNu
jrGmEGfTc3gE7rHrSHP4CM5gMc2Qx8z9O/klINM/tdFTZ5v6/MuCaqCudBnTFM8Ya
9y2uOrv8nAy6c6Rse5JnE39TakJ8dg5cym5LlY83ZA2iIF5I3sg7nZv9/EySintwg
47/AfqdAd/3Oi3pjh2C3kHGe9jUKaBHqjA6CXwmgb+6GccXDuxgeA49UOYEaR8VOU
QJDK/FN/PsM+lnG5l9IZKuYHbDFvyF6dcwmJCh4ssfhLBuYY5nkmFDlUNCicJnd7t
HOyDK82jw7Eixyx5n7BpB6lSFa2sfTJb6XEDj4mmH7ZdV2z2HZ9qS9BFCz9/BE+Lp
pp3dPr/rBMX+r3c4ou/PMuDC+eiGbUy8v53wqUoUdDDJxM1q46fhbrv5zqA64W8OY
PyEpOkvGBGBLi+yRoclqYAk3mxr/AOyLj2fsUyOavj/i1I7k/F7kuAbHmmEToxsQU
78DzGjFPNrLvWoqi2EIsaSJuw+9OhJSUFgBikRiT4TP0OIInaZaDzCvEq4X78x+IK
ICn+us9G5Ay/qnfHeBM1CA8/S+Qc38YTAvA/AHVUEqAgX8yXJG1f6YE8RHM3GHBb9
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-09-13
 *
 * Changelog:
 * 2023-09-13   1.0   Initial release
 */

declare(ticks=1);

class MegaRaidCheck extends VNag {
	private $executable = '/opt/MegaRAID/MegaCli/MegaCli64'; // TODO: make configurable (Also with sudo?)

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

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
			$cmd = escapeshellcmd($this->executable).' -LDInfo -Lall '.escapeshellarg('-a'.$adapter).' -NoLog';
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
			$cmd = escapeshellcmd($this->executable).' -PDList '.escapeshellarg('-a'.$adapter).' -NoLog';
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
			$cmd = escapeshellcmd($this->executable).' -AdpBbuCmd '.escapeshellarg('-a'.$adapter).' -NoLog';
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
