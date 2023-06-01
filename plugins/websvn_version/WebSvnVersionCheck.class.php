<?php /* <ViaThinkSoftSignature>
O3yOuJ0uY8SaZtsrvzay90oQ6sp1M+CxL4k53lThK/mXA4x6XNuTX1K8OygUl0swc
+yV3RRKNVNaLXMxKBOhRVC1SqJi5jDM+/AN0vJ2j0fjYcc0mXbuy4amScMq0wFBBq
0Kf2mqWu4QtZiZ6PQo+SoUuoR5QsN4IQkOKdj7cdluNFClt6uar2ZPNEGeHFGt0eT
ddCEJf13rjkpta4FUjjPr1eN2uWE7qFIAqgYbx1tW7+fX3iFOOcnd9F85hbvGQhpY
LJgFwwsFbfgH26E54Za6ynVJ5yvZ3FBFItO/UMtuiAqjFgsv6tmU49B7NxzzFeAln
xTPhhF5SILLJr/aKkMhNd1vWup4s6ZW+HWlwxd5kRdNedGPiWExZZuGDfdorycLLo
kiP2xEHVhMFlOFtBsprOwF8ZjkQ8Jhak+MmUyXIPyhPUPWIyow8kttnBhw2QNxwI3
9l/7Z/HNj+XYcJq+lRPLG7CGzmD6xBDgjhLMVGTb2ZSqR6Y0YVpd8Wsq7zhok5538
YhE0jtLjaX8U99Y5l9hwJnLZJH7POHbmR5GFAyxZdH08E+nboIwlV5LJrFYb6odSC
S2zWC1PCr3fvSErp9ikEE+GKvsvDviYymcR3rd8xxlsUFi8N/gOKgLY1cobY2Ic2s
jtdinhvnoKU8Fmmc9qUUNJcFS2dyM3uv1CSvYJp0iFcMqEZ3G/FcM3/octYhl1hl1
vPJRe+0Xm9O0T6VqhWNvAf6MjJ2FQGMRAKWkP4NGuP1jOkN3yU0GjP0nc3ZXY92r/
wGnYqLTFh4c3WiGRjNJOmWHqSy1QCVKz13lqVTa4/g6RK0dNVphvTjCcpB6/VCAtw
II0K/y1ZJ0JmhWUbN7JXULyB1Ghy00WUZa3XPZCfCGWqVT/KtPkZgL/8zBzg+NFkF
ZNINJsjM3aFebt2yeUn5E13WCOCFsm6fqhhtNBe14tn2b/AEew4gZfuitFuvbPtY/
gPSqg8EnJ2qmoGJEQhgmkLdmklBLQ/Xtyf68kYFGeoRU/IPVnsODqytvS/J23v0vF
f//VIjpE2vmrRi7aE1QmnT+JIKqq1HZsUPnuEjpIWhdH1W+sztLSbYHTmVG89QOeT
cQzGPitGrPgqqSuVJo0q/zYdXDjE+Y6kbIj8QrK05N6MfhCeund5JvTab7AQlg51n
BSNXZFMdena5C5QTXGwU3BQwBpvr92YWCj7EXzGU8N5XTDULiyInNNZ0xOULA+AXP
aV79sQdctyrWEkll9ykl00641y3jdJl0b3FzFy87v9OoRLj8vAsvy2cUmRykRjyJm
Uc8V1D7xjhvuxa5APwrpcaULIpOSiC3upgmNYlZIODV2CKixY5csoo7Ka2wnAttss
Q==
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

class WebSvnVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_websvn_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local WebSVN system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'websvnPath', 'The local directory where your WebSVN installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/include/version.php")) {
			throw new Exception("Cannot find WebSVN settings file at $path");
		}

		$cont = @file_get_contents("$path/include/version.php");

		if (!preg_match('@\\$version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[1]; // e.g. "2.8.1" or "2.8.1-DEV"
	}

	protected function get_latest_version() {
		$url = 'https://api.github.com/repos/websvnphp/websvn/releases/latest';
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

		return $data['tag_name']; // e.g. "2.8.1"
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the WebSVN installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$local_version = $this->get_local_version($system_dir);

		$latest_stable = $this->get_latest_version();

		// Note: version_compare() correctly assumes that 2.8.1 is higher than 2.8.1-DEV
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