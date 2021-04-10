<?php /* <ViaThinkSoftSignature>
NUptu6NDosNIBvJQmC5uOWWyF+ZOd6r/wOPmaIWkkQ4gptEri91OX+mn26zlMZ3SH
UPjsLokbmtvCOpjuWAb2pHm/MY9yniX8vvF3qkNpijz121J4IvNw+uGSGrw/aEnUA
xKqj8cFFD0G3GdzYWXtSaIwkGx8FrBghTRkYyLfSrysHhYXWTKsvVhLcOOX0Nj5Hb
rOy/Iw4qJNOpGuqcW68RQfYwujzdIwNxN3amirbjaFg113fk1KMEVZLIlspttQQqU
8GfJ5rBjhMxpL+B3owWNWB4owOGkwwWfF9Y6yAm90sSYgJ4ekiI02swmM1WTWgC3Z
YH5r0FrYnXrDrafo/wG1GeAT9R9gRsHyg26Eli7vPEXNk63eUQEH4hfNf5KzLNAbo
lqqMfZls24Sc3KDtGgrdxT4MogY5dSlFpZuGp7MG/E0uSTVallJ51j2ON9Zpjl/Hq
NqPMDKmngl2cv+2oXPnVn2NQY/97Muf4qCkbF+4TrjQBPDABH0husOVE+KJ++fxg6
MXkM+cdc3kNP0TPvcTt9Mo2PivN1JOlzCcyXZGuqyIN7hVY+gSc3JnKx192ssknsM
6A1qwQ0QlRMs3uYvgTpOhwY20u0gD0qFlLcbR0hCnthTG9DRcQI1UIoXwNFuSzrzj
KuNLg7CQyRKWP5Kyh+4NS/koqR6++0aNhJKeog92+4f0FHXKB61eiEfAGopv2E0lt
w/7z6KsJGgi5gryIS7mnYylxoov5sKSrTtJFIu7FlU5vgwFXS1x8esxEhfdFAyUrX
Wi+Kq630XFUp/KCrTKt3C+vTXmHPKv7dWets4kjujP4pP2G4piyiBZ8VixVstlYfb
FekrvCC4BjYcnJg//DLEwomAhd3JOu8mUDwpoB3BMmfr80y5jiGLrbKcIZwygaj7c
hPx/g15Rg/FBuS9U+4bugdfgB/KMwbez8zDz0A2u6JMIoXitGiWt2sBm2A6hoOfh/
okno16NzKy2d6qykUVTjo3fLacEXtE99mj0/4bV7TlOdB6A0E/54bqb5sQb3yYTbb
qpjgXq2p9WOPFulmBp8HFw2CV8y/xhgnhWv3sOuASrKlvjOH9CcSGZg8OiEljL/nm
u65gz6kNrWeSvCazD+5muYpgl3KOEVuEqNtFwdQ6Kd35M3HZyTAcU8Oq35Uz7KSdq
2wOn567RqBlVnYe5rEHMgLurffXuP1WmbyywRaJSZxeMSkz4BCrx8j1KxfIMFPXQC
AkvK8uXY7+x4OvgPDWFENRDDv6M5Plc80Eahu2h3OxlYDbQbA1RoDQMWtonmBXS+i
LYfO+ccy8pkpC/BQVu5dt96Fo++0pljePuARglmLydvQXsH39yYWSv5oONkyo1idi
Q==
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
