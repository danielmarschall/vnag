<?php /* <ViaThinkSoftSignature>
Dn0TAtS8an72QeGrW4rRe6KqVYdOn1iBSJMs07mG3B21qZSA77cSJqdqIvni4+mQ+
k3ee/H/eGuu/j6XsdzIRNXk+p+CjfSaXf9jn0VwWXhurSgRzF7uOIbhv3jpKI9mss
dY4aQVblJiTTKuN27AB7LjqSSmJyEidWDYVe35g79sj76MJMP5Dz3+dcZSlcqhDrH
6T+uR5aoz09Kzro50iZ3M6n1e4EY9hDJH+Bqdj8kJRk0qGGt+XFHwqEAahJMiE1Sq
eFZmiwhEBmck70HpC2gcmrm5L3cp5pv7z3UFGXmi2QoKyzZMQKfyw2BD+uGv9uKR4
LE5sdpJy4ZsHjRzKuKXmu+eLNEh8loDzYzIRBnikk9NV9FrO9Rraw14aVhcRvxY5K
j3CmReIweYJRqJ9kfEjTh5RMMut33ZazaIw1rgGvKSNq5HVgP/tPc3Nx1RIEmgBu5
e5gB+ou9i4w01gi+b5Akw/yWsTrr+JE83j1BXWaDXLtenaCRAMGB7/q/kEcFgoJlt
qSxcMFmvTExlFldfF9ohXlE52F9AVQJv76AYu86FTZH8qQFMn6Z2yL/HHrBJvpEsW
AsKOWsvKH9x8EZLQKzwuaQRGX01Z936d2V/e7Rb+EJjmUbQ/9TJrpYSzAZB4PCPnU
YHeWNMwIHi82MDwG+7ey+bF9A0DJyosa0f+tgQ0szpoOJgCXZNm+guCYFkp5rnbWs
16VlVsqYxn20yFUX3uci7s31ToIqJfLGpZKi1yaWtavhW2Jb6SxuMU7nsaQgsyv6J
VR2eJ49itgeTpArGWLscTJ9BHDeOjhkLqoVPVWut2XJF8mJTn26pnvIn04997JL+I
e7FSV+6mtWLae+fh7A/aASM/4PJQhKqBARZtGAsgH111z4iqE4psACt6Z62ekgO7O
/slAwR+9Je7VT/CZdOoNnShNIYGlVgm1cKnHxAP7H7vBDhMmTbTeZUtdZU2hOAJME
1C99Q4btFMnE1nW5oB9jQpeKXZ6DEivyNsy7gG+YPqauMlOu30gXYx7V4juAaBvlO
kGwJ1jpqsaPI4C0o7smkksy6WHWGsRioTgxc+vOBnqOczYWaste5TlFIEaW4KAHcg
3hEeebMPeNWhgUp/uJnfpEuSt3M89tEhD6Hr1LxNY5dflperss3LeXAtCuuqdT6qu
STrB6iTCcAvngMfqyZKrnrq1xhraH+b7iC8qpmDZIPM1pjoCTJjVa6DrEmmh/3+mQ
ZuOwqRNWSmhvMf4V1gzx4py7KCPP+bwMe8fphXG9Qu3Drl0vV13PB1QXzDkMAQ5zn
+LZva+1J5Q33Xu1DH8ih2rqFYdhKYS+Er9KP1iJsTmpFoJHe+b8IXzI++MeL0WGEV
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-12-18
 *
 * Changelog:
 * 2018-08-01   1.0   Initial release
 * 2018-09-02   1.1   Added argument -e|--emptyok
 *                    Output a warning if the Linux user does not exist.
 * 2018-10-01   1.2   Fixed a bug where too many unnecessary requests were sent to ipinfo.io
 *                    Cache file location ~/.last_ipcache is now preferred
 *                    A token for ipinfo.io can now be provided
 * 2018-11-03   1.2.1 "system boot" lines are now excluded
 * 2022-12-18   1.2.2 Use the new cache directory now
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

		$this->cacheFile = $this->get_cache_dir() . '/.last_ip_cache';
		if (!file_exists($this->cacheFile)) @touch($this->cacheFile);
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
				// reboot   system boot  6.1.0-11-amd64   Fri Sep  8 13:10:27 2023 - Sat Sep  9 17:40:50 2023 (1+04:30)
				unset($m[$key]);
			//} else if ($a[2] === 'begins') {
			} else if (substr($a[1],0,4) === 'wtmp') {
				// wtmp.1 begins Fri Oct 12 02:10:43 2018   (English)
				// wtmp beginnt Wed Aug 16 11:43:03 2023    (German)
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
