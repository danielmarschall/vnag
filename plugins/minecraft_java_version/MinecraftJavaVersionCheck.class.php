<?php /* <ViaThinkSoftSignature>
YU84r4UF1RQYMGABCCGu0MB2mvfy3E9RC4oCgHx+trADi3ukDqRO/pQV+NqbQUgWm
M7brafd901QAYEjRv1U3cqwChtRpLthnkHFWrISIuesN+ztweQ0ZJpzK3U5t3gN1d
Cp6z3x69BrgC8lmdsZVHn5OKUmneMK+5dEEvlAhXp4y+fsvrAtkqajxfi9lNm4bSU
TD4OEknecfUj95+5d9exG3ml4UlI6VGDHeIz2GHG1sPy+3zA60eV1Y2xnQS12p9QF
TZEevtPWG3IqGzsBKZ5OJkeWFi4g5N+eYGZIcQrlV44Qv3gFfj/jGgb1lo/1JuGNA
VVhE8JJqfuQrus1xtEOU5I22FFoNrcRYmqdaTRQ/VzWbLbkc07efqA+5gpqgL0gc0
7Y2XwdSoHouXJ3z9OiLvcOntnRmtELyVL3V43MvyfrUtB74q1VL2GfFzMiiDrtZ9I
kJQ5+OJeD8b2QEn6Ug85So4Pn6Fvmj1oloK7H4kGg/j6TK+s8D30TnsLSLwlr8PgJ
NMeaIm6ctfA+0za3FGICzmmi7bupNWxHTwppeFEGIFoOXQQbYHQSKpSymkBWhaa9N
bOXWe+5zapwtM6HNxBomYAeSa5q45TuE8/+Fz2KrvP7oxNVQMO0Vd14PVf5oyPctv
nmTQwGBft5//WZKAK96srAQPBwySbUGEgJDOc5FboVWPUbGG/zNkfXrYohcexkv1l
JlihNcLd2/ZnYrvo8ZMfToCGBo8Ie39D34GC3taCMQ73W9TOp446MdndVeIFV4NnA
VhMddt5cYzrWiS4DfLaCdwvjMmrhvmWiDuSz2syTVlB1dfMSE/kUBQGPdeqj0T9DL
fVJ3pBYoQ2SnbM65xk3OzPhokuXghM2ViF9laaelD5NIF93xA4EJGxFW5Qx5GYRjY
AZLjbipIRHvje+5U2Tc46V8XrOjbT3HKJcdwBPDDTHljDZwttXcGjeJj6wJI72hHm
W9Mjhag8rUwab5Z5c6jE+fuqsE4CAaqADoCuu/GYRjt0bYzoJSoQkX3yJvDf0P6gd
sIeL079kvHsTPrWegItcL6HzMMQOs8ej63qjFCX6rLzlghxgAHX4g6GiCTL5eRk9I
TStX3wM5+gyNboUqs3cR1pU2fIlnbWOZy5aEaUDlDWw6OjUvr4ZHmExnAWdMfdUnY
3mbBb5DHM1ccd0dMvouowi3sOKGPeRVQh09BADaSzhVF7xq6t6mYfB16pTejboFkw
3fHjYuiSGwsX9ldnhj0QhjO5eKJmQPmWdLCs1bpG3od26aX+Dwxs2gKk2Hk943stv
L2L7y4yxqO76zPbI660m8JgS/Z9B006pJvJg0wa1u/mXL39mtD8l3clqPJLWZ8KDq
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
