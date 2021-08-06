<?php /* <ViaThinkSoftSignature>
IVBwOjr3n/pCZ7PzcZqZdKFy6ibL85VvOoRY3/b9Y0f46+8ggo8jVRVChw9BQ8BEl
tAr7KvfpxAcNf5TXhbrYurHeIJT2hM2x4ReAPEMcwxh7cdBYuaXAfq2rMKILRAKTy
p+0M7thMCz5LUw+is5DWWtpuW1oaW6obOzJ43+y0QPz0EGE0RbUTLFyeTHmHTdCHk
rETmMwty+s7tzdthAEB9fCviq34QlKiBmqMlw5DfwDJmLh+NK5Y441M8pJ31EZQvP
nyyGab+i7ZukBApllkdzYyLWy0xVSHK8DaZ7qLK3Lz6t+ClZLa/1N3HsIiRLKYYZ/
LAFZJyETaGaz3DrrbsdjtpKKEWe1GHIRLqW8h6ENFK9nBYC8NOSSLSt23W9B9T3RY
pD4CrdxlOog1hEeAI5HBvJ0h9W8x2MvHPk9K2EgIQ2WR1ThwYjykuf2ohfukPe/zK
tWUxYaAwzTOVGd+I/PGgEKFEbTa2Wk0cZJCSSkR7Tn67HpDle2Th6/KKq9se8GlMb
dfs4CDulmCpoDslnBPxsdNMYQCJSTAVooVEPywg/MZsZUEgMn8kXDMNmPAVTz29Tr
YCjcXRyiz3eWrCTyRpPCoAttfQnxdtebZi43rsI51wuetAzYUlmDxNMSfbfzyrIMN
HXovpWmjaFni8unCe7KUNxwWZlEUnj06SjGrFCWpSmYBdKRt3k0cJUs2lEjVhiQ6W
mNLLizMlw4/s5B5T8+P52+wcwGX8++etkM1GMDTnrA8yBH37gtOdnvOe7kr9pLO0Y
8zk5rJxBfQtc+eIflITiyrUNzLE3cznNzYPay2STW6v+iuRfXCPWEvxJbIOCcuwW4
DX0cfCzhfoL1IuSAUVK8D7eoafnebdKJa+H8wNnK8SWejwa27z2HXXYXeZ4BUcfL7
ubKtxOLRQLuqjKo5O3U9o9vMRi/OZLuBwYuabg/qYcPFEtBbI7JnyauU4qzWqL5m7
dC+aWrvbhn8hHqMB8p9A3PilL0NlVBlQXwZfYxYIanfooxpVPiJHBjRafCjEBQwsq
YYgDlcGfCl8r0yyw5Meq3hE91F1MWyW74nRzJPZElOCNmvAMxxdMk0N5Db5/KsC37
mKb/9o6R3fe9SsjF+C189u6yXomVmJJPQc8xWvQWEZ/i3MIGwtGrMpEBMKCU2Qbxu
mrVSsFzSZBBThut0deDgoTZ3B1VwJ3fcZdYCy7hsMBTtAxZbC/rtkMeUvSTRiHM0d
fpMIt0utv9dF+nbnnx89k6W2ML+p3ZkfoRJ/0ANYUAyPG+tjpuB31t9FkDHCQBKdB
0A9IO8pG7STLiEm73ZKxF7oOZ8xwhaUqBr93brTRY1fmsKgRo7lAlHcSGe4viwDP5
g==
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
			$cont = file_get_contents($updateserver);
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

