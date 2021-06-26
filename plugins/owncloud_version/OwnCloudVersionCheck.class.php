<?php /* <ViaThinkSoftSignature>
TAItYQDND8ibGUd1YC/f04SIIzeSm99JNg+mmSjefJjt841dZ7o9BCrAGDh2jiv+7
akv0WmInOLOjoJPsglVIAEEBh/SfaxvzGfprA8vj54DZ11seUBF7jXUy+WCVapBke
yAGvtEAIZ5gToiyzs0aE94svxZZfpC7clFRhqON5u1XMZhdSsVgEfzJcIJroq35NE
2IRUebM4La1mBuopQWwx6FSFuxUjwcn7Q/zUP1rCwU8VAL/ycbG10FGNtYU14sOXl
ymQoh2A2ODFO2LYpMxh+7vHASw2Nnk6BhuvQAwM3C+SBW56SkWZK/JkRXLN9NoqKk
5uxSC6YUlsi1OPJULaVWOoBvjUIau809jeeW72COFRjZKkX266Rv7mm6wrcxoOIXh
Cj+m2bP42thx+Df1i4/CFFsxg/sHvorkwS+6B+0kGl1+2Ghwx3L94F+ThJxWtGOhM
Jawl3weaWZuojsU79HRnqBK+Yy8Shuzq/k+FonrJE4FE4oogIf7pzXGKOqXvjtXxG
DoWCa/hotEFcs+mCYeAAjpDf2V8qSFKT2odHjJwLklKpIov+Tlr/XtMrZtCIDjDh3
KBH6/9+8Bw5S5nFGvNfThSihlWKloqvysKDiCxIaLCOszzWNgsUUbUgppv2NFFzso
QkJ22yObljS6p47cYnNmd5Pp3q9+JCTn5epn+XP4RRN3Z+ax7tiusqqVnlik0pwb3
+9Yrl4Pq4QkloolrEnsQtyy3xlrk53fVCWoA3aLNcYVHN8Nl4OjyIEImLbkNPddHz
RUqGSNgoEZDKKbfi7i52VssHWzv/9HzHvNpoThbln8C3q8WFlgnuSStpwZSonZEPo
9wvnVo8orJjLzflkjPtBLIS7EL+R66RqpP3PF1lTi4Xl6fh9BFxif9juBSsCaJAjV
H0YECXyGiNPb1XcUXrDhWUrCViloHDq6sEQgW64s33SGqksFXG6IN/NV+54mW0hG4
PUzIZ6Bgu9HggpfgFSAuzAW5xTMq/ng19pjo9y7cdeSnMF43BPhg3SSdvX2W8oRn1
Fv5BHbsvw5vvHJUf+kUA5YN0IRUtOmoDB2Z4kkzuP9VBLoqK9KWG9PXZ5sMWp6ib9
1KqMuFbx4y5NI9kjwhCQXuDAF2bJhtCHuPU+IyjhWqtFTWhD583AaHjt0gaVzkcuN
umskt4IR5Yqvo6oDtWCP7YRWJnzAFB9zDItFk1OjjomaSkKQvhztITWO4N3jmtonG
PkIUkR8DWEYLZUSP+zWV9UZ2yE5KYAwgXIstx2ovLSvV6f5kyz+z+x0sU7fo4m0qa
2HjjwaFSWf/yGHDkEch/5woquK4YWrrlFzCQZmhok6tSF8WS+eb6Ci4/wsCa1Znx4
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-06-26
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

	protected function get_owncloud_version($local_path) {
		$local_path = realpath($local_path) === false ? $local_path : realpath($local_path);

		if (!file_exists($local_path . '/version.php')) {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'".');
		}

		// TODO: this is a security vulnerability if an non-root user can control the input of version.php !
		$vendor = 'unknown';
		$OC_Version = array(0,0,0,0);
		$OC_VersionString = 'unknown';
		$OC_Edition = 'unknown';
		$OC_Channel = 'unknown';
		$OC_Build = 'unknown';
		include $local_path . '/version.php';

		if ($vendor != 'owncloud') {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". It is "$vendor".');
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

		$cont = file_get_contents($update_url);
		if ($cont === false) {
			throw new Exception('Could not determinate current ownCloud version in "'.$local_path.'".');
		}

		if ($cont === '') {
			return array($OC_VersionString, $OC_VersionString, $OC_Channel);
		} else {
			$xml = simplexml_load_string($cont);
			$new_ver = (string)$xml->version;
			return array($OC_VersionString, $new_ver, $OC_Channel);
		}
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the ownCloud installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		list($cur_ver, $new_ver, $channel) = $this->get_owncloud_version($system_dir);

		if ($cur_ver === $new_ver) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is the latest available version for your ownCloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
