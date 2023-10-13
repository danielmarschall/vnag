<?php /* <ViaThinkSoftSignature>
XADYb11P/nrgh4ZQEY8fC2h7mYMpi0Aj/fKxEEtCNnEiJdFfdPBDrFItkj+/gduXM
i3kU6hyR+RFstliaT8F7Yj/r7ta65WnhYw7DAsIRLTT1bC7pXC5BS+a75FQe+Lxeu
n9AjjXyuyy+T8S88RSiyRpRSMFn6q16Jd8naGr2IVKZ5T0lKSQBe4AYURLJ3p6U5G
cvKLFOerTRMxQs7BiBmJGfmSnk6oU587MxJN5vEvOa5nwpRb/vSWcMActNmZg3moh
xfPI1E31tMU9yV0jTAb/2bWEP8+KcysXmPBelti+iAA+QHpuYrwHYVPNFrBAmtOmB
/9dCd4jk/ucReEObKhow9JRPrIyJ+8hCAXZovfqKgP+jF3FdlNWypLHcCYsYClfk0
iYXTCPRZHTsKwKrDUPDZ+Gksq4ODBeIkkLlIGixCSmVSr8M4VAB2s9H38xPbU+vhg
LmgdD1mj+abGMKZnzWjsLdlyuOsRJqnmqZ3QCPhmtRnzpgnYAo5gR/QxvUiPUVMdF
KPZJikmEHm/esM9Ia8J7gP+Dv6BLKLTpzJIMzEU9C4Kk3cVlEX5FlqWRwUdwdN+lS
LX9IWCFYN504/h5EEeOZz8SxZQxFONfk/mbxTx2yFh4kXetv2GSuk1oAvZIpFbZz3
XHNi+t7zhQQJm67lUf/SRYuySfTamEcuA6ueayY2jQh0lFLU9yuH6LYMHoOyP+1d4
QJKs9M1KmEvzyX4k5Wbpd2zRJfJiUqnkqYBKDV81yCzf9f8l4NtLHDaoJo6e5w3ut
ch4gUlpYNmo8lS7fyhx7u3cpIGYqKpQp/8pSsPvbMisFYjmzPWkrdTnsNxpaqkN9+
XKOdmyumj++IDxUrIXQovLJNcT3nAF7+0lG40YigI77YD0jjQV6P4Kxwbh9sRCDjq
JmN7aKB4NqvA2DYrtqklvIOl85nKA8+RdXP8Nvaa/5yBvUrWZfHmcHLK6ogQ2Um28
ZEAiTB6HUfP94JVA6KXglPN5vQ7rR+gptxJtt82R1ms4VXJo/NPf/c2QVO29LEsxD
ePkfikCPJLfwNqNOYGpoUm7HQc1C60tHHPCbkMwEy1+J3GfIr+q8sHmFcxGLsqAg2
LECUGs5Xa/Z2wY87sdqBpYZXx0yHxfnWYWR/ixCtOcoTvUSKdqRxHZJAVdzY8gteb
aIXX0w1JZ6Hj9AS978aCF3Y833KtgTlyUXzD6JKVddYkpc33zK7yHJUy+QSOxYKIe
fo4TzHZtydviNY3vjE7aDiOO9tBGfW8ZmVNmwcicSdLUYJuEMgCWsH0hpIVSAw6pD
QMRu59uTvROscljpySe2DBmYaBTKKIkaDMuRQ/r15/qd5TsRQH6FjjCfvUdK+2zFI
Q==
</ViaThinkSoftSignature> */ ?>
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
		$this->getHelpManager()->setVersion('1.0');
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
