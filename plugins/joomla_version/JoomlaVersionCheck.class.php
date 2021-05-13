<?php /* <ViaThinkSoftSignature>
xnimF1hRP8VA+VrRi7BCZ+FnBL8zRgZB2fvCr5qNHNC3yRGNwotUK9Q7F3hNDS1bJ
Bj023k4NJf8+ADrh4fuvQ9sgc70cX77jtd5Bm0YZHj4ILzetvzycPei4C8P9ix/4o
Pal++mdjpYc2byRJMwBY+SgbCzudZrck7bBFvxGU+fnT6jIDVklmrQwH7kVUXdggy
0TCh1323Kuwfu/7t4Z9+tbp2XHDwtQZ5bNZ2O6r65cKq3jg6UTZumb3AQfU1C0qyu
QyNZYIevVNdnA1NoSl43tDDuAvnUC7Xc+nS6SYDYPTnAfASWIa4j13l+zSiayoxKt
fCUvHRsMFskQrYJEWWS4MNmqSbxiv/0IQfyPEPhuYvyYiTVBbOOYZv5h83F2689k8
dG3GhSjpefRfuSEM0FOGg2+Zb8fVU1svESytKNS0UNqgwealisqUiaBxax5nASRHT
1ygBTDLg9Suore+xEKWWe3c7rpj68lH0XUoCInLizlKpqz7DzqIJfrbS1bLuMSuxc
fJ1Ddv6Lee6DNtBn0ygUc7Fz1x2Xoa9p2+kK9DqyKqaKn84ne1T+xQZDgC2zfp4fY
K6Rs9BOKRnmj+zCYXVwm3qe3lWx/1YZwVYklBXMIMBRe+/sXguJarauizmXwyajWf
1ODM91X3Rxcqt2Tuw0hLrJUqvsKCHN/RxjMZRGAg73UIE0N+WJ38BHNynDWLXvS1p
5BUbMLylTIgWVsLQb+bAkZEZzdW0ICM0AG2szwjpvC7eGq+Ng68kQCT1sglMr+oRH
UG92AzTGYDyxhAAJZZtvpR+O4X1sGOjRxXs7l3Votp9lzExWwZ6F9GD0R4HC16iNF
wp5KhCCttaRAkvSQcssoHnYYDbqVCdvzVDbB12FOz2sG6JfAxkIAZK+b8ePuobMvb
f8hTv69MgA+n+UlHuWlbCA8Yfv0Zx53m6bD4XA7Yh9b5dcUL52qvKQc50Gt1nvVcE
ZzAdqrzHiVD32JwUBcTOlg/dF3sz4+S1VlljM4IEtxDTQwQ9a/54Q5cdCBTHbesGt
yCR/dlsrmNOucjz/DURbLyeoFobJ9fgnD3RbtP/FfKo0jC+xnTM+k6WKhgJdqYQag
wZDSSuweiwAGfu14ky3b2WCjKbnvXpA7Atr3salnmBOOnwMVMpP+8Gfu+2MrKfBgL
SUAW6+gi3R+nc2t7862kXyNf0Hq3VwkKsMTFmu/UCqoUuBguV6p6AzLLpttWeeOqy
/b6wExct+zpXNxcwTEtplKPdh91uCQ/Ww+Qf/yoILl+mz1TkHLZoQANG1Cwx/yG0J
KpYowYKg7GFc+sg4WpljMU63j1o0nlAw6MiGcEOycq0+hmbGdP7hiSBumE6ZkfC/T
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

