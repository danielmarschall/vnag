<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2023-10-14
 */

function vnag_make_phar($plugin) {
	$filename = __DIR__.'/bin/'.$plugin.'.phar';

	copy(__DIR__.'/plugins/'.$plugin.'/icinga2.conf', $filename.'.conf');

	# ---

	$input_files = [__DIR__.'/framework/vnag_framework.inc.php'];
	$input_files = array_merge($input_files, glob(__DIR__.'/plugins/'.$plugin.'/*.php*'));

	$main = '';
	$files_for_phar = [];
	foreach ($input_files as &$input_file) {

		if (strpos($input_file, 'index.php') !== false) continue;

		$input_file_short = substr($input_file, strlen(__DIR__)+1);

		if (strpos(file_get_contents($input_file),'->run()') !== false) {
			$main = $input_file_short;
		}

		$files_for_phar[$input_file_short] = $input_file;
	}

	if (!$main) throw new Exception("Could not find VNag plugin main file for plugin $plugin");

	# ---

	$max_mtime = 0;
	foreach ($files_for_phar as $input_file_short => $input_file) {
		$max_mtime = max($max_mtime, filemtime($input_file));
	}

	if (file_exists($filename)) {
		if (filemtime($filename) >= $max_mtime) {
			echo "Plugin $filename already up-to-date\n";
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

	$shebang = '#!/usr/bin/env php';
	$phar->setStub(($shebang ? $shebang . PHP_EOL : "") . $phar->createDefaultStub($main));

	#$private = openssl_get_privatekey(file_get_contents(__DIR__.'/private.pem'));
	#$pkey = '';
	#openssl_pkey_export($private, $pkey);
	#$phar->setSignatureAlgorithm(Phar::OPENSSL, $pkey);
	#copy(__DIR__.'/public.pem', $filename.'.pubkey');
	$phar->setSignatureAlgorithm(Phar::SHA512);

	$phar = null; // save

	chmod($filename, fileperms($filename) | 0755);

	touch($filename, $max_mtime);
}

$plugins = glob(__DIR__.'/plugins/*');
foreach ($plugins as $plugin) {
	if (!is_dir($plugin)) continue;
	vnag_make_phar(basename($plugin));
}
