<?php /* <ViaThinkSoftSignature>
TBeKugtY79IHmpxPAXtF3f6WMNau7HxZM6RFwlzkNgRkDuIm45j1foyRVPD1JGJlr
1QtSKGH4NQgVS0mx+ylACyq0AfWVT75lwrvKEsBsuBdwWkppwJGHOuQlNV9sSzo+A
rto2S0Cee00ZfMvXEI0y8sxDtRwm0IjM2rcxYF22peHvV+INydrS0/6L7z8qmHwRN
0jEAVLvJFVWFPQxHa7aOMnKWI1qPUFP39AG0hP5z/wRZwvQ2JkyQPrL+GR/TYoVdU
yEupr7DFxZk1/nOtG64k+olmp1tYAPC286t67htTI6hW029yngWt5A167sm0F3xUj
xGUm1nIj8DmGx+hr+vYQowZcUnM3sakMX+SxdB3ENjfJjzMddA2w1MoestvU5MiDQ
Gldc5BIqOaVRgQiS6hGYq7DqSIQmdER+0z8nU5TpttEg4tyPpnPJxPuspTd89zQcq
DaEFF5/I1mXguH8W0g5lPl/3oXyk3aeL0zoz730QnwzYHSU4OKaf3EFSY0KBDaFVo
srevwH3N5D+m8/saoIWVFa+eYR40oZedNxMkgMIeyrX1KquxbWeyx8lgvouDJm1hB
Pj+dOaBjEVctlE/+cidfErNY+5Ov8T2YCnanw6melvW1O8c9tzgYcrzielblpG8vs
lQzYdILVVrNUkT+I/KVBw1kbndwGJT85woKAk57BzPTsNfTJb0pRnD3h4DOYICSUC
GfNj2nS4jxAvZ0clZTqUrmgweLG6GGXC3qPzTkLntqwBJprIPLq7FVbAvLa7bvaaL
aGiswofPduLh43JaPxCLk4JUrWgFCc81Xwmiuel0sGpWzAsOuoAHNbCxJ3iTWewBW
41DfScShmOaZo+zlu3LU6ujOrAhxnBLBv1UsRk/Aqf+SCtSS2xT7L82nK+NRi4whB
KCFRTTwxqlYV1VFKPoGFNCHuhTx/5NCb/HKSgD/CrSP3h3voFQZOcbnvGzVqMFRCY
+pnJl4NvXV9P781Wmd11kFANdA5eImdD2EwDzc12P39ldhl3u7OtddYutMnBJDrwg
A2wdo2T31ZltXSgu9R1OUMl2rXduL7RUwwyqfLGe+56NepzUBEfR7IBF2ZKmBqaLE
UwsJNvdiUabd3FkdA4eLMoLXoXqS0QojzkfJttza29xW11jTLKM8lJhOYCZP+lCHh
hEE7EylbJ4jsSDRBUZuI5dsQFBrMlpGVmUXKtlrjOeTXqQmxSpt8rWGqPPSL1u/Im
tEIadXOWAc0y+N+DECWZD6AC0ZSh/5SHJTq7AML2kI6Vn61AEqnlUCxTytT2BsK4+
0Uon3cn/lxFhJU8Xd6suK+xfXanAVIWMuvLGK30x/Egfsk/l/NOSc1d1LuNwrqmZt
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-11-18
 */

// TODO: Also let the user decide other parameters like -4/-6 and number of pings/timeout etc.

# ---

declare(ticks=1);

# ---

class PingCheck extends VNag {
	protected $argLogDir = null;
	protected $argLimit = null;

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
		$execresult = nl2br($execresult);

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

		$this->outputHTML($execresult, true);
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
