<?php /* <ViaThinkSoftSignature>
Ag3defe83bFC1h4qusQeuKtptcZzldvFcdE3Hwhg8gBnQ3wcb2Idoj+tPw5SojcMd
KoRcuTrvW0SjG0wT4gnN86CVdcYjqFdXK64k4xWGP2zftxmsfO1FQm7ASKzOWnWJI
o9ySzrp2jTYQbVoCzFlTsKgQOat7s1IflSA/ykO1iJ5Lp+qirlV1wr99TAjftAFpA
B51ZeoGLckQPFPoZCXF13NZ/QTo4YLcUtn+h1u39MzTdPFerBvQdx1BiXnzmMR7cy
W4yS5mX1Vz+Y4xq8kIcSqsC1eX7bLXlUtkLBC7oPQe/3OP+VoMDz/99MvVNDUeZ3k
9HZQRQKoTClHBxkZ4j41PJxJwtZEK11e0CbQQ0xci2cOjx0yBnQ3cAtB+Gg2O+910
4K0PhRJ5990+wYjZ62LNyMEu+ri+7P0PW2x3eXY+bh4GLfdNcNxIeZffDzT32EhNX
Ore9Qxbr2HYru131rtvwI8K89icuT3yIuPEagMArGqcDoCYdssLF1eEVqnWRYdRfU
O2DCVfh5qvrWasV9C38z6RyGT6CJYv4+k1sk4WDXCC1se+lDBKnRpaVBuJHDMjcjn
KvrLjAderDel+pTGvZ5Ht69em/YQHrdPom6Cpl+8yjhk9XqS/6jh+HFuPxQQBcYwk
z/HMTnCNFE0vF0v1UATA6tfT4McqNYfDQtJengQ8n+XDFKx+l4g+0xSNB2AAbO3zP
vplioHVfZt0zaQR0JQD1QPp529vOCJnHXAASPoRwvdJVT9mMbx4gbIbbXdxZkQGju
e7UeWByNLFDML53vWTA6vel413YmZbFXhFIrzONiVHaPYynmwqu4lXqmGkZW3NoPB
7yT4rsV3cWsxHlGNlF+La2RX8y2aHvAA/Q/HoOL1f4NUlQINe5ms0inQu2vuMvjCu
GeFmfuT5QQ5pUzneXDgkxtEfy4mP51Obko5ZiXmKB7qaBDl71dFO0mGf42f/YVvn4
x5I6K6Wb0/Qrz/zdZArWwtrh4FBbeOG56qtIo7zeJ7NVpZoelwmFlBLBkiF5tebGD
5zLcP5DtQ4JRNvT1PnkW4TQJxe+6RjSNuo8hLD1fs/gyJUeZJg8eux43axOgUgtJG
YLpE4CjN0HgcqNVKRTPpOYawHlfqR8Ua0wZ0q2b2qGRG/0kzRprlaY8eaCZldZIHs
HNyHC04JkFGVFdnUVI9a1XAI9aHn4Oeeim0Qg/Pjr64tiD7PIoEmmAp1OUmqQDcGv
DtfbxZoxjAB3SPYWVhMckmktRsW2fykBmQVS5z+FNYG31w7fnbCIoyb6eE5pAC5Yf
wznGurziUwinPFP7zSvYtAhyt5aUca3BGfkvnkDW6b2folcc/KmLxMVTJEGvMq5Eb
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
			$this->setHeadline("Error checking physical disk status: $cont");
			$ok = false;
		} else {
			$cont = explode("\n", $cont);
			foreach ($cont as $s) {
				$s = trim($s);
				if ($s == '') continue;
				if (strpos($s,': OK') !== false) continue;
				$this->setStatus(VNag::STATUS_WARNING);
				$this->setHeadline($s);
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
			$this->setHeadline($s);
			$ok = false;
		}

		list($ec, $cont) = $this->ssacli_logical_disk_status($slot);
		$cont = explode("\n", $cont);
		foreach ($cont as $s) {
			$s = trim($s);
			if ($s == '') continue;
			if (strpos($s,': OK') !== false) continue;
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline($s);
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
