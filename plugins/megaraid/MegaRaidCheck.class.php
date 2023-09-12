<?php /* <ViaThinkSoftSignature>
x0TUq9AscwEzm20qbzj5jrBoiQchEvHi00gPikIfDMz66iw1W2/4y8yLDOjOX0AV9
tbIXflk2weq0JcUeJPVH9k8TKqt3Oqh8nobvSjhGmO/KxG/QiDVcw82lER9ZDVcc7
OhB8nsWDQ2xxkfgv4ZEv1rdmLP0AuH5tEHv709eVGYWZf5Fd9EFJ++TtKS16lwim4
AUYIOYPCTjbD/J+7KtY3uxc4QV9am+EyLRIkeBeo1UcjccWhfh7Yw8OfTh0RDinzH
1rkMCRC4cSSVXQlkLOfJkPSNrgwyZ6i61hB5W3CwFFefaXEkzQfcwtQnz9GrzK5U4
6LV+gHa+QSaSSoyBg4VJ+kYhyfzXHR33LTURSnSHZpdjzQ+cR3SVDxUhJNcx1b+yj
Zhl4hwlskI83FVLweCjz3lcGD259Uy62WOYwZFOMjpUVjyKfloMRJFsoqOKYjxv9V
9SriY8A0GDaWq9XML3CuUtSs2hPAK024uxMY8NfppLi7F0jEioOwyILekWB/HioYa
8JAyRvDqgUCWWU10Uw7PlH1RUmkbL1bcMmiD29BxZh+rp/dJ/IdZ0BCUiXtcs5jgR
K4STam7277Fh3Ok5i1n/TkHM/Mk58ro/6HmBKpPnlMn16Z/dPzQXKlsIT/swRyIX9
G+hTijkSgtBa2QJ30OQr1+EM4l0sGSlE4hb+DbWwmplI5yz8asTKlE7DNXonPmNBz
9WUsAzsxQIsgaWY3tBHPss/shcOIeuotAsuoquhdnhBbcr6tAFDIuDsG4EqoljpBh
b8yIjNSJdy+d7lBK+tY5u8zBlN0UO8A4E5pEKk+YvPrMV+O/Hu32ZH2y+DuvfWojC
M60ueYnQ4+hbczVLVTnjkofyCbkLxCZHoUyLoVsdyLP7ViOaAZiriZnO5aXT5WPfG
b929o+LO+sP37d7UC0Npl/j9GJeIcLxqZcTO+XBt5tHwVtDOam+H/WW0FuZUOo2bQ
Ex1MM9EKoUhfcTAdD4mlgcLKLqRvZIvDLSxvAxkEsBlSP8pA4QJGcUP9Q4X+5dgdg
UFpn6i94DGA5CLtfzRRtZXQ5ynZyIxOU893kd6ZLQkPhi5aRuBUWDGAktgPfWUphl
+Ht75rsaU2kd1nwaeH/90Aftxzek/bBtSHR7EXK5nBESw41SITuAdVrr6wi5VSqjy
mqeVW0cPax89e26uqnwBTP+RuZ1TOy8eDAV+2KwQKBtS9lIw2M/8e3YsSfcT15tZX
yQ6sk/UXculG71x796oNAdu8mSLrN03WTtO/4qLfIcX2/MunIm0MwudzYa+fzHzWh
nkv8NryrsTKNAKl00WCJWlSJeTgDSY/jHeJUWDzJijkZHA8daZyCOUwL79przBhC/
A==
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

		foreach ($drives as list($cur_drive_id, $cur_drive_name, $cur_drive_status)) {
			if (strtolower($cur_drive_status) == 'offline') $status = VNag::STATUS_CRITICAL/*?*/;
			else if (strpos($cur_drive_status, 'degraded') !== false) $status = VNag::STATUS_CRITICAL;
			else if (strtolower($cur_drive_status) == 'optimal') $status = VNag::STATUS_OK;
			else if (strtolower($cur_drive_status) == 'initialize') $status = VNag::STATUS_WARNING;
			else if (strtolower($cur_drive_status) == 'checkconsistency') $status = VNag::STATUS_WARNING;
			else $status = VNag::STATUS_UNKNOWN;

			$cur_drive_hf_name = $cur_drive_id . (!empty($cur_drive_name) ? " ($cur_drive_name)" : "");
			$msg = "Logical drive $cur_drive_hf_name: $cur_drive_status";
			$verbosity = $status == VNag::STATUS_OK ? VNag::VERBOSITY_ADDITIONAL_INFORMATION : VNag::VERBOSITY_SUMMARY;
			$this->addVerboseMessage($msg, $verbosity);
			$this->setStatus($status);
		}
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

		foreach ($drives as list($cur_drive_id, $cur_drive_status)) {
			if (strtolower($cur_drive_status) == 'no') $status = VNag::STATUS_OK;
			else $status = VNag::STATUS_CRITICAL; // unsure if there will be a "yes" or any other output

			$msg = "Physical drive $cur_drive_id: SMART alert? $cur_drive_status";
			$verbosity = $status == VNag::STATUS_OK ? VNag::VERBOSITY_ADDITIONAL_INFORMATION : VNag::VERBOSITY_SUMMARY;
			$this->addVerboseMessage($msg, $verbosity);
			$this->setStatus($status);
		}
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
	}

	protected function cbRun() {
		$this->megacli_logical_disk_status();
		$this->megacli_smart_disk_status();
		$this->megacli_battery_status();
	}
}
