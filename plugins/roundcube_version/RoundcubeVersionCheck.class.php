<?php /* <ViaThinkSoftSignature>1BHpcBSx7ON/HUpCcUkKhSUoiYXQMQtnAdXX2iAlOl/JzKKg6SFnZOPnKWmR7wB3gptP+9uUjA6jE2DUc43XLKjhqhDCzZRlDPpTSxjfIz0b096R2o1AHchZq+IM/CoSWER0vlvFuA6Xw4XT5wT394u1Ndj49cBNf59F3cPnWO+vPK2PZwnKs7jgYAUssOErx5C47o2B1iYx5iWZu7TvW/8WKEKXBzHC0rvmT1qF0wvcyx6kQKoRENBHdZsRoVjDAuffY6YRXBiNfIcOWn4lu9hZqyWufxSJ3f18RTpul67YbcI5G+MNUqQmn6aDtf9NSiafiRLngQwU52ihlEHHTZWfAu4oKRWXzMHhgvrjel8B3a7nOl3iDUmvCVIw2tjGCCzeS9kTDEWBu+Uy6r8T5AjHkYcLRV2Icl4c4qQNQWt68VC2AVUIqIPpHqIITI4tluiY5YoQLiPqNgtI362fWzkMivY4Ybj2+G4llNd4OUCA3ob5jG3CoqNCOt0wtyDnXsMUGzV3114FR0TNYkI7SSaJsXZ5kMeUxh1Fq1axclrmL/ABRvmwpYl6OfJtNRTYM+YBh9NZndDDHOZaJkl31q8CqPzSbOX6YNeJx1u/qFPFl1y4LRW+KS9uT3xCSZKfik9gHSNBt/bGyytmBpJMSZcLB5Br/qB0A+IVNBdslvoJ02SDLyWLfC8/PffgedJCi1BbFJ3V6uPkTlAOiJTKYYB4CMXjlfByMMKZ6WzJR2KoT/yvn3ulOWU4tFK54p+2DTfSQWK+i7Jmw8klkRrFNLbUnBnoMSADYFaOEco/6SzCbaWUOWuwBCPk+HmmOTMLUrsGwiyXT8EKTeghRtKBb3F8RWVNFN8mW+yraq4a04JZfQQMXUwwaW7Uq9Z9gBsBSkzw/2kbKsqfj9TE3GuaNweD9yd3Pfy3j88F9Hx55AsNpF+nxk/3Q4RbiGtE5o7/lD2GG50Jbnvj/tMRxd5jAepzZHcs9SkxHNmGA0DfGHZuDQ53Yx2+KnQKFP3FJ7JKtn3WB6ia3KvafDxYqSFaZAiIT7B6Z/7cDIxQ2QJH6F7mh5UXcg06mtoBuHyEiyQnp8vW9ayyDMRR7VAYgjTu5RBLZ+9woEBB9/hZv3M79Vzrrvou6/Z2oyoVp2LcOBJlu5HZYClL56s0gXFaQ//7KN6mNB0mB+kfLOHw0Yy9tqh8HPASh51IaLBO1J7b+YErbRP4GSs5zjgcU0rOvBHlhcyOpYqm8xlXCtpNrwYfprWXxV7YZdTSGK7vlIq5tIK/C5nkrf4md7gIdzSQ2CI2wcTytO6uQWTuNlEm25bx7VJUdj+T+s7em9GLkXyMaI3nfgnU+gl8sr95AEoP+45X9A==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
 */

declare(ticks=1);

class RoundcubeVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_roundcube_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Roundcube Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'roundcubePath', 'The local directory where your Roundcube installation is located.'));
	}

	protected function get_roundcube_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/program/lib/Roundcube/bootstrap.php");
		if (!preg_match("@define\('RCUBE_VERSION', '(.*)'\);@ismU", $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the Roundcube installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_roundcube_version($system_dir);

		$cont = @file_get_contents('https://roundcube.net/download/');
		if (!preg_match_all('@https://github.com/roundcube/roundcubemail/releases/download/([^/]+)/@ismU', $cont, $m)) {
			throw new Exception('Cannot parse version from Roundcube website. The plugin probably needs to be updated.', false);
		}

		$latest_version = $m[1][0];

		if (in_array($version, $m[1])) {
			if ($version === $latest_version) {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
			} else {
				$this->setStatus(VNag::STATUS_OK);
				$this->setHeadline("Version $version (Old Stable / LTS version; latest version is $latest_version) at $system_dir", true);
			}
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}

