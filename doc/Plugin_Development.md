# VNag - Nagios Framework for PHP
Developed by Daniel Marschall (www.viathinksoft.com)
Licensed under the terms of the Apache 2.0 license

## Introduction

	VNag is a small framework for Nagios Plugin Developers who use PHP CLI scripts.
	The main purpose of VNag is to make the development of plugins as easy as possible, so that
	the developer can concentrate on the actual work. VNag will try to automate as much
	as possible.

	Please note that your script should include the +x chmod flag:
		chmod +x myscript.php

	Please see the the demo/ folder for a few examples how to use this framework.

## Arguments

	Example:
		$this->addExpectedArgument($argSilent = new VNagArgument('s', 'silent', VNagArgument::VALUE_FORBIDDEN, null,       'Description for the --silent output', $defaultValue));
		$this->addExpectedArgument($argHost   = new VNagArgument('H', 'host',   VNagArgument::VALUE_REQUIRED,  'hostname', 'Description for the --host output',   $defaultValue));

	In the example above, the two argument objects $argSilent and $argHost were created.
	With these objects of the type VNagArgument, you can query the argument's value,
	how often the argument was passed and if it is set:

		$argSilent->count();      // 1 if "-s" is passed, 2 if "-s -s" is passed etc.
		$argSilent->available();  // true if "-s" is passed, false otherwise
		$argHost->getValue();     // "example.com" if "-h example.com" is passed

	It is recommended that you pass every argument to $this->addExpectedArgument() .
	Using this way, VNag can generate a --help page for you, which lists all your arguments.
	Future version of VNag may also require to have a complete list of all valid arguments,
	since the Nagios Development Guidelines recommend to output the usage information if an illegal
	argument is passed. Due to PHP's horrible bad implementation of GNU's getopt(), this check for
	unknown arguments is currently not possible, and the developer of VNag does not want to use
	dirty hacks/workarounds, which would not match to all argument notation variations/styles.
	See: https://bugs.php.net/bug.php?id=68806
	     https://bugs.php.net/bug.php?id=65673
	     https://bugs.php.net/bug.php?id=26818

## Setting the status:

	You can set the status with:
		$this->setStatus(VNag::STATUS_OK);
	If you don't set a status, the script will return Unknown instead.
	setStatus($status) will keep the most severe status, e.g.
		$this->setStatus(VNag::STATUS_CRITICAL);
		$this->setStatus(VNag::STATUS_OK);
	will result in a status "Critical".
	If you want to completely overwrite the status, use $force=true:
		$this->setStatus(VNag::STATUS_CRITICAL);
		$this->setStatus(VNag::STATUS_OK, true);
	The status will now be "OK".

	Possible status codes are:
		(For service plugins:)
		VNag::STATUS_OK       = 0;
		VNag::STATUS_WARNING  = 1;
		VNag::STATUS_CRITICAL = 2;
		VNag::STATUS_UNKNOWN  = 3;

		(For host plugins:)
		VNag::STATUS_UP       = 0;
		VNag::STATUS_DOWN     = 1;

## Output:

	After the callback function cbRun() of your job has finished,
	the framework will automatically output the results in the Nagios console output format,
	the visual HTML output and/or the invisible HTML output.

	In case of CLI invokation, the Shell exit code will be remembered and
	automatically returned by the shutdown handler once the script normally
	terminates. (In case you run different jobs, which is not recommended, the
	shutdown handler will output the baddest exit code).

	The Shell output format will be:
		<Service status text>: <Comma separates messages> | <whitespace separated primary performance data>
		"Verbose information:"
		<Multiline verbose output> | <Multiline secondary performance data>

	<Service status text> will be automatically created by VNag.

	Verbose information are printed below the first line. Most Nagios clients will only print the first line.
	If you have important output, use $this->setHeadline() instead.
	You can add verbose information with following method:
		$this->addVerboseMessage('foobar', $verbosity);

	Following verbosity levels are defined:
		VNag::VERBOSITY_SUMMARY                = 0; // always printed
		VNag::VERBOSITY_ADDITIONAL_INFORMATION = 1; // requires at least -v
		VNag::VERBOSITY_CONFIGURATION_DEBUG    = 2; // requiers at least -vv
		VNag::VERBOSITY_PLUGIN_DEBUG           = 3; // requiers at least -vvv

	All STDOUT outputs of your script (e.g. by echo) will be interpreted as "verbose" output
	and is automatically collected, so
		echo "foobar";
	has the same functionality as
		$this->addVerboseMessage('foobar', VNag::VERBOSITY_SUMMARY);

	You can set messages (which will be added into the first line, which is preferred for plugin outputs)
	using
		$this->setHeadline($msg, $append, $verbosity);
	Using the flag $append, you can choose if you want to append or replace the message.

	VNag will catch Exceptions of your script and will automatically end the plugin,
	returning a valid Nagios output.

## Automatic handling of basic arguments:

	VNag will automatic handle of following CLI arguments:
		-?
		-V --version
		-h --help
		-v --verbose
		-t --timeout   (only works if you set declare(ticks=1) at the beginning of each of your scripts)
		-w --warning
		-c --critical

	You can performe range checking by using:
		$example_value = '10MB';
		$this->checkAgainstWarningRange($example_value);
	this is more or less the same as:
		$example_value = '10MB';
		$wr = $this->getWarningRange();
		if (isset($wr) && $wr->checkAlert($example_value)) {
			$this->setStatus(VNag::STATUS_WARNING);
		}

	In case that your script allows ranges which can be relative and absolute, you can provide multiple arguments;
	$wr->checkAlert() will be true, as soon as one of the arguments is in the warning range.
	The check will be done in this way:
		$example_values = array('10MB', '5%');
		$this->checkAgainstWarningRange($example_values);
	this is more or less the same as:
		$example_values = array('10MB', '5%');
		$wr = $this->getWarningRange();
		if (isset($wr) && $wr->checkAlert($example_values)) {
			$this->setStatus(VNag::STATUS_WARNING);
		}

	Note that VNag will automatically detect the UOM (Unit of Measurement) and is also able to convert them,
	e.g. if you use the range "-w 20MB:40MB", your script will be able to use $wr->checkAlert('3000KB')

	Please note that only following UOMs are accepted (as defined in the Plugin Development Guidelines):
	- no unit specified: assume a number (int or float) of things (eg, users, processes, load averages)
	- s, ms, us: seconds
	- %: percentage
	- B, KB, MB, TB: bytes	// NOTE: GB is not in the official development guidelines,probably due to an error, so I've added them anyway
	- c: a continous counter (such as bytes transmitted on an interface)

## Multiple warning/critical ranges:

	The arguments -w and -c can have many different values, separated by comma.
	We can see this feature e.g. with the official plugin /usr/lib/nagios/plugins/check_ping:
	It has following syntax for the arguments -w and -c: <latency>,<packetloss>%

	When you are using checkAgainstWarningRange, you can set the fourth argument to the range number
	you would like to check (beginning with 0).

	Example:
		// -w 1MB:5MB,5%:10%
		$this->checkAgainstWarningRange('4MB', true, true, 0); // check value 4MB against range "1MB:5MB" (no warning)
		$this->checkAgainstWarningRange('15%', true, true, 1); // check value 15% gainst range "5%:10%" (gives warning)

## Visual HTTP output:

	Can be enabled/disabled with $this->http_visual_output

	Valid values:

	VNag::OUTPUT_SPECIAL   = 1; // illegal usage / help page, version page
	VNag::OUTPUT_NORMAL    = 2;
	VNag::OUTPUT_EXCEPTION = 4;
	VNag::OUTPUT_ALWAYS    = 7;
	VNag::OUTPUT_NEVER     = 0;

## Encryption and Decryption:

	In case you are emitting machine-readable code in your HTTP output
	(can be enabled/disabled by $this->http_invisible_output),
	you can encrypt the machine-readable part of your HTTP output by
	setting $this->password_out . If you want to read the information,
	you need to set $this->password_in at the web-reader plugin.
	The visual output is not encrypted. So, if you want to hide the information,
	then you must not enable visual HTML output.
	If you don't want to encrypt the machine-readable output,
	please set $this->password_out to null or empty string.

	Attention!
	- Encryption and decryption require the OpenSSL extension in PHP.

## Digital signature:

	You can sign the output by setting $this->privkey with a filename containing
	a private key created by OpenSSL. If it is encrypted, please also set
	$this->privkey_password .
	To check the signature, set $this->pubkey at your web-reader plugin with
	the filename of the public key file.

	Attention!
	- Signatures require the OpenSSL extension in PHP.

## Performance data:

	You can add performance data using
		$this->addPerformanceData(new VNagPerformanceData($label, $value, $warn, $crit, $min, $max));
	or by the alternative constructor
		$this->addPerformanceData(VNagPerformanceData::createByString("'XYZ'=100;120;130;0;500"));
	$value may contain an UOM, e.g. "10MB". All other parameters may not contain an UOM.

## Guidelines:

	This framework currently supports meets following guidelines:
	- https://nagios-plugins.org/doc/guidelines.html#PLUGOUTPUT (Plugin Output for Nagios)
	- https://nagios-plugins.org/doc/guidelines.html#AEN33 (Print only one line of text)
	- https://nagios-plugins.org/doc/guidelines.html#AEN41 (Verbose output)
	- https://nagios-plugins.org/doc/guidelines.html#AEN78 (Plugin Return Codes)
	- https://nagios-plugins.org/doc/guidelines.html#THRESHOLDFORMAT (Threshold and ranges)
	- https://nagios-plugins.org/doc/guidelines.html#AEN200 (Performance data)
	- https://nagios-plugins.org/doc/guidelines.html#PLUGOPTIONS (Plugin Options)
	- https://nagios-plugins.org/doc/guidelines.html#AEN302 (Option Processing)
	  Note: The screen output of the help page will (mostly) be limited to 80 characters width; but the max recommended length of 23 lines cannot be guaranteed.

	This framework does currently NOT support following guidelines:
	- https://nagios-plugins.org/doc/guidelines.html#AEN74 (Screen Output)
	- https://nagios-plugins.org/doc/guidelines.html#AEN239 (Translations)
	- https://nagios-plugins.org/doc/guidelines.html#AEN293 (Use DEFAULT_SOCKET_TIMEOUT)
	- https://nagios-plugins.org/doc/guidelines.html#AEN296 (Add alarms to network plugins)
	- https://nagios-plugins.org/doc/guidelines.html#AEN245 (Don't execute system commands without specifying their full path)
	- https://nagios-plugins.org/doc/guidelines.html#AEN249 (Use spopen() if external commands must be executed)
	- https://nagios-plugins.org/doc/guidelines.html#AEN253 (Don't make temp files unless absolutely required)
	- https://nagios-plugins.org/doc/guidelines.html#AEN259 (Validate all input)
	- https://nagios-plugins.org/doc/guidelines.html#AEN317 (Plugins with more than one type of threshold, or with threshold ranges)

	We will intentionally NOT follow the following guidelines:
	- https://nagios-plugins.org/doc/guidelines.html#AEN256 (Don't be tricked into following symlinks)
	  Reason: We believe that this guideline is contraproductive.
	          Nagios plugins usually run as user 'nagios'. It is the task of the system administrator
	          to ensure that the user 'nagios' must not read/write to files which are not intended
	          for access by the Nagios service. Symlinks, on the other hand, are useful for several tasks.
	          See also https://stackoverflow.com/questions/27112949/nagios-plugins-why-not-following-symlinks

## VNag over HTTP:

	A script that uses the VNag framework can run as CLI script (normal Nagios plugin) or as web site (or both).
	Having the script run as website, you can include a Nagios information combined with a human friendly HTML output which can
	include colors, graphics (like charts) etc.

	For example:
	A script that measures traffic can have a website which shows graphs,
	and has a hidden Nagios output included, which can be read by a Nagios plugin that
	converts the hidden information on that website into an output that Nagios can evaluate.

	Here is a comparison of the usage and behavior of VNag in regards to CLI and HTTP calls:

	|  CLI script                               | HTTP script
	|-------------------------------------------|----------------------------------------------|
	| * "echo" will be discarded.               | * "echo" output will be discarded.           |
	|-------------------------------------------|----------------------------------------------|
	| * Exceptions will be handled.             | * Exceptions will be handled.                |
	|-------------------------------------------|----------------------------------------------|
	| * outputHTML() will be ignored.           | * outputHTML() will be handled.              |
	|   (This allows you to have the same       |                                              |
	|   script running as CLI and HTML)         |                                              |
	|-------------------------------------------|----------------------------------------------|
	| * Arguments are passed via CLI.           | * Arguments are passed via $_REQUEST         |
	|                                           |   (i.e. GET or POST)                         |
	|-------------------------------------------|----------------------------------------------|
	| * Arguments: "-vvv"                       | * Arguments: GET ?v[]=&v[]=&v[]= or POST     |
	|-------------------------------------------|----------------------------------------------|
	| * When run() has finished, the program    | * When run() has finished, the program       |
	|   flow continues, although it is not      |   flow continues.                            |
	|   recommended that you do anything after  |                                              |
	|   it. (The exit code is remembered for    |                                              |
	|   the shutdown handler)                   |                                              |
	|-------------------------------------------|----------------------------------------------|
	| * Exactly 1 job must be called, resulting | * You can call as many jobs as you want.     |
	|   in a single output of that job.         |   A website can include more than one        |
	|                                           |   Nagios output which are enumerated with    |
	|                                           |   a serial number (0,1,2,3,...) or manual ID.|
	|-------------------------------------------|----------------------------------------------|
