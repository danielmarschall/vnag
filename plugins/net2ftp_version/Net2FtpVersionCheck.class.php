<?php /* <ViaThinkSoftSignature>
by0ygdz71LOIgQL73U0gsS+js06/4FRI7qIt5kZTDQkfFFUx0j+5cExDvUPgLuMt4
ztFDMXlVUvlT2w+N3NHkrq4lPoaBSZVaowa/uvmMSZPgywiRIUGhBxdCO76pFc6a+
RoNQQJLMFMvv42HedbCQWZeTkmmlTyIgwEJe1wALqPs7WVKm1itXkEOp6rdP+y5ek
x2HyjnJXP0qIgzsAylsBCpM1bpb7WQBhnxVL6BS9PBD8SVgudUvXFH3gYi4OAFk9Y
mXkn5oyEL9uWLEH3zqOSMstmxM29BKC985lKBrL1NdIk9gYoR2oFNnN5USVNyNENH
zqxYnHGf5VpCnuy3zg+Rdnn7HJUBnoUCR6nYy12fzy20atCbvN0tpoTiErvl2T/66
4/Ha8D+3Mx7QsB5ZiXHH/d6OqWsRli918suPuHBzeH+21MWLyT4pCzq+OzX1HkrFK
A8auAAunzH7kKJE8jPNoX8QbLK0sPoZlqhXPzL5cZi2LR0J0V/AM9eKA0vpP8Hhm4
5WKwQnkZ/DD5fQNX5NvrCj8Q6NDR4UuOyjz7LbdPECsogNee/EFTVbyIBPbgRCnVu
oRthcr0uqr/reYsaf/j3Igk0kYo75j2l0veMY4Pa/2cZF654fB8ph1y0nWwnN4CzM
7jPpK6UUBwBk7iYA+9ypZD1Z/4gkAWsGdebU/FiS4r+KyFwTYmatdcFt+gxjWJYb/
1vUt/LyD8bkPV6hcHll0Pk7txYQpj3vFcm4qAqPOOmEDxYrW356EhLL9uZuU6iXHB
dqpi/rIPIRFCrNzN9CwlrM9mENgJkh43OiPZepQSE5BETCIt4Ib/1eEOh4I6WHYlA
LHFtS1H8OTEWODrgpIPk3MqgD7QIPPakxykFQrNliByFq5eWQ62GXWlu7T28+U+X8
W5rnv+Ir2w/h5lRDRKiNeS9PEgYv39YyCkvfiqc/g+AWIjCX/0KlZFZ3geL3Uf0Y7
DN55RFvgHXaCdJn48bPCHsBs/tpgzXN0mi2ku3y4Wo3jatyHWosMGE87JNRju+fCF
wTIkYVPc3/qGiH6UyF8t55GSAvrlbWQvuo7bay6uqfxJq/IGxJ6QsfXdqpqD7HfAp
r3HpUy1uZm0DdTxMavk8HPZ6bFoatbhGH14sYb2dudAPV01XQq9ld0KYqlI3OUX18
PnstgJPiRFwPzZ84tlc0x+04WAq8/WYNISuBWJSS58wo3/1YZLFwgKt/wtbkg01/v
NklWgLk/7rJ8P+FPjIAozBo94CLFSbQtweTDM9okQqEO2eNoFe62i9HbAMjgHZ/0R
AI/Ldy0111mbO7C6F6fcoth82F3jDudq7JLYPithmV3TOD5JTJlreA9iAXkj4zUhY
A==
</ViaThinkSoftSignature> */ ?>
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

		$cont = @file_get_contents('https://www.net2ftp.com/index.php?state=homepage&state2=3');
		if (!$cont) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
		}

		if (!preg_match('@<h1>.*Stable version.*</h1>(.+)<h1>@ismU', $cont, $m)) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
		} else {
			if (!preg_match('@http://www\.net2ftp\.com/download/net2ftp_v(.+)\.zip@ismU', $cont, $m)) {
				throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
			} else {
				$latest_stable = $m[1];
			}
		}

		if (!preg_match('@<h1>.*Development version \(unstable\).*</h1>(.+)<h1>@ismU', $cont, $m)) {
			throw new Exception('Cannot parse version from net2ftp website. The plugin probably needs to be updated.', false);
		} else {
			if (!preg_match('@http://www\.net2ftp\.com/download/net2ftp_v(.+)\.zip@ismU', $cont, $m)) {
				$latest_beta = "None";
			} else {
				$latest_beta = $m[1];
			}
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
