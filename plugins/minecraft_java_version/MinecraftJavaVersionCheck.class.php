<?php /* <ViaThinkSoftSignature>
AGm/a5N2RKsITjiz0KnYh35+TQ7ya4jusXkbmTSo9tutK49ouGx05yBncsc3kC92Z
VU1rk2Nau7wWDRL942pupi1q/hSDkQjslkvp4j4P093stHeI8QnGAYSyn3F4DJcRi
AEemHB3cGDkWVR0AK4kW4b9VzD6r1id93T9aCG7eWFWMd4wWE7z/wqVr2cOQrPkrr
ka7tChyplVFrtq7qeekxSvfWqNu1/lQBXFhVyH85J7ttJrggOvD0WKNCGUCpJm+Lq
MqbLjBEGa8I5g334BADhJI572aUjhLxBwqh9oL5UDFHLuzFecHbH6PO1EoUfX6716
eQoX00kWsZi99L7xbRKWKw8Cx+Kf96/Q8aJf31w5wjBi5X/rwODnAXIjxIIZXUH8r
a6TY2RSmrKlKfxdzmKVlEP6mCPSz1CZQhCJq80/pv8fmSIE9yyIpSMc2acUn62vWm
ICrDZd/j19o798vHxDZiKurJn+B69yzFdWuR5mpS2M4chXoVeGVofSOJqzRNwgeX1
r/LUYE1P5Vsy5t2CpzEg2ibcHP+0VNcA6zXvdIN0Cie9Yq6c6X5/nJKDajVjXKBJK
ytXN7ANThK7FC9SASZZ1aQSKsuj6NNccNp0pWYRuPlidALr9q0+G/8Ea6VYiDNFwp
Wr0Hk/xGlWBT4n7fGZKwN7ZPd0vwzSWdGtwBLjPWAN9Iu+qzQutAhcJ5LVWbdBsKE
xQY2C2GDPWkmX5rmETlQ7eaJLrbYqGE+tKTH9nDmExMkecVM+KHbqMhkCS3Fh3ygz
p+KSgCUB4xkm4KTeLMlb+xoD6G+JsapR3GJ2Ejgn57Vc5ehU49BeI+Sv8X230Cu9T
C2h/81XipPdrJ7uXkDx2rTBWjQ4wwEQweCFiVUnXQbAGHjkJqtj823Itzi/Si+1Jt
vOP9L2ByepU82pM3O0arnSJwkJG4LLs2OIYLw0UD6K9rwRPzBM4fODHgIbXmZeH37
x3m7f3DzuLSLlxqIXNIcP8t5AXpe4YlWs8yczickR2oAqx4TJbaMVhu/SpVm34ot9
9ryzDnEyfRAIH7cXby0wNaLhHrfGd+mlR1Bgd6bSdd3W9nSLkwqbpVYpC4bB/HgIl
MY0A4x/nikcHjCh5hpgdRb0AkZsc5K4m5AyWnPloS7/cVytQ/g4DfDSobteOdOkhU
o9lTyfyDsmAxuxA1JNqVLOYZfcwrtgPBX/V/WphAmrgZGsEwS4PGlGfaKEMMkxxI7
1zcmxYoNQgVpS2GmET6UOYcRP6FCjNMhp4pSaaQ27GejA5Mc7d0VgkbaNLFW8KaiO
xTK8JurMkIVeWqLY990VRuJCxk0ncOJ1xqpFXtLXWeeTzallHU3R3UoN5MRuhLsqE
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-12-13
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
		// curl 'https://www.minecraft.net/en-us/download/server' \
		//      -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36' \
		//      -H 'accept-language: de-DE,de;q=0.9,en-DE;q=0.8,en;q=0.7,en-US;q=0.6' \
		//      --compressed
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

		// TODO: also check/show if stable or not
		if ($cur_ver === $new_ver) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Minecraft version $cur_ver is the latest available version for your Minecraft for Java server installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Minecraft version $cur_ver is outdated. Newer version is $new_ver for installation at $system_dir", true);
		}
	}
}
