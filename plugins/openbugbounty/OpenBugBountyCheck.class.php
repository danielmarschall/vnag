<?php /* <ViaThinkSoftSignature>
tnq+qJ0FVMFjtw0VxxXU3yGcuwQtR+YxngRuF612pYRja0m6907iUi6E2uuTAiK6d
lCx969+n5MBG0N9yM/vPLKowBodxgEAaE5PlS5cfU2WKqbPbrAI6yWjfMruy5OzOi
KV2wIIWY+QR9HuNDKvO5TQjFMaLvXdOqNZp+bCP/YDiLJ4oq8s470/z4MZu/jz1ou
2pLzyjyDdaMAjrphpGrG9BY0eS1j9EQo88Kv9sFJrmOR+QNRawiMSL1Vuy5XxbriO
VH65ZkZ6hs7NPsojgKM50OQUUmRiMi99S2CCqQHPh2O0VaZMB9hQ4NiWN5wyjExMN
v5vVpWLFDwG20YKNWdfPd/hADcJ+W3E17RuDbRqphzEJlHcgKgLMULmCCT0H7XWb3
NP3iKqVJOGnt7SVDXPKsNbjP2oA6/gAOpBZptV/i95f0kplJ69T7AxVmoNg9dWJnA
JMOmpteZCmdZQV7vKbPvCLVOTMh9/Q9OFe877kjRaEAQJaPtrdus4Q8uhvghRFiiL
yuJbsZgIAnZvliEe9jDBPCFxTC4tMDqoG5rXRltz4J+Ig52L9AWq0bSf9+AywMjdT
c1jS22mBcqC0rx2cmKZl/AWutrBisVeQweAaipRncW85wyZMWgSB3lowbMKZHNqZV
YCZt7QSxUGPZAIKy51i6QivhJaaQhvnCZW3lkQGZLqruuXU7QJzw6BzW+aMz+kWqM
wMHANFDgw/VusaSWW4a+oaYCyygKRiRkb2YQE8U2EObxkaDDEhquWLHhqEJ8F8kly
2aZghC94ryvIkMmjUCOhxJ9a429MyDrochi4RLI9OkYF4WmF4AkqFnqYJWf73kRUV
mLpohXJGLaRp5e0Q7dxJto9hy/I/6yntTREvnDkm19cY8lHceJPRv3YbuSVybMha4
9nf3KgaF4hmAwogIqTcSb5f18uqMC+Pp4sZaChQnpbC+K7StY7lI3dWL/MINHUGRX
yM702pX2l/WSbflcWcvHaPoOkfkvJwP+R5BZ/GIB5F5Yv5Q4K4BDNs23u2stvbzuK
6NyheDgjSRF+PckMy8AmIHtGMn4wBTbw+mH+nmBnN6HmQgqM6zHpU1CwVw1Q/c2IP
xihKexQelORhik6WyUWXR8GPT4PAFUOkIKV3ayKibd2zLDAd3YM3J4uDbEwp3vg/b
neIWy36vzf6xGnPFig0qobZGIisfVMMpvnZkXA9c67K6LFNEx1eOlW6Cx068NZqZW
l1s/Q8qJ8UjnPurbrQ4k1v62ZHMy3s9LbiNRyMEt5kdjCMFNuc1jWGpLwp2rw5WKC
O5yx+62O5GPX+qZpkIjxZwe/3woj5dGiFFdsCo4afv4KitzI3czXMCDgiL4oTanQQ
g==
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
		$unfixed_ignored = 0;

		$this->setStatus(VNag::STATUS_OK);

		$domain = strtolower($domain);
		$cache_file = $this->get_cache_dir() . '/' . md5($domain);

		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = @file_get_contents($cache_file);
			if (!$cont) throw new Exception("Failed to get contents from $cache_file");
		} else {
			$url = 'https://www.openbugbounty.org/api/1/search/?domain='.urlencode($domain);
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
		$cache_file = $this->get_cache_dir() . '/' . md5($url);

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

