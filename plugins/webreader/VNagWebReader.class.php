<?php /* <ViaThinkSoftSignature>
A/Ae4mLugX8aWi3kU6+IP3v8vJqavUp5B0oOzj6LG/H28D/deFDPma4ukxYN933EN
3jWEdDAPnJYHnAnicrW6X037PjLCDDfCFSI0Ag7uERGhacn0+jjtD8HlUp1Rx1vmw
GbMVnhwbZaTHb1579ASAEE+s06LC21zzaAovhaQbe+zQpSMyUijBU7QuiOT93DHZj
RsxmZe5RUU8DIQ55hYj70k3iiVkN7QKss700e6dtfctedZxSThXIjFLWgv/8PAQNV
sIzpb/T9EbqiibvsWDWGn30pVjDsGLltOM8+iakOwaY5/0lO5DEfLUuChV1d2CZpz
vH8QHD8iG2ur4i4JJyJ953yzvKO5ewFsOFKupHAR1QyTMYGyd+wtBLsiRYw7IJgsv
kF7Z3XUzuwoVhnsDReF9jH2IG6R1rH0CuOAMspnD+PravAeFCSOJY2XVN0eJZmSNZ
Fh77U8mIHTooJSCwBJDzgxT3RrRs23mUrvLm3IDPvN9QHI/tK1YaTGDwQkc9ToO5H
5jQwvz90zH0ovu64QtGVVlBqQ0ImoBIs1trB4cPsLKTQwCNKrSPbbeUq80p92o1kV
LVC5IWMma6WIwI1t/8YGRdEDhHtxtI81LevAqTKoiA0/YxKk7fHeYb0Icl+v7VFTc
WOluoM7E3In9mvRnnXXW3yUlT1/OquygQxkJ75xXPQXRHAjE5m5lMNePySNZ5HGdw
vfJjUMH5hRkiMlIcTz2uLNXWo04DCAksS45frCDdsLGik9Djwfii1BQHspudV77QB
AWw76StXbeThv8k4JXG/3+pANiUp+AMn9RccVrvcRUuJEyJkRxxlMjFmA8zjfGWRl
hd39hotSf1a29SkECiPLbYWO95D3Um1PGVaT4IqNAC/I+Y3Vt+Cu2I60cB1nKIeZo
sMHjdR1bCrib9DNbT2Pv30p4BnfLYE4hR8WvUpkEgQZRnTYhMQIa9vdMfUo6tEYyv
u2xMI8LD22KsgZh+IYQtsfBMtfs9UBXGVBOg6W6BSpCivBUqT+M5PDz3VAB39dB+q
aWs1wgkhAN/7/Uqo4DyjheoIYVzChgyX8p/zC9m62ppAhwr4aIrEdtrCsiI1SpBWA
eCIKMzXU1FdY96f+EgnJzCD5ViENvkx58HiLUURl4SLYX/YYinVn2m2yS4mIk02S5
LEU6tgGjnDdc82KGQLDDQKjU5w9/xz08SIRqwn5PpGeisv3EYoj/9KandHgAU9fDp
2qkurm42hGkly4avVs7H58xVrzXW0pP2NW/fJEUOoYqSWZp9bE+Jgr7Y2etBdov2y
0kNYaeil4VKsM/m/cIFBs9RmsGmSaBamsj0WMrCuiM9jTDZcj2q0wqvd/yRoIuMny
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-15
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
				sprintf("User-Agent: PHP/%s Vag/%s\r\n", phpversion(), self::VNAG_VERSION)
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
