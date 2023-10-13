<?php /* <ViaThinkSoftSignature>
UHbkNGOegcwLsuVviNRiWIcc8NnArI073d2KVCAfBnRx5/6bWSpqMWFaXhO7QxuIp
ELk8yRemsNGY0WfEHSnqDpu5w48gbD+9f28f/7tWkLQygz9ycjNxledVUOnRvBbN5
c98Bcwi6uS5K/KRjG4ugUMVN6oj9SpRhJzbYUy70vUMMRmgWH3z1jX52jXFI3ypO/
Ntr+lvskBgpA2DrD//dA8fOyrvS7O+AxhsDUuSab4TP09hzcl5/1F4ZyaG5K5egdk
QIb5RA77WndrY9c0AqwVhjy9OLlcFOGIqEMx9KlaBHeSjCtIiWFHlMz+Wr+VNyp3M
2YG9JAu7Ex7LBq//DKfK0XfTcJ550NEU6eJ75Uzk7wO4xnhs2b3iVybf4t2ffyAxs
OAevkcM3BPxueMzJOe9vbogcpgVoouWR6soLGB2pGze+x7p6fnxtuzzDJAr3WdHKd
5aZbsp/a/+SttWWbMwqLJPz+pqHb1rOL1e1I1z47tsJy4w6BDNs2CmbpOK6AUsrFS
vni6a3Yd8m3AYvq4LkYVzVsV1n+yR7IVXu9MHt3Hk9ccg1WSKh8emwbRDujMY3hQs
W/o78J6J8m+auI1VpL+Z0oJK2/1/o2LUTX2YQWeuXZrXIkc1WfrL91pUYT/We8ycj
MkcQXGZY0qC0lbB8ZqeTsLvn0x7fvvrLI2G1apFU2byd9VAmqAvQL/KWkd9JDraVE
Y9py+WbfbkhaWmfLneJExlKu6jH1ji4hTnkLqREdny0tAp6jRw6y1yD1UobPRp7H9
dlyGTBDUOMH7dd8w9/knwBej6+4LrPMjUZtAzkwg8j0tgcqDesYaT6ifgm5WndleQ
bXxbsVrNW4Jnw0xzs+VPI0do1VWOFUF4MeRHFGjzUhe7mrrQggbrII84dAaTu5ErQ
PzMYvKy2fBd1DageP0+hk3DioFJc4h/de8re9lYqJl24QDox9yqIvPIdjNBlte2Fa
5i/G/lTqpGiLlpzOS3K0farbW+xBq0TikWjWwOJkc4UejF2Z2BwCOFHZ5HSIHHACy
VfFBKTIp76xAsHKswOyjALMLgqQNRxclIAZ069Q5e0iF4GFOYv/mWPWmdV+na6TnH
f3IQtBbRGgg2+EHyOa1sriW3CCvZc4vKprL6bomNsSmZhHGmRiyaNkffbzsnyYyxE
vQ66okP7D/BkdvkGBT0843gaB/2nfRdDomBCxTLzmpVZszntCoogUhp18tVLbxX2z
2uGI8C2w1e5yjtYNcB4LK/6/Gl0hWQhkV64A+paPDmcln+ubGOWoReau840i0wH8Q
0ceApxIC8f0ob372tgS08h7sU95RvZdU6+ngrFbQhzXbOBd5/9mTY1k91NUrQ9h61
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-09-14
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
