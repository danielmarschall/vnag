<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
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
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin performs a simple ping.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ -H hostname');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		$this->addExpectedArgument($this->argHostname = new VNagArgument('H', 'hostname', VNagArgument::VALUE_REQUIRED, 'hostname', 'Hostname or IP address to be pinged', null));
	}

	protected function cbRun($optional_args=array()) {
		$host = $this->argHostname->getValue();
		if (is_null($host)) throw new VNagException("Please enter a hostname");

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
			throw new VNagException('Could not launch ping executable.');
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
