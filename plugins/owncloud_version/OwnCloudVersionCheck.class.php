<?php /* <ViaThinkSoftSignature>
HlSVetfv0XKCBAHe5nbDalbR8T1VTGwFp5Sso+NV+Ai6FGDOxUZ5V74bkZm6zjj0l
geBvlNO5x4g5a8VBt1g5p0drOul4hbXTSX8GtOzNGLWrglTmsF3RHDWUMMKOI4EMq
lqi7fUsppvUJ61PnR+XFIx5zNomQ3FNI3NNPnfHiN4O5c0Rz6WzEF5TVT2F7DPlDo
ZbEdmo7cXZuSJG0fGpD6Qelkq2eKNxkE0DyNJrLAcEnntvuTYpoc06QYiNJk+OIED
kd5i0KB9poE5BORsGMhVMwWK2s4ajE/TqZ9fGACbyk+lbinTMJ2VAGzR4KTSThJfb
KbavleaX9ITi9jbBVxGorDiFY2Ly8BvJ0gaJmSXwjUDSUsLdJuMruNVULjhoGgzAs
lXGu6RZ0GvuPly6jBkXGqdguqrjBvczkGLc+SQM1TkxafWK3rjnVlL3NcAVeNp1mm
+F+UawOvT1nBNXsAodiv8G5Vt75t9/KxPZ/9pSfoui2efNodb+WWUVM5y2/2bSuWR
7jSuquWKooOdHa3KIppD4RDhV2q2eq8AQhP2d+KihlJCoPMB3BZgmut++BBT3K4LN
vp084TEGxwi4vJmFXBuHMzLBG5I0RFaAdtiO6MFuJLfYx2Y1A+rP5C0dAlnqQSD45
KF/ibeJM0FP+9XxT4OhGxehwbKTm9waBBqc2k7d3h45XTFZ3ygP9UmffI1Nol9N2g
kWFAdelxZxiMX/AOl+kGm8lv1oCJbpdFWle6YSunl7PUV38llVeFMcNsTf6rM+ZdG
c2O3bHvcVt39tRBS2eGDwg90dxCMUwVu4k8DbmU0ga5FyjfvEZLDDceYFR9xgu3TT
p+62MtDaxkPIku4XinpH95FbF/m/ZETBM1r1wMa8+C7qp6exBmrKas1e7cy482Sx8
MoD6prrA35xL3dUnr7dZJwtVS68Znpb1hy/adYKNCOnzidZYsK2e+BcnY9IMaI3aX
4B6ZNoJTRVxFKTkgNx6uZC22HVwcp/EtOW0SAHUGax7ICDY7f2FhUnTOXh3pU3KBW
9h0MgnzC4jjE96F1ROUw0gGT8wA+a6yugaPGjaKVVWWJiyEikHofPt3YkEVZUPOV0
q7I0kgW0PqqqzzlMu4rJicTWEWXofYAI6j4zuTZ9XrHNPp0vw+f5stt/zYqAJ609h
VhL+iYaqO2r+3p+YMDLzL7WQCQA2VDNpzV9XVrQEAPLYkSgS9ZFZ/o8TgjgVpGnv/
AfEwJned7Te2NDzlIAcg4UoEk3NL8sCcIyPvwxf2zbzceqEAlnfryacxS3Tt8vrde
F31XxnfmY43Tzzoa0I0P5xPRAhmcwnraY/emXTAcoAYmkJpO1fp7mqDpDPHHbydtv
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

class OwnCloudVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vvht');

		$this->getHelpManager()->setPluginName('check_owncloud_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local ownCloud system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'ownCloudPath', 'The local directory where your ownCloud installation is located.'));
	}

	protected function get_versions($local_path) {
		$local_path = realpath($local_path) === false ? $local_path : realpath($local_path);

		if (!file_exists($local_path . '/version.php')) {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'".');
		}

		// We don't include version.php because it would be a security vulnerability
		// code injection if somebody controls version.php
		$cont = file_get_contents($local_path . '/version.php');
		if (preg_match('@\\$(OC_Version)\\s*=\\s*array\\(\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*,\\s*(.+)\\s*\\)\\s*;@ismU', $cont, $m)) {
			$OC_Version = array($m[2],$m[3],$m[4],$m[5]);
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Version".');
		}
		if (preg_match('@\\$(OC_VersionString)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_VersionString = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_VersionString".');
		}
		if (preg_match('@\\$(OC_Edition)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Edition = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Edition".');
		}
		if (preg_match('@\\$(OC_Channel)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Channel = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Channel".');
		}
		if (preg_match('@\\$(OC_Build)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$OC_Build = $m[3];
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "OC_Build".');
		}
		if (preg_match('@\\$(vendor)\\s*=\\s*([\'"])(.*)\\2\\s*;@ismU', $cont, $m)) {
			$vendor = $m[3];
			if ($vendor != 'owncloud') {
				throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". It is "'.$vendor.'".');
			}
		} else {
			throw new Exception('This is not a valid ownCloud installation in "'.$local_path.'". Missing "vendor".');
		}

		// Owncloud\Updater\Utils\Fetcher::DEFAULT_BASE_URL
		$baseUrl = 'https://updates.owncloud.com/server/';

		$update_url = $baseUrl . '?version='.
		              implode('x', $OC_Version).'x'.
		              'installedatx'.
		              'lastupdatedatx'.
		              $OC_Channel.'x'.
		              $OC_Edition.'x'.
		              urlencode($OC_Build);

		$cont = $this->url_get_contents($update_url);
		if ($cont === false) {
			throw new Exception('Could not determinate current ownCloud version in "'.$local_path.'". A');
		}

		if ($cont === '') {
			return array($OC_VersionString, $OC_VersionString, $OC_Channel);
		} else {
			$xml = simplexml_load_string($cont);
			if ($xml === false) {
				throw new Exception('Could not determinate current ownCloud version in "'.$local_path.'". B');
			}
			$new_ver = (string)$xml->version;
			return array($OC_VersionString, $new_ver, $OC_Channel);
		}
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the ownCloud installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		list($cur_ver, $new_ver, $channel) = $this->get_versions($system_dir);

		if (version_compare($cur_ver,$new_ver) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is the latest available version for your ownCloud installation at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("ownCloud version $cur_ver [$channel] is outdated. Newer version is $new_ver [$channel] for installation at $system_dir", true);
		}
	}
}
