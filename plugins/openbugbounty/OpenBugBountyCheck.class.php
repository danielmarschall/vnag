<?php /* <ViaThinkSoftSignature>
mzaKKaKFfLfRIsTQTH2cX8a9JlHCc6jY10g8Ejq7bD4x0K3yVK7cy++q5gSLwMtR9
tdpLTt+r1f/OjfXplV0M16fpBZk8Ug2KCH0XcK1Aa4p8Ulw+jfEuw29h7oWXmOwhN
V6ThYMvpJzHjf5vW8e6uo+iT9lAHNmkA0eQ7oYarLO3uOUetRZBj9/njNJfhmHG1k
oSrJ0i7DSf9dbZQP+LOI6KGkx0sPbX424WszMbS/JZwRrEQK950h0jU9g4E73Vafi
bZEZT+9CDy2OhgmfpMubKFwehsNGm7ZJkN6uhH07kNqd9zgwSHnrP9g/Xdxerm+Bd
hF2UIt+FvnXsg1dP478YW1mN0pnvZN2DYTg1BCewgwqsJ7vTOzyxcMTKbDENfAddY
MeQMefZbxQs5IYajnciJXxYplVp7LiW8Nw5xsELchAZNkfOzrdFIOiCCpOKgA7NME
m6MBXDTQK9GxstugP0wO8e6tu7YYb8wrIOS/jtLmNsHMXwYD7sPIyUpXrs+fjtYCg
pKHWabY6pjEWesIYwQOSyaU7TOk0gxmNeYTw4IBTcnjkbG8tuzLTez4aJWJJUc0B5
IUJrldu2ftKu8cNguaRdcZnL9IPMl1bFwXAJgKBUGUJglQMIh1zdccsy1G7hphicK
6N2vWkF956yeJZYJN3qvPl+su1gSGmPxUZfJJuh1uG0WnjEAbQ5YcRKFmo+zkrsoR
j5NoqXH4ExP8an+Z2V6aBgWBG3HZXcI+df19GuRkJWFJ9OrCUJ5zlg6d2bNP9CE4o
xHnaRnoxWPGBSQZ+DScRXPcGP5IruYQohkvJGloszvid826BZkziUW22dydu+6Vw9
fWNgDe90sGQvHGragPTH6oZ2RLn5YK2RPPHV8kZvrvfbELICXybwUz5x3jNEamAhD
1DMrqse962LOMw3XHXog53+yH4eVOJe676M1eeAqGnx8onj3ibR1+G/1mO0xc5v8z
3MQfNFPFHQDKichstUX24uMAx7rOl5MzYEC9/DtdwTibj1VtnGKr6uXYlZKB5e/hB
3xqoyBGH4M7dPgJizCWRCM/P4DwIKUAjNJG7+0ZYFhehRhIHWM2Lg1ns9DkCU4YvI
0DDTmTiTEWikZFRBktB3y4JbZ+lNKSQKtguyKdLgNIcbYce3qkjMLkcsxsPR0apTY
hNWbXbBabOTvFNkKDz2jiA1DLxyNjMUYwhQTysRBsqO+YxFMKC1nRIiLhuYkfqTnH
r0dbbkLNFfoekQoR/18DnH4d04wz4s3OcDr++NTO1i5B+u6Q5bl7+ur34Zi9KZOcq
707rZuZ3BaDezP5G3rK1QcpMCM+jIdfxBxRfKWon3FsMqbqtD4vUM1xdK5EPRJE5S
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2020-02-14
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

			$this->setHeadline("$sum_fixed fixed, $sum_unfixed_pending unfixed (pending) and $sum_unfixed_disclosed unfixed (disclosed) issues found at your domains", true);

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
			$this->setHeadline("$sum_fixed fixed and $sum_unfixed unfixed issues found at $count domains", true);
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
			$this->setHeadline("$sum_fixed fixed and $sum_unfixed unfixed issues found at $count domains", true);
		} else {
			// Possibility 4: Single domain
			list($fixed, $unfixed) = $this->num_open_bugs($domain);
			if ($unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when bug is disclosed
			$this->setHeadline("$fixed fixed and $unfixed unfixed issues found at $domain", true);
		}
	}
}

