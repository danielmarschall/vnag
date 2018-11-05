<?php /* <ViaThinkSoftSignature>
FiyjR9oqgAeei220CERRBiLHuRAHb0/kx6b9OY4Ltv2b0fJO25aP3K18k/KQntpXh
28Yw3g1W/w807lr4xgRILQhhA3/JejkqNlKMb5bTZMcaNbTxBD44K/AHHXE62HesA
YgtFyG0j8qRn+MpTEBktuwxUmMqU1HT+SHQYq9Ytj0jVX4o0hbZ29yv23waZUYI7q
5as5ROs4veCrjPDSlVRty/jjviFe9ercdIta/ihXGfTR4MNymXCLKO3c3Y52yVBcI
N0xd0D3YvWZb1eyjMT1akpnFWfg+2RSqkwtNJUGHmiTF9m54dE3OfQGLEQhx062oY
WkbB7/Mc1sSs/U7eBdN553UycFI6j+M5yXhKmcAFTb3RMdiNRL5RsWYkx6u/tvJxO
CmJZ8Ln41pqdJiXSjE52psHLNcVlOAwzKdjqsC8l4kpYBQJhVMaIkfAiJ8t5lKkYj
T73QpaJl2db2Z95Skk8Utp+apI2jK/1x6IGE+PXUAIP7luQLPBrSaOrcCRRr1gXzX
WJJNpDuWMVIfsFqaLblSMcRwEKz8fRHM3Z/fiRKHW4Zo5eBmoq5kqafSHmZnA3jVy
QkIuqYa7nrwLpmsVT1rHqKWYbj7NduStIm0MNwdkui2AWZsA0G/NsXx08IrZlJ8Cd
3U0pZ1QGbGrOSfyWzLZh6oGCHnbHFgAIz5x6uWrwLpxXhuTDVws+oiSA02YdC8Flb
ew71AQKkqVsV0U6hQYFsHsL+pvu04TcqP05xfwBZl3UWtRPuSg5pio5vOdtGIVFa3
m3D01fQ9F4iRSZJCCMLcndD5F94CSCimd3zUyjNU9fMYaZ1Z9NE7HEt4tzhM7M0wD
/ea3HhG1cjNfulUFvy+04x0YuS7cIT/zCiDFkAB4T+hWwgbPBIJHumVCVbVhQr8gl
c+RGWMIrQ1BfHIsxOdnGxrW/jxMpcqzjO9nNB0QDsMSNaAuXQ8EVOMnDmiH8daaQM
QH2QBKXtgcKPVPJpqSbvPT2WGEAgdlfDphFhOC/L18dt5Ja8KGbBtQ2dhL6FLoPmc
WPY9jFrxv+eSICvUi5Wc6okClao/ml2xPis+O+kRbve1r6PkfKFhnG3hCQ+jxyO1M
RM4D+oEOAP/Xbl4/Uc+u5Ot1sbFGs4VoiKibPeiggIizzfp05R+tMgMIz7hUmAM6Y
FkZzxzKThl8V3s4yXLGv/drBJpSa1fG4lZmmOXfCvsAf15J660ObOxn7AiJN8fT4E
fLjHgoTZiCFYU2UMFwXpfKedm7u6+0gLyFk6ni4C6DAlUKKEVK5Cm+p/Ql3cNr70m
UhimfsrB26EYkwFWcfQGWhkUs1F5fmglVC1TSn9MflBEO0hUH2e/ivQIdNssN7Ni1
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
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
						else $type == 0;
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
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
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

