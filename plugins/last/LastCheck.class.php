<?php /* <ViaThinkSoftSignature>
cNxgmYaPudAjw7GVR3a8SaJwW1pTIXIVKgsgZHEZ/F+STg0sfH7Ulvmikp0JAFSf5
96xzM57qOw5v9UVkE20zL56ZDyc48D1nrtBsNp6Pf22UVC3Kg2x+MYiOo06oV4WgA
9aA8dsyf4m0sqifbQPmSh7IXq3ZSp/nnpYSgbLGRvLr/LshnRIohLrbcFd0oaVuT+
HPtvUsOLgKMXYa8U5U86aOvW0aAMDOxNDassNbYLwcp+shNWiMwi4TVKtGbzIThYM
hCCvo4JuCKqEYbvMKT8SwbPP00DUCjnxPkytagC5Y4x5L5Hqu8z30fxB/aiKclJ1L
DncG4NrCvBfg/O589suxghv1AOThIxi+hcRDyy/+VX3Lk/FPkd8XBbTQYPp8KIOij
TyOdBhr3mHtRsmWvHhwhFo4H0HPA9d1rfAoECuvk6MLcTO2x7nJByA+WG3SPZkOn8
N24+SAaM+DxR086kOl3sxLd/khWZgp35mObWyKnH0gZ9038X9k2m5qXnRkssVhLih
Ff4BKOGcMrigx+xcteJp26AUJo6YCxvlobCSEo61Dm1bokv/xSawA8fexpVCSNOhR
F/eZM1DAoxR7oMQRNcnZQYcTlLhmEOdcARRGJa03EkAVNYAdhDB9Fw1dUyZ9GI0M7
ElQ6zj6djPZBzEOVjhbTwc0RlbrzFi2EDilZxmeC+dy/lp+w60xe0AgZ/xjM8JRFc
vWWGlQ9Y94nPeEnqz7HjWijzCAXinY76WBjK298SF/df5yfDgKPf4xCaNS5OaJwMp
IcE4g2I4UHhhRyA0FLaP8kqwHIqYJGO+fKA8034IWFjUIJmA14H9lpd1dxVnQ7hNp
3tnW71tluuT8Xuz++qkGFXAIqV8WefymZbNPgWyCoyuC91F9l3+4eoUXGU9R/dqw1
AbtPWmttngs96Ico2U972NfLf/PTwWuElSccHwkHCTSEmtEmhwCxRVMFDG8wvm8sg
HFam2Y8ObeeYc2UpDP1lsRXsNMNLjvdi/MuF1qeATf/4oqAjnxcZg5qTCH8VVa1iL
CNqa+SqazNKOmpSOALRAw/tr9fJsQyvdAF0mE7JiAdvyRgiTG63ab+Dc0njHWeTOC
Ol4D7NgtY70pIwB8DKteivgLOZU05Pp4LGlYcvQCRreTNqRD0kv83zxogpT4kGnNS
htJA0k484/KFe3lNLzKe7b7xH7CoBOKnEdemaMGDds8bc4mHDN2i0cBGuqQW9URTo
vwGF46UgiE4DcRBEOrTeZ0e8h8I1Zk6ZhXuM3pX2sOhzPlq+JCCQ6LIsjirPVTEPS
mrBfP64pFDKeXmMcdiYctq29dlaZtcYohUm7bS5V5HcEWk6LlNYv+XoPmsL3wzHX8
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
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
		$cont = $this->url_get_contents($url);
		if ($cont === false) return array();
		if (($data = @json_decode($cont, true)) === false) return array();
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
