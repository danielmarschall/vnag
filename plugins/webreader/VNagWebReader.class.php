<?php /* <ViaThinkSoftSignature>
yjpYd6RjoRdUv8xZFrEs3f8uvPQ6gVhf+TIEOFZTd7KIRVbDHy+ZOONeDDtLXJAeX
EPpNHBT2jijIHlPKklWF9fMPoZ3xOuXNaQ8m8aejwxZzMxoB4owNDq9L8neC4PN3y
CvtiaAJa1+mex9P9D0GFEUoVpwNMlKZVaav0ncmedOFE6Y0U+X/hnlbcWF5ItdzAO
1IufVyVECA48edIfuqRQjqGATLs/vkBJPwzbsH3JzrL6hyK5Ry2p+ReMO3/50q5zt
unz9oeGa1A7milra0kZVBapqcrX3XEHsgGNkt9M+S+BuRLnDHYiJiuqshQZBkwfAr
rr6xyd8TahY2V3b8NWjnOEuh6DlKu5BOWzMwDNa43jST9vopyhevNQe3iIDqwswVw
Hw8EnFQWXt+ASFZzi2NSKMWbEWcjR/8Tv4ciQUM8wgAYoAM6YwamTeuuNxJg2fF8B
Oipr7+OX98qyPBTLg/6yGOo1ie/w36toFe6uS5c430fU7VlFHugGo2VMixFOa5f70
E29E6+wzA2LVi9eDrPZka5iGfSdhMyGI2VOQ/keqCp3Xi9WhqfUVJIO4kSz5C9iy/
9A2Nxj7GAQbkn/aYGXyFYvhrZFzwV414P4xXzoS0+BgPjVXbunkhDbsncSoX3YWU8
MRORTxNMdz9WINlLvfx+/7MWMob+z4Bieq1l971cufFyhefDbQ1rluCfIU6Cgxpyf
rq0GmPUSjL6FKWtyDFWKuFPoftz12/9evd2M5Kxr5zz2yiR3+c8w3kta4AAuILNsL
Rni2lUIF5usajSKVZxWVNc4sFoBJ2TCG3GQ9XxeLDFYwxPkGzz0z82h4vj0pnSBGF
Q3z5WZ/V4NCydpmXQP4gR1ghMB/1YKZ7OyJ7jiiglCBSQ2ZASXcnmgvXFMWxCrNFC
a6dMKnKFUAFGjINp+/uKO77qY4z0foWBZJl5+YfzDZgOSWgSPMEPVHKjGZXl2JM0J
Dl3jeI3PwuzrsiCY8GzEEla7ggvkSOJ4jzzXDsx2QULOgHgIOb77FfWDl0EgHQGgf
ezMkbe5JXxP5guV4Y4bMvTNdMIW3OhOE7rl5DMhgUM8u5K6XuxA+cF+WFd7eCOe85
w5FeTdWMWtT/0C3lVhyTFfzLbeRqUuI/9i23xwPURpANo3TQd5BTaYVQqltTKKCPi
wZ7Me7bRnk07tnnnElrmSebv5T9hX9G97GKG3PEHtrOcDLzmhSOITNCm6/0G7xbi/
4DKZr9Dtc86eHaQXEunXKKjuk1pEODb2zh57xqdrtyf7LaW8mRCi/vA5C3uxIgFvj
LZLY8vUeP5eK7+8m8qBkzb+xSJMVl0QWlFDINIcZzM/6JDljLjeEGo+S7kd1VkY2B
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

class VNagWebReader extends VNag {
	protected $argUrl = null;
	protected $argId = null;
	protected $argBasicAuth = null;
	protected $argPassword = null;
	protected $argSignPubKey = null;

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			// Don't allow the standard arguments via $_REQUEST
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vht');
		}
		$this->addExpectedArgument($this->argUrl        = new VNagArgument('u', 'url',        VNagArgument::VALUE_REQUIRED, 'url', 'The URI of the page that contains an embedded machine readable VNag output', null));
		$this->addExpectedArgument($this->argId         = new VNagArgument('i', 'id',         VNagArgument::VALUE_REQUIRED, 'id', 'The ID (serial or individual name) of the embedded Nagios output. Usually "0" if only one monitor is used without individual names.', '0'));
		$this->addExpectedArgument($this->argBasicAuth  = new VNagArgument('b', 'basicAuth',  VNagArgument::VALUE_REQUIRED, 'username:password', 'In case the target website requires Basic Auth, please pass username and password, divided by double-colon, into this argument.', null));
		$this->addExpectedArgument($this->argPassword   = new VNagArgument('p', 'password',   VNagArgument::VALUE_REQUIRED, 'password', 'In case the machine readable VNag output is encrypted, enter the password here.', null));
		$this->addExpectedArgument($this->argSignPubKey = new VNagArgument('k', 'signPubKey', VNagArgument::VALUE_REQUIRED, 'pemFile', 'In case the machine readable VNag output is signed, enter the filename of the public key (PEM) file here, to verify the signature of the output.', null));

		$this->getHelpManager()->setPluginName('vnag_webreader');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin reads embedded machine readable VNag output from a website and converts it into a Nagios compatible output format.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ -u <url> [-i <id>] [-b <username>:<password>] [-k pubKeyFile] [-p <password>]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');
	}

	protected function cbRun() {
		$this->argUrl->require();
		$this->argId->require();

		$url = $this->argUrl->getValue();
		$this->id = $this->argId->getValue(); // default is '0', so we do not need to check for available()
		if ($this->argPassword->available()) $this->password_in = $this->argPassword->getValue();
		if ($this->argSignPubKey->available()) $this->pubkey = $this->argSignPubKey->getValue();

		$header = '';
		$auth = $this->argBasicAuth->getValue();
		if (!is_null($auth)) $auth .= sprintf("Authorization: Basic %s\r\n", base64_encode($auth));
		$header .= sprintf("User-Agent: PHP/%s VNag/%s\r\n", phpversion(), self::VNAG_VERSION);

		$options = array(
		    'http' => array(
		        'method' => 'GET',
		        'header' => $header
		    )
		);
		$context = stream_context_create($options);
		$cont = $this->url_get_contents($url, 60, $context);
		if ($cont === false) throw new VNagException("Cannot access $url");

		$data = $this->readInvisibleHTML($cont);

		if (!$data) throw new VNagInvalidArgumentException("No monitor with ID \"$this->id\" found at URL $url");

		if (isset($data['text'])) {
			$this->setHeadline("Special content delivered from the web monitor (see verbose info)");
			$this->addVerboseMessage($data['text'], VNag::VERBOSITY_SUMMARY); // VERBOSE_SUMMARY is *our* verbosity, not the verbosity of the target monitor
		} else {
			foreach ($data['performance_data'] as $perfdata) {
				$this->addPerformanceData(VNagPerformanceData::createByString($perfdata));
			}

			$this->setHeadline($data['headline']);
			$this->addVerboseMessage($data['verbose_info'], VNag::VERBOSITY_SUMMARY); // VERBOSE_SUMMARY is *our* verbosity, not the verbosity of the target monitor
		}

		$this->setStatus($data['status'], true);
	}
}
