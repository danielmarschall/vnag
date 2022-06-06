<?php /* <ViaThinkSoftSignature>
yD85WiHZjflrHQ2QRcEofZmKA2Z6gE3vVEx3U/12QhmKLvrHTWhO0HufTtLsvdX3S
+PTIM/wZuGh8LIvrjnmScZ3sDyrM4W7itIipHtLAbvTUBIYqET0dIrj2fV27vFF5U
I8r3Ggjb9pukb+Pcl1MdGHwikLbZfq8/V7bScK6L8nDFwO6hcIBTfVpnx7GHtHG74
ycB4X7wNhOb4+PYzoRaezU9U9RMPbk77bmKoTLvGOjdHwBTx+2TwEowHEFnoVpZ/S
vtTv/qDoz4VRdWa1mDwZILdPnRWGP7AgCBMz/RymwMbk6ClIE+iaPuRBvcJZCcKut
Nbn9hSPM4lexojx+GTTKUUie0s/VII23vFNWLA5O6f2y8viVnWPCM1CS4bYw0Lvb3
CWDJye23yuSZmmzb6c/Y/l4iRlJDpVf2M5I9hxlnPfzekN6kRT4z2V0QRyecgsBSw
OLZm3hdVv3sZu4C8Wm75bZTXiu0SZF2raxKOLoYU84CgldL6utQrnE5WbT3MM7rUc
v6SQNF8wQpc8kb6uztOb6sQ3n7JBApZIPKrAC/8OFL5vLaUOICQyufbKZAfyeFukb
0NFGJG764+Rkbxq0fvAu6iQd/WD29PYgu03PjfVM8ybcIB99X07vhADBSk8VnhSqL
wr9YKiaZlmFu5GHQFV2EMk5I54jpuUOGn4j3+OQmwcMZe1X6kX+ka5Q+Q/iig3hMZ
jK9fCTukwhpWjUi4gtxT5Pz/WWKi1iK3t/C+5Vn5sPA3icWcHgFbZLpBLP+WRBHg7
33OUIbSjWQmiQ27zP+3M5caTYnmS/kXGByP74b/+czS7MnYUTehWa/STBxI3rKyPI
j9qx8PcpHtxgZOZXBBbkYOV6E+7bPvwNaCuFpHEeJ8aZ0twVJFcM0NsSH/UCM9NQh
ABvciyD646QcnZ/r6x65Vae6Mtd+1c/jgY+aPCYUkCnprGPQ569P/FagxRSf5FhwQ
qxFDJxkNSdnABnpc5SnwJ2ovm1PuIvnIyhHrL5GZ34oY6qciDn1TGEzd4gsftmiAE
dZORViwQxXoKi9AetP68mkn68pjfmOrQcInbDXtbWouJ5ran/Z1qslbNf2EcMaUq4
t7kljW5+C34s3RcSuuUcx6H/KbmfpS5FQDW7Sott5P3j3u3Q9JXXEKn8+Q1Fxd2xW
W7UhjKp9fRFMb1pvL10n81ZR6xlfp1Bfz1r95MoJtbrFKdVXsbOshm0hIk4Z3iwKz
iLaTHhoHq+1cT++qKZPaUo46SU8WI+FCBbJO/muY7l/4Z/ehY3IAKv3WMiVKfPkYl
dY5E3msj1R04CvUsRwP7TzsFrZRaIctF2TSMujXguuBE4nPi0TVp2xRukwMW1I7Fx
Q==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-19
 */

# IMPORTANT
# The log files have to be named YYYY_MM_DD_HH.log and need to be cleared (zeroed) every hour and dumped at least every hour (every minute is also possible)
# Please see the file INSTALL

# ---

# TODO: trennen zwischen server (ip1, ip2) ermöglichen...
# TODO: prüfen lassen ob die config von ipfm korrekt ist (dumped every 1 hour)
# TODO: monat vor/zurückscrollen? (mon/year)
# TODO: anstelle "year .... overview" lieber "last 12 months"
# TODO: Some hosts might reset the traffic counter at any other day of the month. Should we also support this?

# ---

declare(ticks=1);

# Attention: This constant must be a valid Nagios UOM!
# TODO: Make it configurable via command line? Should we use the UOM from the w/c/l arguments?
define('OUTPUT_UOM', 'GB');

# ---

class IpFlowMonitorCheck extends VNag {
	protected $argLogDir = null;
	protected $argLimit = null;

	public function __construct() {
		parent::__construct();

		$this->registerExpectedStandardArguments('Vhtwc');

		$this->getHelpManager()->setPluginName('check_ipfm');
		$this->getHelpManager()->setVersion('1.0');
		$this->getHelpManager()->setShortDescription('This plugin checks the network traffic monitored by ipfm.');
		$this->getHelpManager()->setCopyright('Copyright (C) 2011-$CURYEAR$ Daniel Marschall, ViaThinkSoft.');
		$this->getHelpManager()->setSyntax('$SCRIPTNAME$ [-w currentGB[,expectedGB]] [-c currentGB[,expectedGB]] [-l limitGB] [-L path]');
		$this->getHelpManager()->setFootNotes('If you encounter bugs, please contact ViaThinkSoft at www.viathinksoft.com');

		// This is the amount of traffic which is free. Every byte above this limit will cost additional money.
		// You should therefore set your warning and critical limit below this limit.
		// Don't set this argument if you have an unlimited connection.
		// This value is only for visual output. It does not influence the OK/Warning/Critial result.
		$this->addExpectedArgument($this->argLimit = new VNagArgument('l', 'limit', VNagArgument::VALUE_REQUIRED, 'limit', '"Traffic inclusive" limit (e.g. 10TB)', null));

		$this->addExpectedArgument($this->argLogDir = new VNagArgument('L', 'logdir', VNagArgument::VALUE_REQUIRED, 'path', 'Location of the ipfm log dir (Default: /var/log/ipfm)', '/var/log/ipfm'));

#		$this->warningSingleValueRangeBehaviors[0]  = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->warningSingleValueRangeBehaviors[1]  = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->criticalSingleValueRangeBehaviors[0] = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
#		$this->criticalSingleValueRangeBehaviors[1] = self::SINGLEVALUE_RANGE_VAL_GT_X_BAD;
	}

	protected function interprete_ipfm_logfile($logfile) {
		$res = array();

		$ary = file($logfile);
		foreach ($ary as $a) {
			$a = trim($a);
			if ($a == '') continue;
			if (substr($a, 0, 1) == '#') continue;
			$a = preg_replace("|\s+|ism", ' ', $a);
			$bry = explode(' ', $a);
			$res[$bry[0]] = array($bry[1], $bry[2], $bry[3]);
		}

		return $res;
	}

	protected function cbRun($optional_args=array()) {
		if (!defined('USE_DYGRAPH')) define('USE_DYGRAPH', false);

		$logDir = $this->argLogDir->getValue();
		if (!is_dir($logDir)) throw new Exception("Log dir $logDir not found");

		$monthLimit = $this->argLimit->getValue();
		if (!is_null($monthLimit)) {
			$p = (new VNagValueUomPair($monthLimit))->normalize(OUTPUT_UOM);
			$monthLimitValue = $p->getValue();
			$monthLimitUom = $p->getUom();
		}

		ob_start();

		?>

		<script type="text/javascript">
			google.load("visualization", "1", {packages:['imageareachart', 'corechart']});
			google.setOnLoadCallback(drawChart);
			function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Year');
				data.addColumn('number', 'Total');
				data.addColumn('number', 'In');
				data.addRows([

		<?php

		$day = date('d');
		$mon = date('m');
		$year = date('Y');

		$didata = '';
		$logfiles = glob($logDir.'/*.log');
		sort($logfiles);

		$monthtotal[$year][$mon] = 0;
		$daystotal = array();
		$max = 0;
		$first = true;
		foreach ($logfiles as $logfile) {
		        $stat = $this->interprete_ipfm_logfile($logfile);

			$date = '';
			if (preg_match('/^.*_00\.log$/', $logfile)) {
				preg_match('@(\d{4})_(\d{2})_(\d{2})@ismU', $logfile, $m);
				$t_day = $m[3];
				$t_mon = $m[2];
				$t_year = $m[1];
				// $date = "$t_day.$t_mon.$t_year";
				$date = $t_day;
			//} else if (preg_match('/^.*_(02|04|06|08|10|12|14|16|18|20|22)\.log$/', $logfile, $m)) {
			//	$date = $m[1].':00';
			}

			$in = $out = $total = 0;
			foreach ($stat as $s) {
				$in += $s[0];
				$out += $s[1];
				$total += $s[2];
			}

			$in    = (new VNagValueUomPair($in.'B'))->normalize(OUTPUT_UOM)->getValue();
			$out   = (new VNagValueUomPair($out.'B'))->normalize(OUTPUT_UOM)->getValue();
			$total = (new VNagValueUomPair($total.'B'))->normalize(OUTPUT_UOM)->getValue();

			if (preg_match('/^.*(\d{4})_(\d{2})_(\d{2})_(\d{2})\.log$/', $logfile, $m)) {
				$t_year = $m[1];
				$t_mon = $m[2];
				$t_day = $m[3];
				$t_hour = $m[4];
				if (isset($monthtotal[$t_year][$t_mon])) {
					$monthtotal[$t_year][$t_mon] += $total;
				} else {
					$monthtotal[$t_year][$t_mon] = $total;
				}

				if (($t_year == $year) && ($t_mon == $mon)) {
					if (isset($daystotal[$t_day])) {
						$daystotal[$t_day] += $total;
					} else {
						$daystotal[$t_day] = $total;
					}
					if (!$first) echo ","; else $first = false;
					echo "['$date', ".round($total,2).", ".round($in,2)."]\n";
					$didata .= '"'.$t_year.'-'.$t_mon.'-'.$t_day.' '.$t_hour.':00:00,'.round($in,2).','.round($out,2).','.round($total,2).'\n" +'."\n";
				}
			}

			if ($total > $max) $max = $total;
		}

		$num_days_in_month = date('t', mktime(0, 0, 0, intval($mon), 1, intval($year)));
		$expected = $monthtotal[$year][$mon]/$day * $num_days_in_month;

		?>
			]);

			var chart = new google.visualization.ImageAreaChart(document.getElementById('chart_div'));
		<?php if (!USE_DYGRAPH) { ?>
			chart.draw(data, {width: 800, height: 440, min: 0, max: <?php echo $max; ?>, title: 'Traffic in <?php echo $mon.'/'.$year; ?> [in <?php echo OUTPUT_UOM; ?>/h]'});
		<?php } ?>

			// ---

			var data_month = new google.visualization.DataTable();
			data_month.addColumn('string', 'Day');
			data_month.addColumn('number', 'Total');

			data_month.addRows([
		<?php

		$first = true;
		foreach ($daystotal as $t_day => $traffic) {
			if (!$first) echo ","; else $first = false;
			echo "['$t_day.$mon.$year', ".round($traffic,2)."]\n";
		}

		?>
			]);

			var options_month = {
				width: 800, height: 440,
				vAxis: { viewWindow: { min: 0 } },
				title: 'Month overview for <?php echo $mon.'/'.$year; ?> [GiB]'
			};

			var chart_month = new google.visualization.ColumnChart(document.getElementById('chart_div3'));
			chart_month.draw(data_month, options_month);

			// ---

			var data_year = new google.visualization.DataTable();
			data_year.addColumn('string', 'Month/Year');
			data_year.addColumn('number', 'Total');

			data_year.addRows([
		<?php

		ksort($monthtotal); // First sort by year

		$first = true;
		foreach ($monthtotal as $t_year => $x) {
			ksort($x); // Then sort by month
			foreach ($x as $t_mon => $traffic) {
				if ($t_year != $year) continue; // Only this year

				$date = "$t_mon/$t_year";

				if (!$first) echo ","; else $first = false;
				echo "['$date', ".round($traffic,2)."]\n";
			}
			echo ",['Expected in $mon/$year', $expected]\n";
		}

		?>
			]);

			var options_year = {
				width: 800, height: 440,
				vAxis: { viewWindow: { min: 0 } },
				title: 'Year <?php echo $year; ?> overview [GiB]'
			};

			var chart_year = new google.visualization.ColumnChart(document.getElementById('chart_div2'));
			chart_year.draw(data_year, options_year);
		}

		</script>

		<?php if (USE_DYGRAPH) { ?>
			<script type="text/javascript">
				g = new Dygraph(
					document.getElementById("graphdiv"),
					"Date,In,Out,Total\n" +
					<?php echo $didata; ?>""
				);
			</script>
		<?php } ?>

		<?php

		if (!is_null($monthLimit)) {
			echo "<p>Constraint: Max ".$monthLimitValue.' '.OUTPUT_UOM."/Month</p>";
		}

		$current = $monthtotal[$year][$mon];

		$fontcolor = 'green';
		if ($this->checkAgainstCriticalRange($current.OUTPUT_UOM, false, true, 0)) $fontcolor = 'red';
		if ($this->checkAgainstWarningRange($current.OUTPUT_UOM, false, true, 0))  $fontcolor = '#FF8000';

		if (!is_null($monthLimit)) {
			echo "<p>This month ($mon/$year): <font color=\"$fontcolor\">".round($current,0)." ".OUTPUT_UOM." (".round($current/$monthLimitValue*100,2)."%)</font></p>";
		} else {
			echo "<p>This month ($mon/$year): <font color=\"$fontcolor\">".round($current,0)." ".OUTPUT_UOM."</font></p>";
		}

		$fontcolor = 'green';
		if ($this->checkAgainstCriticalRange($expected.OUTPUT_UOM, false, true, 1)) $fontcolor = 'red';
		if ($this->checkAgainstWarningRange($expected.OUTPUT_UOM, false, true, 1))  $fontcolor = '#FF8000';

		if (!is_null($monthLimit)) {
			echo "<p>Expected for this month: <font color=\"$fontcolor\">".round($expected,0)." ".OUTPUT_UOM." (".round($expected/$monthLimitValue*100,2)."%)</font></p>";
		} else {
			echo "<p>Expected for this month: <font color=\"$fontcolor\">".round($expected,0)." ".OUTPUT_UOM."</font></p>";
		}

		?>

		<div id="chart_div"></div>
		<div id="graphdiv"></div>
		<div id="chart_div3"></div>
		<div id="chart_div2"></div>

		<?php

		$html = ob_get_contents();
		ob_end_clean();

		$this->outputHTML($html, true);

		if (!is_null($monthLimit)) {
			// TODO: should we put a percentage at "expected"? or if it exceeds, should we show how much it would exceed?
			// TODO: It would be good if the UOM for displaying would be automatically detected, e.g. 200 MB / 10 TB instead of 0 GB / 10240 GB
			$this->setHeadline(round($current,0).' '.OUTPUT_UOM." / ".$monthLimitValue.' '.OUTPUT_UOM." (".round($current/$monthLimitValue*100,2)."%, expected ".round($expected,0).' '.OUTPUT_UOM.") traffic used this month ($mon/$year)", true);
		} else {
			$this->setHeadline(round($current,0).' '.OUTPUT_UOM." (expected ".round($expected,0).' '.OUTPUT_UOM.") traffic used this month ($mon/$year)", true);
		}

		$warn = is_null($this->getWarningRange(0)) ? null : $this->getWarningRange(0)->end->normalize(OUTPUT_UOM)->getValue();
		$crit = is_null($this->getCriticalRange(0)) ? null : $this->getCriticalRange(0)->end->normalize(OUTPUT_UOM)->getValue();
		$this->addPerformanceData(new VNagPerformanceData('Current', $current.OUTPUT_UOM, $warn, $crit, 0, is_null($monthLimit) ? null : $monthLimitValue));

		$warn = is_null($this->getWarningRange(1)) ? null : $this->getWarningRange(1)->end->normalize(OUTPUT_UOM)->getValue();
		$crit = is_null($this->getCriticalRange(1)) ? null : $this->getCriticalRange(1)->end->normalize(OUTPUT_UOM)->getValue();
		$this->addPerformanceData(new VNagPerformanceData('Expected', $expected.OUTPUT_UOM, $warn, $crit, 0, is_null($monthLimit) ? null : $monthLimitValue));
	}
}
