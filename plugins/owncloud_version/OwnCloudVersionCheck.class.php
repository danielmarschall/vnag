<?php /* <ViaThinkSoftSignature>
kh8uYUEmpc8WCbykBWys6Nxe0uUtfTgDrmwLakX6M0m3Nn4Ck4F2a6Z4n8IVcVrFt
kTw2Tk6x9jBe4YKiiIHp9M3XijivZ/tzmSgnQGrz9UWmnzUu2P+P/ospsRilxR3cJ
hL5BwqSzBcIWYf09BNuNxgeGi00hzbgZOtR/nQ1JToXMw9ATzPDHxx7sMmibUugdm
9BTAAHMH+3b3DUYmEQD7ZOqmfFYp0NvnbGMsUKsdS2EJUc9dqVKApfZ/Rjr38/5RA
JgPFRQ+8xFD0UP0Ifg89tvts2x2C+K+uaUG+vV5yxmpA+QWNu+9YoXvUP+txnxv76
2KQeejsLvQXjegjYQv4qfvHvpVm2PKfwlnDvHvBObUXgqpyguWZkEhy/XeEbbKZ0Z
drbaYM5UDVpVFKsGXU3k3K+lwDDRj035PGrVwvG7WIIqNc3yB0uHEKjLeA4wr6t+s
JI2LcoeaFAHvSCoePNkr/2+Ksw6hg4PQaofwgaAHZTbFiP0LnuxicdHJPfNmrcADY
1CbkqQBXS7pFsZYPK2cPw9yE0h4cpl8wuTHC58J4FxZDYi/ZsH+1C+eK0z8PpFihM
JOguZWxtkjiHp5OMhAXi1rv6G/JBZlrLr8rz/UBYVenP4JrZ7qZiMmiJZISfRsDRc
oviI0zxJ1bXJxoNPV7wNFxMP9FJvcUcgovTiHrQa5NJjXEROhQz8MlFwtSl2v/CZg
At8th0JKwWOsyz7kl/CPJwK+adt7h68tBkjCI0pG0fYGxwiMICF/mrlFpj6ZImuWv
cQH4hOS6Nca/f6pudH5WFbPyzqqximhbg+ZUZExlegvl9llLlltFiM+41oLERCQ9b
BxI+k6YP75wGF1wWEjY+j3b51kF42mQrT5jGt5VpY0UdEwdPtnWyUGHNJAcAdvwQ2
RA3+7lq3iyL4K0FOgNrIbmvTNRE0y/3KnCcXq6eqAvkL0MtjLdrgRfRcDdJ/WfRNR
lprNXKPYHheCe/gVPeMwyVGd4kqb2R3KFvLPhyBJe+HIgDfkgCI5jqSpxFWQmPP12
Y13kO7iD3rEs+bTHmUaepcgdX6+11HLG7BLwntWj2oc3WhN4QtzA9GtkZGQTUmGec
HMOXqiMeMyTnIA30UPqmV+6/OzTabVc2kT+uV4JKN4lxA+erdO9pKM4YQ0zJxdAS9
nM8h5N6II7CNriKbb61eqV+3v51l8nxmOZyeezS7zPE4kClem7wWTDoX1MIWzDRxV
FX83Qih9jLq3vuDyMvqmfd9VBsRdRp/RjDXheyijDvbllnVWirDrb9cKxiLs/oRDd
NuKj/vvLwR66i82xvWbmHyrYsSOxj89NJZGO2nvQiri4lke1WC9lnIB41RtYkPvv3
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

declare(ticks=1);

class OwnCloudVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_owncloud_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local ownCloud system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'ownCloudPath', 'The local directory where your ownCloud installation is located.'));
	}

	protected function get_versions($local_path) {
		$local_path = realpath($local_path) === false ? $local_path : realpath($local_path);

		if (!file_exists($local_path . '/version.php')) {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'".');
		}

		// We don't include version.php because it would be a security vulnerability
		// code injection if somebody controls version.php
		$cont = @file_get_contents($local_path . '/version.php');
		if ($cont === false) {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing version.php file.');
		}
		if (preg_match('@\\$(OC_Version)\\s*=\\s*array\\(\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*\\)\\s*;@ismU', $cont, $m)) {
			$OC_Version = array($m[2],$m[3],$m[4],$m[5]);
		} else {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Version".');
		}
		if (preg_match('@\\$(OC_VersionString)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_VersionString = $m[3];
		} else {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_VersionString".');
		}
		if (preg_match('@\\$(OC_Edition)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Edition = $m[3];
		} else {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Edition".');
		}
		if (preg_match('@\\$(OC_Channel)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Channel = $m[3];
		} else {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Channel".');
		}
		if (preg_match('@\\$(OC_Build)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Build = $m[3];
		} else {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Build".');
		}
		if (preg_match('@\\$(vendor)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$vendor = $m[3];
			if ($vendor != 'owncloud') {
				throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". It is "'.$vendor.'".');
			}
		} else {
			throw new VNagException('This is not a valid ownCloud installation in "'.$local_path.'". Missing "vendor".');
		}

		// Owncloud\Updater\Utils\Fetcher::DEFAULT_BASE_URL
		$baseUrl = 'https://updates.owncloud.com/server/';

		$update_url = $baseUrl . '?version='.
		              implode('x', $OC_Version).'x'.
		              'installedatx'.
		              'lastupdatedatx'.
		              $OC_Channel.'x'.
		              $OC_Edition.'x'.
		              urlencode($OC_Build);

		$cont = $this->url_get_contents($update_url);
		if ($cont === false) {
			throw new VNagException('Could not determinate current ownCloud version in "'.$local_path.'". (Cannot access '.$update_url.')');
		}

		if ($cont === '') {
			return array($OC_VersionString, $OC_VersionString, $OC_Channel);
		} else {
			$xml = simplexml_load_string($cont);
			if ($xml === false) {
				throw new VNagException('Could not determinate current ownCloud version in "'.$local_path.'". (Invalid XML downloaded from update-server)');
			}
			$new_ver = (string)$xml->version;
			return array($OC_VersionString, $new_ver, $OC_Channel);
		}
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the ownCloud installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		list($cur_ver, $new_ver, $channel) = $this->get_versions($system_dir);

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is the latest available version for your ownCloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
