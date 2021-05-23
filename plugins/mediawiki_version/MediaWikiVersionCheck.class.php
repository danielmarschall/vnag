<?php /* <ViaThinkSoftSignature>
c6K9U34rihSmV00RaktE6k1QfQMXELztOZJRg2Y/oTgHt9UsKQf3miExkxS1SlTM+
e90vjqFv4CCayiwriwphQ2vGbel0NJMzNXLUpiajfS1uNvqbisve89dRxQzKahk6P
JKqm8+GfEJN0ytjLxMbYGEacwLONVsgrQpVFtsBpkrdPV3r45kIcfbe4uRotHPqgR
rdc05cxUWoGUmLy8aylO4IcrzgnV4Ca8YMV1gucKy2vhiGybySLrE6NHUtLqrHCCK
y3cZxxtkMvRx90UWfK6/wl7QJdHe93jfAU+pbXAiRmOhwIB4zB7umUjw8zKcS2/Dl
0Tu5XY7FxpgkbueC3lKDXvFiVVTEb+pFSl/cVHktt+IaOYVVG0zwAwaMd0eaDr2Su
NdFSIQzYWjTQ5SrSORbao4WZdao4uN7YjYFSRcI7PRnFyaTfR/SoLOq1J/CK7SD2m
gG1xvVce0I3GpSjlWVksBu2R5Jr4/J1vT7wJZuuxLbJhcpH5PAhuzcAaCw0PrhTgT
IUjPwZbD1bzTO4HOx5WRCV9d68FbPoufhEesol7Zgb2gBbMSlkCmNpH+KQ9Dwv6/X
XUWgtQPA/CDBHRvVECzY1du6JdEnVZbjkRPuRmUBLu1ULsix3nQJxS0MGJsVVjWj7
1vD7LeRA86kw22/GKabMqSaJRyJM9b7w3xiWZiWvUEtSWieUWoK80qFwVrNhtgNtZ
UNs6dDXDkmzOjxmPBR5ZEhRkaCSMSTtI3GONxI5Fr/weBEMavurTqF0mDpDL+I5kJ
lTt9RIBG0vttzIx1a7ZR3T3NJp+3kNUr0xlikpCobf0lpd9aErkB5+Hv2JOa+Glbg
sDbFnOf+yUj4VHJoNgaLs+TafkSZ4a0j2dQSja1t+KMYjeUYH1Jxp4LJV/pUsn4Fc
1fgF3onuq/hZO4BlVMU+3pDT7sfL9ofSuJ4SIjsD350aQZW0Hl91g990y7mjBGYM/
dG9+NhfPJKqj0pe4l7Rqcfeo2whmWEmi+rO847gbEJtG6mDKl6fqwP+CtXkVb1QBb
reXI1aYc0cX7bAuvJ6Jut4OmtbYVS5I2xCl98cdmiyaGYtII8PPIzF8NOx6PbV+Yo
Cgl1KUNepBqVnODB6CfdJKLp2RBv1WIwGsRmzZ+cOYBVbntj6urUrwfRTBxb95NkF
nM6xiLZhPlQlLwlvDVlxONeVTrKiJ1T6jg/piv8AZunrTHRaTAJd8rEX4ZtJ4kJDw
Zdor1tSgWF4n7ZSs4eKxwRHTCe4+Y/CKCHUGT1ntmpfB75ZC12h+XiqOifG1p0l96
sJhUNp6++mfh+nowQ02j3v6QMxOqRFwhfNfTTvQMlelLpXMU5lGjcv8GrecP4p+7s
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-04-10
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
	        $cont = @file_get_contents('https://www.mediawiki.org/wiki/Download/en');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

	        if (!preg_match('@//releases\.wikimedia\.org/mediawiki/([^"]+)/mediawiki\-([^"]+)\.tar\.gz"@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[2];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the MediaWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
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
