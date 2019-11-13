<?php /* <ViaThinkSoftSignature>
kWqDAxadQcLolAtJBJHGdvsMbOHCDuV7iWSIH/9pscpZ6v9PY00h90t5U+hl0YCMK
orI+CWgQQn4ezt2thMpOKZT/OtGsjCKGkshY3BFj8go9ESdXLEAX4oUgh3+292zDN
9RGpJIRljZU1eWeiOxUl6V9lSqhbMdIONuAvK0AfKmEwzIA6NmJq4VaXqUedj54WK
YcIOot11dUmeYd3H9lOjjc9hEV33ITVLNt9y5uTdhQ87DfxLOHCsyp1fupWw/aPge
4pNxirv3MdLWu0AjveExwA7X4BbPTwjZtNaa30ZI5gwzbNyCz9U5aSyxo8Nwu5ahZ
YYMrmjXMVyYtJECtJWmytbE3wlyc7EynAhWSgzZh4Lnlba4MiE9GlTiAuVaGqfibm
loql5DfTGBxkrIjeBI0ErdW61/7nq+Cj8WYtRYKWTpaUVOkmSs2c7rlzISCQXZ7Rd
wCH5vMGY5XXs4pxZtu6JXPDf2ziPkbKQrVCWdjq+vGXrxmVYVJc4CuQzyqtW37pOi
q7zlnDONuV+ps3PjLCVS+7KiOJhlVtG6prcloquABd4ndmE5MpZL7Ykh6h6q8IVVh
sheMcxihKShMJjBavFImh/pY6sOQ8AFB8piKxOYqUiTlrTeJoXuSL+AjRbt4L658i
CPq454YuEqvS6BERttgwcqrt6G8ncnUWnICZofe9qBwUztVGC1l/7a4Ef39GKfgYl
Fu/xPn1dGqEVu3kJfvQPekjd2Qp4IBUu2PotVDxnklrAgv0Fnb5lExJzsyByVp8nq
mkhNFNb/U5aFx8CjNk8x3oGTnfhJy4e04x9WX0VqMhQ/nqzekJVpzr7mOLBYbI8zE
jiOtDH1V+b4CLQ6/3jxWL+Vbt37S5gBkNQEynhOed487hSXBDiSog1iPYYtBSpoaZ
D+G2Cb+wMKRNMNAdL6MaiyYHc0kWrnmfMmxSF0t/Gf6/D7QoJIntAR2QLI9b6JWsp
tZo8kETGAfWfZ7JhPk4/B2o+PH4oMnd6qoDAJ4xI1MRT3vNYJ1aC/wXYUsbVLiWuB
edFi8cXLgrKzDU/fDA7tb+LV8yWbbXV9EF8vdlmAEE1S62CWAmRRchblPsPva2N4l
uigpf38Lk7mLQ7/SamkpAlDXzYUR8cbNEpvUjdeeOTlLf0sWZdou4ON5HDlCnYa21
D2Hfd+sYFfvfu1u4v7t+BwA/TfC6JV3zChzBtFSq4zrPlpU38duCviCaMlRfeA6os
AZDhcCxjrdNs6P/xGtMXJqeRl6AGQVm4z5DC6zDpDhMqhVGvZ+LnmYLu+9nWcdOin
Jx05Jkks5qChGeVhigG0ACWgznuvqxVrzxsQtO3EaAfF12C1Y2M7Hf3u2BsxB4cb2
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-11-13
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
				if ($unfixed > 0) $this->addVerboseMessage("$fixed fixed and $unfixed unfixed issues found at $domain", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
			}
			if ($sum_unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($sum_unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when some bugs are disclosed
			$this->setHeadline("$sum_fixed fixed and $sum_unfixed unfixed issues found at $count domains", true);
		} if (strpos($domain, ',') !== false) {
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
				if ($unfixed > 0) $this->addVerboseMessage("$fixed fixed and $unfixed unfixed issues found at $domain", VNag::VERBOSITY_ADDITIONAL_INFORMATION);
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

