<?php /* <ViaThinkSoftSignature>
fAs3Z07rGtxrdHEAlJvotyCva6okB/eU6qxpWnYIvszZb6P57MsGJ+iXYLqmNQNZu
181I2sPJtIPBXp/5Q9BZDB6ux9UNWrRFkc6PNZYidC1yBZXdbKQ5n8EwFAQEC1jf0
LeyGXd9pSMSEm7oVYZWA+uNvIDsx3CjPom+1L9x32aPt9SpCovQAbd7/798g4BRTy
Q8APD2ZWPWrVEJF8vnrOXRO/tu6RVtPrrKCFnM7CIuy1//QuHGZv9plmntj1HaiWU
c+ma1VnyVZhjDCy5R8KNNAzCMRgn+BhIi/+3rkfhPw7qeSHRa1k/Gsllm1FIRGFuN
/EwV2b9DzzZoNEQXfcqu9j7DeCYxz46hR3EMykDVkmXqn/9jHC+o1U74DunB7rJIc
9A0++8j/j13Hja1SF65tdFcOrZeCRIo1ip/L9aq4PZpN5BbhRmeb0gHnqM3EY/nZ7
vC/JBmYRm5EHedxxb+AkK+jMXW57vq0SqrQketzydJ2DbhL5i/+w74f/P+52HCFr1
M005w+JjVgL+4P5AQa0T76wzqISRtuDKAPOpbjpRYYdfa6OjeRewPPTM72hjL/edD
TV6k/cxbjKCMRU3GDGrJWUKXYEcyd8T6pNNRAjDQxidOyaYJtdXMhTTVEs+NLe5C+
fJKX4Lwd5HP3A/8kd3fuQX/RjwLNe/pAXNdqq6Wbe71BinRsV7xmwWDPjYds4s1IV
cvEM+VfI36f9jIZ+hsOyWauAZwMZeAS8Uy3/Oa1Z0wrCJKUBPC/BaO/GyFgAiiS4w
jT4L2TFUMv5DUGZcdu1ATUun9IQkpvFCYnj3stINFf48C29Eldy8eYFSCFmHVOkI5
1KIBRHwp6b3GCN1QoKSExzPwXqcxsjIoAc5oxbCjbg2xQ90mouPCjf4zIqYYh7GjR
ayHuF16YZ8cXg9jPnUPFwkJz/+SJD/1J/qNbVpqrhRmz9NpPNI1bM846P+3DTs+M3
AcfUhU3Ieo1fz6YgNZaJqcWxEPsghhu5edo+gT+wTiec90/4BukTCJAENzRfhyayB
Xu627pk+MMDGgZUAXXC6qsK0iUIdrv9JgC7vXGRnBQSd5XNnYac5XOZW0nUGmvqrz
BopRintZejsgSYep+in8MZK5XaOOh0Jm9qoOFcBfhOQ2Fu5v8XiOIPMqXyiHc3CVR
oN4UQpEAkgd1DLR6O00rH354pQQ67LkPoSulypfUWxvi0aow6ZjhG9gDybpohb6Le
GiA7pslhVDnsGJqJc3n1RHTidg9sPbisugLrjgDI/7M3oG/HVbb5RNlA0bMqXTKWT
XlirvKXiMC5dru4U0FeOQAU2C7n8aTRr4+mB5vUw3otQIvakeEBSUg9nSNqXrbMmd
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-04-14
 */

declare(ticks=1);

class MediaWikiVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_mediawiki_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local MediaWiki system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'mediawikiPath', 'The local directory where MediaWiki installation is located.'));
	}

	protected function get_mediawiki_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$c = @file_get_contents("$path/includes/DefaultSettings.php");

		if (!preg_match('@\\$wgVersion = \'([0-9\\.]+)\';@is', $c, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = @file_get_contents('https://www.mediawiki.org/wiki/Download/en');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

	        if (!preg_match('@href="//releases\.wikimedia\.org/mediawiki/.+/mediawiki\-(.+)\.tar\.gz"@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the MediaWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_mediawiki_version($system_dir);

		$latest_version = $this->get_latest_version();

		if ($version == $latest_version) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
