<?php /* <ViaThinkSoftSignature>
GKHpDZMbCYVTA8LdpQ0BJtXNS/xsd1Q8vkqgeT5X+2hx792Ip+IcJaFR0PDwo4uLD
R36CGW3YQmkNZem3CPQr3MHXe7IDse5TabN0UwS9pgxp+nHLvUKq0UMpVE7Yd+HOl
azSktveAoacUlCXNOozqyFv6O0Re29KI7vVU+iqleF2LwqUlZ+lvA8+8oZ70Xv89P
zt/giYbQaAeVa2ALd9oJu9yERnkyoa1l976dwVWNoJBZlFTiujYcGcbNlQojaNV7+
/XA7oftS45nGhyMcwgvrgZYbDlOTOrqG6A8CpK5geELGQEtr3MVlTCLPBXM5zTnjj
6FaKd9innwyatdwk60watfSAtp400iK15wVF2bkk0BDXDYaIwRQ1/sAuawkK6opU/
d4+rgxpIdrojPrnS9Zwa1oCJk/oF2iNCytiMaG7cZafeVAtpKE4mCfhgkH3BNZKOX
VVcLGuET3QpqCY8HaaLv7LnRlbS8HKkScevGPFi7ci7ywJE9zbk3cwa3nHXW7tl+z
6zS7CylFaIkr1UegsGitZbbn8VpaAWYKNOzzvfnI6a1txWQA1hdslFUIh8URyAaXI
zS+IWgSIBpPfTdyLegEuE74MIbuwRqHHAZuyyVnvSmjzaM0JNuOkZ9/l3PkxBT8VO
il0RBDucS/FPlw9gzKYfrKpig8I6kE6pFdziJ56suvyYKr/0RV7FljiKTLMJ5YpDz
OzFxDVP2kVnhkCXGCNRiVk5t1P49hmZ+neMi6rHfOVuxMSdiNHUfdS4rjvQorvv00
uAaRdqzRMp3sd/QG3Du4SDx7qtCadktg2yGjYIZOV12nCa86ERpRuXc3Sip2bcVMd
D6YG+jVrcC+XWKvR7BW+LXQMef0JKc2rXoNUu53SAzo3lKfsMxcQ7fFZ4xfXa7aES
bdiI1gEFnLcr7tZ1tkSVWRV3Xc3um2xcuwT6O+yI+xBcdj/5ybDIjXRWAuxqoHGiF
A68tkTQVSy50hgHy/brxK51Rf2whK3Mf01dNySneqsmRYFXCRVb1+kMQcTyijuAM1
NUWcHETl3/gLUFcP9cS36mxNwwWsaSfrLYCIncOUTEYsLyoPURjmTS7jcCbb1+6Za
kLsAM2tPVi5+s4O1MK5e28cNbsELReRFLI1D5VQUUyaVUCdjg8tKOfwiixNjIdWbD
c6lwugjnZDsJRdA6n3EgMOkbLtcYoWDZWtM8kVtTr1p7MBt5+Y+BHd2YH2h8+S/6Z
FukXIDkxQ78hNCtpr6/UseL+R4/mkGJ9DphOS5r49+wviURMzGGFg6eip5GQawAo7
fK8eDLRj1dCnU4L4dsePjDD8BJVht1QfGUsQ/4Yraf1Z3UH6wpN8dc7L7feQHNDmt
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
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
		$cont = $this->url_get_contents('https://www.openbugbounty.org/api/1/search/?domain='.urlencode($domain));

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

	function num_open_bugs_v2($privateapi, $max_cache_time = 3600) { // TODO: make cache time configurable via config
		//assert(empty($this->argDomain->getValue()));
		//assert(!empty($this->argPrivateAPI->getValue()));

		$sum_fixed = 0;
		$sum_unfixed_pending = 0;
		$sum_unfixed_disclosed = 0;
		$sum_unfixed_ignored = 0;

		$this->setStatus(VNag::STATUS_OK);

		// Get Private API data
		$cont = $this->url_get_contents($privateapi, $max_cache_time);
		if ($cont === false) throw new VNagException("This is probably not a correct Private API URL, or the service is down (GET request failed)");
		$ary = @json_decode($cont,true);
		if ($ary === false) throw new VNagException("This is probably not a correct Private API URL, or the service is down (JSON Decode failed)");

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

			if (empty($data['Vulnerability Reported'])) throw new VNagException("This is probably not a correct Private API URL, or the service is down (Missing fields in structure)");

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
			throw new VNagException("Please specify a domain or subdomain, a list of domains, or a private API Url.");
		}

		if (!empty($domain) && !empty($privateapi)) {
			throw new VNagException("You can either use argument '-d' or '-p', but not both.");
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

