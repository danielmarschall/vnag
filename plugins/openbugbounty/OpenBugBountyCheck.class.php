<?php /* <ViaThinkSoftSignature>
D5ywBZegRuOX5CT35IVYKi1Y2lmXoGA7fv9U8Xo/t+6u/jHti2GaiazfQLaikgVgA
NDUvsjLNnYRqk5zx0eFyMxVdtQ8esfJ18M8YvmiZX2eZm7wavgqGTHzTKLOlS3TZb
WBsQrPgDisqeBjPxKDF9B10ZJNej69YFLZsOrLY35sDIOP9vFkaKleKXXXe+XodxD
QZGYEwTV3QZV3/gIm3SSgqSChx/15QTV/saxHNe9xEYdn6JPCNQT6u4Jbdx2F1pxO
4AwSi9g7CFG6IOy859tLRPuUS9/+0j8+VYzvBZWcPq7xbrccJ7Quq9F96H8On8xeT
m2ekmGVVVUJfpe6bWeOLtESsarOIi1D+Ywlm3Ctk3sZ8+TONrXbaKS5dHeVPKNTdT
wSDSNwmXgYn/DAMw9JJZOwadiWbE+e7yr3DUUnMpk7PTghSCyf89cqOE7Tk+w/a08
N6I6Mt7Ad/W7VP14bmepdbAaNp2ihuR9DSkiqd0Q4gPu9HiDwQeFoLF2uARwPYTLC
uJRu6AE833K8v0DHs/Mt1pKtaiAd90ZElDscbACJWFeQDUpI13/RNiU6zNL/IsyMx
yQA/2Ehk3kqDKz//EtEev+9wdJBIJeCU8U0+JCrlr1kOJKKo1gbIb+AMsY4ODdw3a
DgfGCSeEfjE0ar0FtPNyN5/6Zxyf+m1ziJn8xY6r56vXR7+bPb7nxr4gopF04QJ7z
TyZpn8PJKUKVZDmlO7N12j4HPvM4c/KoGh4GyBmIkuuSQWkm5gZbWZiiC5sdPIoA8
JYlLd1fkiOZ0o9Vpcdwa6Si93nrmTDP98zg/DfgFtrZjJSpqcbur66ilhgOw8ZLew
pckJL57zC72nuqPbEYhNmJixcMuLGI29g1ISXkvCAnkdSWH1gwLgJfDCJ9pWAjoS5
/eqrFof+4OUG7MD0+xmf3135GYwB0mbUQh+4t3pRDQgHnluXtQnb/93XCBX28qyzV
A8zM9dHEhzeJsHxCthOQQshgSliLfACA/0/CCIoSwrLJr3th8VVNoEy9YN2ovfN10
1n3t3HOAPU9SiWFMfOVTtqy2OmtBcXvMxBotXRGnHsB5vDeNa9XDuLJEJqeL+FmXp
sjY5UkayvkliF1qjwVRX7hP64+UDDPVPa+fPg0ITHZTQdawfi8AxRwOzjjH2h/Rbj
mNsePALoCoVp/0Oq9kIwxCTWYAHjFY81Tt0GtOHV2Yo9Mh9WGg5VHhDFFbHoC6o0p
S5CUM3HaByKWCfqTgNsR4govazSZKqigbBBA5whW4BPMALNukmU/Fiu5NPKF0S4bR
xr0g31lTS3K2cTQoKrI8uYbhaG8e53DCkuia3F+/76U2UYV5SelD8/qJO8Z872p88
A==
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
		$this->addExpectedArgument($this->argDomain = new VNagArgument('d', 'domain', VNagArgument::VALUE_REQUIRED, 'domainOrFile', 'Domain or subdomain to be checked or a file containing domain names.'));
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
		} else {
			list($fixed, $unfixed) = $this->num_open_bugs($domain);
			if ($unfixed == 0) $this->setStatus(VNag::STATUS_OK);
			if ($unfixed > 0) $this->setStatus(VNag::STATUS_WARNING); // TODO: Critical, when bug is disclosed
			$this->setHeadline("$fixed fixed and $unfixed unfixed issues found at $domain", true);
		}

	}
}
