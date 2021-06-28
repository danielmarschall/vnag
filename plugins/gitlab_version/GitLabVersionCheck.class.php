<?php /* <ViaThinkSoftSignature>
E+xbKfuMVMLiT8y/mLDk8A3rdE2o80wkrCmgs6SHcPS5crNSDvmYwJ1yoP3P0m3M/
M6ig8GrCSCUos4ldzwL6thqs1dRl3hnhIEFso2eCm34ZrUwhBDHAyw8MVpFqx8iB0
SXuhdTF85EWsZJ5ARDtS6t7JXgpyVMMEggw2ufcL36+mYjOPoiG8p0Zu0tUEL+WZe
Qvvi46GV/kcjnEA0MB0hpE8rT9dcaDVQisyLBarmlDp3Bld9L+sQ7TzYBErxmX6c3
eghZd3jHFYoiILT9Ls4PL2iTysPT7vddHK9ZMyeyF5mKSyTRPSYq4oZVg0wlcExw7
JsQQBtjRmMUECVuYHbwbusBEv6fbrzQieDVBPKYbU8eiEY2+WO1ZDasb1uPkZgHar
zMz7AH/JnfByP8UZInB1YmnXIxWrD34OsJuBSDLJnvv810LVGxddRZn7Z3UaHPw34
YZHYh7mOS9YvzDZaD27nc4UWSOcd+Y2zettnx7MLhbhFFSvLoWdDQul+ejBvsGTFN
qSZqTNN4u/RGl4mfdtuwlXPnS4YjlzdgYZpPLt2b5aHynhQQY3IN+SWr1S+Guilts
64gyupnWmzo8q6VAVa1eqe2N0Vxa+/7Co3EHQPa57X0gKuKrOD5EWboTeYkDm0f5t
h4+B3PgvZjmgWQd4EPVKUh2ijM1NVOpT4SrLfKvLu+WpATAel+0hgnl7cfUH3mjQe
OT8z0J3n/AyzcSJQ7QiqVNs7ngDN7HRp5Wq0FHeD5Jge7POyfXaa7FjMMikPXXHPE
DUFdcazsDRKmrYGzXmvDrwYt0zJhKHIwb0tJ5Lull1ZHgU3FQXmje9Mxr1QvTG2T6
5WJHgr9P9nC7IzH+zpD4AFoaizfE7YiFXYotKfmc4X1ODoCTBa1zvSw8e1Sajd5jQ
x3OaENuchUDU32I+Ex9+fL4y7DUVZdfXzF0RoWBP7vfX6J4d75Xtp040zrt3YrvUD
xqX8trBOLz4TmKOFDmf73idFqQfIJL8NEgvAoosOIZr6W+C9nRy+RN0CVguOyREwM
S9EQ0TTzDl8GJOHkNqFRFW5EFunwMQCoAzv93BslQ5wPGdv5HVccXu9d7hnuwwcF0
M7IMEOUchns6+4GvzK8N8zKhiIMwAEWvFk0KA0u1zlMCjG8wQIFdogho34KuMvUXU
XEAaK+mL5LPW/+cdO9ZDT4FoCl6KjW210neRizzoiYJVN4mx4N7aS15OPWiMLO6f0
n/FkWXLSFHGLxWKkvRmwyNAlDjddFOs8vpGgdsaaY3xQqKWg68mBD/qGkPjYt6M61
FNXtEsWOYErBCrCXDxtgBEzGy1GK6LTwavMto8ln7h5r1VP9JVSJyoZXFQwChKCGU
g==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2021-06-28
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
		    'header'=>"Referer: http://example.org/\r\n" // Important
		  )
		);
		$context = stream_context_create($opts);
		$url = "https://version.gitlab.com/check.svg?gitlab_info=".
		       urlencode(base64_encode('{"version":"'.$version.'"}'));
		$file = file_get_contents($url, false, $context);

		if (!$file) {
			throw new Exception('Cannot query version.gitlab.com for version check (Version '.$version.')');
		}

		if (!preg_match('@>([^<]+)</text>@ismU', $file, $m)) {
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
		} else {
			$this->setStatus(VNag::STATUS_UNKNOWN);
		}
		$this->setHeadline("GitLab version $cur_ver [$status] at $system_dir", true);
	}
}
