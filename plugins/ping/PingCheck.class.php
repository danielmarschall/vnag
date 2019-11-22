<?php /* <ViaThinkSoftSignature>
qJPHPNNT8RXL6dx9mkSPwpj8amFDYFXapTfVcbEPLUia3Cn2VW8f2Hltqr6FyJ1tb
v1//jnvaEzG45KQy5Vnsi0dgZoeEJRwgWQmCctbUQ7Vq59MANDr6ubAn/0DIKT+DV
SEluG/NhxC0g5eeeuaT1hWeJRTFKCozqkQfck1DZSxYbDE+JG5kC2CjVR1bBtkjF7
nXr/Ms7x7d1tIEP+5r47xJfk5CyfJfKOWhToK8VS1cDfrYqB+o6nJUF7XzHNJEyWA
0udnZPzDCTn+yPTE0w6YpsAUyCox8oEpz2uui91lpFGai7F4v3XAviQiIIBR3OnWK
bap/iuPCzT3uCE0THZDh9s1Rhn8mczXVqGjRXF/hlb+hkZxfKf38MYbMLLTcHjZWk
kw9QtuWtQleSR0Dy/VurXxbQscaHrtnOntxoAm396fqmZgIwViVTOiApRxqN8rbLN
ysTecZTnltZGBuozhytAfF/bwEyRs4IlIhPWtK6+0Gpqfo/NRN35DaJMdzML02Vsu
n+g6ah1X1Ys3FLdrwwqnm14iiUCS3m43i8MByg35TdW+X41AHSuSoQjpwAg6/u9S3
xW3LpXKkVJi/VN/wzevvTDamy0eFJkj1nA5EJLKDGe7g/bZR+F/MOKFN6oOlnPagN
g5+2pplhL3cPIjJjt2pUI72A1GnhMBdKT0focEU+g75tjWGAJCy6GO4d7EG/uFtdY
qRp5GgVf1xP+orULZUrd7fXhTfGrzk1oFgNdyQTE8TiYM31pagU7nBblDIM/JOomj
GIbqE8bt7sZVzkmHXK9kPBdLINDf+eBb3UGvPQ01h5XTnbjqIypZS8naYpkRWshIw
gIabcG2993EINhso7Y2pFq40//L+4Ubz+wlZ4QhfyKxg+A8KaK7NyerFeHctS0ujd
wk8GnR+P64uWvroSooAqgJZ6d6KE2G8rvHrK8kFhi8hG4rgaXcoH18NfwKESgSoqg
4+LQpWQm4mS0fyLXfE+6VOzknztVcbfH43hc0DWODns3F6fN9b/A8wEzXIaABXT9l
spy6NleIj+mzRc6+tqKSEe+UhtTXYVMgo5dvYESLJ6bnB3MoyuFD7CarcJ54RaOL3
0ShtWuMgtDHAr9g0AgF78W1DOHd78OscR9o/VRMWYUjYyn2FZ37Y5j3P1tqRmRx4B
EpZbmFdBJSW1Xq37YPeX0DYzkqnDLnnDQ2Oqi5/wwzORarmcsVEXDxzmU+x2pq/gj
WtlNzsmZbuiSjoH5Mkg+HZYL9PhcUUb5HxHNFRtq3BD7b0MOGUGlTulkdFT01YMTn
N9ftz7ON0FAVmkoK9VBXNrOEiH/5GgyK+WrXwQsMzss8cH6VRoTuJh2ems5uCDsBg
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
