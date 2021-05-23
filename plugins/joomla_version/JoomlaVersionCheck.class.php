<?php /* <ViaThinkSoftSignature>
K33sSoM0IKpmPqNAvAJX4IZOxiI4PJ3Az1/SbwuExGGZCAoXMGdC4qJwLQdefulEV
W8GKw5FJq/aBeUb1JIHdh22S45AcdWp5zeyY1uOHi7kkI9LIobrtyI/KZ4gGp6b3m
pmFSgAUJAiuXdO2+BW6PLAi5yBwODAtD3oWeIVTINfyY+hyMb79qYxi703sTc+CG2
BUggv91FVPIOQ0j3if3kTjX4pm2SQrogm+tcq/w57b2uKME/3E3MxzIHJl0d2V1jI
KXPlf3zgz6ifT007mSMEXBqcmhOLGLDpkWzm81dFM6WDg/xkS3rTrL0fKDT4YNDjM
CV430Sbr3bghuhEAtC168PNyDUnH4rKV2dn3DhQYA7Ubh9Y5BUvAOjKMkuXtyUlkP
G/enu4v6+uH2RQIXW0KSGXvvCDayTjEZRUI5x6Co069LOQ0p2/Z2PB1feVwtFnv7i
LojjxhPVP9lbeNh38QhRF3SxcnW0oXF1U7hqwBS/n5P38FVKtOy7EUFA+PqOiqLh9
fvsr+Oz/yXP0W1uvaWai4l1v1g2YbSe5YLfA6QrAxB/z8KeEy1zkjVhigtOTiT7Gv
xywredNXsMTC9fGpfmnJx82CcVpGDFy2PNzILYP44ubjeLliXOUSOA4wXcnegUh5Q
G41z3mDIrUTCwuZ+93LbCWzw5DuPPAj6tXndfKAhtZJTNDeQMkHZgOZjwBIHP38Wm
BglIhY9RsWPdI6J59GMBIfCmGElHRQjtxscrMNtfHmPLM+fue6RAHQ5fi8Zok49x4
z+EXm2cTWs0AwmVmojluDZENV1tLxXloioalY+x5VuvTcAfbE7IAXeblkrIOJQrRM
WcbUzfLXkLQqjPqpj1mZ10X7+zWm/hY718nITJCBtTcJ/QSHHVqsh2GNAVrO4UPY5
mB5qLGq5kFOtwW4sSCQ5l97++zQ0cDIQiL0Xxy6YabYTTePodD/ncr1HfB8Jm/380
E3pULdzyhbHmyIn03/g43nkbhOogVMEFmwTprDchfzCVj8QJf9XG8SRVhbtqugYP9
hhCLvRtBtX0vTiYJL0n/shPZ7ZN89PDYLRMnuxu1L3r/syYulLtqykOHdto3K6PyS
FofDhfwXXezLcf99yyZhf9w01zkK1szRwEaz2vJseQXtMXzwGu1RzxXqfSyFXblpw
/2OujCLG/AKoT718DseQBOQAqCKpKLmNlGFmdI0tPQcqwwm6KF7dTy67PBQIZ7IHd
/BpuMjdyKpZOKTpGEBvDjk1a2CRDAhDNQreJaqw3bW1vNbLiqtjARKEDSXg3fx84h
8ont601uiedZGJ/o2lWwaZ5uU7tPQXEmL2eNvbUoXnYG3gCZSCVhpAYjzhnKxygeP
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-05-13
 */

// TODO: Also check extensions

declare(ticks=1);

class JoomlaVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_joomla_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Joomla system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'joomlaPath', 'The local directory where your Joomla installation is located.'));
	}

	protected function check_joomla_system($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$manifest_file = $path.'/administrator/manifests/files/joomla.xml';

		if (!file_exists($manifest_file)) {
			throw new Exception("Manifest file $manifest_file not found");
		}

		$cont = file_get_contents($manifest_file);
		$manifest = new SimpleXMLElement($cont);

		$version = (string)$manifest->version;

		$count = 0;
		foreach ($manifest->updateservers->server as $updateserver) {
			$cont = @file_get_contents($updateserver);
			if ($cont) {
				$extensions = new SimpleXMLElement($cont);
				foreach ($extensions as $candidate) {
		                        $count++;
					if ($version == (string)$candidate['version']) {
						$detailsurl = (string)$candidate['detailsurl'];
						if ($detailsurl == 'https://update.joomla.org/core/extension.xml') $type = 1;
						else if ($detailsurl == 'https://update.joomla.org/core/sts/extension_sts.xml') $type = 2;
						else $type = 0;
						return array($type, $version);
					}
				}
			}
		}

		if ($count == 0) {
			throw new Exception("Error checking update servers");
		}

		return array(-1, $version);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the Joomla installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		list($type, $version) = $this->check_joomla_system($system_dir);

		if ($type == 1) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Long-Term-Support) found at $system_dir", true);
		} else if ($type == 2) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (Short-Term-Support) found at $system_dir", true);
		} else if ($type == 0) {
			// This should not happen
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version (supported) found at $system_dir", true);
		} else if ($type == -1) {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated at $system_dir", true);
		} else {
			assert(false);
		}
	}
}

