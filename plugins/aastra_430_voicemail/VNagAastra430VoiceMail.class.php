<?php /* <ViaThinkSoftSignature>
0EnQNRDWzFcXwaftAzmUPaGYZNHpSmfRPYwDayJPRlOuYDZhpLM2vJaz6bo1nnpkm
3KNtIWATaSPJrVk02QBZhNHdk7lbB2O0+kjLIy19o7AcCRxDgQY9CZ5ZicZwYkLwz
8Bbs12z4c5wHuYFBe0w9eJeTGwAzzyNT6jF0B9dSV5x/ncml6zH6WXXUjCZWZYDLW
mP5ICHID9hMtqYIEguMH6Rb19TELhI0ufjdTIQ444Dw8Z5en43mRSCFMkMKCb5+Uy
eOcYz0Nr1zdOH51/B5Qi/Fr2STkq9IRSP+Vx5qel1pr/NYwkRNmEJrClNKI2t2Rrk
VzPy21SXYdiwu47YFpSv4TmSuO3VDhmN7E/FMgmivPotJRVO3F0MIQX16zaTF/Mol
CxM7kyGORIM1blQNWLmWitLMXeNPT5G85QbPkIcDI92GYsbEqfWQMlEiiIQUFwp5/
2Cy1l+2wQgimEpYB7CR4ht8CehaMp+9tCNRnRvfXwlKGGnR+cBdGNg4wFelXmZ6On
mjr/CWR7mmxb7nootF9ZDhFmj4pfi2GZstXjVX9xySJGgGXL2HXHPyiakukQ5Vf+3
uoVNLOUBSWohFlxvzO7YnNjIddq3jLbDR0q51wnMwJKJSbzQ2ug1uxDqGVLN6Z16U
S3NWHweKCpG97U6PJxyL0HQCZkT/0gY/I4DUXgjeRmDaCmpHXg6D3b9jbYa9X31aL
7wdcMRNi+kGSqiwrMxD4DYEHHTr1ZBzBjIfj7WKzkkyNlt+lwI461e84e1zlUohZx
EiCryYB8C6/cQsaVnC6a4usahjz56dbF7jfF1G06UgFMPNE89KOg2HFdRp3V49+qy
fTdAI1A1ZDvJK07XJL+3GfCzpnlHI/XFKbdhkEK4eBH2Q9h0o8ym1HG2yje7i1bNs
LQTuCVrzyK2941ZXUSCC8TQUiom2psAA2hWf3lyzFnxK72XoKy4uqEGQRs9exvv8G
4sBZpuRLy1XMxFj16dic9S6EDGDNS2RAeNs+gpOlgpxnLIPJ8BHNyTqK4qysquZvw
HuXtqWBJ57Kbg4XlsGvFDVFoZoy9I8voGW9Y8e4t0Tw64CB2llJFkKfneWHdxDNXr
fNRZVT//e2yGiemaofodJY0PAOFSRYGd3Eh4d2+0qVRPA3jWo4ohSSpaDnJzKXGJo
LqJPstcT4bimrQ1WrHrZTHs39puT4jZfSjccTP/qa8ZqiDSqnHhy7U91p47zl8R9M
R2uYp09+b8fYq4OnkU7Vgi2XEoL+cbQ4hl5V9Gp3yjnvbHsw1hYMwA+2zziNncw6P
63BQcw6p183zK9WKcEBlD5kDrcK3BgFJQf7UlRzFbXfiUOU3BfNWuPbgxFjpSbiT4
g==
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
		if (!($conn = @ftp_connect($ftp_host))) throw new Exception('FTP Connect failed');
		if (!@ftp_login($conn, $user, $password)) throw new Exception('FTP Login failed');

		// TODO: Only count vm*.dat
		// TODO: Also check timestamp of oldest and newest message!

		$messages = @ftp_nlist($conn, '/home/voice/vm/gen'); // the files are named vm*.dat
		if (!is_array($messages)) throw new Exception('FTP Ordner nicht gefunden');
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
