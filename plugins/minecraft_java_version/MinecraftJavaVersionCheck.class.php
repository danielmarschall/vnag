<?php /* <ViaThinkSoftSignature>
2fWiqNt7gMw7rboV5l5dvB/ceP6jneWqHFYRNvge/NXbEoRDqossYdeNkXUGOZ1oQ
PwqVwK/kkKISa1PTYeK33yJiiH8hBBc4LOTh4p08AEh2gBCrsS3J/Jk8+oiqg5yFi
atnTF13ri4URJQTxC7AUNw3yDgWblNFaSSzMDIteljnDvMMb3EBCbeM87RTOxd8hw
HqfcDqkgSRfBwDYi4NyNDSrk2ZZDkMlgH7XakxnP3GqsZgkujBVKYvhdLg86xu+1K
qXBqfoQFhZBmL0l9iAbsFrLo0F+T6QsqsfVVyLZkuNJAGk0nRJudhaO+WwQmUealB
ex4VsL3r18tyMri5fwft87GY1fLKjL2t3ABm03TczfGSMRQBNPdDMDziAbwBt7Vjy
2pnW42l4jv6gsM6FEfU0LcsfXZiZvEVOqWf4RaoyUj8KjsfZTLKxkDql3qB7NEpDr
pDasGsKk5VfBS73zqVuBaNt425atLVhqQM4ufTyZTBr+rfcTJzxraMAH4NDzilXkP
hxGgDpG8VcYDBohmoOD1MqjNXESHp915u4y+/41lNmPAk8wKN5OXzz2WgT/8JjvP/
XBbpw03O+R95kTkNA6yhCDOs6FeO6962N1tmeYMp/+bw2CzPLtlUx4/vnLQk/A3Kd
bXGlF2vKXgPLJckehzhU7Il4xbXbOL56R7DGLypuBcYTqRql/LlYKZwCctnboD0k9
VsZK/kP6Lp5ra66qKSrg3vT84dJs51mLBS4pUiFZ3mpcyDUhAbYYH0gGWVU/+FvJv
uIfBialrJXttcAZ+ntXF21j+m/hVSaUocyvOQ82DUgXmRrFTvqXA19c5EI3Kg5yPE
UL6td9X1vmUjDTXDMidWSrgbU1bLUc7WRRxPO2OVKhvAW5SUQgD144OQCnHny5NfG
Hv8Pn6e9c5INrM0PToqBP0co2GrSjCBJj2H8oHZrpUnOyIKKLFBBajxRBZuEVySC/
AnKaSpJ/GgeBjQj8n+QzO+YTROnD0HPmhsPKFAXyQKp0AMGTeKEjvQ/BMY366+gnD
tNhV5bW/Jg9HfmFCCc9HFl49mRODgfY/RPgXomkXXJoznI35gzuoEJ6oYa0l5/3x+
CPQVEwQD27aZ16THSuo7B15PDqlDqFK6oBEIIQo0wiKvG3ZKJ4o+/8dEFpuL7iojN
DF6090cihGns5gccj5uwrBkqswwkZjGb9Q3b7L5kivXuuUDXkYF98EbndhDAhMCxZ
kLVD0Wuc87Jv9C5sb9jSv+KVPbcp6PtIGuYiKFv1QTaEkpiTHDfgxRf10DGo1Ferw
tyc9Ot1W9I5Grq0bFQVenj/si5x+CmjONBOCnByNt8aRmhl8jp88LcDnyV5FwCLYE
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
		$cont = @file_get_contents('https://launchermeta.mojang.com/mc/game/version_manifest.json');
		if (!$cont) throw new Exception("Cannot detect latest available Minecraft version (HTTPS request failed)");
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
