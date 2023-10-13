<?php /* <ViaThinkSoftSignature>
PWwDKp/NSf/rrEh35zHyCMaO4gcZlVT+EjMyvCDND7VbDr2OfniffusJQ/+G/mPHZ
v2dTvk6poY69nCYrUnsqeEdmeVhi5l++vvSAvpVUZiENZkyRUrTBkPVBhQcT8kviQ
pfuBHklcTOhQqiN6MMKD1fS9MkG5jqelOSvMt3L35tTlQk0Fdkbu8WTp2kqWJ/gaA
aZQzTXCserS7Y7SHpWoVvul7kfnpKWkspryM0l1Ih8fCFhYrB7/mMKTL9TqgKd9Ka
f1VGax1Xm3H6z8OkJ8HfgbvbuvC+zruB9J7uYFCnnrtS5JqL660UV61zkTVLE0KBE
Qd8T2cs8ROPsJpwO3StbG3ZU2rDChfpLVKV7VsEYnQpjyIACwIlu4E2kMTzOlIPaP
hl/9HDuL7vfM8DYkUVvvMtxqvVVHYpIx/AFigxakY2HteLmfekxhL9uRJqX2e4SJH
XWeu731yuH+0/c82rzLbEKadHYtV4XBXOxIlSxl4VYM17RCe0r2/R392aMQ4Ziy9j
dhQo22vBJrtmIutBpH7KaecQUwT+hM8sWas68xwfIMWzFuwofsanyA2iJvRx5tR+s
J0mhHQ08HszAqZ+G8byAuW6z8ncRieLhseu2g91OMHOiuDnxpDbGqqrluXDDGs7W+
0aHGPmIbPTYTP6LGyuqV/Q5gg0tgZh0ESFWLoRh1mzrJMBh6ZYMrsByfAruq9ewdB
FZ8rE3a91IhpbFa76wfcN81lk6QMrrc0crd0Fp359xUacXAz6DEO4mQ/f5g06p6rz
jPLfClhpm1Z5AhhaVZS8AhgHNrXSFchksoE+oruo+CO3QDhcZKygGXntMb0I0KIYR
8hsl0CYfkDD9oaMSXPE4g22WO48k6jKxt3MyCSta4vtnTeH6Quon6PEvSXkRs4ZCa
aKYy8owaVxds+h0lGd4eNBgP/bZZb5nxeMuOlO9lhvEgxpXoksLB8Oh10BMj9EBQT
T2TaEtY1GyIr0shBb/mD5BQO0v+qW3PHKbtDtWccKPxNo5Cx4CPf0AiHoqzj6Sa6C
7MdS57nQ9DQAnwhBPOJ5+tt1LF468AerjjdDjsWEYMmicZjOpPM/9APdJjUe312+9
AkVN9Mz3mVQ8DGpI0wEfDdrHH9IWs1G/0RofVtQo23PxyFbX8S64Di7vwBrlrgrWg
esBRLltyn9hSdqv+C1XEJltlunWo2yM/HggjK9mbq2pNLqgW2r/Ddfhbhg+UnHPk+
3XlC2nyPLzLt9Mpf/3IrfbTl3siOB1F6eQT2cQCa9pKrrE1TJu49oMmpiwmD3xT6P
P1c6cVZn8g3N+MdZEJJknf492Zr1Y5oSx93ofxLFYrh5Mf3CDrHAPOTpPZ9J5Kh3s
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

// TODO: Anstelle "-d diskname[,diskname[,...]]" wäre es schöner ohne Komma, denn dann könnte man auch die Shell Erweiterung /dev/sd* nutzen

declare(ticks=1);

class DiskRunningCheck extends VNag {
	// Currently, we do not accept wildcards ('sd*' and 'hd*') because this plugin should monitor if disks become offline, e.g. horribly fail or get disconnected.
	// If that would happen, the disk might vanish from the device-folder and therefore would not be detected by the wildcard anymore.
	protected $argDisks = null;

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

		$this->getHelpManager()->setPluginName('vnag_disk_running');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a disk is running/online, even if no SMART functionality is available.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ -d diskname[,diskname[,...]]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argDisks = new VNagArgument('d', 'disknames', VNagArgument::VALUE_REQUIRED, 'disks', 'Disks to be monitored, e.g. "sda,sdb"'));
	}

	protected function cbRun() {
		$count_total = 0;
		$count_running = 0;
		$count_offline = 0;
		$count_warning = 0;
		$count_unknown = 0;

		$disks = $this->argDisks->getValue();
		if (empty($disks)) {
			throw new VNagException("Please specify the disks you want to monitor.");
		}
		$disks = explode(',',$disks);

		foreach ($disks as $disk) {
			$disk = preg_replace('@^/dev/@', '', $disk); // accept '/dev/' too

			// We do not check the size, in case the user has more than 26 disks; https://rwmj.wordpress.com/2011/01/09/how-are-linux-drives-named-beyond-drive-26-devsdz/
			// But we check if everything is OK, and nothing nasty is done here
			$disk = preg_replace('@[^a-zA-Z0-9]@', '', $disk);

			$disk = preg_replace('@^(...)\d+@', '\\1', $disk); // accept 'sdh1' too

			$count_total++;
			if (!file_exists("/dev/$disk")) {
				$this->addVerboseMessage("$disk : Drive does not exist", VNag::VERBOSITY_SUMMARY);
				$this->setStatus(VNag::STATUS_CRITICAL);
				$count_offline++;
			} else {
				$state_file = "/sys/block/$disk/device/state";
				if (!file_exists($state_file)) {
					$this->addVerboseMessage("$disk : Cannot fetch state (Is this a valid block device?)", VNag::VERBOSITY_SUMMARY);
					$this->setStatus(VNag::STATUS_CRITICAL);
					$count_unknown++;
				} else {
					$cont = @file_get_contents($state_file);
					if ($cont === false) throw new VNagException("Cannot read $state_file");
					$state = trim($cont);
					if ($state != 'running') {
						$this->addVerboseMessage("$disk : $state", VNag::VERBOSITY_SUMMARY);
						$this->setStatus(VNag::STATUS_CRITICAL);
						$count_offline++;
					} else {
						#$ioerr_file = "/sys/block/$disk/device/ioerr_cnt";
						#if (file_exists($ioerr_file) && (($ioerr_cont = trim(file_get_contents($ioerr_file))) != '0x0')) {
						#	$this->addVerboseMessage("$disk : High IOERR count ($ioerr_cont), but state is reported as $state", VNag::VERBOSITY_SUMMARY);
						#	$this->setStatus(VNag::STATUS_WARNING);
						#	$count_warning++;
						#} else {
							$this->addVerboseMessage("$disk : $state", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
							$this->setStatus(VNag::STATUS_OK); // Note: This won't unset a previously set critical state
							$count_running++;
						#}
					}
				}
			}
		}

		$this->setHeadline(sprintf('Checked %d disks (%d running, %d offline, %d warnings, %d unknown)', $count_total, $count_running, $count_offline, $count_warning, $count_unknown));
	}
}
