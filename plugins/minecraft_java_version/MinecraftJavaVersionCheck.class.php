<?php /* <ViaThinkSoftSignature>
VoLrukd2+1rHWc8YwnYyT7d2R4OI9x3gYpI9oiFWRkU32Yv9zQ9Yl2iHQD/2eq1vU
HYHjdpEAWrMkSrpo9MNZ+mbyRwXXd0gK/cLQiLlYy5BCMypYdNJ54Yy/7jNto7O1u
Wt3iBPVZeLhgmdoM3UCWSJW1c0Ngj8zv8hg7vrgU5tCqwucEvfDARDNfZs/wyQj0Z
n8Kdj3FxhzBuM4Jm7BUgz5YJnPuKF3HVnnAMp/F68zr1ouQwzA67xPr02vd8Apbu5
WU2gpJzhxMvmAvNnWz5HgkSqSQb0W4ABi0M2HNzTx0f6Xr57phn8MkL6fPqGpiNE+
U/3IhdaSIGBqmhcg6O14MNHQzvdMcowSyEPEAz4nyGNjmobDjhSN9sYeW5zwotOfd
Kwrz/0UlgGMhWeW3dwgxd0lUDgjvYjJtaru7Z0mkyyZNcTwKnE+BOFTZfADYe0PUm
zY3CSSftjsfuUBap1aF1Jxy4/xVctgTVBWdKiK1Lk2C/iGEkeIFxWIk5wFVhJecr0
QCDzCc88s7HHNRmZwiGYGzN/2jjswOHc9KpgOczQWd1BLgX53b8rRmU1CT9u1EXli
2ePh/QHB89fo9iaGaC5uWmFYpGdLh8CSI4evdPT4gC2hhjIpl3+t9LoR4T7i1wHKZ
Uk1SVNvQdQUeDBZNkoqFMW8iwJcU1VgrMmr0tCqu88bxaNFAPnvOZq3W5YbPMwOvQ
Q+G9W8dPafu0x8VjlcREkd3oHDMZFmRULPgOLhzj6Q9Ms/aBoKHRZH7T+2nzhJ1w3
OL7MY33Jr9WBZszPfcerOAPil65w4dS9LVzUq1mdufZ4udRMqr0DCARgk637KT7Tj
tLntP877IW/ZaveUaLIEmFUPrK097tNi9d6og6Gb26aEeH6bd3KsVfmvgYAw7FSnM
v9QvxjMYXrAp7X7mZ1DTad4lwAZBml8k1uQhNfeMxGXCF9Hs8wlnLCyQXM9HqhVof
71yLzIGfS8DPIRoI6Mv8MZ0LLWqn8spNOyoazOPcNAcJaxz21J8f20F6cTjquhiaG
oDpLH5pqjnJptdPJmZGCfzocHpAHqSsZ2zlghcoL/25iVa3sCwSVlW3/SXdrh7OIz
smIG02GjCFa4n9/kAGewSb/UJvZmB+Z2UbMfbGKqRUoUmZvh99+qfnFHfH2cG59+b
uviYgBLY37MEbnJ4rdyO8KtlZjcBsHzf8AyqDPthJS9hN9KGd/dq4/EneIlm+nnbo
zQuo6Q7W2IahVsrI5Wnr9GYl9mZLBoZ1X2kn7xrprMT5K8RwohjKv2zeQaOlsBX7+
NkeaktqUt69FIza1BNJ5irvqGbWdkVLeeVv7W6oZYZkaQCLtgy3sujDDxCs+mIMrE
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-05-02
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
		$headers = array(
			// These headers are important! Otherwise the request will be blocked by AkamaiGhost
			"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36",
			"Accept-Language: de-DE,de;q=0.9,en-DE;q=0.8,en;q=0.7,en-US;q=0.6",
			"Accept-Encoding: none"
		);

		// TODO: Version is currently not shown anymore: https://bugs.mojang.com/browse/WEB-6497
		/*
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://www.minecraft.net/en-us/download/server"); // TODO: make locale configurable?
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$cont = curl_exec($ch);
		curl_close($ch);
		if (!$cont) throw new Exception("Cannot detect latest available Minecraft version (HTTPS request failed)");
		preg_match_all('@minecraft_server\\.(.+)\\.jar@U', $cont, $m);
		if (!isset($m[1][0])) throw new Exception("Cannot detect latest available Minecraft version (regex pattern mismatch)");
		return $m[1][0];
		*/

		for ($page=1; $page<=2; $page++) {
			$url = 'https://feedback.minecraft.net/hc/en-us/sections/360001186971-Release-Changelogs';
			if ($page > 1) $url .= '?page='.$page;
			$url = 'https://webcache.googleusercontent.com/search?q=cache:'.urlencode($url); // Bypass CloudFlare...

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$cont = curl_exec($ch);
			curl_close($ch);
			if (!$cont) throw new Exception("Cannot detect latest available Minecraft version (HTTPS request failed)");
			preg_match_all('@>Minecraft: Java Edition \\- (.+)</@U', $cont, $m);
			if (isset($m[1][0])) return $m[1][0];
		}
		throw new Exception("Cannot detect latest available Minecraft version (regex pattern mismatch)");
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
