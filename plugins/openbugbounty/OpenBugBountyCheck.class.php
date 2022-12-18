<?php /* <ViaThinkSoftSignature>
d5Ch9xyUxv3Bu3N2jbIjfBn2mP062FT5Kdj1z31EUW9w+rJMXfvaeidWQfLapqU7c
uIAztDKoRCMsAsvZYSB1zD8+pi0ClJYGbiB+/7ACt1TSVOvtrWxE9lsWXIOjLA0Ft
YeccOh4Mf/JFtLbLyo/xQofsIy+9umKQ1fbhgrcmJfaWY07QeoZVE7HHauTZA+ld6
HyjNklTAc9b3tcFQBp9bgB3p4Pt28y6irIqDhaqvBja8F2oCK7FCZQFwE/JF0UKT/
PMOJ0VtzC1p72SiOWMg4U0+hMi4Sre48SuSOiUtLaF14GdzfATGOxOqyFvFAFXClh
qUqgxXmQTFJCZzGW2rga9BJw35zTYy/jtWLN8nGFG+S2c7e+IJAqN+iDMD7I+NyRd
jjZKqDjXDZrIolL6KP2yl4WPtVD8iaJcgrxUQD+TQiJkcjvSEp2DVN0OETDFUzlx2
Jr9hc14n7z78ebkTgNToAGauBvfyFRCEyRBcXyS9VluTd23g3ICXY0qdWJUhf4zpz
PJPdgAVmO1X8kPaVB97GI2iQDqIMFLr6PEre6GTaB893B3lrej8sTdAiV8WPlzKmZ
LHxxUr2uQdIT2NkLbvvzNSoJKsWXKdmbvWk3Oy+VYGH9LEjw+bRTweubH36gYf4to
QpoiUcnQ4B1f9GxDUp455aPtN9HmY7dOb4YehyWCFKge/1PUoT3/omz4cRlz5+8QM
aYOB1/UgdeCUS674R3tzhr28LhNfJ8J4pyXzOVjVsmBkmMd+he/4MJcXWyDYDWpVl
AMA700hkscLcwktA25TDd/Qh5vjF4i6dWcNlIbFxdaKyN9PTs/0T+Jza0JaDmZagW
gYD5R4+ei44asrZ37oAg9CjOeDm7FKRuMYiMaw/08LAYGQKFxgIAsMdcKOHya4By/
5yVVEVLihCLKdvdn0u9d1d2BQHZS0n2KNG4cMQxnNKVjjTXDybRbyB2C0pp2S9YXN
V9WWzveDY5m8jYrZ9wFjvbHeJHfFNXMrkN1k4U7mYM5Be2Mu2+MYA/Xw2oLtzmmyt
9WxiCO0B4TrEKOJYV1jC7BDWoAAHNnDxFoOJo+dm/hjTU3XOaWSpsxTURYFR/Ltn5
G1yakcUq18mS5c2BEXpwXaJq5+f3QP5COzEcrvgGqBJRfpy0JxsRtKAzTQpADzRtR
8jI68XrP4y9FrcUazM4RPp+U8dRY2zjMas559t7Xb7RysHRpFxf2SZ+Xj0SVQZEsj
YOATRaQdV+a6haeDvB346iJIjVaNY6SaaMPI5JEhAr79BM6nJCXIu7HT9DCBWvhbG
MCePzFjOa4XRB1vaCJo5jolQUWyy0pFLKD5VbimI63wd1a5KqRPmtSFAu+/nQ3aKC
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-12-23
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
		$unfixed_ignored = 0;

		$this->setStatus(VNag::STATUS_OK);

		$domain = strtolower($domain);
		$url = 'https://www.openbugbounty.org/api/1/search/?domain='.urlencode($domain);
		$cache_file = $this->get_cache_dir() . '/' . sha1($url);

		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = @file_get_contents($cache_file);
			if (!$cont) throw new Exception("Failed to get contents from $cache_file");
		} else {
			$cont = @file_get_contents($url);
			if (!$cont) throw new Exception("Failed to get contents from $url");
			file_put_contents($cache_file, $cont);
		}

		$xml = simplexml_load_string($cont);
		foreach ($xml as $x) {
			$submission = $x->url;

			if ($x->fixed == '1') {
				$fixed++;
				$this->addVerboseMessage("Fixed issue found at $domain: $submission", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
				$this->setStatus(VNag::STATUS_OK);
			} else if ($this->is_ignored($this->extract_id_from_url($submission))) {
				$unfixed_ignored++;
				$this->addVerboseMessage("Ignored issue found at $domain: $submission", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
				$this->setStatus(VNag::STATUS_OK);
			} else {
				$unfixed++;
				$this->addVerboseMessage("Unfixed issue found at $domain: $submission", VNag::VERBOSITY_SUMMARY);
				$this->setStatus(VNag::STATUS_WARNING);
				// TODO: Unlike the "private" API, the "normal" API does not show if a bug is disclosed (= critical instead of warning)
				//       But we could check if the report is older than XXX months, and then we know that it must be disclosed.
			}

		}

		return array($fixed, $unfixed, $unfixed_ignored);
	}

	function get_privateapi_data($url, $max_cache_time = 3600) { // TODO: make cache time configurable via config
		$url = strtolower($url);
		$cache_file = $this->get_cache_dir() . '/' . sha1($url);

		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = @file_get_contents($cache_file);
			if (!$cont) throw new Exception("Failed to get contents from $url");
		} else {
			$cont = @file_get_contents($url);
			if (!$cont) throw new Exception("Failed to get contents from $url");
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
		$sum_unfixed_ignored = 0;

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
			$domain = $data['Host'];

			if ($status == 'Patched') {
				$sum_fixed++;
				$fixed_date = $data['Vulnerability Fixed'];
				$this->addVerboseMessage("Fixed issue found at $domain: $submission (fixed: $fixed_date)", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
				$this->setStatus(VNag::STATUS_OK);
			} else if ($this->is_ignored($this->extract_id_from_url($submission))) {
				$sum_unfixed_ignored++;
				$this->addVerboseMessage("Ignored issue found at $domain: $submission", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
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

		return array($sum_fixed, $sum_unfixed_pending, $sum_unfixed_disclosed, $sum_unfixed_ignored);
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
			$sum_unfixed_ignored = 0;
			list($sum_fixed, $sum_unfixed_pending, $sum_unfixed_disclosed, $sum_unfixed_ignored) = $this->num_open_bugs_v2($privateapi);
			$this->setHeadline("$sum_fixed fixed and ".($sum_unfixed_pending + $sum_unfixed_disclosed + $sum_unfixed_ignored)." unfixed ($sum_unfixed_pending pending, $sum_unfixed_disclosed disclosed, $sum_unfixed_ignored ignored) issues found at your domain(s)", true);
		} else if (file_exists($domain)) {
			// Possibility 2: File containing a list of domains
			$domains = file($domain);
			$sum_fixed = 0;
			$sum_unfixed = 0;
			$sum_unfixed_ignored = 0;
			$count = 0;
			foreach ($domains as $domain) {
				$domain = trim($domain);
				if ($domain == '') continue;
				if ($domain[0] == '#') continue;
				list($fixed, $unfixed, $unfixed_ignored) = $this->num_open_bugs_v1($domain);
				$sum_fixed += $fixed;
				$sum_unfixed += $unfixed;
				$sum_unfixed_ignored += $unfixed_ignored;
				$count++;
				$this->addVerboseMessage("$fixed fixed, $unfixed_ignored ignored, and $unfixed unfixed issues found at $domain", $unfixed > 0 ? VNag::VERBOSITY_SUMMARY : VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			$this->setHeadline("$sum_fixed fixed and ".($sum_unfixed + $sum_unfixed_ignored)." unfixed (including $sum_unfixed_ignored ignored) issues found at $count domains", true);
		} else if (strpos($domain, ',') !== false) {
			// Possibility 3: Domains separated with comma
			$domains = explode(',', $domain);
			$sum_fixed = 0;
			$sum_unfixed = 0;
			$sum_unfixed_ignored = 0;
			$count = 0;
			foreach ($domains as $domain) {
				list($fixed, $unfixed, $unfixed_ignored) = $this->num_open_bugs_v1($domain);
				$sum_fixed += $fixed;
				$sum_unfixed += $unfixed;
				$sum_unfixed_ignored += $unfixed_ignored;
				$count++;
				$this->addVerboseMessage("$fixed fixed, $unfixed_ignored ignored,  and $unfixed unfixed issues found at $domain", $unfixed > 0 ? VNag::VERBOSITY_SUMMARY : VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			$this->setHeadline("$sum_fixed fixed and ".($sum_unfixed + $sum_unfixed_ignored)." unfixed (including $sum_unfixed_ignored ignored) issues found at $count domains", true);
		} else {
			// Possibility 4: Single domain
			list($sum_fixed, $sum_unfixed, $sum_unfixed_ignored) = $this->num_open_bugs_v1($domain);
			$this->setHeadline("$sum_fixed fixed and ".($sum_unfixed + $sum_unfixed_ignored)." unfixed (including $sum_unfixed_ignored ignored) issues found at $domain", true);
		}
	}
}

