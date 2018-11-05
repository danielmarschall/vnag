<?php /* <ViaThinkSoftSignature>
OJBtqRvIU6hU8LTzanZKhpd9dpHHiBOX4pwyUGXYgEltRcNKKASFsv71lmshrkuxm
gCp2q4aa9jB03T0RPOPXtAulRlFpLvsmD7eXHRqt82AC8H2PMw8uoCMk/bqgfAMqf
1y0sP9qs5s9faijFgdKlQkJH3nLGDQ3YsN1dYLMwtUQsKtnVobLizdd5tIlyW1OcY
5h32Yd2z24CjcgBUbknqBD+I6dBx7TBQWXqX4CwC7N2jDXyn7TBN2CHWtYlY1BldU
ndt6PXEaaoYOtqcQw/cSc7NvOenQFGQt++Kwur1f31+6HiGbLFGLfrA0sXJWUdYWL
HGkNHKmzmPoDZFWweQIadB5QQGtnLWUpPtIsShIg4/G0+Lt6V/janYr0D/IGVnRyX
Uikh59qNkx942oGF/ozuu39OYyAhyUTbpCT8/PeDwjS2qPbvsZEq2tUDGHHBxq1XW
8DmsmLkRCertcVGsG+WLBmxCJLRjI/5UE0zB9XCXwIPOI8+ImtvBgGhEPu63xbORE
Cr6R0L8FWZ8cjCvnrNEFZOSliTQWwUzpcgJTAFSJPlHEF/X4PbqbhsJLU6Z75Q/jI
fKWzxzREzkyPKQGDAitKnGwiNZo04tJSxBryd8Vqm0sASmTkSm1TU60C7MOzWCekI
ekea+lOpoxPwdXuphxwahGTxXkp+FCQ7/K1e/jYOkCgq/Xz7uZRVXK7/bL0QlrwMQ
tJbbiJHon4pE7aar6yP3mAE6zqXdPzxCRr28ochhY2dFU6RKXK9mMgkAdvalsyksL
R4ivEOcGrRW3ZasY8RhLu1UiZKGpVrlanK7E8AqL6moXB5xxTmNeBGBPzWmYJaAFj
Hp+iFNVy11jGTQD9HW4bQ0vqhj71TyCpmXJnsiF0J3Zurj+iB22U192yBVluuOemO
CTfygSySdZXsQCb2dARjnalkyb9ee5j0VrgeI7FYeVne9PkarpWRgvVhEQzLbwcCg
RHKpQy0w4V41wx5HdmrcncYntEdbIx0iITKeIFy65VaQYQyk4RNL6PG6jY32gyNqI
A7OzwnTYrguBlKhf4Fp9ij8g9xPtXp16Jz1AEzxkbxMzIZeSwQXIv14rbZszTuFJ3
LsjH77agS2Gc6nqframOvPF4YszPJUG+EpSLiiuM/5SAXcTB8pDPoaRSVXSg9ouoL
jH5BcppDUQqBIaXuJzDvqpfXQzAEBeyZg0jQw/3ylLAIlp4oNmuU+UlmIO4GYlGWV
o6eAqH/v/g3cffTVce/OvpjUR0+3bTgmlf8/QC9s/rqtYEJ9jB953TfnSkP1RXP2y
0ZlfuumRB2u2mDuJEZrWbZwXdobkfwwLrxPGeucLqPeY+it+yD6tNguvu2c0/BMuP
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

class WordPressVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_wordpress_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local WordPress Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'wordpressPath', 'The local directory where WordPress installation is located.'));
	}

	protected function get_wordpress_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/wp-includes/version.php");

		if (!preg_match('@\$wp_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = @file_get_contents('https://wordpress.org/download/');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download WordPress ([0-9\.]+)@ism', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the WordPress installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.', false);
		}

		$version = $this->get_wordpress_version($system_dir);

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
