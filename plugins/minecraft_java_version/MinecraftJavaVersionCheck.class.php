<?php /* <ViaThinkSoftSignature>
R1byw31mTTINoOFpCUaDZk9PM8lgAl7cFUGkK3R+yrE+NnO5O3OwOyY3XQcOV2kSd
d21IoZozk4drHLRhiDzodcjBas3+QfWpKVwfxjX0vkgtgIO38a/AhR4inB/dLiLQx
Wi8xTyFJdmfOKP5QH0mlIAhhM5RccMTV14klhw8g+l4VPMlS9jjKbSIX/1T3+EGnk
iAs8SWn5YiK91gMLCpQwvkDeGZjk9usuUnplW7UmhEs1fRXU0byNC3VX2sdSMIoPP
OziyMTzuJIsOm1ihFY34V2093JAWKWLgS9pK4jhXZ0amqIjwh+4PAnNXK8My9QkCr
7z7WB4r1ZuVGCRMW5q1DD8hdyzuo4+Yn8wmrzuYsnFbcZtpUtxSedTRQyoFgQLMWx
oq9txiiqjbNUeRCRJ2SJvzvP6niDK+Yqb8ElkcjzN7HButAok3DQDhXdfCqbAeWpQ
z/4vP9ruYoo3YHIxnXXLJyrSSbklsCPHTEDqpeSq2zty+/1Z+YCpY1YRTTlzpv9V6
csChl5vJmXdyJrpKTHEOjbyJPhhW4MJoJMjyIQ7zFVZTssLhMPGuYx5VRcUJ4PBhO
iSQilZlSG8NR3wxnAXP1xEFNtZbbBW+C3062/+tvwWATIFuhBuIdQyAmDr30mSaKm
PmgWft5w9khqRlM4nJe5g+P2p5GG5JgrzoVM6FnhKRH+XAjlHavZAPlucTZ1X3S07
5ZkED9IQzOREIE73mshS/j1bATWrEQFdiWJCohN4L2QhIyVORzXJQztAkvOzjSqVE
s1vMsVLTmJAB6pzt8RLxqE0NAlzyF11l/9v1/LdxfScmPFt0CDcz2etBVM1yFWEtz
9RDguHEytq4z5dXvojoOUHvhLFVQGC4jH+k0iaooc+EtaU3/R34knaJGJppOsA03y
dkxDtv/T2iKwFCXTB3vF58fl2wAJXxnubc6AT1UgQJSHMNRvppkhUpFUcxdYc4e7Y
lkG7fnbxz/Fvxof1p/H1lPfTPSNxoJqn1Ogeb4DcW3XhKbe51R9X6FgMUeOE4NmoG
CJx9Bfs2gXhe+iU62GDUOzgAp+IMoE7Vjw8vQqTvfWqvIvSDzIR194o1ajZ0I2yEy
B3VbLvLP12ppwTI2MkhMipU3k+VlPo8Wxs0FKMSNlRxIJ5rUspnspgPfsgX4Iz7b+
SCIejj5xCDZb/EbMzoAaE+rzOavtf+uhPZv+/HecrSyVff4VxJsfbNueLRgaxdyBm
2FHJHaaZfGxFBUdsV70C4zHNFItmkgiqXO4JGgJH2T6rd+0uRkutLs9ko8MSjwCvi
QkRrAii8ODm2ryWJXahOAVWERPIcvyahd2gjPeseOUZgKBnSFh3lxG3bBdhuIG9d8
A==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-06-12
 */

declare(ticks=1);

class MinecraftJavaVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_minecraft_java_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local Minecraft for Java server has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'serverPath', 'The local directory where your Minecraft for Java server.jar is located.'));
	}

	protected function get_latest_minecraft_version() {
		$url = 'https://launchermeta.mojang.com/mc/game/version_manifest.json';
		$max_cache_time = 1 * 60 * 60;
		$cache_file = $this->get_cache_dir().'/'.sha1($url);
		if (file_exists($cache_file) && (time()-filemtime($cache_file) < $max_cache_time)) {
			$cont = @file_get_contents($cache_file);
			if (!$cont) throw new Exception("Failed to get contents from $cache_file");
		} else {
			$cont = @file_get_contents('https://launchermeta.mojang.com/mc/game/version_manifest.json');
			if (!$cont) throw new Exception("Cannot detect latest available Minecraft version (HTTPS request to $url failed)");
			file_put_contents($cache_file, $cont);
		}

		$json = @json_decode($cont, true);
		if (!$json) throw new Exception("Cannot detect latest available Minecraft version (JSON invalid data)");
		$version = $json['latest']['release'] ?? null;
		if (!$version) throw new Exception("Cannot detect latest available Minecraft version (JSON does not contain version)");
		return $version;
	}

	protected function get_installed_minecraft_version($local_path) {
		if (substr($local_path,-4) == '.jar') {
			$files = glob($local_path);
		} else {
			$files = glob($local_path.'/server.jar');
		}

		if (count($files) == 0) throw new Exception("No server.jar found at $local_path");
		if (count($files) > 1) throw new Exception("More than one server.jar found at $local_path");
		$server_jar = $files[0];

		$cmd = "unzip -p ".escapeshellarg($server_jar)." version.json";

		$out = array();
		$ec = -1;
		exec($cmd, $out, $ec);
		if ($ec != 0) throw new Exception("Cannot unzip version.json");

		$json = implode("\n",$out);

		return (string)json_decode($json,true)['name'];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue(); // note: can contain wildcards
		$cur_ver = $this->get_installed_minecraft_version($system_dir);

		$new_ver = $this->get_latest_minecraft_version();

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Minecraft version $cur_ver is the latest available version for your Minecraft for Java server installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Minecraft version $cur_ver is outdated. Newer version is $new_ver for installation at $system_dir", true);
		}
	}
}
