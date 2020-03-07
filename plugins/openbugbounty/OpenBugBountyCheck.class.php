<?php /* <ViaThinkSoftSignature>
qWEItTVwmWt4bCuj7GpdpdKLH3wLzHxhgQxr8p/AKPM17DRZoAn/i9x4YUloNuiC6
ut7naeSIlD/SKCob9Qli9lrbCjlfAsuZUaV1MVuZutycJBp3xSEOLCxLfBWB9DbVq
pfAmFuS/ukEWAVipyeddB3RPmsAPDnZlNkQyRXXcB/yO31GYANCB5K6qaUPRoHVc+
7AbZK3WfZBBaILYXdFDzincJfCyVxVOtFqdbuwyIQiJKAf6fmcWqJeEWCOGDMve+i
bcRpS8QuWbB6rntnmh0NB6JvjpaSkdh0I5m4Qx505OeB6lFgeAVlmszJvUSi3zqVz
b2zOvnEfm2+QGjrolHyP4vWAQ5ldwoYCXK/9yLsQ7YjvWM8FmgGIyO+E0Gn+diEMo
PivhhivcOyxpa3O9o03WenONl0ERYB5zzrqzxfqGo5Az4Wluy+8vtpCneUf1VmtRT
zTLW0KLe/j6OApYgzgKWOxk6cyZMNCpWwDz5jt75xeysEBJSlVe3Z1uNLkD+XFjFm
b+0TgAwq1hDS0aHbDBwE5dwO3vCkVOGZoANnNZtU4vzs8Oy9GVHmR8muj563Dex6H
7TygozGMfzI9WxttN9HIvPiA5ncsgHDoA9h7BT+AE1cmwWX+0Cs5m6sqlZZ4dLNsT
Cwe7vXisS6Q4WTEX9qsldDXrzTgJm7Vmb9wMxwr85nEpViET4uEPbeG8ZsFF1tPIU
yJPE+yZUCCgml8AWrAcdNaRfx92QFdsYhSOIcypV3VlSjvWm1NE2dStuTo/dqX5M3
fmNt/04tZnX7Mh7MzgvcXncuprAS6qgaOPWo5uFL8nUU4WIOzxYywNqaSpA2VFCz1
YewKblZVRJmVMcszKHdpcIWYAqX6UbZ7UGO/B06RoFQ0A7oS3A5aQOuKyjQDIFe5n
8MZGCBfp+yINGcJe68yBLu853OSxfs34/6MzbdEjdei2+y4OP8yWU0pJ9adj0ZLxc
Ev2ktdqE10UyiSvdxmCnJ3ti/1aS5IFrXiA4lUzsABnZzIOsc6GmrEjSS/G/64zfl
eCU3scGGBnD4/YNDtkGoMFOSw5qJUHjaiHWnFHwqov5G8LksPfc7MhZgKqLmadzSA
uw6ns9crjn/zeppNCaO0RwN/dnRkGS1dIdZ7r7d44zyuxG4HPWeJOi/6a0mtGScrO
tZRjuLQItoOFAImOgZ3QmQELxUKapDArl/wQDl/J+dg7XGIdocpquWQRqi3gsaiu4
LW1T0hDvpqEBsj5wgPLr7f4M0hUNzF6sD3WTuj+wZuo+Z2/52/yEqinYUsR29NdJ+
8mb/hZ6T5atmeGnhS8pG7y7sj2NsanxEtvucQHdCWT3Di9B+jnUGVlfPtr+3jwKW0
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2020-03-07
 */

declare(ticks=1);

class OpenBugBountyCheck extends VNag {
	protected $argDomain = null;
	protected $argPrivateAPI = null;
	protected $argIgnoredIds = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_openbugbounty');
		$this->getHelpManager()->setVersion('1.1');
		$this->getHelpManager()->setShortDescription('This plugin checks if a domain has unfixed vulnerabilities listed at OpenBugBounty.org.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <SingleDomain[,SingleDomain,[...]]> | -d <DomainListFile> | -p <PrivateApiUrl> | -i <IgnoredId,IgnoredId,...> ]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argDomain = new VNagArgument('d', 'domain', VNagArgument::VALUE_REQUIRED, 'domainOrFile', 'Domain(s) or subdomain(s), separated by comma, to be checked or a file containing domain names.'));
		$this->addExpectedArgument($this->argPrivateAPI = new VNagArgument('p', 'privateapi', VNagArgument::VALUE_REQUIRED, 'privateApiUrl', 'A link to your private API (https://www.openbugbounty.org/api/2/...../). Cannot be used together with argument \'-d\'.'));
		$this->addExpectedArgument($this->argIgnoredIds = new VNagArgument('i', 'ignoredids', VNagArgument::VALUE_REQUIRED, 'ignoredIds', 'Comma separated list of submission IDs that shall be defined as fixed (because OpenBugBounty often does not mark fixed bugs as fixed, even if you tell them that you have fixed them...)'));
	}

	protected function get_cache_dir() {
		$homedir = @getenv('HOME');
		if ($homedir) {
			$try = "${homedir}/.vnag_obb_cache";
			if (is_dir($try)) return $try;
			if (@mkdir($try)) return $try;
		}

		$user = posix_getpwuid(posix_geteuid());
		if (isset($user['dir'])) {
			$homedir = $user['dir'];
			$try = "${homedir}/.vnag_obb_cache";
			if (is_dir($try)) return $try;
			if (@mkdir($try)) return $try;
		}

		if (isset($user['name'])) {
			$username = $user['name'];
			$try = "/tmp/vnag_obb_cache";
			if (is_dir($try)) return $try;
			if (@mkdir($try)) return $try;
		}

		return false; // should usually never happen
	}

	function is_ignored($id) {
		$ids = $this->argIgnoredIds->getValue();
		if (empty($ids)) return false;

		$ids = explode(',', $ids);
		foreach ($ids as $test) {
			if ($id == $test) return true;
		}
		return false;
	}

	static function extract_id_from_url($url) {
		// https://www.openbugbounty.org/reports/1019234/
		$parts = explode('/', $url);
		foreach ($parts as $part) {
			if (is_numeric($part)) return $part;
		}
		return -1;
	}

	function num_open_bugs_v1($domain, $max_cache_time = 3600) { // TODO: make cache time configurable via config
		//assert(!empty($this->argDomain->getValue()));
		//assert(empty($this->argPrivateAPI->getValue()));

		$fixed = 0;
		$unfixed = 0;

		$this->setStatus(VNag::STATUS_OK);

		$domain = strtolower($domain);
		$cache_file = $this->get_cache_dir() . '/' . md5($domain);

		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = file_get_contents($cache_file);
		} else {
			$url = 'https://www.openbugbounty.org/api/1/search/?domain='.urlencode($domain);
			$cont = file_get_contents($url);
			file_put_contents($cache_file, $cont);
		}

		$xml = simplexml_load_string($cont);
		foreach ($xml as $x) {
			$submission = $x->url;

			if ($fake_fix = $this->is_ignored($this->extract_id_from_url($submission))) $x->fixed = '1';

			if ($x->fixed == '0') {
				$unfixed++;
				$this->addVerboseMessage("Unfixed issue found at $domain: $submission", VNag::VERBOSITY_SUMMARY);
				$this->setStatus(VNag::STATUS_WARNING);
				// TODO: Unlike the "private" API, the "normal" API does not show if a bug is disclosed (= critical instead of warning)
				//       But we could check if the report is older than XXX months, and then we know that it must be disclosed.
			}

			if ($x->fixed == '1') {
				$fixed++;
				$tmp = $fake_fix ? ' (fix asserted by operator)' : '';
				$this->addVerboseMessage("Fixed issue found at $domain: $submission$tmp", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
				$this->setStatus(VNag::STATUS_OK);
			}
		}

		return array($fixed, $unfixed);
	}

	function get_privateapi_data($url, $max_cache_time = 3600) { // TODO: make cache time configurable via config
		$url = strtolower($url);
		$cache_file = $this->get_cache_dir() . '/' . md5($url);

		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = file_get_contents($cache_file);
		} else {
			$cont = file_get_contents($url);
			file_put_contents($cache_file, $cont);
		}

		$ary = @json_decode($cont,true);
		if (!$ary) throw new Exception("This is probably not a correct Private API URL, or the service is down (JSON Decode failed)");
		return $ary;
	}

	function num_open_bugs_v2($privateapi, $max_cache_time = 3600) { // TODO: make cache time configurable via config
		//assert(empty($this->argDomain->getValue()));
		//assert(!empty($this->argPrivateAPI->getValue()));

		$sum_fixed = 0;
		$sum_unfixed_pending = 0;
		$sum_unfixed_disclosed = 0;

		$this->setStatus(VNag::STATUS_OK);

		$ary = $this->get_privateapi_data($privateapi, $max_cache_time);
		foreach ($ary as $id => $data) {
			/*
			[Vulnerability Reported] => 21 September, 2017 05:13
			[Vulnerability Verified] => 21 September, 2017 05:14
			[Scheduled Public Disclosure] => 21 October, 2017 05:13
			[Path Status] => Patched
			[Vulnerability Fixed] => 7 August, 2018 21:47
			[Report Url] => https://openbugbounty.org/reports/.../
			[Host] => ...
			[Researcher] => https://openbugbounty.org/researchers/.../
			*/

			if (empty($data['Vulnerability Reported'])) throw new Exception("This is probably not a correct Private API URL, or the service is down (Missing fields in structure)");

			$status = isset($data['Patch Status']) ? $data['Patch Status'] : $data['Path Status']; // sic! There is a typo in their API (reported, but not fixed)

			$submission = $data['Report Url'];
			if ($fake_fix = $this->is_ignored($this->extract_id_from_url($submission))) $status = 'Patched';

			$domain = $data['Host'];

			if ($status == 'Patched') {
				$sum_fixed++;
				$fixed_date = $fake_fix ? 'asserted by operator' : $data['Vulnerability Fixed'];
				$this->addVerboseMessage("Fixed issue found at $domain: $submission (fixed: $fixed_date)", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
				$this->setStatus(VNag::STATUS_OK);
			} else {
				$disclosure = $data['Scheduled Public Disclosure'];
				$time = strtotime(str_replace(',', '', $disclosure));
				if (time() > $time) {
					$sum_unfixed_disclosed++;
					$this->addVerboseMessage("Disclosed unfixed issue found at $domain: $submission (disclosure: $disclosure)", VNag::VERBOSITY_SUMMARY);
					$this->setStatus(VNag::STATUS_CRITICAL);
				} else {
					$sum_unfixed_pending++;
					$this->addVerboseMessage("Undisclosed unfixed issue found at $domain: $submission (disclosure: $disclosure)", VNag::VERBOSITY_SUMMARY);
					$this->setStatus(VNag::STATUS_WARNING);
				}
			}
		}

		return array($sum_fixed, $sum_unfixed_pending, $sum_unfixed_disclosed);
	}

	protected function cbRun($optional_args=array()) {
		$domain = $this->argDomain->getValue();
		$privateapi = $this->argPrivateAPI->getValue();

		if (empty($domain) && empty($privateapi)) {
			throw new Exception("Please specify a domain or subdomain, a list of domains, or a private API Url.");
		}

		if (!empty($domain) && !empty($privateapi)) {
			throw new Exception("You can either use argument '-d' or '-p', but not both.");
		}

		if (!empty($privateapi)) {
			// Possibility 1: Private API (showing all bugs for all of your domains, with detailled information)
			//                https://www.openbugbounty.org/api/2/.../
			$sum_fixed = 0;
			$sum_unfixed_pending = 0;
			$sum_unfixed_disclosed = 0;
			list($sum_fixed, $sum_unfixed_pending, $sum_unfixed_disclosed) = $this->num_open_bugs_v2($privateapi);
			if ($this->getVerbosityLevel() == VNag::VERBOSITY_SUMMARY) {
				$this->setHeadline(($sum_unfixed_pending + $sum_unfixed_disclosed)." unfixed ($sum_unfixed_pending pending, $sum_unfixed_disclosed disclosed) issues found at your domains", true);
			} else {
				$this->setHeadline("$sum_fixed fixed and ".($sum_unfixed_pending + $sum_unfixed_disclosed)." unfixed ($sum_unfixed_pending pending, $sum_unfixed_disclosed disclosed) issues found at your domains", true);
			}
		} else if (file_exists($domain)) {
			// Possibility 2: File containing a list of domains
			$domains = file($domain);
			$sum_fixed = 0;
			$sum_unfixed = 0;
			$count = 0;
			foreach ($domains as $domain) {
				$domain = trim($domain);
				if ($domain == '') continue;
				if ($domain[0] == '#') continue;
				list($fixed, $unfixed) = $this->num_open_bugs_v1($domain);
				$sum_fixed += $fixed;
				$sum_unfixed += $unfixed;
				$count++;
				$this->addVerboseMessage("$fixed fixed and $unfixed unfixed issues found at $domain", $unfixed > 0 ? VNag::VERBOSITY_SUMMARY : VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			if ($this->getVerbosityLevel() == VNag::VERBOSITY_SUMMARY) {
				$this->setHeadline("$sum_unfixed unfixed issues found at $count domains", true);
			} else {
				$this->setHeadline("$sum_fixed fixed and $sum_unfixed unfixed issues found at $count domains", true);
			}
		} else if (strpos($domain, ',') !== false) {
			// Possibility 3: Domains separated with comma
			$domains = explode(',', $domain);
			$sum_fixed = 0;
			$sum_unfixed = 0;
			$count = 0;
			foreach ($domains as $domain) {
				list($fixed, $unfixed) = $this->num_open_bugs_v1($domain);
				$sum_fixed += $fixed;
				$sum_unfixed += $unfixed;
				$count++;
				$this->addVerboseMessage("$fixed fixed and $unfixed unfixed issues found at $domain", $unfixed > 0 ? VNag::VERBOSITY_SUMMARY : VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			if ($this->getVerbosityLevel() == VNag::VERBOSITY_SUMMARY) {
				$this->setHeadline("$sum_unfixed unfixed issues found at $count domains", true);
			} else {
				$this->setHeadline("$sum_fixed fixed and $sum_unfixed unfixed issues found at $count domains", true);
			}
		} else {
			// Possibility 4: Single domain
			list($fixed, $unfixed) = $this->num_open_bugs_v1($domain);
			if ($this->getVerbosityLevel() == VNag::VERBOSITY_SUMMARY) {
				$this->setHeadline("$unfixed unfixed issues found at $domain", true);
			} else {
				$this->setHeadline("$fixed fixed and $unfixed unfixed issues found at $domain", true);
			}
		}
	}
}

