<?php /* <ViaThinkSoftSignature>
UW2wHYguBI0V/FDoEIL9OPSFCBTIDfdJAKlietlbUOy09dhIqhsWaXTn3jtd2Bh0O
KfHPU3JvgNNpkYUCIAatf1V6DMb4L2yyYA++Dla8708PriOen8QwcWEEKCjWbBong
8fBvHbGyJh8if8uiJDOnfbcYEiKxtMQ2N+xdkndhIudbYOj/Tz4eNR5wZ7176aV6d
Kin7ZahX4w/IL/TSBqMYi3M35QK+9GFJ15c77Fc+PLoCfss0qaRNgcHgDPUNxPQPk
c7CCHUwO1j/GIJ2gmd2uHsp8vvUhdSUmCloyk+ea/WUIBs4C4nr68EBDu05zTeCtz
ED9WthnwqLdemP56dlerzS+VhdHyYKTs6xDwOtCaueibBJLdnNPVTjSwWfMIPGan/
uiD//WKA11RFFMI8GtmQ/Fivd0qcHl4dgXPup6oVUssaKyzHarggZKkPs+0aENHtO
smXcznxb+sdtZAGwwqoc9Ga9jUrfbNJIqa9kxOn+6aUvUxHw88o5mlbfNhv1LpgL7
LbVigNSmVh5MCMKiajndozFXVuNlOaTyMyMNDTgSZv2z7Msg7k3vtUd7Clr8hO1VF
VrW5pZiZijqaen/Llv3FJXtftf6mJtCDHLOPn6E5c4M5FQthN3gzxlW/ticIQPfWk
Ie3Cv23UGin065x84Yj8Mlb7GZJc9jyt3FdEPS8RK/8GXAYpSjk+/1PHv/YF+Hp8r
z1HdDcRr07vrT1bRog6L0LBgqub1HaznRxmzdvt/MeZ8STUqGK9GBSx2qBLkEnM+x
F8C/tJxrLytmDAM+Mnc6uA3tj3ntdlA96k+Is5rrnG8JKQv3JOnoGUZ+niy74rNyd
98v6uuFqFAwLOjYtjkWZu3heiVGX/BT4WWqB+5szJM+qQr149OmnKZ6omLwEK4LJX
HrXNyXPx+0DNiNbEb7Hbf97kxXzwU07hYg+Yd7LQe41MMmytiL8u+cuAU5ND2lI2V
3qOO8ps6A8ZiGYERhAATKOlPP/kdwp5VqYqbFytavaTqLKqGRAwpiRfTM78zEy0v5
PW2RDyBPY4HzcblXfDVZkAMz2LWnTgvM9uUd+eI+xULRH47U4G60xN6DzSi6u5wrm
FcYqesfnWM5gsVjj8aGSDq3xmSILgjUH2aH9naRsLLGqTUpefRWzMIkBggMETtAk+
IlUqaWwV2baFESGfebhIfwLziBm9Ikd248RelD5o3MAqfbuiZOVFMp35IYvze2fly
6yqauVfJxRxYZNdbwJrkGlEh29By8CzX4yBcEhuefwa1il+SXmTbjuPTKyuhRo/TJ
mkfzMLjOwfnT1+/0p+ZJxVahMYT6ts4U0wVopjS5Zes+8q9Ae5qYJkHdNG18Ogg4j
g==
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
			$files = glob($local_path.'/'.'*');
			$files = preg_grep('/server\.jar$/i', $files); // case insensitive, for Windows
			if ($files === false) $files = [];
			$files = array_reverse(array_reverse($files, false), false); // make that array keys start with 0 again.
		}

		if (count($files) == 0) throw new VNagException("No server.jar found at $local_path");
		if (count($files) > 1) throw new VNagException("More than one server.jar found at $local_path");
		$server_jar = $files[0];



		$cache_id = 'MinecraftJavaVersionCheck:server.jar('.filemtime($server_jar).'/'.filesize($server_jar).'):version.json';
		$cache_file = $this->get_cache_dir().'/'.hash('sha256',$cache_id);
		if (file_exists($cache_file)) {
			$cont = @file_get_contents($cache_file);
		} else {
			$cont = false;
		}

		if ($cont === false) {
			$cmd = "unzip -p ".escapeshellarg($server_jar)." version.json";

			$out = array();
			$ec = -1;
			exec($cmd, $out, $ec);
			if ($ec != 0) throw new VNagException("Cannot unzip version.json");

			$cont = implode("\n",$out);

			assert(@json_decode($cont,true) !== false);

			@file_put_contents($cache_file, $cont);
		}

		$json = @json_decode($cont,true);
		if ($json === false) throw new VNagException("version.json has invalid JSON data");
		return (string)$json['name'];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('https://launchermeta.mojang.com/mc/game/version_manifest.json');
		if ($cont === false) throw new VNagException("Cannot detect latest available Minecraft version (GET request failed)");
		$json = @json_decode($cont, true);
		if ($json === false) throw new VNagException("Cannot detect latest available Minecraft version (JSON invalid data)");
		$version = $json['latest']['release'] ?? null;
		if (!$version) throw new VNagException("Cannot detect latest available Minecraft version (JSON does not contain version)");
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
