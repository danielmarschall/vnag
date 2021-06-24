<?php /* <ViaThinkSoftSignature>
c9r/fj8IW6Nf5LoeSJPY8SFXmPThJNj45cxTdW+9Iot0ccSLSP0kE5U7z3fKByUCa
9bBmWXRQ38Pv6Ztfmw2mGneTXNwcWAud4NRRlRGSJ5mX3Kn6Db043oqYjl5EAV7CO
NTXCI1NK1go/2tIh6Cl3OMRS4dyV11F+krGrg6T1+teNJr2zpniNyWDyVImOAMJXt
VGL6HcXQjApsQ8/x2pzXwFJ6jFZPLZVNLbB5/mhLqiAgemgQAVIGSyJlBmYlhcoZB
/lCaC4RuSrVqr87d4VxREWMtaRSh0dKShXlmG85gaMnVF3eRGo+yUsw1OutIM1OPv
EEYRbmbZvnbGjGVnf1uP6gpeTzPQHIp+kFFLDdFus5AflovPYEDi1UHT/Ez4B6lZ4
HphJOzB5kK4x1/Wurwlzfx7exGsmFElRmFxMD0wXahOpWcOYQ39RcrIrcg1RlEAfM
nLZRG8BUyRcaefS72jgmOTV/VgB+Ls28AkVdgWOS8eCb4egM7krg7kHHwCDHzuKcV
S7v03mxZi5QeMEXABJqJUqPN+09xfnkuYkXIRcDBG29Nj8wp3cmt8ND8cCjLR19fa
KCqKVf6u4V7vw2asKRPyYnMc3FQitwmIxDGsPtREg22wJ/R8zV8WfArNXkcq2Y6tZ
ws36ogjZ466SI9EQyS+YYay8Tln/wZ9uY7wZf7/L9hAAC77kEhViJ1BfAKXA7O/Lt
+vshYbv8H0gpkhyzgqielK/Ke1xdAiE0NujgBUuOjAWT2/WFj60ln2WRJcwVytJuK
Dy/VhhrYWkKArp7MNB7YI8Gj/GIvCWCGuIF7+H6tG7aNRmJoC7Tzns+4VQOhIztXr
rdPcPjo9e3T0zxNbNYKAwxy4QV/cymhoWP8etvPOfycEMhR4BKei+xBAft0TWVKFT
gxPZQg5y4ZyvpLXRyfwdrU9nncj6Gt4ot6ia7F6ctHWqvOD4y4sZ7QMP8mYsVA4Pi
Bf388N0mTdmmlr8Zvyzq4TD+wtioX/LmynDYAVDRcoQJOhVsSUz6LY2StolcOlBzm
rqBsiXEsrgLveb0Vtg93PqGCBRshKWVwOa/bZEBc8zv+4Ia36hL0bS4yLUMvllYeG
+K4sJI2neWKBT4PWJSx7UTKZsMDO3g7MCimfC/2EvVQp3M2Uu49QNzPUTzlHVZvVr
i1kyEtYaCztE2LIDOFXeeLdvz39sbloz+4/o9TyAsUd+MEXTns0SjA965Vw8bMM35
CLky/HVnfGoIEo5ekhyuWrdTEgoCc2bQ7XcdoyU28IuqshbRKvdKFqAHeW8en/et1
Kchk0pi+oQa/68RMxZDpJH6WEMLESPA99Fn+OfoG+j4qex1lCtzjUgvGzIgK8QZ7q
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
