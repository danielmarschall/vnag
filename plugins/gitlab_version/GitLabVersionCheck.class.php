<?php /* <ViaThinkSoftSignature>
cGW8X73co5z+uum9rrdEPEHmuDBAo+pukQfYDBdqlYGcl33wNfhUwLtwwntxMpDWP
+M05qfvdttLd8tIQEbmRp+rRgzTIqifraqe4TYBZ+yHqKLoadnepZndKc9DzXzGBm
Zt8t1ByTLihOSHaDcUcoNViSoVnUQhUHuBljS1axSydVPkm3eFNBhJpvlNDLGDhGk
nqRVJ2MFcpEs7wCXKznjhBNsNygJxkmDniDVvlCVK/45o0QlHr1oV7SJ1EoLl2+Qb
kKtgZDNDVtsPPnUtoTjh7Zl2nwNRqtyOb6Cvacc+oFi5z8x8yss8jIWzvl3VVJLAt
gA1jy4fak1qwQhS+nQRNksbBs3eTnIDUl13wlztX3Ats9oW/hbkr0MqpoAp0aP7yU
kxe5YLPM/4v/8ZOYEdpP3Vk58ss6OlQTOkbTHhw9HPPlK34sf+e//EIxa/bdFGDPL
4sRiIDVCGU7K/m7UxOYZTR/XxwrUzWf5sOpGtklMjMzpQfEJvig3j7ymAx/plUCyI
facgGRhC7vA6XrWj1F6MfAZ2QgJcAPAZl6gpfFaJ1WcqzzILZmkGP4FLHt+Vr6n78
GM2+RSSmf7NAUxHhrDQu7OAHaZWBdJL7Bk1FZqrLuj9k8BZzqbdPEykVZYr6VVXzS
6xoxR/KCFcyB/l9anxML1xmHG4/OdcMH+QhSPXL+of0KxM/GP+JhtYsR0cMoj6dB1
+k/0idxXXvdqrRGxDCUacL3YAQl5SkiKwGUKvDE+Ix9KCzRYEneZu6iTLNar6bpMu
Vq5opP4yuYmMyw1E/Ugng4j88m2kFsKOsmrOEzCBTxnHCT36xPLLw5J2+Qg5uNIkR
zLl25Va7k/OfHdgihR4wIpjqD/xX4hSsc4DyQu0qpXAGSx1uAeLY5Pnr83yQo/DKQ
3ErDKwx76L/kkv6WUBRT/V7NY0i+/UsifyKSIWMtX2aQVbxd3kbwu+9Yk+VIkc/L0
FLiRix1W8e7BEJu4cbU/e+ME1bQpWP4mzFTmSNQxF0dlyAtvx+i09r1CV8JLqLgkA
W6aSJLau6mayaSxJBC1got8I4NiQDqRmx9TXErYO2pyQ2uPnoVamhzxSBV0kEnfgD
rWiDLLbMiDQ5YPUtO+W3uW7nZ4JL/Nj9fpmMBjMSWTVVzWtOT5+dsDsjaBrJ7v5CR
YRpXFC7WPU9/O2+RuuDWebxvJvF13bYkuCNyOc7Y+sUl3gEKMXtK7WaYn+hJVscwX
0MZaBmfRaLMIVaM4fKRlY6ltaxKxZLmWtN9tcCVQjK6X19iytYQwsTlTjO2+sSftH
pYBgkcSCH68g/f1JClxtfClqM8xJNr1proltFplb8URLPIwEuDkKnnZE+VJR1ICZN
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-07-01
 */

declare(ticks=1);

class GitLabVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_gitlab_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local GitLab system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'gitLabPath', 'The local directory where your GitLab installation (version-manifest.txt) is located.'));
	}

	protected function getInstalledVersion($dir) {
		$version_manifest = $dir.'/version-manifest.json';

		if (!file_exists($version_manifest)) {
			throw new Exception('This is not a valid GitLab installation in "'.$dir.'" (version-manifest.json is missing).');
		}

		$json = json_decode(file_get_contents($version_manifest),true);
		return $json['build_version'];
	}

	protected function getServerResponse($version) {
		$opts = array(
		  'http'=>array(
		    'method'=>"GET",
		    'header'=>"Referer: http://example.org/\r\n" // Important
		  )
		);
		$context = stream_context_create($opts);
		$url = "https://version.gitlab.com/check.svg?gitlab_info=".
		       urlencode(base64_encode('{"version":"'.$version.'"}'));
		$file = file_get_contents($url, false, $context);

		if (!$file) {
			throw new Exception('Cannot query version.gitlab.com for version check (Version '.$version.')');
		}

		if (!preg_match('@>([^<]+)</text>@ismU', $file, $m)) {
			throw new Exception('Server version.gitlab.com sent an unexpected reply (Version '.$version.')');
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the GitLab installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$cur_ver = $this->getInstalledVersion($system_dir);
		$status = $this->getServerResponse($cur_ver);

		if ($status == 'up-to-date') {
			$this->setStatus(VNag::STATUS_OK);
		} else if ($status == 'update available') {
			$this->setStatus(VNag::STATUS_WARNING);
		} else if ($status == 'update asap') {
			$this->setStatus(VNag::STATUS_CRITICAL);
		} else {
			$this->setStatus(VNag::STATUS_UNKNOWN);
		}
		$this->setHeadline("GitLab currently installed version $cur_ver [$status] at $system_dir", true);
	}
}
