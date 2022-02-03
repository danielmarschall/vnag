<?php /* <ViaThinkSoftSignature>
PIyn+/UQNjCJmoA9tMfbQbKrqegzwomG9GtkDY2OujjD+3Bq2SBPkIjcjngGB/ZMa
rSGVqBRzE3pxHHFliBnOtJEeeqAXSz90E+qMRHu41+a3yhwP3ixOBo8qB/sKX1xCn
H8Vz4nW+NytnkCHUpGmw5S2bgG8c3jIXiamGrgQXgmVNtxjOB1zlVikuafLt2Kx1P
/j2v7d0G3vHyi+0If9AqxM0fx3lxftBlTgv9WpFGx01ep/MoS0wXcvSFcrWtN/eg4
5XlTZbqnF4pYnUhRq1/wfMjuhjvprRHoEd+HrAQqcOvo+Bi2bbh5F+z2VFg5USv1M
IaC0kCFZs7/09gP5MjoLBxYzbcq0ItA+YdSpIdpTkBTP01ZvkN84jli5JaiGwkU5x
EWTaJwP1zHsjhrJzP+7drjjykdzoPs4b4hnjh1FRJ5U9KKMbDsLQrfmm6hb3XJ5Ic
W7dea7o00FYMxUUuse0jhZdpHcdTtO7ivLBFs0Fzhquff1BubBA8zUJvyj3kUs4+X
bUKmvsi+i3TDz1jUaAtPd6moEqDnIxVd7kuG40KjlPaIjLx4L0zshr1pSrUYv4o55
/7cycdN4wdY5S/DnMRk6d/O/4fWrUoY2Vh76gUlHD9g/uz8c62a1OTwiHA152HrjP
4p6U9PwBu0u4h4IhNwnHPPXaanVt0o1wgvz2O98AiuNcusur4QBE9AgVCgoa+hi5Q
FmK4/Ik9UqZ+zgKMz43QCiIyoQHnmvwBCQQDH+KRHgc1BR8nXQPD04YdLHd6Gp7av
cnrdE3v/YvHQUHVwEFfkC0TUO/C4t/aStQPTnat2W68GGf1szh8YV5d9eZ/VUA7zY
HxIHOt8YH7aP7D1OvN7hoxHxwtxW0oWJownfKxgbXNkv3sJb+pMgGlYrNwm3uZ9Qd
d+LPQcL4wMi6HGwRrWJGg+P57RqYeJT2B8lhubFrsCNNmJn7KtSuVfoh57JqMqOjN
zsqFxmlnDSKbHO2Xo972yUJbvg8JrxpYAvuNVuxpBlsiSEx6Jn8HP+yzwbP6DGGqB
EsoFqI6oKTiEYo6amqmOcLFX8GjXR0DEb0tK4IIf73uN1ymPpM2AW3Z9h4f6SprmG
osKy0Lq0ASujUZJ0gZf2KVYlgpBrv8+kxss4LrEJutuHK+FKrOWCVYNEnrZ1GVPFP
7DxMolfnil8VVrBBFCs4gROiL9YcQSABt8x5Ol53mU/EmWx1KypCSml8J6nh4U87D
rKYbwwne9eaZURG0uzy0dOGS78+kSZwQPNgKmIsKT9VnJXQWG9MhA1UZlazsfnDGb
5oGdaHQPbJlYtJJmLg/RPw/BT9XzXIluF0VWosZ2zJWRijvnxaCO09nVeYwyPwj5f
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-02-02
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

		$c = @file_get_contents("$path/includes/Defines.php");
		if (!preg_match('@define\\( \'MW_VERSION\', \'([0-9\\.]+)\' \\);@is', $c, $m)) {
			$c = @file_get_contents("$path/includes/DefaultSettings.php");
			if (!preg_match('@\\$wgVersion = \'([0-9\\.]+)\';@is', $c, $m)) {
				throw new Exception("Cannot find version information at $path");
			}
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = @file_get_contents('https://www.mediawiki.org/wiki/Download/en');
		if (!$cont) {

			// The server replies to some older versions of PHP: 426 Upgrade Required
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://www.mediawiki.org/wiki/Download/en");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
			$cont = @curl_exec( $ch );
			curl_close($ch);

			if (!$cont) {
				throw new Exception("Cannot access website with latest version");
			}
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
