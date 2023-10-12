<?php /* <ViaThinkSoftSignature>
hKnF6Hd5bzvvU4SQOXMtp89OAgeryMb2Ob8UKCrzfHkJ5uAchpNs6JBzm8YFh3mW6
Y2Q5dME2QNcDxmEQoWfq6++2/5ln/6a1o43BxWKUZVW2QGg+8TKgJU7NVcEDP/L9/
cO9dP5aYSp97qXgm6WO7a65s8FmSQN7LYJZquQRR7FFKALvzpOlGLlzbspEORbJdm
6+3cVC9iCZOTYkho8ISqAxvf4pIFJ5onDHgwjyQXZ2wU1CVzVcwM3t70wZ79dbvIX
oHKmrFBNLHZIDnRtjo6wMctjxFrofXNXreTxzS3Dum2aUfQJSceLusF1VFvZ1iBra
gZRKjFlvj2J1VIKE/2tpQ2odYzmTq/jUHZdZCmtT9vFx9pH8elsTC0wyiMehyy69m
tGChnZW9wjGQtKrVuk+47UQsjbuZJsTIQxw/D6+fbMxPr0J9HwtmNqFc5VRfs3Cd4
biSdnAUV0TkZzQ5ISxfDHX3ywDKWxd/BqlB5zUkIU5/rZM/rD/Q1bb/uKOVFtRavi
ZDLbtPfXU3NMhf3kbfiEzR7tHWBYL+Z7JWiSVGdHhFvfLzqQh6hHDG5XIOUf+TuWh
On48GsqQVwO/qmyOUOjxwBXAWfGhIVnp/coiu2YYJfYcSuLzVzxLIys4VqCk0/zTD
PhjP7jui3q/abeFfsWOddcGOaFik/V29OUhGK1nNGOZRIC+NSTOYTmR5B3DCRKQi+
kZaOTJVUpwaOZybp9Lln2zlbX53ulu9eXhkldtVGqm/pEDVVPVa7uRSgBTPvtpLFf
BWSh0tAuKAEI/9Qpu+BIJSdCdGxiZpbVt2unMo0vrQV3Vy4+rXvtWuJjyFX55nEQf
5wBp/unxyBmNm8cRvGmzmy1J0t0mB3nkiAj1UAEKRFYnN8Owb47mB0q/gvE4HtDof
LS/IT5ECgJyVxNpWESR5Dx3fGKVr7G4ihvzqC3jkCj7FldaVLyMZqTiU5fRkOnvnA
0joAJvJx3rPryvcfez1iXrUVRBH6y8MFVs9GeLlcNkdtWC5XlY0j8VozMJ/lrgXGB
N9wjXvLgZK1W7Db07N84Qo2gBcut9HjK3g04au3IcPGpkT4HEe1R9l2khYITemAWQ
o+vMnuxxpwqbs1g7xlKIkglUsplQrbEub1huzWyEY4H4UAEKibFLGF/NjNmJDceV6
ornstaiXrBUlc9lCO4RmAwOpF5JhCDe/XYjON7biT/9Yp08IAHT7CWgtqEeod6vzJ
Fj+qUgptMRfgwoslF+jA66bOzSDs5A0LMynv99yFvQ7IWjkL28zNXnsfP9y06ZyyN
VMv1/pNRYq2/T7H5cVzotED8BXkvR1N3NpGxCYziJBGD3tlcjzFPdPyMhsQ8DFewC
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
			throw new Exception("Manifest file $manifest_file not found");
		}

		$cont = file_get_contents($manifest_file);
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

