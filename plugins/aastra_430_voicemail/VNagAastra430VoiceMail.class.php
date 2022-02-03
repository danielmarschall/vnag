<?php /* <ViaThinkSoftSignature>
DoLNft7Sdu3ePi+nSXN2sn1rZfmAxotlrnJdcyD/sLEQIXP9r0TYkhkPeQhns9gAy
A8gheT9CYyZ89Wa9eAAanbapthrk6rFk/A3xKxVVGe1ieqlEefmkPPs98eMkLlAyw
HpDFLR9aplZH714N2QqHazvVM2tl5nu/POgSJoPcAIlAyj0O70zTUuh5muRA1kJRF
sp0E7dEulwOYLb2+UBiKhzmcIe1ixJvVsJzrroK6LHwGvUmES54EVfRu0hVkkrUBv
j5LF0uDTWLeDudPbJK+oldLM6KRA06EBSkKQZxfTPMpDkEk3I76rojAnjm8Cp9/bI
G5uyU1YrEBJOpo8tIJWqI8GFs6dRDVVQXnLNz2TZHDSeuL2Ij5LCBk3RnUDSzzFni
/YAcu9HkM6706NylZJ0IR1z3GmUAlRnGN/sGaophcvpzPm+RAhOpr3H7N2/IxXPdl
VUsL5Q95LmBFCXPDRIqQmhhqDS+HO6aoYrxu8pEvd3JdQjh616XLWQDeHHtl1x6cE
SszyAzeVpCSyEyNzk7Bn9ypIZqO+/pauLraS1xL3Xt/xCGQvzYZAVjtoln2y/nbqO
vKBBZdXRXXCOExCbZB/u8sp/Wc0vua/qWoONWx+YtDjySUXdEs/JF0ZGEVndRym0r
4TfM7GJposp+kz/NKbQ1Kh+UnSMk6L5y/I4xMWAuBTCpQ9zl0yDpFvLzeOvsH3Etr
WeACWabnH6GVXWlI/JyAVThHWHrog+no7/OSIymRBHgRwsRu/87qW1aZVB2H05jW9
PlnCBNF7nz3XgDaBac1W02YeHc9hYv9uOjeMX9nEI7W3gDoO/Mn/PrTaEvB0UO50k
/ZHh7k5nQqZKq20kmfgEbII6Rtn9pUZNAmKEpwEMV9Hg3gRpNrvG+ZCw48ts1+P/T
6tsshOByw4o3gRa88tr/LSlGR47EzAdYotq1ZleoWYt5Nc2kJmM29xOc5PVLP5mrY
QecXl7IL35JFzyazi0z/QX4e2VyI/llLcMI767Oq1dckhXlf1YpE0yG3O4bRzDvf3
tH2aWeuhREviasvFK5UUQAc0mWmFuklB35i/v4x+/63sB3zUjHitqI/L+mpjxHTtz
km6KpaBKFQ0UeYnH+zuX9ttjDi8yxTxp7i73ZU0KcOD7KT6aTztSyKTwvyfyVzi7A
81q88rMWDPioVHW/XE32/T2VDuD4RuhC30T2bKQtMPqGcSs0xnNlGGaGjPKkxEVxQ
K6CIAO1fi4B9FH7R/GqY+nbJ6I3eazIE294xbKTp+5DbR6hJ0rC8f7UL9mkyyHeHW
3BQiZndJkv0akOtd+CaWii1bNucrPuAdVm6icwk1WPOorjdZoAgZSRSFIUf/05bRT
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-01-12
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
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks for voicemail messages on a Aastra 430.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ -H <ftphost> -u <ftpusername> -p <ftppassword>');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
	}

	protected static function aastra430NumVm($ftp_host, $user, $password) {
		if (!($conn = @ftp_connect($ftp_host))) throw new Exception('FTP connect failed');
		if (!@ftp_login($conn, $user, $password)) throw new Exception('FTP login failed');

		// TODO: Only count vm*.dat
		// TODO: Also check timestamp of oldest and newest message!

		$messages = @ftp_nlist($conn, '/home/voice/vm/gen'); // the files are named vm*.dat
		if (!is_array($messages)) throw new Exception('FTP folder not found!');
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
