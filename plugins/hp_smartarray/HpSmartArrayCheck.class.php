<?php /* <ViaThinkSoftSignature>
kDLkg8lpkGUPmjU+Y6sWh3rOd8GSVnmq9wUcKJ+7XiVweZiNjoyUTZvAw/58fhWmH
N2+qCMoFCeFwxbBVxgROA31xaIDWbWi9i+iYagX9OHF+BA5ZAHhpHscbOxaCiN+iN
FualKnWi5rgleimA4mvhvaDRHXOw75McTTWssIuxMU/SaHwNKYS8AIDj5CPtlXMdV
Hd+yC82qk0hRAnlptRT9wc/J4NvmB3r02pSIOk0x5OqdyAID4ijiXLy2erM7UMwIY
MRwWHKIbxDGW4YgaP3E0kYp/ARenaouE9dKmzeBAmuLDJRs9gXm9GMQMbUwjdQIA3
qC1eSYAbojcwAH8cjmfDOeQt6ro6igMKBAzZEqRJKOk9LGpeP2mTg8zQzYX3ImST5
KmVW7KHLtvxxCs9fLyZhrwbt98Etx9zhd2ObeS5RFzJV7JOoEc9wHbtkCWNhFAUqg
PEzB1kpjhibpA23aZZL2Zz99TWKRqscxy2Rzk1U6eIHt6jhQY6sQHjJN8rgZIXHiJ
QFQFKF8COYgiO4a7UhKbJKvaeQqaA91NZI9mvboyNUQidi9PUV0stzV7p6XTJEFrq
WyyQdqrsWJ7jtvBl+zMjwMmtFlnWH51niI8KqhiOXdHN68Kc56FhPN4EBiadriXxN
LioxFCijfjn+748jXRBCjE//raAvvvMuW+SATPMQ3Nz7qqsFaqbhcvWMW8j1aGo2i
7h/lRGuSgFG+WUpJ+N7VTVLVsJBYq8lHsMdiqOtyK4FqCLLXSbxiFKi09jEaCUsnx
p7sjiVts/+oclV2QyPJ1ZvXDZCd4D9cXFBMRmFFd8vSV9DIZYavewd2NgrJFqGvAU
fzZTI7G88b/Zlq8aoTT/iueMg51yTGJb+yKKq8jDf+SS7YnrePSrUBC87oBZQYlmz
Sk1HU1hek7JRwL/8HOv/lK8Rv/V2Kr6Tykl0dG/Cha0M3cr8SCIYRFx6wXnecXLxX
mqZns0JHW8VCyzStM69o0P81dKnKTV1/PgWMeGtx9ZUL8DqZUQobHOiU1lfvclsLi
XnrNBkCDc8Vvfxqs14Nw1AuTRRhWAF8SXremC2nxTsvOrjDk/xqonDosLN18U/QLY
sdxoGGbgZ4sOb7IxjkdpzA9Y8xIuAZrs+w+uDHPpm986G4Ey354eQW+lmtE2CZmIb
wwIJwpUJxhpsT0KZkn8GpGhYt8V+xkvg5UIlexHLXT9m/YX+Q9x5vG1Q0z2Wx+hlH
acKXH7LKVVBeGwgyCHr7Isc/6ZFc6ft4MBXDuji256Jrur8RTIUmHMUSOD22Q8kBr
bdxGFSsI1eigrjuTwSOA9wGahPGgJgrfrUxRZJoGqttuqq8APlRYaOck1ZjoKPLQT
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-08-06
 *
 * Changelog:
 * 2021-08-06   1.0   Initial release
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
			throw new Exception("Require slot argument");
		}

		$ok = true;

		$this->check_all($slot, $ok);

		if ($ok) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("All OK at slot $slot");
		}
	}
}
