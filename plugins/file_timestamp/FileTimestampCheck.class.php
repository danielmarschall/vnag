<?php /* <ViaThinkSoftSignature>
Pvh08nOB4+wOw/RYGUvi9TRprIwSRd60uk+/5xYr4kuU9aC8i3NojUOlHc1kY3o1x
m8zV1VPbyrXggHd/2gGxEP+JjQrrrFTiTfoOaZXnsqNnTkKak5hXvm7KedBcwMjDG
ph8jucKz7z4BovNE5AQ8A/qUSuur1AqsFPXOtsW+CS4MDnzYq/54k49V1wN7qHZ0b
+Y2pyNq0wf5LmQdPZSB39v8lOeKQRIlDtxVxMKlrkJF1g01PvE/GUSy5A5A7UDaYg
/UCDzq/nEtq5Catg3S4m+M+ftIpfw6PktcMboDNXtQnyyj3shWUmn3e2lEt2WzLqC
pMtNi0w1KOfO6hAg5oox/H97VbumMb24kxv4GaJSXmU38qjfmIA1S/OyBVuxRcPXO
gm0LTlAihLWg/nPKs3+819w0JkuxG2GJj+q84iStMcI8bYOE3N/EI3s+QbAltL/xN
4q87ZgS3WRKyY7swTD9HVLwEi/whfraYgfs3y93wV3Leh4u0fD5tuVQ2xSuROxKAc
9mwiqllxuBj/6Pp7XKhWWlqmmCuSW+bULzOKVwh8KaHtoOSDRYeVY0c3nFneODZBa
1malWf4MfeEvGMDiaXnvhXhA5dpPU6NPKTxngnz6v+nVpArc/OWYH9qAD3vImKy2j
bOrja4pR5oIeYT+bfbCxQ7l7vagOFswApiom+oFx1Gelmsh90SfMhmlN16lpgYLo4
+zJYbY82Oy9fhaTmn/yA3PPT0XhpJHjLAhjSH0FN9wUC+e9Xh9/BSB9lJP18OP/u9
jCvW803IuhAHiZwuGLIs55utuY2DITTpce14+R48YR6/ArsRcGodBrDko7Rbqw54g
WreaJCKAzuKngOJ7o7X9ET+Cl0aJV02b0W9UfLIljlHqMTXOsVzAQ5fla22pahToC
zU5QuiCcczEfGTtNKudK7ABhgYa5slr+p7q+nu8e0q8xFkHtT+rcfDshdg/hGNNuK
lYIX/1XBJ+2K+Jrx+a4jh2kt0k4VTcwmIYKHlL0s66HlHTfiyjdeUkBS8GfAkLmj4
J4/SSNb98XH/si+xoSsAUcJzgjXUhZcJLnS9icqLctPr6lARsyHE7sEyt+GcfxFxK
wG2ya3rMarVhmLW3XTdr0qLYE0Eb96PTsU0VUkxK+lNa6aNNqQarmdwSmRJ09dKDw
4wB6rmYO5Yk7v5Pkqtc6ulTqSBALsyHBv/lqIDM4htUOPfpGDUxtXumd9fM0uDAXW
/grWyFmIRTVw2csDEHpb4aZA1tOtlT1luw8QmWg0tbmWA06RgUNV6v/VuDWgJLXOI
OrR4n1izC/9ierFHNCyRxdzE2kVOhewObOa+2UJP+7HuChHdAa+cyVgmQe5F0615X
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
 */

// TODO: New parameter: alternatively check for the creation timestamp instead of the modification timestamp.
// TODO: vnag soll der timestamp monitor auch warnen, wenn gewisse dateien nicht gefunden werden? im moment macht er nur einen glob() und ignoriert damit leere ergebnisse

declare(ticks=1);

class FileTimestampCheck extends VNag {
	protected $argFiles = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwcv');

		$this->getHelpManager()->setPluginName('check_file_timestamp');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if local files are within a specific timestamp.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-v] -w <warnSeconds>s -c <critSeconds>s -f "[#]<mask>" [-f "[#]<mask>" [...]]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argFiles = new VNagArgument('f', 'file', VNagArgument::VALUE_REQUIRED, 'mask', 'The files to be checked. This argument can be used multiple times. Wilcards may be used but MUST be passed as string only (not resolved by the Shell). There are two possible checking modes: If you put a # in front of the file mask, only the youngest file of each group will be checked (use this mode e.g. if you want to check if a downloader is regularly downloading files into a download directory). Otherwise, all files of the file group are checked.'));

#		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
	}

	private static function humanFriendlyInterval($secs) {
		$out = array();

		$years = floor($secs / 60 / 60 / 24 / 365);
		if ($years > 0) $out[] = $years == 1 ? "$years year" : "$years years";

		$days = floor($secs / 60 / 60 / 24) % 365;
		if ($days > 0) $out[] = $days == 1 ? "$days day" : "$days days";

		$hours = floor($secs / 60 / 60) % 24;
		if ($hours > 0) $out[] = $hours == 1 ? "$hours hour" : "$hours hours";

		$minutes = floor($secs / 60) % 60;
		if ($minutes > 0) $out[] = $minutes == 1 ? "$minutes minute" : "$minutes minutes";

		$seconds = $secs % 60;
		if ($seconds > 0) $out[] = $seconds == 1 ? "$seconds second" : "$seconds seconds";

		return implode(", ", $out);
	}

	protected function cbRun($optional_args=array()) {
		$this->argFiles->require();

		$countFilesTotal = 0;
		$countFilesCrit = 0;
		$countFilesWarn = 0;

		$fileGroupMasks = $this->argFiles->getValue();
		if (!is_array($fileGroupMasks)) $fileGroupMasks = array($fileGroupMasks);
		foreach ($fileGroupMasks as $fileGroupMask) {
			if (substr($fileGroupMask, 0, 1) === '#') {
				$fileGroupMask = substr($fileGroupMask, 1); // remove #

				// Mode 1: Only the youngest file of each group is checked.
				// You can use this mode e.g. if you have a folder with downloaded files
				// and you want to check if a downloading-script is still downloading
				// new files regularly.

				$files = glob($fileGroupMask);
				if (count($files) == 0) continue;

				$youngestTS = null;
				foreach ($files as $file) {
					$youngestTS = is_null($youngestTS) ? filemtime($file) : max($youngestTS, filemtime($file));
				}

				$youngestAge = time() - $youngestTS;
				$countFilesTotal++;
				if ($this->checkAgainstCriticalRange($youngestAge.'s', false, true)) {
					$countFilesCrit++;
					$this->addVerboseMessage("File group '$fileGroupMask': Youngest file's age: ".self::humanFriendlyInterval($youngestAge)." (Critical)\n", VNag::VERBOSITY_SUMMARY);
				} else if ($this->checkAgainstWarningRange($youngestAge.'s', false, true)) {
					$countFilesWarn++;
					$this->addVerboseMessage("File group '$fileGroupMask': Youngest file's age: ".self::humanFriendlyInterval($youngestAge)." (Warning)\n", VNag::VERBOSITY_SUMMARY);
				} else {
					if (($this->getArgumentHandler()->getArgumentObj('w')->available()) || ($this->getArgumentHandler()->getArgumentObj('c')->available())) {
						$this->addVerboseMessage("File group '$fileGroupMask': Youngest file's age: ".self::humanFriendlyInterval($youngestAge)." (OK)\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					} else {
						$this->addVerboseMessage("File group '$fileGroupMask': Youngest file's age: ".self::humanFriendlyInterval($youngestAge)."\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					}
				}
			} else {
				// Mode 2: All files of each group are checked.

				$files = glob($fileGroupMask);
				if (count($files) == 0) continue;

				foreach ($files as $file) {
					$age = time() - filemtime($file);
					$countFilesTotal++;
					if ($this->checkAgainstCriticalRange($age.'s', false, true)) {
						$countFilesCrit++;
						$this->addVerboseMessage("File $file age ".self::humanFriendlyInterval($age)." (Critical)\n", VNag::VERBOSITY_SUMMARY);
					} else if ($this->checkAgainstWarningRange($age.'s', false, true)) {
						$countFilesWarn++;
						$this->addVerboseMessage("File $file age ".self::humanFriendlyInterval($age)." (Warning)\n", VNag::VERBOSITY_SUMMARY);
					} else {
						if (($this->getArgumentHandler()->getArgumentObj('w')->available()) || ($this->getArgumentHandler()->getArgumentObj('c')->available())) {
							$this->addVerboseMessage("File $file age ".self::humanFriendlyInterval($age)." (OK)\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
						} else {
							$this->addVerboseMessage("File $file age ".self::humanFriendlyInterval($age)."\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
						}
					}
				}
			}
		}

		$msg = array();
		$msg[] = "Checked $countFilesTotal files";
		if ($this->getArgumentHandler()->getArgumentObj('w')->available()) $msg[] = "$countFilesWarn are in warning time range";
		if ($this->getArgumentHandler()->getArgumentObj('c')->available()) $msg[] = "$countFilesCrit are in critical time range";
		$msg = implode(", ", $msg);

		$this->setHeadLine($msg);
	}
}
