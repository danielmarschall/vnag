<?php /* <ViaThinkSoftSignature>
ISv4gHvD/9kQtWE/SPYuEUnOdEDii0T07ud44GyGM0+pMX84eqR8FziJfACrSOGki
biOuiR7eAvFtoo+JHG9Lg59yO+3BE9gKIda2eeh2mek2XPBGM5XppwRIHQ+4DnL5x
Gzol6qmr8IKLTScgVJZWogNX+CfkjSYiLMpbBYEL/bVolUEdfJSbh9D3NQ1kDbAjm
H5UHlJbnSXm77xj0gSEAFZ7MpmUjPNlhDewpLyzsyVMp5/GJ2N7M8Lujq/JdZYbVs
unbAUJR7kzBIXkXsPcimOFesS6kB1xI3+y+pgHJ8y6ODLLHZ/ec23lIw2EFHCWt3B
lcE2XSTdFey7z578vWWl9fAHw41HZq38SSgz8IfFFllWYKmwzap2ZN93GbQNa+jaR
gAETrvVpGorFAEvZJyhhhSelVFP9gagx7aFh34Q4qpM1cNTgsP9HUe7cyULuOsvmE
lKFCeadIuuV7QHx7miQmlCWPKjzdAOkTRMYzYfZVzSStRsjk+Qwnz3OqoEpbfggkq
gj6Ynd/rtzMcqbz9FibhjQHsvyDR/OFr5mzLt7UhFWWSiOrS0XLj7soLMMXWWDtZo
pSRIyGyspZH2IuU89ZQUakJpP2N1srq9zH4f0TWwS01jCfdz8ZYxfAbUKQQmsQv6R
CTRAIOo+MjgBe3VQspksTL3h03Uy7xgSa1l0xeGYCRGAUV2NAlU7IdxXVAfqMmdvt
s0X1Aw/s2XyJRIIXzHb2axDRXqXDJGIeIvHTu/Y3kVuU3ilr1XnyVlfBeNg22LAwo
Zd2EoBfx8WejxOHSC11oHnm8qQuED6FSp4SzZGzYKq16+tZm4H5OcxNk1dsaoVY1Z
59wCSxke/ZMhEiAIl1C/SlmCiqXSVMQR6IYVLtCfajS1oyWw+jf107IpEjETm7eBs
HsScH7/1Aa650UPTwkIDNxakRhaNa+LaF/U+ML8q4DSZJolmDvv2Ra5nlULteEXjD
bZghdLENdIQBsWIuPXVmOXcD3/CR2by+F0I8yX5rTYLXwbVV0L4dQWqYxNS7tRugO
oR5NIKEf0EA3bMWs8LBFrXy6amfFkWfYN5TPZYRbjGAfpBqqy844jocKOdNpjrKVB
6Y/cO2n9swccNoYOxUrcsGU/9rKe/2p15pT1DjlYWN/nXyzvCGA4XTss0kgJ94SB/
rRNattVBzoqMDbP2gGsVJgf+pIpN6q5Ny3MuxuA5ROTZNC3qjT3DswtzrN00mRQBG
xL1IvfRDSW9ag+HPYAJty0MZr8SxD3Flj7SuuznTN/DC5RAVUz58i6O+pQ/7QkkyI
u3LnJUYdMUcJidVv6VvpwCGZs1Iouw0UDHabbubXAAYz+Evt1Qu4ovNPDs8HnJ64w
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-12-19
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

		$c = @file_get_contents("$path/includes/Defines.php");
		if (!preg_match('@define\\( \'MW_VERSION\', \'([0-9\\.]+)\' \\);@is', $c, $m)) {
			$c = @file_get_contents("$path/includes/DefaultSettings.php");
			if (!preg_match('@\\$wgVersion = \'([0-9\\.]+)\';@is', $c, $m)) {
				throw new Exception("Cannot find version information at $path");
			}
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = @file_get_contents('https://www.mediawiki.org/wiki/Download/en');
		if (!$cont) {
			throw new Exception("Cannot access website with latest version");
		}

	        if (!preg_match('@//releases\.wikimedia\.org/mediawiki/([^"]+)/mediawiki\-([^"]+)\.tar\.gz"@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information on the website");
		}

	        return $m[2];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the MediaWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
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
