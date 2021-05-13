<?php /* <ViaThinkSoftSignature>
UjhlO/5QHrsP5L+WYqJadIE5KtWoVmsLiG7xA8tDymg/7YtpmzmVXfAqkkJNZ2fqB
xeNFr9lrOWB3sY1QFme+6ibUT5ELu9sc6Ts19CoZ2YvSLmuJbi/nVAEZ2FYJif/T6
JcXeTUAHeV6CkzW5NTj6yxj+pn0nCkj+hAzNps6l6VMRJH8NVf7UGjPJFAw8ncQmn
biNBi/pOBfSyhQOvnZ+zuTQR7IccxDnAIsKdKs4dXcZDW30OoGVaZ8Ks/UXXhv/BL
4WEJ+5tMcYSgE5XgTPVEsewAvq3jlWjG7W2NT/a+2l5Zzp3dpbKPl8m8oJy6Lb62a
DhHFrd6m6HfvgFHk3m8/a713Ax6eLjyCMFak8qHtTQCJMZxLSqcyG2hjzqfHwK1SL
HeDv8MjK3zqWJiLqfyVGMiSg3CruKEsr4pTgDisy/k7da3FJXPgHmMWMss/3G1X2y
qSqjz7HvMz30zblmfMQM39qGrsHxsM10IZzAZpKQbDbOEJ4MtdZcnR5k1r4e+5ExI
usy0uOO4a3RFrzNtsX6G1R/QU95IXv4epoGwNLHW6VVOo+rdmbpmIwreS83LFLY7+
xt6neCQstuDg78IsXvrQauQaYuxvdFJgaZm0jGcoP9MU8Gg9Wiql+XKDZ1zUHXC5d
r+DC2HPl5ngZjettJHmLq8/MYGddxrrzlyB2gqDFtLHTdf9ZxXigyDpcWpmOgpHsI
u00Hc99LNYnyeMfiZY5/tAA54rhNgOZZ/t2HKt8QlSV78voS1SRpUebk4WrHVWsbR
CmoCCdIgLiXkGOOhhXy67j3+sJ8ty/aM/sJlCigYvYopEOIztmpLf+CgUWY6AXnX9
FuC5A36+rg5PI7Kb9xYMsaxMC+BV1p7sGspsTFY2Y1gtkK0gbZeBxa6/4cs5O+LsQ
ihPVB/SUxIS9zeix0ZwXvHfYI+wrFcmMp8xY+S4+m1QhZJAyIf6nEWRWmC/tn/C0L
fjwlU3KKhgH66a93TzPKCJuxuex4NY/a3L+/GmIFbU//XTUb+CcncmC6yHcOcFtw1
rWy0Yaq9T/4IXZNDumc1hKmN14hYlQXCasLVEJ83sXIppIUMx1Xeh/Kh4ABupy2r5
StWSZglF/ZEk3i2xVEIX4RKj1VFP0qrkzlfoOF6yZq6ItlqVdeLE5CqArK5zn83+E
2R9Pdq3vWs04yaCAD6EFjU5uayWH/3vyeq9xTAaqJVNZoED0ei5OfOptYXGaP3aQI
Vlsofey5OmF3ahIYU5U6tP1xDMF0ZswAOS9bOSlMe97s24G8Vkx+6k/Yj99fnk5lY
OMSKShr/eN5aBVkr41b+O1Z34RRcQR3+1QQh53WpOTUYuPXgAJEJwexyaYxNqZ6Vb
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-11-22
 */

// TODO: Also let the user decide other parameters like -4/-6 and number of pings/timeout etc.

# ---

declare(ticks=1);

# ---

class PingCheck extends VNag {
	protected $argHostname = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwc');

		$this->getHelpManager()->setPluginName('check_ping');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin performs a simple ping.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ -H hostname');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		$this->addExpectedArgument($this->argHostname = new VNagArgument('H', 'hostname', VNagArgument::VALUE_REQUIRED, 'hostname', 'Hostname or IP address to be pinged', null));
	}

	protected function cbRun($optional_args=array()) {
		$host = $this->argHostname->getValue();
		if (is_null($host)) throw new Exception("Please enter a hostname");

		$this->outputHTML("<h2>Host ".htmlentities($host)."</h2>\n\n", true);

		if (self::is_ipv6($host)) {
			if (self::is_windows()) {
				// Windows
				exec("ping -6 -n 4 ".escapeshellarg($host), $outary, $code);
			} else {
				// Linux / MAC OSX (uses bash)
				exec("ping6 -c 4 -- ".escapeshellarg($host).' 2>&1', $outary, $code);
			}
		} else {
			if (self::is_windows()) {
				// Windows
				exec("ping -n 4 ".escapeshellarg($host), $outary, $code);
			} else {
				// Linux / MAC OSX (uses bash)
				exec("ping -c 4 -- ".escapeshellarg($host).' 2>&1', $outary, $code);
			}
		}
		$execresult = implode("\n", $outary);
		$execresult = trim($execresult);

		// We could also work with $code, but it might not work under Windows then
		if ($execresult == '') {
			throw new Exception('Could not launch ping executable.');
		}

		if ($code != 0) {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Ping failed", true);
			$this->outputHTML('<p><font color="red" size="+2">Ping failed</font></p>', true);
		} else {
			$this->setStatus(VNag::STATUS_OK);
			$this->outputHTML('<p><font color="green" size="+2">Ping OK</font></p>', true);
		}

		$this->outputHTML('<pre>'.htmlentities($execresult).'</pre>', true);
	}

	private static function is_windows() {
		// There is a problem with this variant:
		// Windows 9x does not contain the %OS% environment variable
		if (strtolower(getenv('OS')) == 'windows_nt') return true;

		if (defined('PHP_OS') && (PHP_OS == 'win')) return true;

		if (getenv('windir') != '') return true;

		return false;
	}

	private static function is_ipv6($host) {
		// Quick'n'Dirty
		return strpos($host, ':') !== false;
	}

}
