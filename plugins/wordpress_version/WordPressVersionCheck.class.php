<?php /* <ViaThinkSoftSignature>
kIPLa/arm33y/tKdRQOo0OrLc7J0AMf8QkJIHHxuxu0gIy130GFppwRrCySj2ZygP
B8cdjiKqbOmfnXNeOVWYcw7fEvU/Qt5dyI6b/JL17hv/aZPfj74pfIKuy+gfNwGsS
PxFFtuU3U8bqs8nzK8a/Z2sLjY+jdAjiB9+SJJngBMPv9mjTmXAw5w5hlKTrIt1ed
CHwtEg5ZEFHZkwkd+fwWwFFsfU73r/hXxFxeK9r4Y/JG5EJMzhlXHHerJSSw1Y7LZ
pNwQjAfCbqeueh/BfI7Rt8MvFQopGp4ryKp7kUWhxcvhWSgIXP4y099rt4Md3QAK3
zzYSE+S6gVz7nUKZpKKjRcg4Q+j2eGIYR6FKd9OQRdfBxcR0wP4wBcLbVLnFrHLLu
bd/Q9pvBbraeZ0T/WRHWu0+P1MRAyi8vUufAbmzsxrc9xjTVwpWRHdgnHQfll2WD4
gNqRV3frkYsXR6qWhq8I5s5v8Xbukk8dEQcC9kz9PM/109zZdpLPpExPqjI6+KgZ8
y49DOdVzJq4oAFNNVpQKago6z8xeGV2R4q11/qXAWE8Vi13F98LZXZBHMw5rA/9jD
y4Jo7PfEGFqIZj7eL8VNCA3UsC0gYI5cjeH7RmWsrwwqFIs0BZ5ahztXHZGdorvvS
FKrLNsp+mqZ/b8HcsP/lIhxZSPnkCh2Jyun/BQT/CN5Dg+pGU5wtYNmSJQXZbEoIE
hpZ3tFJtHVEO0WQPzUxfRbAmo8fUXATTuCiqZsdYhtH/D5pygpUxr2qPSkpTt2Hus
Efe8niyZ+6BDkeTynlsERWtLe/INYJptsMdf/7MMkFRHqQv/mWjapxFfiz1Xssd9D
ZW9XyED1SLx6i1ACNT6+82GCmEe/Gkx1V0vuWgyZcEgHUJtzbrYY+ZuWXM9xmKfLP
R8QwI/BMeHH8LxL5zBOkM7hV0aH6rkfq873J6O6iJP9j/Di5XTZqsutWiBAFhuQKZ
ukryuJ1gYqMjyg/iVntyuZNiVvOBq7sjNv9asmln4G49DK/wVClPVf0oufI8CNCWA
g6yUzaa/JZvk8xOj1Bfs+ImSfgkWMTIg4ODv85A3mZNE4oYXkFgzXYUbsuvV+FXhU
FIdKNSyzi+39AUeEtXsz9JS/O8s7e57nwXQgauhtiVY/sUYxNwLluKmTfAzR3dzTT
lxnG4lEJMXvBJEyFlEuevboXRXlAyDUQEdl5HtwJvU/fxz2asvisTs5iMmWkb+GFv
S4XD9FoQWg5FHvN6h77Xyoo/8MtdUKkBElB5+F8ZjbBwdmJp7GV39XC6TIhxMNuTB
kxyQLNXrbefpBNy2o/JlPu97V79Q+wcjqwLZMJ4Zs/lghdnUDw24iL/YZXtKADFYY
Q==
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

class WordPressVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_wordpress_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local WordPress Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'wordpressPath', 'The local directory where WordPress installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/wp-includes/version.php");

		if (!preg_match('@\$wp_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://wordpress.org/download/');
		if ($cont === false) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download WordPress ([0-9\.]+)@ism', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the WordPress installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		$latest_version = $this->get_latest_version();

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
