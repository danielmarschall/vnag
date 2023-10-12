<?php /* <ViaThinkSoftSignature>
okEz+tdTNB6lllJohO9ECI4MAh3STmwseFV3T2s7zbbhS88rBhz5eOHaJqH9jckV7
GgupfsVt+X87nkce4g3GD3vNJlaol/TJheT3y6YPFI8W2l9gKkgMZg2899j9SWgGy
KQ4fOJAV72EvpYpvAaIDOou1peDHeweUagSjAEn/2OV9X7A0fxrgu6tR+f9xscCpB
gRjW9BVXjLkx0WsthEr+fqkFSvQn2EnYk3sWLe9adWwlIvbFdbPoYmWTUP4CI6Fn2
SeMWPD0UFM/e4UCUeFbYcC8SxPVBpACXaRQ3RVi4TUG0+TLdj2mV+yLb5Jm1v6j5g
n87wlnGLLvF7IlZbJuzO4AZkbUWS7u0z1lRGdNL+N5mOUlAdVP05c3NmURCvTeSa4
NwN8WiBLnY2a9EUquS9V4qanKkhh8762JqjmQqBljCOaY+/7H6Ev64ei7sVrBykDQ
vlUWG1eGV4eAxXJZFOIKcrSjXIqPHp9nLGBJNUS63Yeat622F/RucRDyOSzuzo/6f
w8lpZD54E/f1YJc0g5KHpgVOciVG2wpXJhoUbPhjIE6Q4kxA/j7b/XE4cdgnSOVec
SVm/FVLVwDpruhYhxvY4HNFLzQgG8IaOaIgvRqe/rcEQ/5suV+bmcyO9LDof4yh76
ZmPktC5W2s15zy9Z2FYiUMwrtj2kxoDSyrwGwWk+jR5efrh+yDr0ygwkdaowUkzO2
oBHWb8fWodhA+Jv3ZEtHKy6wvxoEb+bhpBrQLa+Mz6kUf+FmBMGuYgUXXxzwEUh0+
0zgIacwPlz0av64ab5GvxM9y5ZN/r7zF0GALFR6GoyI+2B4E90sxQMqOdS1Ts1GDo
dQf3l4FPaDIzbFgb2QjJjm0/5C3HpV8QU3NPRYfzGQSoX0lDxtPkJOP+u8+9bEq9N
4lyWIIgJwMEYlWdck9CmjRPRFQ5Yx58DhFDz0voxbtsoY5nNV92zTeikhKk1Sw9CH
vRKkZV75XFuM50o2U9a+XTBiSXgHLN6nDSYEUhLXHM/DDwVVTln1hlPKrQarpiVuW
v9MLvzTZJrZTfG6/ika2RwkvzKvQB+JHxEEK/Lbofq4A8QncwwIN76ptXqeB0vZlA
sM0JuhgXzrF6RYAFmJAIwuiPe32cxtBS5fwvhzCDJp+Z5w0kVrI1hkYFVBSK4Z3j5
VE9xAPz42Tzplw7Am3nfMEF6UtQN0FuollQ9KXnDUpRXRiwZn81tUcYkiriWSkooo
FFv3f5KbWDqYABVKhCbZ4mhjPyEg0E6PXU7YULW9nEQbYJTNUsE3NAcaQmDgaTw5b
zgeyl05G0z4Mba2ILtxCa2oG5D06gGM4HIYo12JDzjHLcD1KJZjGeoIo5cV6foEQ0
Q==
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

	protected function get_local_version($local_path) {
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

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://launchermeta.mojang.com/mc/game/version_manifest.json');
		if ($cont === false) throw new Exception("Cannot detect latest available Minecraft version (GET request failed)");
		$json = @json_decode($cont, true);
		if ($json === false) throw new Exception("Cannot detect latest available Minecraft version (JSON invalid data)");
		$version = $json['latest']['release'] ?? null;
		if (!$version) throw new Exception("Cannot detect latest available Minecraft version (JSON does not contain version)");
		return $version;
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue(); // note: can contain wildcards
		$cur_ver = $this->get_local_version($system_dir);

		$new_ver = $this->get_latest_version();

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Minecraft version $cur_ver is the latest available version for your Minecraft for Java server installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Minecraft version $cur_ver is outdated. Newer version is $new_ver for installation at $system_dir", true);
		}
	}
}
