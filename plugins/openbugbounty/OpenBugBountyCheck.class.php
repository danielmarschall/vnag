<?php /* <ViaThinkSoftSignature>
Df9NfmFmHOn8nGetEQpngqn15a+ckYK/yw+2XGr9FP+WXDpB7WLwB+no6P2OcHzm3
ODFOZ5lZYUWnvFovCkuRtpjcFDiBvFM8hjdRXoGmLfGrUHpr8W10oVbuQtRbTPT0P
MsNYV5O+xkCkIsnryk6T6YFiL7zRKHCuj697tTbzVv0fYUGqW4S5rAU7PgJqyBZgr
8azwaT6q4Wof74EEVf4ol5R1uvI6q2zf36Lk7dIObehz189E2+GkXzAWAixbc+scl
CzQ3hhVOjZHL6XFz0dkTaUYaJW78A14agFK0lMVB4c9TDzgDYVL325FtkSOgeAduy
5XmIvC8MrOMqyCRPT2qqOlseSkXkrK9zkyy0Cj080Muu3TC6T0jYA0I/HdnlU2ryG
n2GQOyPPdTZByDSTijD5288KQ9xgWXuL9I3x7sslll2J3O9HPAkvVsXcn31B3JwAG
JqSd4J02jPVx24Vw7GQIQRcMmRnpU5PKInzfLq5QCsx9JBcOISl+RHzkMjZqaJveB
577GXCnxMWfnzYkmBR7PZLvDPr6Vu37Wl5SYSA49OBEAJ20Fen3Yk0IX6UdVPlvRM
EA1MgAvw9pHuHzV2fuAegDbRYeUVRzTYd+56Y3v1UcH5UKoML/OT/zi9VHfGKtglm
dUALn66c2bKGErATQ6h8mhYMEmszgU1O3va2XCP6GkQkctcCw1Cx4zbtZnCoE8mI0
siWLa+aLIVgRDdBqbR80a8WnBImZRKRP+ZTTSBQ5jDxHC/Vgr9YlNExNpA6BSW9mp
v56COWgRMDQH5qCd950fh8jRao5cuhhf75DvUGeai3bx7V3dqwPmieAOaFNUhV2CG
iZzUPowmQx0uqaQQx5wxdehxbZUXtMChO9fIA+xdECgeGHIgI+0p31e0SR9xrsZmY
+OpHlWRm/KbfM6EdNICh1W7hxAVQwyEhusqVU9jKiDtVzxadipfVw1ou+QT5SgANk
ZuwnTxIIs9R2QezRq2yydz45eKW2d5RXb4T77vE1YSPTVaVnAOScAqVrLQK79QX0J
sOP+47NGMfp9kUxInGV8UlzgYMBVZTT+ezIXmr6vwlg6Dj27OYReeminNoA/eku3L
Fyya17WUAd0vI4ByJsiRWRoJv6z2l1PDtOpwp0uZs57fqyUZJTUIEELdNPu89l10g
uK4tpsB4MHsWG9vGLy+1yvVbmD32kM5ZvHgMFktxXx1AzC3gAI52mQ3ljJOZ1LFX+
3q7nkMcN/HWolPFsRbsuc2rF7R6pQE4mkkVepoAlt+T6UFDnRykr7C+MRZ6k2upun
ESXhOqJcuyItT24eizoccENvAVhbGpZ2pfirheqZ0v+FlevvgASyNSUPk+tp/uWMx
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-11-15
 */

declare(ticks=1);

class OpenBugBountyCheck extends VNag {
	protected $argDomain = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_openbugbounty');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a domain has unfixed vulnerabilities listed at OpenBugBounty.org.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argDomain = new VNagArgument('d', 'domain', VNagArgument::VALUE_REQUIRED, 'domainOrFile', 'Domain(s) or subdomain(s), separated by comma, to be checked or a file containing domain names.'));
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

	protected function cbRun($optional_args=array()) {
		$domain = $this->argDomain->getValue();
		if (empty($domain)) {
			throw new Exception("Please specify a domain or subdomain.");
		}

		if (file_exists($domain)) {
			// Possibility 1: File containing a list of domains
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
			// Possibility 2: Domains separated with comma
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
			// Possibility 3: Single domain
			list($fixed, $unfixed) = $this->num_open_bugs($domain);
			if ($unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when bug is disclosed
			$this->setHeadline("$fixed fixed and $unfixed unfixed issues found at $domain", true);
		}
	}
}

