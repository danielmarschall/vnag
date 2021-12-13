<?php /* <ViaThinkSoftSignature>
oWlckS6PIRQ0p+i9C84P7Tm9qPWTQUJKoU/t1I+E0ripza58ZyM6QdU3kxSKkswmh
Gfk5BmdJ+8a8Q8mSnywxUcZbsJ6mXtAiaJ9VWIGHWuGAX2CtZrxpUVNDZ2ijoMz5W
rlh3aKEvzZ0V/lUVpZFWjE9MHYg4djoGbHW5Ql11iuDsjLO1YQBp0rAGzKquPBwaH
fIvRjdcpy1zLNBIZb8Pz5BuGD1IwDxxGZWeZP0WrNaiZVEaB21FBh8E3Ujr8AOJ76
WEd2k37tRWh0Yy02n3nLqKitFQYCE1KRmVzlGtH3/4N7X82UnYJR6I0jO7Y+6DjvJ
nhxdL76/EUlTeuXpF2vR7yjaxXW/3KRBALA7mP4Uq8OsAn+jt2zjZd8KNiC2IPnGq
aRl3BfLy+z5CyS6v+kTmSUd+a4o+d9D8y+oG2zEl9fCHvJOWJldtCVbNPztr7CrCh
SMG4kgf0xZtjjj0jPYFoVkYyy7UjnMbQwhrz97f3Sa/QihrnUnfOPrcBD6ebgkYdg
UKSBB3UWrJCUan8kuI4baf4UYrX8iRUitvNfbp69/t8sfSrJnywxsVgTdxKu/jJKQ
7LLVb7ivg5xcFpZp2IFeylK+fr/Z0I+T4X2HV624kTrLAzSz4MeorF5+Z8hwBbh1V
U02NM7HXZ3n9TStjWX+bf11BcOkB/mXXBdm+Pzu2um+xzElWh20vwINcMESLxVC3k
h4dAKMDqVd10DEoK0uLRHPeDeRz0Yc5GapuhfrpWf+mRhKbSYOzuMDzoLTZjt2xPE
lAH4I8CkPXXKB1DbHCnj8pEYPP9ul/GGZYVt5eGqJ3zB3oerqa79ftBFvTehJqpKx
T2lQ6u1r6Y9h/7GtURDFz0pOHqDBNHFvrmNhX8Lw+HRSMYFI6d3GzwdsBtw4Ke4YF
DWR8NvAzjYGIJJOh9RQHvz8saSNRjM9iMJ3HIi/YW1Gtb98a0xLeyzMtSfrFDWXPP
uLpZfbtorONbHbF5+ltauvmsDF++u69s80rItJtoggsw5uRXkj6NOGSN19Pw3pqtC
fF0iJ4/Ir/xcUdzbR7MBL85urAdza/Cim0vhfzTN3SpLjYmf50BrvAU0kAuyKrjHf
FAq+y8PrxiWRSF5dFt+lZvUBuxc2uau6gOzS6V7r/ZJwu/8wOtHguqqZVoiDrkhO4
cj09VQOzG3EqWUidlpiDwWKsDLDzrKYCKKSNZt4UjQ5qV8+WgPdh0XT4XSj5nMwt/
kcBwPCaIeCXMhpktVS8AYSskPaD5RYp8XZjCkiSOI8hFHOKYMl8iaUw7cM6j2YuBn
eHavCPB03FOK3YvsheEfIYYfNez+vsQqgTXOVWzuGtU1oMYL9fi15mRoQW44bbdxM
g==
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

		preg_match_all('@minecraft_server\\.(.+)\\.jar@U', $cont, $m);

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
