<?php /* <ViaThinkSoftSignature>
xIodU7lBP0sOx3e6CFtClkfp/bVAPoqGDfH9rytWnT4tezmk0METjFW5UfkfEhftq
60M3zCwBU7WMH+RrYyyzTLpbya28xsj7VQTnI4QTNfawMGEUrHBRtRtG/Eq/B2HLn
sJ/ZpLwpipTVAnt9MWsKmkz5I/7BfOJwjrH2BlLTDxmHk9SoelvAyR29n2kmfsRty
J5miiW628U/lKRI2N53K22DFKBYVwvN8YQzhbfZrwnyNzrfOdvfGZ6DINe/CtjYGi
+u06hJF0q/hceMw3zln3lKNz2bhW9XJ2K8CGtimj1OVjZlhc+Pw9RF46X/SDeaz5Q
nlNoAde16v9GP2cwTEXjnicMR3g7PJdQjiHxj/fTCg3C1BIUQb7kJXsYKe9HlYijD
ZQGDMout6gg1oc34ZCwWttYjo54FlB6t/BKdvZCgPjKqC6DcP0JE/29a+Nb3XhYV7
+juV3nnDAGv9vHtRgZoBlTuLiSRKurhECMhnQO4IAHxSC7pdUiglEG/LJ7CMEyh5c
VJQkq9LyVC8koExrzGiMUBCXh8QC64UWL5LTX/diSn3AyLXX6ZNLXBtV2mmHwGAl/
d+H8Sw8IganZPfNwFcKT2hvj05GalidbNgFMXqOkJBGT7gOrVux6jmUfFuOzeXjHG
qNpPF6e4D3SDv7iCsEOKmM1ueWeawFLnz2tub3NINgRqt91GAULsA53N2tv7qd3ek
1ko5NLT2NFIxkcz0BlDSyhhMu8etDQBQtY+D4g7z879LLREgdrw58MAAhrk/f5np7
+eXVh4qTuGIsJyYoXwCzgDZ9RcZmXfDZ7x9CLJTgpp5YhRzutYiqrgxw3Nkn3R3Jk
9GpVuaRymb0fz/FT/km0rsqKChSaazTeCUxhZF0+vAbLDHurkw5G34IkbypJHdbKp
ny+/83ktrxwLG+2ICHh4WLFDKWmbwepPePqH8fqKy+oi+7xsUJB0Z1joasD9WYxme
SoTWBC9js7r9KpXnuvD6DaTHoYaowLZlo44guo0O++6gFT/Y1Cv6YXqNbNJeOQ8Nc
yxVYXzdTeKO4lixezSI/K8/OeeHEyLhjHMb4kUcEOCuXFzQcO9jPd9pucOZVdwSvB
M2pDAOcrhFWiSFQiwx826zEyY5J+MwTuewOJrJKXNIaMxzu55ZTPKy8qfPma9jvrY
fwDy9ZnDvb7XKHgJIdBdrPfq7fkPVvd2pYYozBvQaYDdrZvapixhy4300Iqx8fup6
pW8xfN+HvKLUWt4NFWojvmIpSp5egb8DGRzBChgLVktI+2Q64cXGXOrfhR1sIK2me
XMs33vPVTK5t1obSksMDCf/9dJmjRQ+XtS0+D2AsWdkD2MUJhoHLJE6spl+h5dQ+7
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

class GitLabVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_gitlab_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local GitLab system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'gitLabPath', 'The local directory where your GitLab installation (version-manifest.txt) is located.'));
	}

	protected function getInstalledVersion($dir) {
		$version_manifest = $dir.'/version-manifest.json';

		if (!file_exists($version_manifest)) {
			throw new Exception('This is not a valid GitLab installation in "'.$dir.'" (version-manifest.json is missing).');
		}

		$json = json_decode(file_get_contents($version_manifest),true);
		return $json['build_version'];
	}

	protected function getServerResponse($version) {
		$opts = array(
		  'http'=>array(
		    'method'=>"GET",
		    'header'=>"Referer: http://example.org/\r\n" // Important!!!
		  )
		);
		$context = stream_context_create($opts);
		$url = "https://version.gitlab.com/check.svg?gitlab_info=".
		       urlencode(base64_encode('{"version":"'.$version.'"}'));
		$cont = $this->url_get_contents($url, 1*60*60, $context);

		if ($cont === false) {
			throw new Exception('Cannot query version.gitlab.com for version check (Version '.$version.')');
		}

		if (!preg_match('@>([^<]+)</text>@ismU', $cont, $m)) {
			throw new Exception('Server version.gitlab.com sent an unexpected reply (Version '.$version.')');
		}

		return $m[1];
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the GitLab installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$cur_ver = $this->getInstalledVersion($system_dir);
		$status = $this->getServerResponse($cur_ver);

		if ($status == 'up-to-date') {
			$this->setStatus(VNag::STATUS_OK);
		} else if ($status == 'update available') {
			$this->setStatus(VNag::STATUS_WARNING);
		} else if ($status == 'update asap') {
			$this->setStatus(VNag::STATUS_CRITICAL);
		} else {
			$this->setStatus(VNag::STATUS_UNKNOWN);
		}
		$this->setHeadline("GitLab currently installed version $cur_ver [$status] at $system_dir", true);
	}
}
