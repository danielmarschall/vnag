<?php /* <ViaThinkSoftSignature>
j/l/RmkPw7emOo36KLIAYvPpYBHeZjxONYqQqMjVBa3TF1IIqILKtQEzvk7ocvzdL
xwJbBQo2FjoEicScuJiew6T8RAH9pu1MrkBrqI9tkhrR6SRW/BvbsL7jS0ypXmu4K
OqIlbRS0YERM35lUPfOeHiMLI2hN746kpIVwlPgn5ilZ4T+ac9FkFjXkM898KHzhd
fQIKnPQ4ADYezQd0QXJ48SqYVO3Yen/VDc3I5Fej9wgocmZachsKQDDvJFyfQegez
ms971SY87VpFO6OUuL23z11Cm8svpPP9ug2ApMb4CEC/RGEZ+ODGEVs5PsSWVN/1V
L8ucXSlY/fsZc0t3yBsrB9XA2S8gV2NAq6EvXuiyPxuKGa/2xCLsaxgimZNHPCJJu
f1aCvoiD7LcqfqVrNAFIaptaYGMwqWAWuy8qk5VR2P3fHiwXdjwD0KMKyyR8r2zJh
gAIVVIFRMOwQ/EIwgvqp7AtdlAIw/VuT+Q0OUVeb02SdNYY/pRpYTwd79jVq24nq4
rzkVa6KfSb5gtbPTiNz8Z5efWIOxKXYqmZFxywg3tdGJXSVYAElPkituhbXy+vw/2
ANj3beLDMujZ8FxBpjEk6NWo1sd94b1mp6fbhY2PrrK82L+/DSBt8yPx7trZNGz1s
KG9EVl2zl2YyHjxZAWFQGTD+Nt/duHLiVa5qTddovnT/v+NuHRsERHVp3vRwoMpFO
bv7wU4R56Gl2okiPw5xT0YuUdveld63w77oL3d8PrKLzmBgkCIBhEy5yfJQ/OMxFM
2726VrAYZ3EEC8goFtm4VHT4+nk7bv7KeOaVzNGikDBsO+LCtcyyawabxzRR4R4rD
QzLGAsPQ+GswGqUSKhvAfx5QA1J1OiYH+/C5eJj4I5eOmVvawWEJt+VlnknOPafCa
oVksJ1C9wVcLcYTNPaX8MPruM9bZq7C7Nu38zGQgQbDgD8CFkIjP98VNS1KtFnHfc
RN3sSPQE4eyinzrP2ySQLMfZXl6Y6IELJJxcw1F2PeomM94ffM6oWqmPrBc8iK35u
7FwbnIf03hPnNnLtZzGCdlv6+CQsHf7f68ebqH4C4N/16tMJYI/ENX+aeUm9JDD1s
EbYqJ5pflNYyrIBYGtA0UCHqWVbRiabtc2b3IQhm/jjQ4qArxjf/x8OBlyvjULZXo
CvxzmBYGtpm+XDWKAw1SoN/3pNncaSioy0lMasqFQGoYVyqxvB5DPvhp/cnzp1XUt
21zUPBObtuxSsYkbh7HLDUg6J660/wRX4ff5ARdIr5fUzKDYLFcbtnVTuw0aXG2ZA
DOdiJsm6/mUdRpraftXyH1JshyX9OpsPoIIyuA/oVDx5Mjn+N6ITvvr2TjYHvXfiU
w==
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
		if ($cont === false) {
			throw new VNagException("Cannot find version information at $path (cannot read version.php)");
		}
		if (!preg_match('@\$wp_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new VNagException("Cannot find version information at $path (cannot find version string)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://wordpress.org/download/');
		if ($cont === false) {
			throw new VNagException("Cannot access website with latest version");
		}

		if (!preg_match('@Download WordPress ([0-9\.]+)@ism', $cont, $m)) {
			throw new VNagException("Cannot find version information on the website");
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the WordPress installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
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
