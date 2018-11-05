<?php /* <ViaThinkSoftSignature>b9H+o+uc69uskKoarZ6sudOUcbQewFqoqOw7j2C41tLdtnY5lifSSr4xHIOsORG8zuE4sWz00lp+ySbp/5Cn1wZVLpkusQSkVQZNfaYXqcyhxbpxA04qB4oUFfsecJ4lGP8cGHxT1BTxg5P81CelT56Kook+U/npTQzOXwCNL+5E/QipYUcKyh72B3+Or4lS+HPyP+WHC45XkTyvkItnCXDbsZxQR/oO5JKhv9R8t3oSHoUMX3AjCws5ZzFJ6QlHVl3zQ4u2o7MXIvYzHiojLFx/ycQ4W3AZ8xsfDRtdisIluwQ37rnQ65gxBe0YMzTjOtKWgNjr/Minaz8Tx3iSEpqCDmtoTM2lKiyf8I73wrFFiDM9BTHy9AO1Gg6lHUQGdG7URusohNh0mLanV/S69UF8EPMzbuJzfeRBd6/eYagge5yIImCLmN/YQdudfM7+ZAlQWXWjaXIRpMIJZcl8cO002xw9MlMfJRXdAJ8yUduLW1ds8b8SkxdDmWlSnNakoIRbGbuoBamN3e6od2Xdy9XIOj4GQakS5cqxxEbMOUGiTIuhKmplJD6xzHCYzIsxFe2n9gCkpGPkNuUwzb6pjS+WSIsltcsBPWu6WaWXp5D7kqspbgm7UrAprZuNojcdn8ymfBo0J+UcpYCWZhnwsjSGjIRJ8zmfedi46eYevSbVWrkZbywdNRv+UdEpTNd/20QQUGzvHbPzsIZMgwL3rDR7j5oJ2nLTygBQhylnzBhzOVdnVJyfaWwJFZgnZUBsL+U+FWF+YHeWZGLkrIPnUzIEcyb3uiYT7VWHl+B7HVDyVdMiT9CyjrYRfdP02KShkLgMl/RQcLBfnxPasSNd1PKWn7G9WoZKvy+d8cE8BrNHnnx4+NoTelV3FSG4gFlX5GlBX4/zxrK5/VPIsfxpDfrH+SqoEgefwnrM35J3hgJmDJsX/L2xwutWA4FuXHQOphEZOWKZAMM5avwZS5Zaig2C1hEbocp+NayTFgXNqjzpLbaZB6iKp4aY9Hm2ZqpY9gPTufdi6cv7vu15QYiIYcr4GmWqciDUZB9IuN8180OZGP1HtCCqzsgZZR38kO4fqx5DTzQEn6mdat9h472K+nF0rrGBRvMKq0yGIX5LhDyn+sXsFuNZOyNJWiuanDDJdTxFWyTA+0X3gzD1N5lHD1haPOp3Zv7eM/ppkGGFY69b1/mxcH8Lcre1TCxOSpG+yM+CaZy4jQSStHmXDm8z3ZJbe+DgawW9TUJg0r6Mrqer56sDgRVw2GuLkWfxCoDx1n3EoelggDhV10tTttGFEIQ2m/ruXQ4Ji/S8/gL6oaiBaIsX3PUomLFTvCSfB9TqHKX3yyCsGlXTzwSIoi+FBQ==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
 */

declare(ticks=1);

class Net2FtpVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_net2ftp_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local net2ftp system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'net2ftpPath', 'The local directory where your net2ftp installation is located.'));
	}

	protected function get_net2ftp_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		if (!file_exists("$path/settings.inc.php")) {
			throw new Exception("Cannot find net2ftp settings file at $path");
		}

		$cont = @file_get_contents("$path/settings.inc.php");

		if (!preg_match('@\$net2ftp_settings\["application_version"\]\s*=\s*"([^"]+)";@ismU', $cont, $m)) {
			throw new Exception("Cannot determine version for system $path");
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the net2ftp installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_net2ftp_version($system_dir);

		$cont = @file_get_contents('https://www.net2ftp.com/version.js');
		if (!$cont) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
		}

		if (!preg_match("@var latest_stable_version\s*=\s*'(.+)';@ismU", $cont, $m)) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
		} else {
			$latest_stable = $m[1];
		}

		if (!preg_match("@var latest_beta_version\s*=\s*'(.+)';@ismU", $cont, $m)) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
		} else {
			$latest_beta = $m[1];
		}

		if ($version == $latest_stable) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Stable version) at $system_dir", true);
		} else if ($version == $latest_beta) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Latest Beta version) at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			if ($latest_stable != $latest_beta) {
				$this->setHeadline("Version $version is outdated (Latest versions are: $latest_stable Stable or $latest_beta Beta) at $system_dir", true);
			} else {
				$this->setHeadline("Version $version is outdated (Latest version is $latest_stable) at $system_dir", true);
			}
		}
	}
}
