<?php /* <ViaThinkSoftSignature>JvUe2EqrSTpzUAa69aGVuvQPVhC4dS9OgSJw+lNJLLFBnB10I8C8EVE5DcrC1/bQ+uF7qNulGCHJktHv8GEUhS79S7VzsuwFo8N1GXxRuanRlk3uCrPtOx8kvgdUesL8dmfCKQAxGHauSogngnj3qyN6PvcKbcBpp+oFzzQvOJrlb3osfKMcaAtJP1k8UYaNvpdT+Osi7jEe6sxRIH4c8iosSumIMHC763S0ngYMFk6i4tzHiyXr3QsfH9afKiVCX+8F/Q9zlj83VHNRsJYFk/z83R74nK8ZEmOWqUtlC3WaHN5ieNwOfwgjCQQP6QBOk/EihVvBuT/RXlZxEpzkzkpevkGfPK0c21XFHFmni6zhMx950oKY3lUXhvjesORZ+wVGN7CVi/ZjjoP0OAqT0dkl9X9t8LA2zIcN8qcq7kfd1T4ySbu88trlmoM4TM8/JE75B7XH/UwsqxX+aG1pqwy9Ea9IkvCRTD1y80bp4HMrrEhStw70j0xPJMB/IxRRQjVoCajN4ABpPpVk5cibj3/KLsWDSSu0PkXqkWBi1i1D+PZvDW/pqPTHLkd4wxj+dLp76kAgaOZ/xKJ6WNXS+bFd95RjURZaOoVYtwSION0tqqtNXTugmnDcrofSAFz+0+ccdLZKXXjlqLHs042ZGZCHW76hjvneraHqpM9svGorm2i3ka88nCG72ssNIaGfEGiVIzneSNiDHzmL5Qb9YLMa2bSH6CEgYmsaVtYmnwxEAPe2fq73/7pAdLXxRznuu5FbVGkjXXF3MjOMEX+EKmIrtaVWvL6ny+EvAI0BYymuuk2H5fvDMzOIGtsAt3whjR2khF/FF1xUls5WgvE7lM6PQftgI11EHNjgjV5EPstThMHuLWbl8GlMCktG/gS8PMJM2arN7VL1NPjDvJ3wath3kEUBZxfP0k1C7FhrL12I3PLy931uPMRtFH7em7zmLe76sYmgludGkR1W5SZCRXwjk2T8q/fOGV3Ra334cr4ix2gw/EjTK1Ojtlidt3qYeKde1N/WK+n2kihOeMjNj28r6l/0ysF5AYL42ZujJnMyyIiOIDEerYNKZKg9+Q5/6fCrTmItuIp0x2SqW29b4fwtMFoPXN6a+XB/f09NTjnZ56tsG3EY7AJHGKZ9MjTvedrJ/cch1wr6LuD6eTaHlf3zFK3Rgzyp/ixZqjVA4zjSrT/xQnoymLp7PjTK8tKcCOFxm2eJ+u3tsdg/UkaXylt+XPr0/QEXhKheAjug2Ylcqf1Aj4LH/WYVrZcUGAFIH/4Bzy7JEI+KRiN9MO3SgSlzEzaQwfXnvm9rMhGawsgbapsRMpAbNDr9qfMO8iSgKy0SBi+16ao0H4bNxPgM6w==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-16
 */

declare(ticks=1);

class X509ExpireCheck extends VNag {
	protected $argFiles = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwcv');

		$this->getHelpManager()->setPluginName('check_x509_expire');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks X.509 (PEM) files and warns if certificates are about to expire.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-v] -w <warnSeconds>s -c <critSeconds>s -f "[#]<mask>" [-f "[#]<mask>" [...]]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argFiles = new VNagArgument('f', 'file', VNagArgument::VALUE_REQUIRED, 'mask', 'The files to be checked. This argument can be used multiple times. Wilcards may be used but MUST be passed as string only (not resolved by the Shell). There are two possible checking modes: If you put a # in front of the file mask, only the oldest file of each group will be checked (use this mode e.g. if you have a directory which contains old backups of certificates beside the current working certificate). Otherwise, all files of the file group are checked.'));

		// In this context, when the user writes "-w 60s" then he actually means "-w @~:60s" or "-w 60s:~", so these commands allow this notation:
		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_LT_X_BAD;
		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_LT_X_BAD;
	}

	private static function humanFriendlyTimeLeft($secs) {
		$out = array();

		if ($expired = $secs < 0) $secs *= -1;

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

		return ($expired ? 'EXPIRED SINCE ' : '').implode(", ", $out).($expired ? '' : ' left');
	}

	private static function timeLeft($pemFile) {
		$out = array();

		// TODO: Call PHP's openssl functions instead
		exec("openssl x509 -in ".escapeshellarg($pemFile)." -noout -text | grep \"Not After\" | cut -d ':' -f 2-", $out, $code); // TODO: check $code
		if ($code != 0) {
			throw new VNagException("Error calling openssl!");
		}

		$tim = strtotime($out[0]);
		return $tim - time();
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

				$minTimeLeft = null;
				foreach ($files as $file) {
					$minTimeLeft = is_null($minTimeLeft) ? filemtime($file) : min($minTimeLeft, self::timeLeft($file));
				}

				$countFilesTotal++;
				if ($this->checkAgainstCriticalRange($minTimeLeft.'s', false, true)) {
					$countFilesCrit++;
					$this->addVerboseMessage("File group '$fileGroupMask' oldest file: ".self::humanFriendlyTimeLeft($minTimeLeft)." (Critical)\n", VNag::VERBOSITY_SUMMARY);
				} else if ($this->checkAgainstWarningRange($minTimeLeft.'s', false, true)) {
					$countFilesWarn++;
					$this->addVerboseMessage("File group '$fileGroupMask' oldest file: ".self::humanFriendlyTimeLeft($minTimeLeft)." (Warning)\n", VNag::VERBOSITY_SUMMARY);
				} else {
					if (($this->getArgumentHandler()->getArgumentObj('w')->available()) || ($this->getArgumentHandler()->getArgumentObj('c')->available())) {
						$this->addVerboseMessage("File group '$fileGroupMask' oldest file: ".self::humanFriendlyTimeLeft($minTimeLeft)." (OK)\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					} else {
						$this->addVerboseMessage("File group '$fileGroupMask' oldest file: ".self::humanFriendlyTimeLeft($minTimeLeft)."\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					}
				}
			} else {
				// Mode 2: All files of each group are checked.

				$files = glob($fileGroupMask);
				if (count($files) == 0) continue;

				foreach ($files as $file) {
					$timeLeft = self::timeLeft($file);
					$countFilesTotal++;
					if ($this->checkAgainstCriticalRange($timeLeft.'s', false, true)) {
						$countFilesCrit++;
						$this->addVerboseMessage("File $file: ".self::humanFriendlyTimeLeft($timeLeft)." (Critical)\n", VNag::VERBOSITY_SUMMARY);
					} else if ($this->checkAgainstWarningRange($timeLeft.'s', false, true)) {
						$countFilesWarn++;
						$this->addVerboseMessage("File $file: ".self::humanFriendlyTimeLeft($timeLeft)." (Warning)\n", VNag::VERBOSITY_SUMMARY);
					} else {
						if (($this->getArgumentHandler()->getArgumentObj('w')->available()) || ($this->getArgumentHandler()->getArgumentObj('c')->available())) {
							$this->addVerboseMessage("File $file: ".self::humanFriendlyTimeLeft($timeLeft)." (OK)\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
						} else {
							$this->addVerboseMessage("File $file: ".self::humanFriendlyTimeLeft($timeLeft)."\n", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
						}
					}
				}
			}
		}

		$msg = array();
		$msg[] = "Checked $countFilesTotal certificates";
		if ($this->getArgumentHandler()->getArgumentObj('w')->available()) $msg[] = "$countFilesWarn are in warning time range";
		if ($this->getArgumentHandler()->getArgumentObj('c')->available()) $msg[] = "$countFilesCrit are in critical time range";
		$msg = implode(", ", $msg);

		$this->setHeadLine($msg);
	}
}
