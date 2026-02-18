<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2026-02-18
 */

declare(ticks=1);

class SynFloodCheck extends VNag {
	protected $argPort = null;

	public function __construct() {
		parent::__construct();

		if ($this->is_http_mode()) {
			$this->registerExpectedStandardArguments('');
		} else {
			$this->registerExpectedStandardArguments('Vhtwcv');
		}

		$this->getHelpManager()->setPluginName('vnag_syn_flood');
		$this->getHelpManager()->setVersion('2026-02-18');
		$this->getHelpManager()->setShortDescription('This plugin checks for SYN flooding (half-open TCP connections) on a given port and alerts when the count exceeds the defined thresholds.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2026-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-w <count>] [-c <count>] [-p <port>]');
		$this->getHelpManager()->setFootNotes(
			"A SYN flood is a type of denial-of-service (DoS) attack in which an attacker\n" .
			"sends many SYN packets without completing the TCP handshake. This leaves\n" .
			"connections in SYN_RECV state and can exhaust server resources.\n\n" .
			"If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com"
		);

		$this->addExpectedArgument($this->argPort     = new VNagArgument('p', 'port',     VNagArgument::VALUE_REQUIRED, 'port',  'TCP port to monitor for SYN flooding (default: 443).'));

#		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
	}

	private function get_top_ips(array $lines, int $n = 5): array {
		$src_counts = array();
		foreach ($lines as $line) {
			if (preg_match('/\s(\d+\.\d+\.\d+\.\d+):\d+\s+\d+\.\d+\.\d+\.\d+:\d+\s+SYN_RECV/', $line, $m)) {
				$ip = $m[1];
				$src_counts[$ip] = ($src_counts[$ip] ?? 0) + 1;
			}
		}
		arsort($src_counts);
		return array_slice($src_counts, 0, $n, true);
	}

	protected function cbRun() {
		$port = ($this->argPort->getValue()     != '') ? (int)$this->argPort->getValue()     : 443;
		$warn = $this->getWarningRange();
		$crit = $this->getCriticalRange();

		// Prefer 'ss' (modern replacement for netstat), fall back to 'netstat'
		$use_ss = false;
		if (`which ss 2>/dev/null`) {
			$use_ss = true;
		} elseif (!`which netstat 2>/dev/null`) {
			throw new VNagException("Neither 'ss' nor 'netstat' is installed. Try installing 'iproute2' or 'net-tools'.");
		}

		// SYN_RECV = half-open connections (server sent SYN-ACK, waiting for client ACK)
		if ($use_ss) {
			$raw   = shell_exec('ss -n state syn-recv sport = :' . $port . ' 2>/dev/null');
			$lines = array_filter(explode("\n", trim($raw)), fn($l) => trim($l) !== '' && !str_starts_with($l, 'Recv-Q'));
			$count = count($lines);
		} else {
			$raw   = shell_exec('netstat -n 2>/dev/null | grep :' . $port . ' | grep SYN_RECV');
			$lines = array_filter(explode("\n", trim($raw)), fn($l) => trim($l) !== '');
			$count = count($lines);
		}

		// Count unique source IPs: many IPs = distributed (DDoS), few IPs = easier to block
		$src_ips = array();
		foreach ($lines as $line) {
			if (preg_match('/(\d+\.\d+\.\d+\.\d+):\d+/', $line, $m)) {
				$src_ips[$m[1]] = true;
			}
		}
		$unique_ips = count($src_ips);

		// Top offending IPs (useful for targeted firewall rules)
		if ($use_ss) {
			$top_ips_note = "(top-IP breakdown only available in netstat mode)";
		} else {
			$top = $this->get_top_ips($lines);
			$top_str = array();
			foreach ($top as $ip => $c) {
				$top_str[] = "$ip ($c)";
			}
			$top_ips_note = !empty($top_str) ? "Top sources: " . implode(", ", $top_str) : "No source IPs found";
		}

		$this->addVerboseMessage(
			"Port: $port | SYN_RECV: $count | Unique IPs: $unique_ips | $top_ips_note\n" .
			"Tool: " . ($use_ss ? 'ss' : 'netstat') . " | Thresholds: warning=$warn, critical=$crit",
			VNag::VERBOSITY_ADDITIONAL_INFORMATION
		);

		// Performance data for graphing
		$this->addPerformanceData(new VNagPerformanceData('syn_recv_connections', $count,      $warn, $crit, 0, null));
		$this->addPerformanceData(new VNagPerformanceData('syn_recv_unique_ips',  $unique_ips, null,  null,  0, null));

		$distributed = $unique_ips > 10
			? " (distributed, $unique_ips source IPs)"
			: ($unique_ips > 0 ? " ($unique_ips source IPs)" : '');

		$this->setHeadline("$count SYN_RECV connections on port $port$distributed");

		$this->checkAgainstWarningRange($count, false, true, 0);
		$this->checkAgainstCriticalRange($count, false, true, 0);
	}
}
