<?php /* <ViaThinkSoftSignature>
WJQybky6qsSpW28I5ZK1NAaUsu8Qpg0fz5halRZOaoxt9IelpsjOdBboT1KMGsgYi
L0S2N6YjA4LJo7mZzxowRSQzdSU2Ndu4Txj6s3dG0mVWxoHLa+NN4bzDEtraF8Nxy
JstPj1HCtAczGuDrb9Jar8P9pRlOianCWMZv9bj3ZcwA9WqwVgngz+y2Jhj5KT20n
MO5oIfZanNmY4C6TVfd9Y4a5X5HfcwInTPZGSW+yk6kGjRc+Ksb0asXtJ02KuWPJ1
jOuZsoJfQgrJnjFgDdE2iiPixcVZpsZQx0GrHnwwh+nhkO1vJ5lprWXAbb9cOsNAT
jG0wlsS4gkC2dgUGsYSi4hptjD1oXmkZDb0+bj8FvcLCt+j+SH3vzKeLwz+CyAMAY
0dY9nPKwqDgS0uWsrqNHs+6Vm+gladlUl1qBwK3/00aHCTYmLoXlaBXOi6thK3gwg
NRDMVh4pVy8Iuc/elZKYY2i51ZVWP2yHDcEb6Rs2Rw3857tKzDmcdDQDiRyKKTvpJ
u8RtLm8WVf2bFaJWDeTXcz8EzzHUlB357Msd8VWBhP+ckpSl4UzTjBpFE5Er3cMmB
YRiMcfV7+MbbkUwifVlgC3mZkA/FZZlOAfvuRB00/kb4Ho8uonOTPnF5IQUuviL1y
Dx9AW/wtMxpek+JpcE4Ld2fNDfBL48nu1UYgmh+RZuWGBobyxlLswE7wj/W50q7eb
+Q3y/QTVYDVMVnidESe19Ed6DNb9BTSe24O1fiXL1fFOh9mUsEbzeN2XiEO620lD+
Jpmv5ef6inzMWGS9ufvZ6wYuMzCJWlX7S7ZtJLLr0Sia5GrKk2FGTAUWfO4VKlXf3
0UyGw9VJZL741NxZ1J7jMI1vDZMt7pSlaeZA/rcCFBW1mtqx723EHvk4w069GhBeQ
5AGtuiphOeCEPOq/s8OPS1pzOXeFCrF7ZJMJ2Iy1sqJD9NiDEt2ibCSgRo/C1P7us
4pgUFexatorqS0vjtGow/55sxjbYMWBlxAAT6Xk2Z6ZfoPEXAWnyJAl1olEHQ3XVX
CBAC3YIK9naA7SQ3j9l+OXnYsBU6+FSweOlSne5Za4D/PdrZCKAdwrcrzh7acdWuG
Za1h7LaH2F21abkju5kerkpqPRIJrkgoDpK5uFxXWraRHmpIIsRQMu+EQhcdo6kJm
bf5rrm1o0PsUXo2LwmcdLOqgFSTe5Z6dMUB4W+8USc7929fe2gyMAMGxyd9gNPwNP
dpepmIF1tuKzpOAO/OvqZrc0QdTh4xkSm3bcHe7deUNlbqzQLzUcEI95V1I6xZRfK
RBh+w6bk8M7/3xkTxiOEfubiypgc7Xf9V7hUhyRiyHGf7rnwXS5iKnRBm+vncFDbF
g==
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
		if (strtolower(substr($local_path,-4)) == '.jar') {
			// Single JAR file specified. Search it.
			$files = glob($local_path);
		} else {
			// Directory specified. Search the server.jar
			/*$files = glob($local_path.'/server.jar');*/
			$files = glob($local_path.'/*');
			$files = preg_grep('/server\.jar$/i', $files); // case insensitive, for Windows
			if ($files === false) $files = [];
			$files = array_reverse(array_reverse($files, false), false); // make that array keys start with 0 again.
		}

		if (count($files) == 0) throw new Exception("No server.jar found at $local_path");
		if (count($files) > 1) throw new Exception("More than one server.jar found at $local_path");
		$server_jar = $files[0];



		$cache_id = 'minecraft:server.jar('.filemtime($server_jar).'/'.filesize($server_jar).'):version.json';
		$cache_file = $this->get_cache_dir().'/'.sha1($cache_id);
		if (file_exists($cache_file)) {
			$json = file_get_contents($cache_file);
		} else {
			$cmd = "unzip -p ".escapeshellarg($server_jar)." version.json";

			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec != 0) throw new Exception("Cannot unzip version.json");

			$json = implode("\n",$out);

			file_put_contents($cache_file, $json);
		}

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
