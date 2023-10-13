<?php /* <ViaThinkSoftSignature>
FWd3PzwACGUUNwFj2FZtdCx2ipXSNX3x1uyBFYgzLzp0K55oOdnhaEN+27cirbkFQ
V7tzbYBjLSBjEJHuxvJ9O0z3Pe4UfpBssdGK8olMawnYpucXTWu5V/LXpHR8+/6Ky
DSnF9zrZWOQVCopAhoxQIr3qrZOVduC5/fVuJ3G4KMQpnPRee39bfjYOw9uZWLYeb
Ad65OvGt4FWinnFF2nWmtwzjJizewBgHGOANcLcGfku9jPWW7yPv4h/AkM0Djoef5
a9KAjruBIl59Ff0f8XwIqHVobn1e8r7C5OxAj+k8MJFEKIuc+p0cTs6jvNlqIPmqq
j3v+HCcLJO3F/EQ5B5RvjJdzSoYWz9SAO+qZj5w+NFnhAOl3wU3tvxPOqqdJbHh9m
8odOGvxQynUPyD5gm6VvRKT9XqaFEWrW4fZsjZBqAQ4OPmr9OZojvI6DgcCKawxeY
Je2QWUTAD8LugXi4+K9SNWAReIUF5JjUAAM52CwlybgYj2JLZGjUiXdIiZ64wn/sW
WKOiHk6kKiNIG2hoSFapWF7kzuqAKLISDzMKFQ6SpnGy72It8btxwpnrdfociZzJ+
8rPoIa4Xbo2Kz3Gel42wHnokXZdqWaTkMGY9kRON+MdXMVNoZFtUxegniDMtwK/48
4E0FrL3LBLdhveKvKbf8D0jpns/tByOELsw3p2m+frCAfUfmjoqeuC+jpkRYF3JxI
jSZdJozWJTwNVpvhT3Q16/0O4gLm6Uqwxz0oLpsoA/c1P/yGOqf8eFdWklu46PvZY
KuB4Uv8TANYvk3HMGNbz2OSWnbKAFuhm7pztZdtztszbYU7VB8/UQ2FLLLoYtkXBf
R2AB2qyvy7hbNp4Qq+NyJFuM13WOHI6EcJHAMdkpNZ3E5D9Oz9QWOELAwpcgUUOGr
uDoK1DYVx80+Ark8BOEQ2EroNSBYhepKltUHoa7hU8168E/9RQPU5j1FPAVxzohp3
T5ZpJlxRG6P0AvwfVXKJq0gYwpdsmY3ICi+B0FCGv9d10lpxiGg7AouUQ4MBQbgzS
5Exe2dkmaQG5JgPwstUCr46aAVTOIKgfgHZKgWKkKdryhLNpRY10Cg6V3YYpKK2IM
83CsIu9sCMGRj6cmruPVU0V03UUazz1msJRYtxZU7kcpEYSbWWpY4vVaRDzNwvhIa
v58j4TUKubiiAUBLlMF/JI5WmdIpAgbNxDmH/th7Oji2cJUMicnyPWXo9ldUUt0PA
JGGODkYUXPzQ3hj4ncI742VXxbhTe6MCkZKPu/D3y54KHyc9Q67tc533Qu7+KEX3A
rO9FDJKPnreXD57NC9hx7CWYkh8XobtlbqKgBW2wHufEy7rtyElus0+j7PFBKjgCD
w==
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

class PmWikiVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_pmwiki_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local PmWiki system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'pmWikiPath', 'The local directory where PmWiki installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/scripts/version.php");
		if ($cont === false) {
			throw new VNagException("Cannot find version information at $path (cannot read version.php)");
		}

		if (!preg_match('@\\$Version="pmwiki-(.+)";@is', $cont, $m)) {
			throw new VNagException("Cannot find version information at $path (cannot find version string)");
		}

		return $m[1];
	}

	protected function get_latest_version() {
	        $cont = $this->url_get_contents('https://www.pmwiki.org/wiki/PmWiki/Download');
		if ($cont === false) {
			throw new VNagException("Cannot access website with latest version");
		}

	        if (!preg_match('@Latest <em>stable</em> release \(pmwiki-(.+)<@ismU', $cont, $m)) {
			throw new VNagException("Cannot find version information on the website");
		}

	        return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new VNagException("Please specify the directory of the PmWiki installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new VNagException('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		$latest_version = $this->get_latest_version();

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
