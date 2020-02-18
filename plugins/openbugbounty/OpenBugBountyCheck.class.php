<?php /* <ViaThinkSoftSignature>
vJ+0x2DUzGuKKU+tOUdLohoPLPnIkH7lNhr7zWbsRowTtzyrkO7I5Cv+5EYNqyqSN
ValuGLqnaSFCzCfKD/3Q1fDXLtMa4i9oik3wXGqw31JXcWLPaTssxNdVh7eccVcf4
nUSGvZTURAHB3tD461buwSHK4x5IeJC8H7UfCgu2xKZmPIxW01tg+6d+JI2VLoZ05
p78QgevhWNnDdHAaWXnitERSUsWqQP+ojulO0RpEOm9tWQ/8/vmCP7egfk1fRvkzY
SDjFxO0A1lNS3ho9fyDkRkh4eNsyo4XpOr4SaE6EJS0ZViTZbgKHfZWxg88M96pO0
c2i0upWR6/cK0AA2oD6KkCo12KWw2kjKu5O+Hi8RHHGIy0yxu5GnYHgq0vXevZX3R
rkYgckOpHbOHL/ynrQYTiKpTKowbc9YsgvYg2GbKCgc8dT72GLp7cvQsb/hS/V+7M
878z3TK7gSeI0iEZ3rdqawCFacmA/Wf6cn5P1ddLO4qOOdnJfp05c5gP49z/aZI2Q
zsIz8wQ96U5sBctKO//nlecjUt+/bKR3tZ58UWpvbmk1RkSJOC9zBc9crhYKNyDYy
fxb2pGajCHQWg3crV/Qx4asZ9ag0Tky3JgQQM97SrlK3ioZezS3YlQSZlq0j1Xhd6
sqyUx45j5l9NgRKy5oFt5z6Rtd69BBloPFIoSQuhv0VCp0qENYCKjMpc4J2OFiQj+
ZIB/5RI/mie5aVVZJMeHslsScEgB7tTuOiGzNPY6FXHy7Dpmk5/Og4779l9ZAS1x6
twrxb0g8vYaoW/w9+HnDXoh4/FiCEup9NtXPDr152klduswpgOQauTLm8Iw1aDVIl
lNj/iNLTiWrm2CnxAmeTffdu62obSFikpG751wzzyqiWBubKUrCw3AILNuEPPOcts
5OcNacznrelP5D1CF7Xx7Nds/FDdIB4X1gd1ombEbpBQuaDN2pw7IPub8/ZMmM9fZ
05fBqLb6lzOK8ku6qOLiKbbst757vcIraINul7zyw8QXKp0fY9Zw16qnWquQAUvdi
eM1B6HbfXhLQ2FpGc1WhFVTCchSif7zOviMnDc1Dbp1BadJfBCfg7IjtbGEyhItHm
Eh6U/m/evT3bWK7p1OJZex9Qnnxkp3qKzQ8hzRoZoYU8G4dwSd7EBT7HiFJIhOTAV
bwSD7zdqXyED2Vy31HlvbdXkF86Id0C69V5jfh23eXUnVs+t3Vjl4GCz6JQiEvlWo
jY0PNM6PUVfk2GAOnpfI7ocAHAdHjD3kwsiXg4Bu/iPFpIKIKSXrLLI54VA3Uysga
vUC6Srjjck8Foeug5mBlaEP7O26Bryvs+iQ4tQwut17TZFQTOcl43u2cVi1jvJ1qV
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2020-02-18
 */

declare(ticks=1);

class OpenBugBountyCheck extends VNag {
	protected $argDomain = null;
	protected $argPrivateAPI = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_openbugbounty');
		$this->getHelpManager()->setVersion('1.1');
		$this->getHelpManager()->setShortDescription('This plugin checks if a domain has unfixed vulnerabilities listed at OpenBugBounty.org.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <SingleDomain[,SingleDomain,[...]]> | -d <DomainListFile> | -p <PrivateApiUrl> ]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argDomain = new VNagArgument('d', 'domain', VNagArgument::VALUE_REQUIRED, 'domainOrFile', 'Domain(s) or subdomain(s), separated by comma, to be checked or a file containing domain names.'));
		$this->addExpectedArgument($this->argPrivateAPI = new VNagArgument('p', 'privateapi', VNagArgument::VALUE_REQUIRED, 'privateApiUrl', 'A link to your private API (https://www.openbugbounty.org/api/2/...../). Cannot be used together with argument \'-d\'.'));
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

	function num_open_bugs($domain, $max_cache_time = 3600) { // TODO: make cache time configurable via config
		$domain = strtolower($domain);
		$cache_file = $this->get_cache_dir() . '/' . md5($domain);

		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = file_get_contents($cache_file);
		} else {
			$url = 'https://www.openbugbounty.org/api/1/search/?domain='.urlencode($domain);
			$cont = file_get_contents($url);
			file_put_contents($cache_file, $cont);
		}

		$fixed = 0;
		$unfixed = 0;

		$xml = simplexml_load_string($cont);
		foreach ($xml as $x) {
			if ($x->fixed == '1') $fixed++;
			if ($x->fixed == '0') $unfixed++;
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

			$this->setStatus(VNag::STATUS_OK);

			$ary = $this->get_privateapi_data($privateapi);
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

				if ($status == 'Patched') {
					$sum_fixed++;
				} else {
					$disclosure = $data['Scheduled Public Disclosure'];
					$time = strtotime(str_replace(',', '', $disclosure));
					$domain = $data['Host'];
					$submission = $data['Report Url'];
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
				list($fixed, $unfixed) = $this->num_open_bugs($domain);
				$sum_fixed += $fixed;
				$sum_unfixed += $unfixed;
				$count++;
				$this->addVerboseMessage("$fixed fixed and $unfixed unfixed issues found at $domain", $unfixed > 0 ? VNag::VERBOSITY_SUMMARY : VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			if ($sum_unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($sum_unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when some bugs are disclosed
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
				list($fixed, $unfixed) = $this->num_open_bugs($domain);
				$sum_fixed += $fixed;
				$sum_unfixed += $unfixed;
				$count++;
				$this->addVerboseMessage("$fixed fixed and $unfixed unfixed issues found at $domain", $unfixed > 0 ? VNag::VERBOSITY_SUMMARY : VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			if ($sum_unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($sum_unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when some bugs are disclosed
			if ($this->getVerbosityLevel() == VNag::VERBOSITY_SUMMARY) {
				$this->setHeadline("$sum_unfixed unfixed issues found at $count domains", true);
			} else {
				$this->setHeadline("$sum_fixed fixed and $sum_unfixed unfixed issues found at $count domains", true);
			}
		} else {
			// Possibility 4: Single domain
			list($fixed, $unfixed) = $this->num_open_bugs($domain);
			if ($unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when bug is disclosed
			if ($this->getVerbosityLevel() == VNag::VERBOSITY_SUMMARY) {
				$this->setHeadline("$unfixed unfixed issues found at $domain", true);
			} else {
				$this->setHeadline("$fixed fixed and $unfixed unfixed issues found at $domain", true);
			}
		}
	}
}

