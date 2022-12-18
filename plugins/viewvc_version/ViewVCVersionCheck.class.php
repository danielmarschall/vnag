<?php /* <ViaThinkSoftSignature>
CmjFiZLK2FpfPQ5dciAKEBihjx7LF9tbFssLowaSzj20S0kXMd/vQEZTVivanUr2+
sCRxMjiFbCZT2X7YurXKAvvr2i/Rdh5lyedBddXrwrbhrGhomKtE/iKGHsJuUIyNs
1GEOY8m+mQD5Dxf7GFE4xdGUkr8wRc/whKv/f4VTVm3S1PCBWlEfv3RHqfkOw0x1d
g5Yo30yTk5nEGZuoxRjCQPkXgvPLHoiRcsKq22xzZdCREL0ujlK7AGWuPIVOxk7q8
qmtzF48LIfsewV3O6U4uW1yfJyqwbHFtMCpePxHKeuTkkOs7ZLFwEL7vOaKvMwKcf
F70BW62yltBl02IliqsCIluQ177KXf2RiXduj19sU71Gb483J1JjK2/wu2SNJg6oH
GEcaQ2bwOA+Ymj4EmZsczTJLmKuf0fPYVdUFY1gzpmAg7tyT6HO5r9SgIw9Yfcbbq
ExcCGFMYPVMWG4ahddZDDjGhbDlXbFhRikzO7EOvea0K1IWb3moavg2GOCtDkQy7B
YnErR/3sL2q7bzaAW68Rlzg6jVm/DGH1apx1+FLFr+DEoFs6TS//JW8sDiqCcoU8S
K72tyWsN3wz1qAVoFn5inm/uqk8mV+rlht4jBoTnYedz1xbdequkPCX2Y2UCMEp00
PGf4oP/oUJ5UwT3/AC3XX+kDNDIDEOHQtzIhTg4CPnno2Y21uULgVb94RPr8zJ4gV
LtWZd6B5vso9LJJaOrYgL5SjkVxLfMEJvOQV97S0ZTsbZ4gYjBzRnBOARmsz5858V
YFdvv6i3xNdPbF6NH3icQIZeVScKEdBPd48d8nSBx4DSJQlzuSYv4ANc+YH+adDv+
52jk1o4d73vvraqcwSWHWIE1DB3ma1DgDfCiCUqubboyh3OxgNIshEZqClNE6xDTa
v1iViJbS4j76tpGmPyFFba/tzIoJigBfECYAIDEmQlKLui/18tSeZizymQDHySLpj
yTQir2F2HyVFYE/CH9RV0QigP+Vx+g4uXEN0OPGCE7Lh6V/d6Yg5mBuYWHd0z53+Y
+HoE0bPGcAkRKSPLWQe8Nk5yDnJPyz7sG9XHpvoRl2NxDhW78Xr6yFhhGuHu0W92K
4xiVRCd8UDWdmrv8uwUdubP8bPtDsTdll6MGVZj5zr1n/tI7dKj8M0FITpW0o0o8u
rpc0NiH0iu/NRTzeqrGehA14SOrM/q63+jpX76iCBIWJG2QXvyd/lBdfNI/SASN6o
Pat9Itt0QC7YH1g9/15d1rcPxVEZua3m+rjMtdqnyRgLQqEH6DWkDW1ixnX7EdVnP
0kiVuMKY38aSkTfEAcG6utYsDY2LY/AkOvOItfXBMuTxiddC4VffmissJHHP+Y3jl
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-12-18
 */

declare(ticks=1);

class ViewVCVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_viewvc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local ViewVC system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'viewvcPath', 'The local directory where your ViewVC installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/lib/viewvc.py")) {
			throw new Exception("Cannot find ViewVC settings file at $path");
		}

		$cont = @file_get_contents("$path/lib/viewvc.py");

		if (!preg_match('@__version__\\s*=\\s*([\'"])(.+)\\1@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[2]; // e.g. "1.3.0-dev"
	}

	protected function get_latest_version() {
		$url = 'https://api.github.com/repos/viewvc/viewvc/releases/latest';
		$max_cache_time = 24 * 60 * 60;
		$cache_file = $this->get_cache_dir().'/'.sha1($url);
		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = @file_get_contents($cache_file);
			if (!$cont) throw new Exception("Failed to get contents from $cache_file");
		} else {
			$options = array(
			  'http'=>array(
			    'method'=>"GET",
			    'header'=>"Accept-language: en\r\n" .
			              "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n"
			  )
			);
			$context = stream_context_create($options);
			$cont = @file_get_contents($url, false, $context);
			if (!$cont) throw new Exception("Failed to get contents from $url");
			file_put_contents($cache_file, $cont);
		}

		$data = @json_decode($cont, true);
		if (!$data) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. B');
		}

		return $data['tag_name']; // e.g. "1.2.1"
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the ViewVC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$local_version = $this->get_local_version($system_dir);

		$latest_stable = $this->get_latest_version();

		// Note: version_compare() correctly assumes that 1.3.0-dev is higher than 1.3.0
		if (version_compare($local_version,$latest_stable,'>')) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $local_version (Latest stable version $latest_stable) at $system_dir", true);
		} else if (version_compare($local_version,$latest_stable,'=')) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $local_version (Latest stable version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $local_version is outdated (Latest version is $latest_stable) at $system_dir", true);
		}
	}
}
