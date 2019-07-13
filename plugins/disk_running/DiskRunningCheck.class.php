<?php /* <ViaThinkSoftSignature>
XttbmAE1XLvHSvNNxaiyHGgCkmXlulTzP0UUheNlN4Shh2EuDZLnziCYMWWUP6xeH
Ea9fAVKSpKzuFHKTvQqe0FMPKrvKiFAKDyjdeZPsRZhp5PwsWjhZlEGpUaKLnIgT3
DhGMd0MWbGIIzJYj9pKLG265VFutV0qXHqSf9Ce535o1aaWEZ8VWClrx3kewFOnuY
QIIbfYZ3duHZOmJezdK4LgNzclyPj1XChygvaf+2IjhGIXUCkaCBpLZ8CNY83ysgf
jI7opKehfNbUHGAfr+MxaNxrBQ3iZodlpWV/VqyPZ7Ezo+dmVY387/usE8jMSW9+S
ERjNyYsLaNahVy5nuLReIsYWLbIbe9TS2u5kJuB8DR+5LVVybNgd72FXLFUs1OL2h
T0CQJk5x2UgoWAw1dZWyQRXeGS63oT8yP9TQZg56bQ4vmP+avZKl+x2CZQEt3hVml
QiopH2LIgDCaPNK/4Q+dSDAhDx87BlRS+f/mgyLu0ZBLh/FsjinQUetWcXabnoiIi
NHyryNr2hAa7h+gBl9bwnmJU9mJ6O1/S/gNIH+A/uvHPKH93sjnOhly32vmcSxKoV
kV2PhHackZolMJ1SV8rJ5wVhGVp6xPzkRsX92fuUlJSno0Hgef/Oqkd04MUQ0NCFf
pggyHSDC6zlC7fieaOnBW4Ky+2lBj49SwRLW8/Eyjfd1IXmi//2lQ6xL54PJ02sP+
alXUqIR8+baWRPVlP2ueEGOoLqGiCdTaGBQGNiJF9G+vwFdr/psdDMtSU+k/XyQXB
lf43zkQaVKuLTCG1Nkh/EC42TaRpviJUVHfXBtLAwhWkLDaP0JEEST7xaA3+h6Oi0
hxftNtQhFkxKGWQW3L6sttq6cO5S74HKqWUSUAidXfLLZ4bDV8DxOXsF6Yd6sUfGo
5/LD5sYt5eNr9k42Yu7LRQlmAne3Q5HuBQeVfNe7kGmc3N6PLWI8bDyfWWCGKvGkr
OFLS/eHpLOGM8OKSwbzJioGct5/lR9dK8PV+udS+tkyiIAdTenIqULKP1PtSvC7rs
kS1NDYcsW8osXnO6nuffPTuQBYKI8xFHuy9Ur1rb7aPlBqt4nqPOJiCbSqubC7WM4
clXTzpV98k8aVPGbxpb3ddWtZ/0gEr01w6UeOJCnqOBvsCXmOjL56Q+JEDBjQNz5T
REjzNAF6bfiLMUZ+VZHU0l7aqDpVZzY6urKanC2VatuQ8KnocJb7+mYd2dwa2Faue
L4MeaMmEb2ckq5Gi6TpNptJQzS+k8AFOqXlE7k6+ksq/s8A/5/G9IS7b6/VWlHkBn
FsS+wXGpFoTj4VxA78iODiTJv3U6wW9OdRj4JyxSWzHtXgH1cmL33pxzDIgGvT70B
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-12-25
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
			throw new Exception("Please specify the disks you want to monitor.");
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
					$state = trim(file_get_contents($state_file));
					if ($state != 'running') {
						$this->addVerboseMessage("$disk : $state", VNag::VERBOSITY_SUMMARY);
						$this->setStatus(VNag::STATUS_CRITICAL);
						$count_offline++;
					} else {
						$ioerr_file = "/sys/block/$disk/device/ioerr_cnt";
						if (file_exists($ioerr_file) && (($ioerr_cont = trim(file_get_contents($ioerr_file))) != '0x0')) {
							$this->addVerboseMessage("$disk : High IOERR count ($ioerr_cont), but state is reported as $state", VNag::VERBOSITY_SUMMARY);
							$this->setStatus(VNag::STATUS_WARNING);
							$count_warning++;
						} else {
							$this->addVerboseMessage("$disk : $state", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
							$this->setStatus(VNag::STATUS_OK); // Note: This won't unset a previously set critical state
							$count_running++;
						}
					}
				}
			}
		}

		$this->setHeadline(sprintf('Checked %d disks (%d running, %d offline, %d warnings, %d unknown)', $count_total, $count_running, $count_offline, $count_warning, $count_unknown));
	}
}
