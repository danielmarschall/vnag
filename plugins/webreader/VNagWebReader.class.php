<?php /* <ViaThinkSoftSignature>
ISLdljjtzCdnwu/ldQDVktIeOGgqR6kkmK0B//MwT0fqh8qhLOqyRpLJnVMxaN6Ta
bfjTM0gb/OhxWiU3+WDI4bN+jwuk+X5j3NVre3JiTKXr39WoxF/aKIUuloeej931e
mXu1CGjaXC2xp7B20vMkf+wfLNnwebMKvIgceR4Qwq76FRSygPeJtfcWIaG1oWywT
Rz1laiI5VNRaBHqVjfcdo4zCVkpwepC9SHICrTsD3cfBe+p8t5jsbYd5g2Z6V5u2r
zvGXA3oZowPTiw+ZnhPiIqdQKshFhEsnfM+unpElkLwM+4a8k+JPYSbNAjhqdL8XY
CE32SAY2wsH6CAIhOpwRN2p4QDs8Kk0U3EeIq6vONyjP9WYj7SWkssnZtQA/5K93O
0uLhqcXCnsWKFzwuuDfIQGdfnkyXyoKp55Gbl23KHSOrKhEokwd7fxS1nLAgDGQUm
i3XZumlRJwPVPaCOcmYwJIGGp8r5+EolOL2g/404Ymogo1B/fs6SAHmoutddnStgL
xgJqxMmNvilf5TfAGU0RRdFkcXMIjv0fCY5LJWO5Mklqyym9ryZecNV/+KEd1btNH
IP5LXQGo91eO12HsfwLN6D0ImsKQmcOFFf6Nh3sePFJN+d60C5NKBuW7Pl2TEMgi0
3bCxzlBtJkfkuCA8cx+N6EIOANUYgbBs0MU9f13VQoPku/0cmFM0n1fpuJ4FdEirx
i3pEr0WJVOa8LFpnw/0o7EgRy74TFnOna+w6B2dt0VR1AEl8kFEAC1vpjzNEoNbEn
a71r9gUGfbwXLmqym4w//T01lYgE/g6nyyyjRZFoKx4z78NQOEEKjGGSAR4IL9Kc/
msO2x7cWsop16o7vimaQad5B48vrE3dQnfI/8r5Zlv7iUZQ654IqFZiR0bCCDlyuv
u8FOnIcK2koM/FqZydvbqC3AYcoxSCccsogc7KARUMtKt7v4OY2HvTZ7IMudoRmtS
FfJzwMZHzv/eqgTlgBOoG9xmtdn04nYucEUss9Pyv9eAb17wDa5x88cTldkCacPy2
ub6eVI9Z2GKSGHpz9+IPY9XwXxn69lVIHmRM2Ngs6qP2lOsseQZmUABt6lXtupv4W
qlPMjP67OcVFGUgqMABxOm+ooRtUiGeKEUF11qxda9bF993Kco6QLDUU/ggXt5lhL
qK4+EnLjPpMsqGa/EOe0kcqBS/+Goj/yz7ot50hZTxDWs7lq29VDZqdaYvdKmrYyb
h169//lL0XytrNCDyZE3WiWUnKvS0nl3gfpOxWPI4vflx2rli8mF56e7COgAASlNI
K9TsrfGFmZ+yfK4cgHRehkAVq85Ym8KLfoZQOGDRpi/ZVeNHJhRfacyBow5pteKVL
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2022-07-17
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

		$options = array(
		    'http' => array(
		        'method' => 'GET',
		        'header' =>
				sprintf("Authorization: Basic %s\r\n", base64_encode($this->argBasicAuth->getValue())).
				sprintf("User-Agent: PHP/%s VNag/%s\r\n", phpversion(), self::VNAG_VERSION)
		    )
		);
		$context = stream_context_create($options);
		$cont = file_get_contents($url, false, $context);
		if (!$cont) throw new VNagException("Cannot access $url");

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
