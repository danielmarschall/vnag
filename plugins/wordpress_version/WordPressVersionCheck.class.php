<?php /* <ViaThinkSoftSignature>
cBr/EeJSYrbn0QohsdW0hHYWtWWAqn8q8kF1PwyoPJO8iS5d0F14B/jHk9yp6xV74
nRDRnrvqZTfvH+FtpDCPl6Sp19i8R8aQANgx/lNHk3w0S/d2qtThvfG5FRIOzn72I
MHxfb3YZTGcGEGb/4f3SdxSIlxVasT0EjV0ygpbpIeUVfiz8cx+2cYyxcpIiO7QUN
Bx1mJ4rxRgrk54j5x28UqbkxWNSqJ1YAC/gm/yzOXJDzgqnrsILtuLuJb05z5gDi9
TlVktC8OaKIYEACHSEzjl5t3/nV4EBtnEHA3+ulBqkVi6I8Lw7JSYLvFw912KTrbA
R/u6p8Hl32Q0BvTx85/5sWiO3pzhcBl8gHtjO8vbH6UrIUM3/cFd29PfcWQXPTaVt
XAlf7JHLnqLuzCzt4E93P3fHk+uCgniM4pqQEdOhZCpw7KA4SbIthEk9+l8GuT/70
mIFa7rS7CgO9CQSmbhZrqI3DUW5M8anTR1Ey/OXCZJqJgWlhvgXYFwj7eqhbfI6YR
EEoUYaWYzoNdB7Cp8t1N6EXXz3/H6c1HClPlqi2oXq+nh7vtUVousbtlAjM0yXGRj
yOpahsRom6BRRwN9rMmXrdFvZ1XwYC9qkxHGKflU6ljKEFeUu3cB/Ib6jTCcv+EOF
QmD75X7DajmMRpFEi0o+6hKUmmoYwocBOxxLqr9kcqd0kabaOJjWtiC7Gi0FaGOzV
XRNw28Fqynmgm48gcEyBW4fHULqaYTbPSpDNgPbnhfms8h9ky319JwjD7yeqY/SwO
P/zcN7shRyOjdRHbxtkD/sf6Rf8aGtLp8Lwfh2guNtHLF8En0k/GyI0vF8Fg6Qr4M
3/ucJMEFKQmAy2j+zcIGsp6d5luNlt/f/1i+womQBqbIj7Xf42BrU30i9ko+3pVC6
Q9afEU7n8WzEtybuVtZWlBrDAJmZBueud9X9FycAL2g7xFq+8vIxaKVBtTIfbX2so
kQa1R9+HQaBPcd43rczU0eOg/n7mZwU0Oy8UyaNaSvaOsMIPyzFmom8wRqnZ6yH6Z
jEhHp4XUZ0oGxG0mrMYBvRsR55GE65n2iQ3OiVZngFay12wSGA6EOc2/xbir1dO/u
EkoYIJuTLEeQxHZFqhE14ZnjJXjIiKd2d2WtS/6MdaX0qNqYDW5Z9wjv81JNthEAq
v4kbCyYNRc+Lsj56x5mT58FfK0DwVKH5Qcwxv8srQyRmPS8HJ3EfyEnezN2y8Kx7b
zDqXzpD/hjsumE4SCMn3n9nYBC4zT6vBT/bYG0XE5KegTm7iqjNiMibuGkWj4p5Qz
XSIs07xKLlLbhzu105richxdAvpJABOVdSs2mSEE7S5FkZRMezB/K8H4iouJvnl1d
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
			throw new Exception('Directory "'.$system_dir.'" not found.');
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
