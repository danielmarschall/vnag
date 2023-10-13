<?php /* <ViaThinkSoftSignature>
AhLH0uzYSC5/Au2X92QY2namTtPWujFr9iWDdPhrgAQq1MW3Z53fKr6eIt+kxjb5R
qMOZzG5gnnhp9nUKZgYDaK+Ic/5yumhv2ekVVZsbW6FSogpfDUTlXbJSqUby+TyYk
OSXj09oHiudrshLdI4Lx5ns75g/tzdR+9DPfNbWxEBFV1kVOtUM0MFnW31u56pHwm
D3oZcWXplxNDt+5sm6K1AdWXAswslMzKwaGIrKHFJdRt1mFmAp2uYBRVz6DQ0MeyN
zR9dMzrO7dgUrwRsxLPp3+hK9veENwcMwRXhKqVtJ6izTIgDP04hTsWeIaluUdMoX
UXX72/y1fh4F3jCDeWJ2iXVFLU4W6Uf7L5S3FR41+uR/pw9/6BALuuDLrA14NDiv5
Q7dqRtD9cJT270Ppj/6oCbdv2FtsUogSFWpd+Ca3v2IHQ9Vhsz55OJ8mlqtAkJ+TL
GXc46tbwi2OYHk/Iqq+A0pIgW9/1aKAeLobK0y9lgHg8zg0YXkBt3fF8ZwZvI4cnf
qUhnzvJ9F8kB/23hiSZVZFovYRn/kDTTX6JGdHQWCXWSU7EI6/lIc6dKBNJF3JZnJ
4MkKqcyLoPxnQlpG8U71bOQfgPRwpmgaTI7b9Ay9X4Y4dLah84LRxpMLGg0EuQxZL
F/NVKqf7wwgGclyUG4oGI+dyCed8IIvEvq8C8aKdHVtZB8pvPv6UfhHj26LCk+UeP
aViNRuif6TrlkYUiEzbKbfpSrQcKvWavd3QsTZfbnivflxLayrahVVebEPNOhdGDR
fyporIb5EhtU5CuPxqCYDhPNjNFOj0C/JJlyf6a/WiGr6TNYjkYp/jdJhBrvDk6b/
d7Y+TdUQaotgtLr3usqTSqoEHZrdqkcdUldB7hUHXzvYogZVYVKtuqlKRn96Yc2sc
3KR2ZAi+Pl3W52pMVzPlZ6zy8UZgmiuDxXuGEJ8hwcVijcTE7WV6P/fAZ7bu0LCKl
HsBFfSRA1ke3hZWtWRisKGpsHhXPTM8ShAHwo6nr56gJnc5Lb1scqfhJRC41V/AtG
4UfvpOTBJhL7wqqM8iIlZxHvGuTOH8b2NgVdkhTEmYxj69ZRZEescAB9znklUn4lF
XD9uW9ZvKwZXhdQ6lTEGcxI/CxGAEo3AcsFTsebK3BP1ugGcg+mkG4gU3/XKB6Hvx
x/3KogBF7tu2lWwPfHiV7F5cUXPpx3H8UykwFdNdQAsTKTWl58LDP/efpvStP8TZ0
kLOMxXXxhdVeIelQQMSsGUQ0Lz/7T4u4G7X9KdsaUrNw/1Btbr9vPRQ7ZkuXMVOzV
VbcCJsanRk7isRkob0574FyPyvMitdTZKC9lDOLAqbYPeaUQMsvoNyFXsvLlfJPRS
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

class MdStatCheck extends VNag {
	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

		$this->getHelpManager()->setPluginName('vnag_mdstat');
		$this->getHelpManager()->setVersion('2.0');
		$this->getHelpManager()->setShortDescription('This plugin checks the contents of /proc/mdstat and warns when a harddisk has failed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ (no additional arguments expected)');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
	}

	private function getDisks($device) {
		$disks = glob("/sys/block/$device/md/dev-*");
		foreach ($disks as &$disk) {
			$ary = explode('/', $disk);
			$disk = substr(array_pop($ary), 4);
		}
		return $disks;
	}

	private function raidLevel($device) {
		$level_file = "/sys/block/$device/md/level";
		if (!file_exists($level_file)) {
			throw new VNagException("Kernel too old to fetch RAID level of array $device");
		}
		$cont = @file_get_contents($level_file);
		if ($cont === false) {
			throw new VNagException("Cannot read $level_file");
		}
		$level = file_exists($level_file) ? trim($cont) : 'RAID?';
		return $level;
	}

	private function raidState($device) {
		// mdadm outputs "clean, degraded", but /sys/block/md0/md/array_state only outputs "clean"
		$output = [];
		exec("mdadm --detail /dev/".escapeshellarg($device)." | grep -e '^\s*State : '", $output, $ec);
		if ($ec == 0) {
			$state = trim(implode("\n", $output));
			$state = trim(explode(':', $state)[1]);
			return $state;
		}

		// Fallback
		$state_file = "/sys/block/$device/md/array_state";
		if (!file_exists($state_file)) {
			throw new VNagException("Kernel too old to fetch state of array $device");
		}
		$cont = @file_get_contents($state_file);
		if ($cont === false) {
			throw new VNagException("Cannot read $state_file");
		}
		$state = trim($cont);
		return $state;
	}

	private function check_disk_state($array, $disk) {
		$disk_state_file = "/sys/block/$array/md/dev-$disk/state";
		if (!file_exists($disk_state_file)) {
			throw new VNagException("Kernel too old to fetch state of disk $array:$disk");
		}
		$cont = @file_get_contents($disk_state_file);
		if ($cont === false) {
			throw new VNagException("Cannot read $disk_state_file");
		}
		$disk_states = trim($cont);
		$disk_state_ary = explode(',', $disk_states);
		$disk_state_ary = array_map('trim', $disk_state_ary);

		$status = VNag::STATUS_OK;
		$verbosity = VNag::VERBOSITY_ADDITIONAL_INFORMATION;

		foreach ($disk_state_ary as $disk_state) {
			// https://www.kernel.org/doc/html/v4.15/admin-guide/md.html
			// CRIT	faulty: device has been kicked from active use due to a detected fault, or it has unacknowledged bad blocks
			// OK	in_sync: device is a fully in-sync member of the array
			// OK	writemostly: device will only be subject to read requests if there are no other options. This applies only to raid1 arrays.
			// CRIT	blocked: device has failed, and the failure hasn.t been acknowledged yet by the metadata handler. Writes that would write to this device if it were not faulty are blocked.
			// WARN	spare: device is working, but not a full member. This includes spares that are in the process of being recovered to
			// WARN	write_error: device has ever seen a write error.
			// WARN	want_replacement: device is (mostly) working but probably should be replaced, either due to errors or due to user request.
			// OK	replacement: device is a replacement for another active device with same raid_disk.

			if (($disk_state == 'faulty') || ($disk_state == 'blocked')) {
				$status = max($status, VNag::STATUS_CRITICAL);
				$verbosity = min($verbosity, VNag::VERBOSITY_SUMMARY);
			}
			if (($disk_state == 'spare') || ($disk_state == 'write_error') || ($disk_state == 'want_replacement')) {
				$status = max($status, VNag::STATUS_WARNING);
				$verbosity = min($verbosity, VNag::VERBOSITY_SUMMARY);
			}
		}

		return array($status, $verbosity, $disk_states);
	}

	private function get_raid_arrays() {
		$arrays = array();
		$devices = glob('/dev/md/'.'*');
		foreach ($devices as $device) {
			$ary = explode('/', $device);
			$arrays[] = 'md'.array_pop($ary);
		}
		return $arrays;
	}

	protected function cbRun() {
		$disks_total = 0;
		$disks_critical = 0;
		$disks_warning = 0;

		$arrays = $this->get_raid_arrays();
		foreach ($arrays as $array) {
			$level = $this->raidLevel($array);
			$state = $this->raidState($array);

			// https://git.kernel.org/pub/scm/utils/mdadm/mdadm.git/tree/Detail.c#n491
			if (stripos($state, ', FAILED') !== false) $this->setStatus(VNag::STATUS_CRITICAL);
			if (stripos($state, ', degraded') !== false) $this->setStatus(VNag::STATUS_CRITICAL);

			$disk_texts = array();
			$verbosity = VNag::VERBOSITY_ADDITIONAL_INFORMATION;
			$disks = $this->getDisks($array);
			foreach ($disks as $disk) {
				$disks_total++;
				list($status, $verbosity_, $disk_states) = $this->check_disk_state($array, $disk);
				$verbosity = min($verbosity, $verbosity_);
				$this->setStatus($status);
				if ($status == VNag::STATUS_WARNING) $disks_warning++;
				if ($status == VNag::STATUS_CRITICAL) $disks_critical++;
				$status_text = VNagLang::status($status, VNag::STATUSMODEL_SERVICE);
				$disk_texts[] = "$disk ($status_text: $disk_states)";
			}

			# Example output:
			# Array md0 (raid1, degraded): sda1 (Warning: faulty, blocked), sdb1 (OK: in_sync)
			$this->addVerboseMessage("Array $array ($level, $state): ".implode(', ', $disk_texts), $verbosity);
		}

		$this->setHeadline(sprintf('%s disks in %s arrays (%s warnings, %s critical)', $disks_total, count($arrays), $disks_warning, $disks_critical));
	}
}
