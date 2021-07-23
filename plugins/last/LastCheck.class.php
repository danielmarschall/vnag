<?php /* <ViaThinkSoftSignature>
bFkiEkyHItv+AQwBu8Jbs+2mPVVp1pljo6oKwEQyS5ugT4dyqfHUANCFJfSr0/Wgw
IhTtUl9TkfC8ZZ41AZod3llWwMp2qJJ4GaBY2etFvOmRSWhEECLu1z43MCt3Q5Dvt
FrkzApfPb3bzX4tUFKi4/z2HJhbnOsQQcUFRPznV3PU8A8xEA1sdPOrnV/IcIDKAk
eMucV6Bch2F6tU1qi7LRlTufNUpqCtPYy1UaLUvzxRLBraLKjdRzVDa06IakJM4OH
zU0tsg3P3fLwEiFecWSLCjt2dyo6woJgPnxFf6W9qETgNX7ItjiCktf9P6YEd6XQ3
+jWZoIm4/SuQqbWnUxlT6k8ejvfE3iM0WOIv9mfJHcMgwQPCEAPVu7aPazLi/x3Xc
vHlgHP9A8ED6a+jh4H1bmrZbW+DpOJ8jhvPvr1kdDTq/vJtadPZkONux7XM7LgzAW
kpQTmRCXh/FkIEOpHWeW/PNKlBqQzPglsi6liiHnN3LMAb9F1PXqMOXxcwbkKGrQJ
I/pGasdex+fVUI0py45Uost/PWP8P89DIxq4BU5nI8Mki4N1MlPf0Pty1ndFwk3Z4
kU8/rdYy1/krfwEt5Cz8FhvA2SSgFgzeX4rqly/u/Q8uyzMVoCK3N26EelKfzO8ux
jhlnBtiwbemDK+pnkLAELbk5pkf158W4Sdl4ejeuVno1Az78w4R5pOatx1Jf6MzIA
lf3xsFT5aycWk16OyhHIjrITl7UZcb98ykv0FWy7+NqDFM9sK/GSrs52FNPzxw3mk
YPEofkp1Qn/qxm6+t0TAqFkXBzGEAvkG+xRRDhGrDf6iFx03baortdMxmcHTqsMsA
BlBxM2VWto7OuTtUgjPn6lFasHERSWoqgUrxomP18OBuh/6Cjb3ajeGL6OQEyvLbQ
HAcBssnfott7LSgZbsqIMIBT1xrki92FMmN/jzcE8eVZ4Dx0f/+4gyAFyqptE/FAn
w70HGdVZFy6MBxXUSCt83Wz4uDdIV/SQEOa826pR6X7/Zl7EBRPNJoSnGZfucpau/
+NEbOfp0TZODpbqBeKRLBCyqJFQfwQEhOE97/am30pIrIAob6lAcNXeimgunQawld
DQLJVhpCbsHYcMHlmIqqKc+jCefCf+T0grx34t9+fRap90eYs0KHxHo9Ubr71I178
iLWcYrN0mve5cNFJ9aHUg8VT3i1wuJYzl1ZXJeuDOynddmIEmHjVEMIdsA3wKGHIm
aB9cPgTkw+dkKkkCqpHZYcqeBBvbQZ3fkovhubKfYdwfX1QwtBjFZmdgayL3gQNJZ
EOr7w8qmnHD+B00v526nYpNqKBWqyQXe5anmrm02e7SC+G4JiMiaLLvy6wo7ya3of
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-11-03
 *
 * Changelog:
 * 2018-08-01   1.0   Initial release
 * 2018-09-02   1.1   Added argument -e|--emptyok
 *                    Output a warning if the Linux user does not exist.
 * 2018-10-01   1.2   Fixed a bug where too many unnecessary requests were sent to ipinfo.io
 *                    Cache file location ~/.last_ipcache is now preferred
 *                    A token for ipinfo.io can now be provided
 * 2018-11-03   1.2.1 "system boot" lines are now excluded
 */

// QUE: should we allow usernames to have wildcards, regexes or comma separated?

declare(ticks=1);

class LastCheck extends VNag {
	private $argUser = null;
	private $argRegex = null;
	private $argWtmpFiles = null;
	private $argEmptyOk = null;
	private $argIpInfoToken = null;

	private $cache = null;
	private $cacheFile = null;
	private $cacheDirty = false;

	protected function getIpCacheFile() {
		$homedir = @getenv('HOME');
		if ($homedir) {
			$try = "${homedir}/.last_ipcache";
			if (file_exists($try)) return $try;
			if (@touch($try)) return $try;
		}

		$user = posix_getpwuid(posix_geteuid());
		if (isset($user['dir'])) {
			$homedir = $user['dir'];
			$try = "${homedir}/.last_ipcache";
			if (file_exists($try)) return $try;
			if (@touch($try)) return $try;
		}

		if (isset($user['name'])) {
			$username = $user['name'];
			$try = "/tmp/ipcache_${username}.tmp";
			if (file_exists($try)) return $try;
			if (@touch($try)) return $try;
		}

		return false; // should usually never happen
	}

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtv');
		}

		$this->addExpectedArgument($this->argUser = new VNagArgument('u', 'user', VNagArgument::VALUE_REQUIRED, 'user', 'The Linux username. If the argument is missing, all users will be checked.', null));
		$this->addExpectedArgument($this->argRegex = new VNagArgument('R', 'regex', VNagArgument::VALUE_REQUIRED, 'regex', 'The regular expression (in PHP: preg_match) which is applied on IP, Hostname, Country, AS number or ISP name. If the regular expression matches, the login will be accepted, otherweise an alert will be triggered. Example: /DE/ismU or /Telekom/ismU', null));
		$this->addExpectedArgument($this->argWtmpFiles = new VNagArgument('f', 'wtmpfile', VNagArgument::VALUE_REQUIRED, 'wtmpfile', 'Filemask of the wtmp file (important if you use logrotate), e.g. \'/var/log/wtmp*\'', '/var/log/wtmp*'));
		$this->addExpectedArgument($this->argEmptyOk = new VNagArgument('e', 'emptyok', VNagArgument::VALUE_FORBIDDEN, null, 'Treat an empty result (e.g. empty wtmp file after rotation) as success; otherwise treat it as status "Unknown"', null));
		$this->addExpectedArgument($this->argIpInfoToken = new VNagArgument(null, 'ipInfoToken', VNagArgument::VALUE_REQUIRED, 'token', 'If you have a token for ipinfo.io, please enter it here. Without token, you can query the service approx 1,000 times per day (which should be enough)', null));

		$this->getHelpManager()->setPluginName('vnag_last');
		$this->getHelpManager()->setVersion('1.2');
		$this->getHelpManager()->setShortDescription('This plugin checks the logs of the tool "LAST" an warns when users have logged in with an unexpected IP/Country/ISP.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-v] [-e] [-u username] [-R regex] [--ipInfoToken token]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		$this->cacheFile = $this->getIpCacheFile();
		$this->cache = $this->cacheFile ? json_decode(file_get_contents($this->cacheFile),true) : array();
	}

	public function __destruct() {
		if ($this->cacheFile && $this->cacheDirty) {
			@file_put_contents($this->cacheFile, json_encode($this->cache));
		}
	}

	private function getCountryAndOrg($ip) {
		if (isset($this->cache[$ip])) return $this->cache[$ip];

		$url = 'https://ipinfo.io/'.urlencode($ip).'/json';
		$token = $this->argIpInfoToken->getValue();
		if ($token) $url .= '?token='.urlencode($token);

		// fwrite(STDERR, "Note: Will query $url\n");
		$cont = file_get_contents($url);
		if (!$cont) return array();
		if (!($data = @json_decode($cont, true))) return array();
		if (isset($data['error'])) return array();

		if (isset($data['bogon']) && ($data['bogon'])) {
			// Things like 127.0.0.1 do not belong to anyone
			$res = array();
		} else {
			$res = array();
			if (isset($data['hostname'])) $res[] = $data['hostname'];
			if (isset($data['country'])) $res[] = $data['country'];
			list($as, $orgName) = explode(' ', $data['org'], 2);
			$res[] = $as;
			$res[] = $orgName;
		}

		$this->cache[$ip] = $res;
		$this->cacheDirty = true;
		return $res;
	}

	private function getLastLoginIPs($username) {
		$cont = '';
		$files = glob($this->argWtmpFiles->getValue());
		foreach ($files as $file) {
			if (trim($username) == '') {
				$cont .= shell_exec('last -f '.escapeshellarg($file).' -F -w '); // all users
			} else {
				$cont .= shell_exec('last -f '.escapeshellarg($file).' -F -w '.escapeshellarg($username));
			}
		}
		preg_match_all('@^(\S+)\s+(\S+)\s+(\S+)\s+(.+)$@ismU', $cont, $m, PREG_SET_ORDER);
		foreach ($m as $key => &$a) {
			if (($a[2] === 'system') && ($a[3] === 'boot')) {
				// reboot   system boot  4.9.0-8-amd64    Fri Oct 12 02:10   still running
				unset($m[$key]);
			} else if ($a[2] === 'begins') {
				// wtmp.1 begins Fri Oct 12 02:10:43 2018
				unset($m[$key]);
			} else {
				array_shift($a);
			}
		}
		return $m;
	}

	protected function cbRun() {
		if (!`which which`) {
			throw new VNagException("Program 'which' is not installed on your system");
		}

		if (!`which last`) {
			throw new VNagException("Program 'last' (usually included in package smartmontools) is not installed on your system");
		}

		$username = $this->argUser->available() ? $this->argUser->getValue() : '';
		$regex = $this->argRegex->available() ? $this->argRegex->getValue() : null;

		if (($username != '') && function_exists('posix_getpwnam') && !posix_getpwnam($username)) {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->addVerboseMessage("WARNING: Currently, there is no Linux user with name '$username'.", VNag::VERBOSITY_SUMMARY);
		}

		$count_total = 0;
		$count_ok = 0;
		$count_warning = 0;

		foreach ($this->getLastLoginIPs($username) as list($username, $pts, $ip, $times)) {
			// IP ":pts/0:S.0" means that there is a screen session
			if (strpos($ip,':pts/') === 0) continue;

			$count_total++;

			$fields = $this->getCountryAndOrg($ip);
			$fields[] = $ip;

			if (is_null($regex)) {
				// No regex. Just show the logins for information (status stays VNag::STATUS_UNKNOWN)
				$this->addVerboseMessage("INFO: ".implode(' ',$fields)." @ $username, $pts $times", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			} else {
				$match = false;
				foreach ($fields as $f) {
					if (preg_match($regex, $f, $dummy)) {
						$match = true;
						break;
					}
				}

				if ($match) {
					$count_ok++;
					$this->addVerboseMessage("OK: ".implode(' ',$fields)." @ $username $pts $times", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
					$this->setStatus(VNag::STATUS_OK);
				} else {
					$count_warning++;
					$this->addVerboseMessage("WARNING: ".implode(' ',$fields)." @ $username $pts $times", VNag::VERBOSITY_SUMMARY);
					$this->setStatus(VNag::STATUS_WARNING);
				}
			}
		}

		if (is_null($regex)) {
			$this->setHeadline("Checked $count_total logins (No checks done because argument 'Regex' is missing)");
		} else {
			if (($count_total == 0) && ($this->argEmptyOk->count() > 0)) {
				$this->setStatus(VNag::STATUS_OK);
			}
			$this->setHeadline("Checked $count_total logins ($count_ok OK, $count_warning Warning)");
		}
	}
}
