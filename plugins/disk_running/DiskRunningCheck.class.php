<?php /* <ViaThinkSoftSignature>F9z2AfT7nAZ8u/BI8TAx/BdR2s+t778tdYJG4+lJV7Oqve9Zovt7XxsagYPLn4q3acTV8dGL2yaMvIWdPqwpWhu9ssXL9NN81pvIItF5t7GR9NWGS71nHMrr9pjQoIn+R6exKklwpuSMMQgFYKiCh5tUKnaMtEgQm/uQYTRei2UhOeV1s59MJ5/b6TOOcy/NNkO/lYQuflZ+mk5L3VcBtdi6F8sLhoiw4gy+oDDyNpGNSNjiB0mi3CMuQDXpnuAOSv9JluhQMRhQlTfKKaiF+4J7EGKuW3D8lt5FKuF+rGca1Iixtbi4bemQ9RqY3pbgKPg/iNdpxY+j24FcRJ7P6QvHBcY+lb1YL0ss9LqxMOV6DeNg+UOu5Aa1AerA8vfIQ4k7iK8Wt1e+9FXsmGmm84SgJ0gukRNRSzCreb+9glJ52V/PMNZcAiH7Qe7O/bzSeaqHlGIsgghkwt+XEBfyAiemuwBRN5GzyJsMOK7VH1Z+YGcj+V9I3SKkfZVABP6zyq/GwN4EOA2sQ9OCo3PVOcreVsIR+mYTUPfWBB+pd/M9Pu73LnA2WweoeKk/owrrb//uDEfBpCa5TIiDahng2xKaBBZTBJ++mO1kSzBP8X+n5KQToQQCmTzSJvZBXB6h4dqOVm6DikflV62JzOPP2839u48PMIo/6NFheEJnSHLR88f1X0B1lDmAgyc41m1sVk55hiTV5QQ9OJthifgehh9p0kakzY5H1PmltTrwlzBbFFE7BypH1rwyzRCwlXMKlFl74DWAY69ZRs6UaOXC0gbuKJT+blD4dfuEuUut/fQyFYwsFsyPgPzhjAsqnVgHT5IbkBrw2tVZ9NZgraYZjZyW+OFBFMk6fhvuxKzPxJXCHT2JAZ6hTwUPoPfq1GogrDMMmLQ0rorUgVMGuCN/lYtB57ak7QiE6Eczyob8qM60r+m738RysL5Jxg6IPGoT1rtekQKqAFGXUT50jOeTCu6aOSFwLCbisGf+ospZ1POtgFofTPygQJgO2uEkfPDjgkd04oP/p4S9UgIpUDQwlxZkz+MV2ErOSegvpcirTwt7OT2nJNN/FZUE3SOpP6tsRbYH+61kEhmbRK70ebeX6kwF8C6LB+eBvxMgups32MsFQOMucsqmyxV/uz5SM2BLn1giTn13fjJZMhnmz5fqHequgbiJ7vIUjUOpE/ccB6EuRFtirMNlZrVq3J26VEy9s3T9Rd/nV1k+cWLda+lBoLszPI7pRHOIda37RGTbWNPhvxDkG5sIVERQH8/rgTmBK1V/1tabXwDSn6FvzddVKPyXgcXsGefz6TcUMB0c0JMhnRY+/+m/MCQ1mAppVbAQUX+Ha9CJR1xw0e5P+hePww==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-11-04
 */

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
		$count_unknown = 0;

		$disks = $this->argDisks->getValue();
		if (empty($disks)) {
			throw new Exception("Please specify the disks you want to monitor.");
		}
		$disks = explode(',',$disks);

		foreach ($disks as $disk) {
			// We do not check the size, in case the user has more than 26 disks; https://rwmj.wordpress.com/2011/01/09/how-are-linux-drives-named-beyond-drive-26-devsdz/
			// But we check if everything is OK, and nothing nasty is done here
			$disk = preg_replace('@[^a-zA-Z0-9]@', '', $disk);

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
						$this->addVerboseMessage("$disk : $state", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
						$this->setStatus(VNag::STATUS_OK); // Note: This won't unset a previously set critical state
						$count_running++;
					}
				}
			}
		}

		$this->setHeadline(sprintf('Checked %d disks (%d running, %d offline, %d unknown)', $count_total, $count_running, $count_offline, $count_unknown));
	}
}
