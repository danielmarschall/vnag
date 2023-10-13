<?php /* <ViaThinkSoftSignature>
0tLzYq26CBq3L+Zi9pnnz74c+FEdcuP/FIzuxU39m4GJHKk04j9NYImnHIvZfPtJI
HlU5GzQKHbazWLUmR31LQA2urW6VlG/cXRe8Vm7vZ/vxkbt0ZCGz93m12sa+5sVeG
g2mkhF0JyZLX1BjvECNeyiZg+VHxZ74RHiDFJF/neXICSrJ69fOFu05xF8CAxKoee
2X71qoRtRelWFlf4HFhNcwdP8mr6IIMEIiMlVGatjLKhUsoAhNIFW9RwRdPIplNSP
D5HfRIA4AT2qiBj3YOBx+O/qoNJ5fFDN947fqZizPU8lLGUjv4t+WDIh7deEWJ3h9
SLQrJzc7VYHkhKf8h6KgX0GYKn8nlILAK0ciMGkQpY86E5jPY7+3VkTM4W6RcXT2l
Ds7iLiUhbfBCeEqpDVNTvhINVaDzynIiT8TsI8X7w2RK93sym3aICuWAUjvtVedZd
6l87z5jlxRTKc0cniTXrqBoZ3rrl4REGSfwgdPEgmVJ3gSbMDZTtN2FAgjIxOa1em
0k1ZXZDIwzDFeDzirHYh5vLsSFKCSVyzUH5v++SwCVRvZZBIPTQFfD0jLnVKpWq25
Ux+MA9JWEYJI3h3PDWzghSQ163MAkvQe6eEb0Yoeblau4vUY7N7Onlo5vLnC5vyqK
RA0Yn79xybAlxSrnOjzUv0G2J+VTfdH0SmzJM9ykFgIKqxk5DFtWm65Fkywx9MCUv
lUdAYn7FUlHz8+4099yAgGtR250ZCQ5sXnnrS/mqRwT9jAYjyCLIn3pXcBAdi3qLR
ntYF8eM47Q7hD4EQl74JzbfV4vRe/imJxRvXsRlj7+4++YOEICq/pOlysk6iS2IRd
yf/ZOIPfc4QiYztD2WHl8+hzQhguQWbfmbV7BJSuXmTwISXGUws6SgJQVvAHoGAM8
Ub4x9G7VmOVTkLEkuumyFb4GW3s10LRYVLGCbFVT/6NhFotSgy75FRqemL1yeUfFV
r+OM1QxKVtEQ9oK6E7hgaCNBcywamNsVTyTqi+7dtPWCYhaGU3B6wCY8YC12EIMEq
ZW6lk4xPcQ8vpHpu+AGeNb0QopOUqIHdZE9rhVOwZYq+h0FW8Dj4zrB1Pwy9h1Dwn
B+OyJ+YqUfV0phVd6znyd0S2xn6tB/L4mxoIHtvhkjk6vWQhAC8aSdnYqwBxT3qav
hKRdOgEYj/RPat9o123+Ekv0YWymdOJp496CuJG+FsD7X60mbim+c1kcMgAKSKUzo
6a2f/8LMZiXdSRVBK159FIQlz1SHtlHIkDiTNXGKFHT5cN8JWS6uFKzWxHBMrJ+zs
A/SxRIR1b+UrahV+7jWm+VaVK48vyQB3+2JqKYoKbF6VBusXjdY70ibJVIK54j7cs
A==
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
