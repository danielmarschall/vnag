<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-13
 */

declare(ticks=1);

class VNagAastra430VoiceMail extends VNag {
	protected $argFtpHostname = null;
	protected $argFtpUsername = null;
	protected $argFtpPassword = null;

	const DEFAULT_USERNAME = 'admin';
	const DEFAULT_PASSWORD = '33aastra'; // https://productdocuments.mitel.com/doc_finder/DocFinder/syd-0343_de.pdf?get&DNR=syd-0343?get&DNR=syd-0343 , Page 178

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vht');
		}
		$this->addExpectedArgument($this->argFtpHostname = new VNagArgument('H', 'ftphostname', VNagArgument::VALUE_REQUIRED, 'ftphostname', 'The FTP hostname', null));
		$this->addExpectedArgument($this->argFtpUsername = new VNagArgument('u', 'ftpusername', VNagArgument::VALUE_REQUIRED, 'ftpusername', 'The FTP username (usually "'.self::DEFAULT_USERNAME.'")', self::DEFAULT_USERNAME));
		$this->addExpectedArgument($this->argFtpPassword = new VNagArgument('p', 'ftppassword', VNagArgument::VALUE_REQUIRED, 'ftppassword', 'The FTP password (default "'.self::DEFAULT_PASSWORD.'")', self::DEFAULT_PASSWORD));

		$this->getHelpManager()->setPluginName('vnag_aastra_430_voicemail');
		$this->getHelpManager()->setVersion('2023-10-13');
		$this->getHelpManager()->setShortDescription('This plugin checks for voicemail messages on a Aastra 430.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ -H <ftphost> -u <ftpusername> -p <ftppassword>');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
	}

	protected static function aastra430NumVm($ftp_host, $user, $password) {
		if (!($conn = @ftp_connect($ftp_host))) throw new VNagException('FTP connect failed');
		if (!@ftp_login($conn, $user, $password)) throw new VNagException('FTP login failed');

		// TODO: Only count vm*.dat
		// TODO: Also check timestamp of oldest and newest message!

		$messages = @ftp_nlist($conn, '/home/voice/vm/gen'); // the files are named vm*.dat
		if (!is_array($messages)) throw new VNagException('FTP folder not found!');
		$count = count($messages);
		@ftp_close($conn);
		return $count;
	}

	protected function cbRun() {
		$this->argFtpHostname->require();
		// $this->argFtpUsername->require();
		// $this->argFtpPassword->require();

		$ftp_hostname = $this->argFtpHostname->getValue();
		$ftp_username = $this->argFtpUsername->getValue();
		$ftp_password = $this->argFtpPassword->getValue();

		$count = self::aastra430NumVm($ftp_hostname, $ftp_username, $ftp_password);

		$this->setStatus("$count new voicemail messsages", true);
		if ($count == 0) {
			$this->setStatus(VNag::STATUS_OK);
		} else {
			$this->setStatus(VNag::STATUS_WARNING);
		}
	}
}
