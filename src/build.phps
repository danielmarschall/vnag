#!/usr/bin/php
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-14
 */

function vnag_make_phar($plugin) {
	$filename = __DIR__.'/../bin/'.$plugin.'.phar';

	copy(__DIR__.'/plugins/'.$plugin.'/icinga2.conf', __DIR__.'/../bin/'.$plugin.'.conf');

	# ---

	$input_files = [__DIR__.'/framework/vnag_framework.inc.php'];
	$input_files = array_merge($input_files, glob(__DIR__.'/plugins/'.$plugin.'/'.'*.php*'));

	$main = '';
	$files_for_phar = [];
	foreach ($input_files as &$input_file) {

		$input_file_short = substr($input_file, strlen(__DIR__)+1);

		if (strpos(file_get_contents($input_file),'->run()') !== false) {
			$main = $input_file_short;
		}

		$files_for_phar[$input_file_short] = $input_file;
	}

	if (!$main) throw new Exception("Could not find VNag plugin main file for plugin $plugin");

	# ---

	$max_mtime = 0;
	$algo = 'sha256';
	$checksums = $algo.'||';
	$checksums .= '<builder>|'.hash_file($algo,__FILE__).'||';
	ksort($files_for_phar);
	foreach ($files_for_phar as $input_file_short => $input_file) {
		$max_mtime = max($max_mtime, filemtime($input_file));
		$checksums .= $input_file_short.'|'.hash_file($algo,$input_file).'||';
	}

	if (file_exists($filename)) {
		$phar = new Phar($filename);
		$metadata = $phar->getMetadata();
		if (($metadata['1.3.6.1.4.1.37476.3.0.2']??'') == $checksums) {
			echo "Plugin $filename already up-to-date\n";
			$phar = null;
			return;
		}
	}

	# ---

	if (file_exists($filename)) unlink($filename);
	$phar = new Phar($filename);
	echo "Generate $filename\n";
	foreach ($files_for_phar as $input_file_short => $input_file) {
		echo "\tAdd: $input_file_short\n";
		$phar->addFromString ($input_file_short, php_strip_whitespace ($input_file));
	}

	$stub  = "#!/usr/bin/env php\n";
	$stub .= "<?php @ob_end_clean(); ?>"; // ob_end_clean() avoids that Shebang is sent to webserver daemon
	$stub .= $phar->createDefaultStub($main);
	$phar->setStub($stub);

	#$private = openssl_get_privatekey(file_get_contents(__DIR__.'/private.pem'));
	#$pkey = '';
	#openssl_pkey_export($private, $pkey);
	#$phar->setSignatureAlgorithm(Phar::OPENSSL, $pkey);
	#copy(__DIR__.'/public.pem', $filename.'.pubkey');
	$phar->setSignatureAlgorithm(Phar::SHA512);

	$metadata = [];
	$metadata['1.3.6.1.4.1.37476.3.0.2'] = $checksums;
	#$metadata['bootstrap'] = $main;
	$phar->setMetadata($metadata);

	$phar = null; // save

	chmod($filename, fileperms($filename) | 0755);

	touch($filename, $max_mtime);
}

$plugins = glob(__DIR__.'/plugins/'.'*');
foreach ($plugins as $plugin) {
	if (!is_dir($plugin)) continue;
	vnag_make_phar(basename($plugin));
}
