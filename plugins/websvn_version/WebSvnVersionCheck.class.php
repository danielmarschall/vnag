<?php /* <ViaThinkSoftSignature>
xhl/K7K2NBjuGDzJ8SDyDfjnqVYQGqJYY8y/rSj2nQ2qB1UaPExnZbVeYXmkuuNHn
0FYtsd797Xuz5VJrqTGE8FjIwYxW4wjuOn/AKdzI7EJIVah9inU52Nh9i/t1ZBBSj
l6M6qn0Hr0gImkW4aPagZ5PVTW9D/kGyI01TiNY5lmXnDYnOIGwVTriOqEl1KWVaY
Fy350Ji1UN5dh4EeA6UyvTCtTPHV7SwVOMTMSBnQSE1/6Tkr0ETMfUsSE/HA2pnp8
V9DKDj5US43AXTBZf7SUhLbsESzgVJBIX1LIUCSRWZC067GRhYpyOqzzb8Jx0GduQ
5+cnZSckAsv4sjDra0blFjnfYS1mVO0vHY0n3wRANS4bqgodaBhmyN3lvzTK+Hf2N
Ki1MAi91Pd8K81mrQ5EbB+TOVAH3w0p3DejtJrdLTQpYuq+pofQRvE8Ua3zBdBu+3
GWazom0drmFfdOrcWOLp9nMqJmF/GYXB1zbuSsi83amZFtaexZePsPjfFq/2Hx2Tb
iO9Ivao5VKayqeUUzjb40/+9UYfNqz38BKp25SJELQBCyJjUQ4KVa3ugZPFu/79xD
NAs33H1qEMOu99KFayelWamGg44th+MQuRQyF8JWYehYEPNA9TGHQoWKeH7E8GJkr
qP291YkDhxSvmGjVIsgypSHrN29tleKE9RahtFzAFE8gpQT0xDLTirut8ecdHLqkc
dmm15VS2X2oGCvrCkTCNhIucD+wweR0EJ1cOYUW0ET0hOlWgxEohCy65d4GXRvIf3
zgSf91dyv/Of2c6vtx+JEs8bGofkYNxrTGF6mUTb7NDW08xs4SiXbL91KB83kjLxX
ATbeS+t6wTcm9op+82CCnb/VFt5TPeFhoCv1U2ETsAf4SvAsYumHfc+GggZi2wtUK
JMpV41FgPUv51XiYfcCLUyCF2nxenr8sAOzVA8L3gGiqJQ3jf8rUTbp35eMIvAgU2
ut0w/gdtHym44ipjArNVYFS7X8OkEq6bMZ4P/wUBVm8t+WTNf7ra4Upybv5YX1QpT
D6jmyGmb1ggDNstqj7rVRT6dYrbuNBPBhJV8As8oBUcW5BAeUWqCIJj44pyXOEH9Z
pkgSanTgFUEqHAkg/NW/ah2/6CgPNb7xKdosgHSgXTvaROUBqBQ8AiQBnTjdduJTp
1GYXcGOsix70/+DBH8MZW1ToP5FU2oAkiin+iEKe/XE6Ek506hhd0CvyLuIXtrScz
4EYmjCEoXYyZb7f3l9yidIWz/HkmUIWc2RIq8bv9ygzF+6t6PZktBrztMC2jRkFbe
vTtpxQWS485kKJO0s84IBNQXPUb+4Dpfy01OjJa8PXcYK4Ajp7DBt+jgLsFUMpCoV
A==
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
		$this->getHelpManager()->setShortDescription('This plugin checks if a local websvn system has the latest version installed.');
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

		return $m[1];
	}

	protected function get_latest_version() {
		$url = 'https://api.github.com/repos/websvnphp/websvn/releases/latest';
		$options = array(
		  'http'=>array(
		    'method'=>"GET",
		    'header'=>"Accept-language: en\r\n" .
		              "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n"
		  )
		);
		$context = stream_context_create($options);
		$cont = file_get_contents($url, false, $context);
		if (!$cont) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. A');
		}

		$data = @json_decode($cont, true);
		if (!$data) {
			throw new Exception('Cannot parse version from GitHub API. The plugin probably needs to be updated. B');
		}

		return $data['name']; // e.g. "2.8.1"
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
