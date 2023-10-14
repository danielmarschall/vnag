<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

class HpSmartArrayCheck extends VNag {
	private $argSlot = null;

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

		$this->addExpectedArgument($this->argSlot = new VNagArgument('s', 'slot', VNagArgument::VALUE_REQUIRED, 'slot', 'The slot of the Smart Array controller.', null));

		$this->getHelpManager()->setPluginName('vnag_hp_smartarray');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks the controller and disk status of a HP SmartArray RAID controller (using ssacli).');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ --slot slotnumber');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
	}

	private function ssacli_physical_disk_status($slot) {
		$mock_file = __DIR__.'/status_pd.mock';

		if (file_exists($mock_file)) return array(0, file_get_contents($mock_file));

		$cmd = 'ssacli ctrl '.escapeshellarg('slot='.$slot).' pd all show status';

		$out = array();
		exec($cmd, $out, $ec);

		return array($ec, implode("\n", $out));
	}

	private function ssacli_logical_disk_status($slot) {
		$mock_file = __DIR__.'/status_ld.mock';

		if (file_exists($mock_file)) return array(0, file_get_contents($mock_file));

		$cmd = 'ssacli ctrl '.escapeshellarg('slot='.$slot).' ld all show status';

		$out = array();
		exec($cmd, $out, $ec);

		return array($ec, implode("\n", $out));
	}

	private function ssacli_controller_status($slot) {

		// When slot is invalid, you receive an output to STDOUT with ExitCode 1
		// "Error: The controller identified by "slot=0" was not detected."

		$mock_file = __DIR__.'/status_ctrl.mock';

		if (file_exists($mock_file)) return array(0, file_get_contents($mock_file));

		$cmd = 'ssacli ctrl '.escapeshellarg('slot='.$slot).' show status';

		$out = array();
		exec($cmd, $out, $ec);

		return array($ec, implode("\n", $out));
	}

	private function check_all($slot, &$ok) {
		list($ec, $cont) = $this->ssacli_physical_disk_status($slot);
		if ($ec != 0) {
			$this->setStatus(VNag::STATUS_UNKNOWN);
			$this->setHeadline("Error checking physical disk status: $cont", true);
			$ok = false;
		} else {
			$cont = explode("\n", $cont);
			foreach ($cont as $s) {
				$s = trim($s);
				if ($s == '') continue;
				if (strpos($s,': OK') !== false) continue;
				$this->setStatus(VNag::STATUS_WARNING);
				$this->setHeadline($s, true);
				$ok = false;
			}
		}

		list($ec, $cont) = $this->ssacli_controller_status($slot);
		$cont = explode("\n", $cont);
		foreach ($cont as $s) {
			$s = trim($s);
			if ($s == '') continue;
			if (preg_match('@Smart Array (.+) in Slot (.+)@', $s, $dummy)) continue;
			if (strpos($s,': OK') !== false) continue;
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline($s, true);
			$ok = false;
		}

		list($ec, $cont) = $this->ssacli_logical_disk_status($slot);
		$cont = explode("\n", $cont);
		foreach ($cont as $s) {
			$s = trim($s);
			if ($s == '') continue;
			if (strpos($s,': OK') !== false) continue;
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline($s, true);
			$ok = false;
		}
	}

	protected function cbRun() {
		$slot = $this->argSlot->available() ? $this->argSlot->getValue() : '';

		if ($slot == '') {
			throw new VNagException("Require slot argument");
		}

		$ok = true;

		$this->check_all($slot, $ok);

		if ($ok) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("All OK at slot $slot");
		}
	}
}
