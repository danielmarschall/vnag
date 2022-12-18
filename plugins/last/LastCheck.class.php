<?php /* <ViaThinkSoftSignature>
Fm798K8OnhYRk2wZ5LGT1eZv1U9J4+xksI2BNLu4KZxIw2YI06hHPgG9ZkiYp88+Y
EuW8MeTg1z1ee4f8qNi9ZLLpSyiWEK9sAvJYJeWNq9Nx3avUf10Caa9iU3UQp4cT5
d2NC00RqKZRtBC5rPZ6u+ZXNoEz1UN6Z3b//FlXtRW1adXqgPpCNhw4HM192iXDld
5YXYDhEsH7/mF0TJ4bFkRmNG5FDsX9uM0+SD5vZ8VgW4HW0KwO3qJG89QOelc1TmO
88fFklxG3wmTZU/c1mCOT7tgrl4AVmUamNyZRygaeKWORfTM8m1PrFm86MyGCvLR+
ZVr8mqtAV5mJt3NR9NxpkxuLJN8qPpEuMzbQV/0MfjsRjdA/bLaXA186maJ7IaS1a
69+xLqrLbcBn+c4Keej72vAdhSs2HmpPqO6wVTsV24LMVDWgS5IEYDfK5ylHCikTI
YQesBoQELvXEtKuJI5EJCBsH0uDuIiuL2xe7btJJi8fUYaBHXOW50KoY0HWlIntXd
UuI/CABDWgVE1XMqUu4xemM6mGoPcZY1V5FtV4A06KtXTEbmqRwileEe12jO3aVKq
HrOoGY6ZB3gk1AxH7sUeUKFWJ6yS+zzUriEoka0YrJVrBgG3g6yaNDebNU4KVNx+G
zQalJ6+k0RaWMdysrRy3/IJlDhu9Vt77QRb7twP9c8WARJsGgEKCTwuutlD10Cl8m
IpJ0CJj4fHDqLJHvXJNb9IH2G1W1YxfmPYqsscfW+n0mVBnp3ZjVo4iuV0Gr4hrS6
WwBMX6XApPU8shgUwUf3mENE7WY8EKaNpybZsn3OTxAGXnJ2293x+o1Zm29Xv3Ovq
R9VszqfHCaGq2V51BAPKGydtgF2QF93mL8vwlfqH1v/m/kRujRjLQfYv7T8+6CxI7
hpXCp9LLsr2KsCNF1B8cxiyBv8fFF1bB9+HpzU+Z5sCIB91XdTs21XaaLHXDg2qJZ
E9SjmfIK3IqG1AYN6CFsEUX0wCloHI1Ua3XtClBQAJaxbsvRdk0SM0nJjPTxsC9MX
ZGVlDxPR0RZSA5XD93OYrF28ZWnGMhSyet2zEUSEcLmAKlCO1WJbGzgq9Prd+HvRL
UJqjbc1FjmPN/ZllnxHiP3/N7Xq307hUmar8lNF4FcLjXcaMzA4umMBUcY2wMxugc
ENsHEkIBh9jzOFnvRDP1K0T44ZVR4l376Qm3nb2ULcvTA1E2Dq5U0H3t1AuniPgCP
D62RRZOp3ZH+5RNQnsMHPL87eyK0XA/VAT7wQah9GnL1d54ocXDyDrVGrJXEiq8dN
J2Zrd6Dd/7TAI1lcPuwUv4BxPP2o2ecsFVC2wi49v/HNPwrpGaRIocVG7XB2/tQvA
A==
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
