<?php /* <ViaThinkSoftSignature>
boKKZRVCLt6Pij9GtZwVtVRcuFrDhyTKij4SbspGGs+24F0eeo10iC1wBP05mJ7tL
WS8JfFSlv7vQDBcdd6sMF+vYQNldSznrK47dbZzQXFxuq26jd6BuuH41aP2pWHv57
XVCpYf5EPR0gFkyXzrtN/iFSr0kJKWk5iSz/iniucplPitxmXR0EN1qkKmcZgTon7
hKIjq3kb8RY6U4BFcXujRq7M/SlRvub2erQiWxPv3wKi+CQPRcCvq33/g7bPOTSpY
1WFCt+3zkCfQUomU27Y7/GSW1V+7T8DjAWUhR4rxO5nXyTx3vxqmv9UjfkKpTUVc/
TjhyS0mZ78rYX2HYW3+NBQQMMWfGlLKvuhHdJFU2dB9+YvW4Krm/N7xTcwKos3Aho
vSlnVHDf48KSXN/gPMKC7U3mOH8RB2oyiLJBX42CE+N30Ui+3TyxAj2/YMAjzDPYP
8b+yRhntXzvGX3wayFPcjBHBBWrchJ6JnERCewVW+/3vDKgjwHhnul4Y/lme79jNA
1Oj0ZX6rCGow+7z3he7/0ec92Rr8NOE8p7mrQn9iofTZcfFo2i5RDy+6raUvSi/Fp
2DP2HqwOGp2uHrFemzo9NepjH2DgnK5zMMRR1Q2hMksAsviWMWTUv0fkYZZSsi1LA
aROWE4CRaEhBuvt0bRIrJGZUbBWvxOE67CVYXHF20YvyKoI5IUN/ZOI2iCLMr+ss2
wrB5tW4ovYwsjV5nDAK1bg1hsdOKx5xv7PchfhUkAn1J3DoekDl9qECt3LKdKG2hl
ZwB/HrnuByxgab30wLUFsELT9v/pKJW6/2TFkWEfYw43MQzpsmKs/h0qTKW691GkW
htPhMbMpSCxgDGloU4z7EcMdnvTEM4ol1GyDn7NZlYhgEHfw+X/5CZNTYH5DJvt8J
K+aQaZFiZDHa8GaI7NyGERRk34C90lL7pgFEhaNGCFl+Z+YGegxwMjKbFo1xH0YNk
CMPYX9GGZIEMTTNEySCyuZsDvXpXK5CbQ8ywuZ9/B0avnUWrBBPLh9RchB07S6kA/
EcH71AxGafK8wDMrPzymdFVsHpwNp6FEvP/EpBHGv0x4/gYpPomUOxmy7uEE1dzdB
talRGPLAt8a/RXXG3ECsjgo3zDzmc8VYx6m4BPXvLsDX1b9NjN34AtBObE+iLLC3A
wMd4oQSnOwfDDYNdKg08s43j5m+SQ+HRGQqgGb9A6IcwxuHR9bTidXBgNORbbdjXf
9I1QW1Za0l5khdpDF/smNPMIwidcg2xBi7faHY/aOpzWTcTviox1ujPJmwaLr0I0l
DYFlTNaQ0wM+UBE501Lwss8gscs4duuSlBHQLA+ddOKWSd92/KK/JW3uf67rOaqfy
A==
</ViaThinkSoftSignature> */ ?>
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

		// In this context, when the user writes "-w 60s" then they probably mean "-w @60s". Make sure that the user doesn't do it wrong
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
