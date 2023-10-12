<?php /* <ViaThinkSoftSignature>
amj1ap5xi11PgKFXmnp+OofR+MgQ+MlW0OAGQr8Ue78IyqsrLk8ANMI7lKoU26DW7
ucLqa30BFp5Feb1f7WRH3g6DtmeFydD9JIZyej+DcGbRMEewH8yrqhnxYHxCuDIgR
3MU1Sy8sZnUzOvqsNduecmgKpveLr2MLfPHQgJM3/ormY9wIs/uIWD34ufNElL22k
yrt6u9BrBIJeliOV9mjxgzd/mMyPdYqdNM2M+6na7RjfP69bx4Qa3soVD4K2h0ZZ6
Kjxv0Be6db3rCUTT0vF71PA6OfB8ku8u2kyRRedULGlPMoCTX20B67ioTjIGdygwX
vTblMAhDpCdi2vDgs9FzjYc/GxZnT7i9dab2oDt2JJ3mcoXzHED3DEh2f26t4SfrS
DWdyEzPeiqGQV7hXiiD5HpfgMzvaXWx6bSAug4LmjV+RKKdiJ1DUNAtPtLHTpR6Qo
oMnUNXIi2kjjgKykNv4VcoCmcKe6XqFTrqo4Zr45VzmbRfwTL42xQD1Q9HmL9i9+W
XNRbqbCNJbOy2KPwTtt6Dma9nhc3cwwTmfHum/egCVb0NdKHpiA48C5Cy+G3bYE/5
4RNuW8djJhEAmCMznMrUwxjiYnEFYBtDHzdlMokRruYUux/BEfPp4yy1Jo1lWOTx9
/MVSzUnnJIC/WGvJ+NSgM3I7KXHcZq6sbEPH7TUZ0RF/fIBlOCP8dFqjasiWyFGXe
bDrAeYJDzrQazWnv0R0i+ffUH/ZL2wzq9HdsWvNG2v50swaCgA7ON+mPn5hj/B4bV
BxaYfIH4ib8O2ClgkjwaVSyp+kTkroWQNX7Q0YwQDBYM2NkqzrAV9++hpYkH5MXjM
NDq2PhlIBCorp/+Lr+dCBM+LIffXba4+unPQ5pO48EsNcHZNAPw1o2riUyQLVsCxf
zn2KFLHA+K+8o4z/SW+iSurQSAgW4HcX4kS5etWG+XDn8tvGb2rVY6+CDnoJ4yq1s
Be/M9JYml6etP61CIf9rvLhqFkvstk/ST15DPYgfF6CqHcQleqas+vvX7UXGvQ8x6
nCKMXy7xQmAjF4J6et10PgXdalsGOsMelQVT4LA1tufiPYQbUAMiV1NbG1oKLWa3C
KJuelWtWun/VWkdbYEOR4Ylj7Q2xz5/80wEKIRNatr4DpfrOcTPk6oJG/FRx0ZkVe
hT6P9t0q2pAGT1Ne1KE3AnEKZB1kyeuF/wtQ9uGso8/LS2XH2oF0oZ5XS91Jawshr
nCZ+H95Yx2woaIB+q5oacBlh9YVvzZ0J5cdnS+C2r63yCdJbQBd7WAu+ngFADt6TJ
VsjrRrrm4/sIw9netkDZLPE8WHjsGe/b7B0AC7WdYUAdYTBgWlcv6c6/dVAgZobB+
w==
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

class NoccVersionCheck extends VNag {
	protected $argSystemDir = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vht');

		$this->getHelpManager()->setPluginName('check_nocc_version');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks if a local NOCC Webmail system has the latest version installed.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-d <directory>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// Individual (non-standard) arguments:
		$this->addExpectedArgument($this->argSystemDir = new VNagArgument('d', 'directory', VNagArgument::VALUE_REQUIRED, 'noccPath', 'The local directory where your NOCC installation is located.'));
	}

	protected function get_local_version($path) {
		$path = realpath($path) === false ? $path : realpath($path);

		$cont = @file_get_contents("$path/common.php");

		if (!preg_match('@\$conf\->nocc_version = \'(.+)\';@ismU', $cont, $m)) {
			throw new Exception("Cannot find version information at $path");
		}

		return $m[1];
	}

	protected function get_latest_version() {
		$cont = $this->url_get_contents('http://nocc.sourceforge.net/download/?lang=en');
		if ($cont === false) {
			throw new Exception("Cannot access website with latest version");
		}

		if (!preg_match('@Download the current NOCC version <strong>(.+)</strong>@ismU', $cont, $m)) {
			if (!preg_match('@/nocc\-(.+)\.tar\.gz"@ismU', $cont, $m)) {
				throw new Exception("Cannot find version information on the website");
			}
		}

		return trim($m[1]);
	}

	protected function cbRun($optional_args=array()) {
		$system_dir = $this->argSystemDir->getValue();
		if (empty($system_dir)) {
			throw new Exception("Please specify the directory of the NOCC installation.");
		}
		$system_dir = realpath($system_dir) === false ? $system_dir : realpath($system_dir);

		if (!is_dir($system_dir)) {
			throw new Exception('Directory "'.$system_dir.'" not found.');
		}

		$version = $this->get_local_version($system_dir);

		$latest_version = $this->get_latest_version();

		if (version_compare($version,$latest_version) >= 0) {
			$this->setStatus(VNag::STATUS_OK);
			$this->setHeadline("Version $version at $system_dir", true);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
			$this->setHeadline("Version $version is outdated (Latest version is $latest_version) at $system_dir", true);
		}
	}
}
