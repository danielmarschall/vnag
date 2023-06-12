<?php /* <ViaThinkSoftSignature>
b7BnMe8qeRUHdsJkqlNsMQYx99h5DOO+Wz1wymeJQ4whzOdN6COeaUVebw+prbhKo
5V4aWanR/7o7sXUic5s8WqyawlrejEOemH7KIHTec6C91T0IXjMOfUBRX4TucEkNi
Ca5Ek4MCjrhk7jHAiTOtEKv3iRWIw1bPEoBwSLMfZ+jNDzhOakXKmCcgpKX47XwiZ
qi+bsAgleBPx8a5Dw74PlMPTKMtAzXq/Qb1g+T+1L3K5kV8l9Cebkoi4GX6KhN+PF
pkIF3c/1342hPlRjYcyE5lrDFelnit6vTTp4oy2ozh4ZkPBysTdFxWfIxRhiE0P59
fuilhqUeyikmk+Q6Z2xbNXZS1OFMYOKEhJo1xa8b38AXys1GGebrQ4A/xYq5xtm2K
Wvh3uW0tKQtTTd0AYt0RoDd8sJ8zFCmMk30NTqPlvZvwQIATc90tjtfGxqHzmYWOC
itzBTMp89hN/xQY3lIWLO8vWxjD+Z9ULCdHiqhEYWgFTvSLLNIfN16oB2qF2kd3Sf
n7Xj/DoMLAI3soU7vxznXcZabrbl4YQXoJQfod4AGzFSHnQJ1mH6/WFRIQsFKZeFN
F3ud2yZFrKn7ee0e1GhieNU4fASa726N9ndxEGO8D9Rsgv8/0eApdV1ZQWbICN0JT
A0XA6NEBKa6n/YYoh7wMrgUXI8DgQSDbqBslIKFgmPyfR7XvQlt2dxItwA5GYGQxs
KJnaJqQfsXjNY6mAguMjK4DgHNqE7ulXSu7uSAkSBIfUR+4EvFXJbwwVy4EWJAXN0
+9xfMMvwnoFYzt0dHyn0Y040KyVWMbx2bmSpkJSgxkUBba0NWosdlM4+wyDFy0FH3
FkCY3GG/PuRJ3aYTTo9P+vPhysOFazBjoTPO8s62lgfgyizGOjVNlVFPe3PGEx2Xr
Xl9cHFbheNCDRZ7wZMxX8fvl+8ipvZznJ+Kl1jjVSZBgDLqoxG08Ej9+gmFdRSlWp
zwBQ/JGD2XU6gIR1Z241NikGNr0AVnB1hZXbBgMWD9ilXXm8dgXle3xcMsBKtP2y9
fRRaBkdbNHuZhtlkAWxQumRtKh1b4jgorEsjgeT3FMNWACygFAQH2/Sb9YSnhbyf3
scECLeyzxgi+S9cRusZj0nyTeepAwRUINkCVmQPjt5loZILc+caAoapLknuwAmKge
d5NnwIBjO6v1nFhxNIw4wyQ0C+f33K96WxhnVRRAosw7q6JIVmy0QQ8DRWa3EtFvu
MzEgPBZx6mKIhP4b2xK1KFOLpeYCz3gQ0DMg71ullD/5DWF8v/3RCFZFr3m76TmIj
3I/Jncg1pz2gUfxk+VxQatkbjXm72BcF/cc5+qJFhJzuYTA/9uLhpqW0sglRSYSQt
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
