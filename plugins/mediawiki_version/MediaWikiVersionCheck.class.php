<?php /* <ViaThinkSoftSignature>TvlSnBB9oFkMG8t23W8SFnhWVFc86YGpHfet/Nbgyd080A7CHl5DO+RWvS7H19uskpIrRBrXZYgt5c1gawuV/u0sH0F+7gJNIGcjO3kaqWzXeaceO4pvNjjPJwLShLq+FbE9kyw08XxHYYHkoJhtxnRiqaEEq4zmQ8U7S+JOnCHhP6cL3eP8/iY0jVFonPpwIcpIKHEd9p5/t5ZIFW02HCSB+wRT+jSXHe9TGieS9Yc7cODueJ80J3Jkh/EDbTOlgiOkgCROGf5zWqZUGaogvaLHk6WmO/XNyP1zUFj0lvNFfK4rhqbSy1Oa6UX+ZlVmpHAXLIR3nrpdFyYR2/zj0wXd21MpG5sN7zGgW98IpvJVSkb9eqsRr7IzmKkGvaEQ55lNYxYVgOQLOo+D9d5qb3V9stJ1R7h+VAN5uouMAd3Kj2uGp1QTUoxzy/cp1UU2ub9zQ8Q4WOvnm0TVaoyFnpptfXvRMIHbfaB4wshglKwnDvzPtQnlsJfq2R9lZ6zqvgEuIEnJtfpk6FOjCCIYR55IO1UVVryBqBWHtRBT2Y3Z0Tg5A1kuqwVcsw0d3rHRrjyhMu70DyMr4aHs1HE042coo4uYfkhmlETSNQgNntKyKcgdon/kOqxxhlc3NGopO5JVR5wBgYmX3tBYJ5OcFEzXgD4mmaF+2MG/K80sxh8t3jg+XCD7oq9dZTAAthWc7ojuWNb3a91uw6gb2lfogiCBSyCyMVRdakeA3gWZ/7zYhzWshW1sU+vegpIkTUt9Atq17k9i6WDI8wNctTvx3ItmTencuUNXmfGyrFC4iUkHlYJy8JnuC4da568M+AX9kxr/qgtsC4yhjRj+ZuEeZgVqBmYt3OIDMgdzK97+e45c0ixe4AAutbAvY00W3NiKOVe/cvYlefCxX0NjBqQ/+1gh4vhsf9Hew5TMIvlvgAVGNwGt4mQfNjcVjXk/QJb52EZ/EsKJbkBqeQqg7jrkgxH3aDhAnCKDVhjBBThQJanjAeCte/EEU3I3jnkYEktBEG6aVcYLPwC+b7joc+KMB23YhlGPfTr1dalKGp+rBLEcZQtIRPRHgsxmWpQbRqjJ6YiEhdB7RafpoN+aalxr8t1BRXiBFvOHSi/UZsRyiMjopKTMHTKuwV+TgWkgatr5oMhUYjF25bHLep2vjHBOLBcLbkvofUMYcC5SzwaT1QbzHmkPNRDMB9X5ghXgCStXGwFnbGZud+GfSnKvpBceAOxpuBgY8qVQipDeuTN0nKEkmjRyIXjsHtcBvJiYk6+7v32xC/QSXP5zdu+1bDQXqx1O1uRtC49kgX259WivMmv/TXaPc/9LMevM1eZIJ0QnMZ3lJFNCmdYr9GTklIIlDg==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
 */

declare(ticks=1);

class MediaWikiVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_mediawiki_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local MediaWiki system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'mediawikiPath', 'The local directory where MediaWiki installation is located.'));
	}

	protected function get_mediawiki_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$c = @file_get_contents("$path/includes/DefaultSettings.php");

		if (!preg_match('@\\$wgVersion = \'([0-9\\.]+)\';@is', $c, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = @file_get_contents('https://www.mediawiki.org/wiki/MediaWiki');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

	        if (!preg_match('@/Release notes/([^"]+)">@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the MediaWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_mediawiki_version($system_dir);

		$latest_version = $this->get_latest_version();

		if ($version == $latest_version) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
