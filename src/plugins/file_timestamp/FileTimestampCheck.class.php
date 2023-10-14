<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
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
		$this->getHelpManager()->setVersion('2023-10-13');
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
