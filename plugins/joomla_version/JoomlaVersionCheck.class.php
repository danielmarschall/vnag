<?php /* <ViaThinkSoftSignature>
f+K8EPao6vhPVpCTEtyIMftupx9RqcfHho7MxGqkqIaCyInQOh8dw6sYdbjqtCgK9
LqCpcOcsDge+TbOu5JBnT1cFUkDISNGUbWv/pjPy+iCm7D3vRxtjIgmXQuf+rmP9D
gJ/z9Z5UwetA1vooQ/8Y+8Qf0vsHA668BeN12L/tUtVNiQI6ePAyt1NGHnILyLZMF
JKxrGghLToBuNc3yIJgtxUN0YgTiE4ZTrjan5+tWzevUhEh+F/S8PftADd+cgmSAp
a3uRdhT+SUQrs0TPCFHOC0cAgyK2+pm8Ln/wJKv3hgIQU6RbUwwH8INQU7uBvX9Rb
kDPIvptPVZLr96+04lnPTxjsN34fLzUlSYKmiP0Agy3sRjcx2tLlrXe+EdCYi271u
Ar2TStJwScyE9DUY0W19eLbU8YXJdbiZwtd1D6DOMvQq4UPdj7faDjcHWcm5kOnS0
4cecP3HaN/liz0EjSy+ZG5qgqBkPlNtBjtlLtbL559fWtc6UD8QTHla6I0OexNEPu
0DcYE2WS2tkIwA0RClMBaFzkwr4d8fuuaRKfJ8DsjfU1UdRiwedDu/x/PqXAdEZ2u
e2sww7APpbAzvFcFiuRtCSeJttguhtgZbSQDNl1tTENMYEFF4+Zxhjyg+gytim2H/
zAKO+Q63OedRSfxKniiDvpz3jPyZor+hE0beT1rDf76nnjBA7dcnc/zAVUPl5Iw/Z
m8veB3wwCUUQZB1xHHMc3YoG6zw5vW8ozIIWIbJVpjn6L8LdaC0uLeNcYuaGSDxm2
Bzzl3hVdamULy7kSB8Ry8mYUba4DTnOqCjGhkLhrWfChcSCeevYZwadqPWNvaVzkw
LGV3jzXuyXmJ6V1e8c0tpZxebRi1V8Aep/NFrgMSV3dc8JHWiYJ4SBOVhhdaOEwBZ
wwI7dxAQHT6pXHtI9ZeZEL1+BLEMqyswWlPMgXgPbrRhHcTQE9AhMaxxGt3pT1+c2
k8Da1MWBLyr2D5U+Mc2Vnrh2+VJRyeZZEGVqiZV9ruOcFajmCv9r6wceFZhVOJAUv
o5cmsnPL9C4QaNLCqFnP9SlD47n+JLxODi6js4MLTqxBqs/HoG8IPkWfRmUuVhfNa
O4P9xA3uI5GjJbyvU+xgQj3oy7WUZZmfhVRSC4eNGjbsUT9vV9d9YKQ8/pnWJSTOC
VOxx/RxQnWYwjEXTliuCHxgFocGU5ySczR5627fK7/KOxTAZoLWg8nnve1kzbogxM
se6hHR6OKxWTZNsSWQHjK5F6IiBOw2fGYGPvGlSWDNXv4yr8JpL33Vpw7yJglsZpW
70ZDY2iGa0BCt7Gi6BMNmUwZ+nxKtMDVwtyYNj2sVMWJfo+VBl6wmXiPuVQ6zxsEL
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
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

	protected function get_versions($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$manifest_file = $path.'/administrator/manifests/files/joomla.xml';

		if (!file_exists($manifest_file)) {
			throw new VNagException("Manifest file $manifest_file not found");
		}

		$cont = @file_get_contents($manifest_file);
		if ($cont === false) {
			throw new VNagException("Cannot read $manifest_file");
		}

		$manifest = new SimpleXMLElement($cont);

		$version = (string)$manifest->version;

		$count = 0;
		foreach ($manifest->updateservers->server as $updateserver) {
			$cont = $this->url_get_contents($updateserver);
			if ($cont !== false) {
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
			throw new VNagException("Error checking update servers");
		}

		return array(-1, $version);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the Joomla installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		list($type, $version) = $this->get_versions($system_dir);

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

