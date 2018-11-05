<?php /* <ViaThinkSoftSignature>Pk9vVzhiMUsINHj3unermWodlsIIRX8gfqCE9mxoch3WXBd3FqQdASPFk8CNjqMB1bPBUTqhkSkn1gKP8qG7e1kcr8pciOd4RYZmd5HuuqNFdJFK4l8cDYSl71LgqQBKQMMLt9bygoUPVyAgN6b8B87qM6XmTsJHDK74u1OtQOaffQNIBMXvbWH8tDFrcDpKvTvQlgkNYqil/gvZ/KMSjghhh0+OTFmBFeaChK34O0vMv7PbWw2Kt/rT1jxs6sfWzGal1cLAP/wAb1/fwKP8+OsmOuRwcNjEk09/k6mc93SxGAxYvRatgNoE3QhMyJMra9iF22/ZZ2/xkubn4T83rDI+AJXzvsvqpTFezxmTeHG8Y02ufSPOl4lFT9Ds6FtRymHaLtSMrXet3r/RRO0vM8V86kpQ8iEQSPEpXNletRZ9XoE6m2fsZ+laMin+WiHibBrSgr173K/fGuk0Vw1k0oZ10DrottkQ84WBizZXNgMqmQCuXoLKbVJj+75LCGp3z6YwU2XiEXRZbcjyp2FU8LG6NLBZJq3BjnoiFppoFRG2QCqCBAbUCCKNXhCqPyxUmXTRmcusxWEwpTZPMgucGixaRgo56ta7Mp8R6SG3c1k6kvOyueIfWoiyw2DGgMTnX/WvVgbH2TEeYn1RNv5YbEdMAYrcKfFDU9gSzKwr5+KJMSuXR6MhCPRPd0x+U8wg8Spf6Ys426ZMSd1a5MuFCoVt9CgvJcdzMLnQ0bLDBtACMfldEjqM8Qg/BdfS4Fsq9glaaaN5B9/RU50qh2fY12V5kxZK+q/3Nr+Lb9Ks+x7RwivwgBNfO+D1CyDY9JsyvOlc5NMOax2tyAkd4VL5eFPqGh4YwYuY1vxd+oBN1ED72Ozva85BzSi+gJcPF8APZNgGaGTRa/Qxkf0nIEQw4XPOrxysQtVjxzfAqsyzLBqad1ZBm/ed0tzbqPVju17YCa6cKWHGiVZlPHgkcKP8CDSJN1CEG1gp7D5luov777Y3PS1jLOiCD2ZV+ZSy1fWBA8aH8M2taKyRJl2mFYqIAk657nKQa/QaIMmPDaTzGMKJGlXSyLySmkTxpkKNcl4OMDIc8NkYxhk/EJW95rXP+J8X/PNBD05wplBx0d3W0ujUSyT2zeFbTGb4M7ph0SgMEFgwP0ivfSjTh2+ChFvdU0BFz9WbBLr0lU20FYnunsQEdlmU1aMgC23+rG3KYnXwsK7xl+ZK7wcavgNYIjrpdSy5V0errT23OWv/XSjyBEIp8KN2yYpz59/p8Z90E7P67DqIO4XWKfkotiLkRxhblpbJpXynVP/rjreWXN0pNtpA6EeO6pPInpekv3/UAKyrRpI151znpA9qYqSXxV15sA==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
 */

declare(ticks=1);

class NoccVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_nocc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local NOCC Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'noccPath', 'The local directory where your NOCC installation is located.'));
	}

	protected function get_nocc_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/common.php");

		if (!preg_match('@\$conf\->nocc_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = @file_get_contents('http://nocc.sourceforge.net/download/?lang=en');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download the current NOCC version <strong>(.+)</strong>@ismU', $cont, $m)) {
			if (!preg_match('@/nocc\-(.+)\.tar\.gz"@ismU', $cont, $m)) {
				throw new Exception("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the NOCC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_nocc_version($system_dir);

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
