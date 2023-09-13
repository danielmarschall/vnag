<?php /* <ViaThinkSoftSignature>
Q5Qtv/JGX2tdxoEqxXuF2TaMaX26SuclN68wP5pYOwMe01/GepIHEZNJU6vFf1JO/
Fu/OZ99tRamEa1ckOEdCR9R/oiadNqQ/3U56/qV0/9NMbVcaETamUFImUm3ztpvFN
83t5S0DtVqqVfCQ08UpJCUTsgFbztlOILKK2yfyIPzwK0vQFAza98fRCZfidJTxYm
xPuThlzLFoVlxyczR5OiCKdwJm1UzZhRCToIbLMU6dIwkx8mE0CBGYcR1sUVBeRoT
Wjqcz4JN7lGNihUYirTZL7kNTllu6lcGIUIQtDGwLw8vYEeUr3XoGvUKZwQtqd6Yn
7/Bw9UmDU2/lNHdMtSgzMRVa7m8hXSApdjKxzzvnoK1qU4bBxaiiCAUSBq1K9hfRf
9oRx5Kea3Jl/uV7aAsr5MMO45CYUxCwPMS/KykTXMAtOe9Yr53GAciQZbxTKCen6G
Wh1wxImMEoFc2m1axWMqzmvsNJw7ZGODbYGgY351htsVhgTdZLxPJOGt5dLs9zsQA
b9/+lMyejv/ZVZ5zOLpfIilbAOZaTSh834YNY2PciKIoPsg/umIYPx2ELcsmgtOrN
nIjvvGBK3r18QBJUN3y9SlEwm9ej62hU/PvQ1HiG+iqtoE86BzxhcUkDn6WUXOlaa
fB4BgETjG+RwX9Kj+h2ZTTiKqoPN1BlpxlUKrQ9ZTLdpy3PHdkA35N2EEFPcCVdf3
ada6oDhmPhvhXbcFZ29Ip100I1L4lxHJcU5XCHJzEaMLkIX5nyT1tPZL1XnV5fOyH
l/LxR/H+t5cvDw4ybqPj11XshtQvxGxkLDyMB2ZUU0mpiUJyxePdAXXPPmOpdO37I
MCStMGQxqrvIJL/EdQn8BVQuDBKm53lXNMGlt1tEky6w8ikTYd+HfSbt/S3yWQATn
elVfQfyEB4+GR/bckylsoirQQpiFZONMIV+iCEE7QUFdM7ms933hRP4Pg9rISaP3i
t7jk7v4Ad1AF9JQLP64cyQdHJaaR97nLYtSYp7WVj9VEMb8WdB21wjBecsJktqdTv
E/gj8a3X71bheeSMc9ZS4sBGhkTryhEb3R4i6yLyyrCJxc3P2ZSvEvkj7OKmHhlWA
nKBhH0Ka4Acv82FFu34hcwksCVf1otQlfAP867EoKRTN9Z5gq0ljpj6Qya0OFuXsV
PVs2h4B4qXqxWD301tYkP6YChHTGLXmo3LAje1OgqOIrivuP7hvZ4v8awaLn/xIFc
REMolTxoQTw+C7F5NmUyy6/1WgpFCQzKcMGxZu+A/xG0G8SwhY5+tlMtWMGUWupyG
RTb901U+uuLs7trE2qxfOq567wmYLdXhU70RKxq1yrGopr2CTGK/g0So3+MqRHNkv
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-09-13
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
