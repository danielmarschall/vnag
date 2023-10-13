<?php /* <ViaThinkSoftSignature>
uj6Nn/5AixWCYFnSHOzNN+6Lcdw/Lq5AZkZbw5P4dDOh20Fsd04emPXxNgG7g754j
qEnVgpmfgMIh97e/MpIvUr3RxglnNvz+ZiJywZZZGQj/wN3rHyn7UsjOq1CRts1LN
qG/NObM5JVTBYCZkjSK6U2lxmpTgKZgwAwfSaSvp/KIChenFdvAVYmCMU/kJddMp1
b44Y96U1JziUMe4VoMnCFzeO7YKHmlfmoJJKvSEQOOKBKcWny0yqh/uv8mK40F5Wg
OP3zBBp+vMrvihBFvHJzewwBfud/FXHFWhsSuAj1NzllmDJ0G+59h52DYzWigoOsJ
HeqVo3bsqLIJdeM/vZsZkZfl3uDBtK2brEUAKsu09s/k2uz+WwmGPu7Nnk8z7n/qo
R6WIWABQ8/8ltd9/GF2uuaeUH4I/vfad8rbKQz9bkhtpIhZpHbXc5UdxTqdzG1z2e
QRpiPkWuqCTPV2bAMq1IJ0W0A37Q0lDE990O5UXWYnvG78MVk01IBpJnoJswXvHkT
69H4aeFEWaQbqoAkELbua6CEXruKCVKAK7t67Qfv3yoNSMD0IHvHpKQc2TRG89mAf
WY1+h8AY3WNHAsjRSeS5p6cPaU6kfcF6vDfY+j20KYdZCyTd0tm4QTz0WC7ONz5aW
ZIQT8U/g05EYJgwWL533V3QcgNFgL0h/ee3LtKAHE0e16q0OkZWx8ZxN82zh3aJmc
c88lK4zzbKpMSi4vYcmlfpnCQIdMuq5NKWDcB5xErbKaLNuTnYYoKNxSnMMTwbVkG
efEzMBp4j6cfA5tYlyldW+odwRjvJyKyGtfXQspeuL0hPeG7978+L3bPIDbiWdYlR
a3md3Nibaxf1IW4pW7WL8QvHQlp1EV1ji/Fj0en44+J9+vdVRIimXBSZcy20sxuP/
vFnn1QY1Vu88dAGgtaFw0JA7yam51QN8mwxb3LHHaouac596PaogZxytkbSdLEkYt
/ERN0rDeV1z0c+IkIbUFeiYsZGMWQJOYWgxpT+2G9RIrtKTvws+ys7G0nJPMd9uO8
9zIJCNwoxJIdact5pQ+JM/ty14u7i3QSX6QpAQ51FZyzWjYtpk6VCwplrfgD7qnW6
Nz0OKVUszbNLvoT40s5rJR00AYY0vdhppa840Mx8gpZRmf44aFh37sZ9LwLAYSM2V
XCxzpoH3/Xd2zqhvwXNBXV4gifLI9igRJOrpLA6lskdDaTfeNmmcTKKMaayX8mg5R
H0i/e+ZHyuKpVEYdoee4wFjX4k0UIsUYvKH/VNVNkRnm8S0ENYv+RQcPv8xNaHiwL
zRPKlv2dUaWmz0d0oOrYRbgesS+TLEMxIp5zGx5qlKqLSz4xW9EPm+kJFTYP2HGiW
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
		$this->getHelpManager()->setVersion('1.2');
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
