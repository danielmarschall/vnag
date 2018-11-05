<?php /* <ViaThinkSoftSignature>cI1s2a3mT+MdTE7/qLxngIG1+5QjriFFNzaY7TpgSXizwFFodagYOOls59lMWI8A3S6h2OfgqP4Bxc5FISEhEj+Ry86HfMDROJuVwjUPlEF8sJX50f0t5aAbA6do23tq1USmXJZmeDF8GEWXu/evWQOXEvTZs6lLKEnYZVcT1G48fHosKIX8bB6rSlJ2dtblxUeFVd3u91TUiqWym3T2k/U8S9/dbrB0KWQf18BJeai+08R8rY8s5/oOuL0KJAimdUCECOlPvOH+QMxPShNEhmMTebJsnHZJYyJN6uDre0nDvqW8g+F3SfbwudblG8+7ng0spQRtoa0rLhEON2H68Wd81UUIRd+Ypx5GzwPaCKFMJ0vKQGtN2fqUa8okw/ssOtO8o449euHaC4QDRkZpMfO25EZvT0syL/aSaiSqpsadUJ8PF38CpOWenTGimaER3vUJf1aA2X/QCNlun6BxL+cb2rTHfgvtjD02W4xyZebAiRIY5n7RkMds/7OVizPiwqU9AcO4XNlTGJ6nqlmDzSPj0dYQDZrfYxsh08ahucmG45qNRJwiK1yf2pMoSVsuCoi4orOehMinxcoKW2uTUddXmcIfxL1UN2TO2cZ4m3dWq4D6/aQSnsPH7sZ4UbQ9RCSF+zuQRvUgxxa2A1mRK3eJOhA2Ne/dYSpsG5dxE7rqLNo1rc5BQw9mOF2fWlfjvwp8vtab0xO/XWukOcaX+rwAQ7nQcugfnxgZRpo7aghvysheDPHue81g4E1NuoiiakguHtg2uyThBCUWwUsvTkuvXTnK1hCpfB9lTYDu8fi4BzTXNhunCIyQpTVMcox7vkxaMe3dYKqmI1P8nmRiivQuZNO8lzFxB3nuLLrVx3b2EnOOAEcpmtXF0ir1iWHSV90DiP/4VhBH8phrKHT66FJmHdGwy18/yPjmNQWp02E7uz/sSoK1peKxsK3FJ1sfUsWAny1DFN8bxN6qpuJvDwF9mJ+H7Szir8JJF/Bt+xBdm68qvEvJpahhfK87FWR8r52nl5kBDveXD167KyuYA/YHlJQnzQcqD3488YUbecRXNojN+x2UVAyZmAcANDejPWdsK0LVXwAZoFKTZ0CUzzZGdFPA+GqeSz3zx+2CpFb2b+7akGuEcRAozWCemeuPd2JGu7r8jA1iTShEGru7X11aBIyRHd/crpijEEyGLdCOZ2prRf5E4Vd+cMOMsuu7di6jvEvp+ttNJTMJ9N+0RnIp+k8h0Z6a1pqT1/t+OYNPCeeSgimtlj6/JktSVDASjKUY2wyQxKTsHez2R9khCrsz+PMroJCp5lSbQLKoUMIlrtVgwWbohnJeN1ag1RlyizLl62l8tP2c7SyWJlL8ag==</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2018-07-19
 */

// This file (index.php) is intended to be called by your browser.
// If you want to access via Nagios/Icinga or CLI, call "check_ipfm".

declare(ticks=1);

define('USE_DYGRAPH', false); // Slow!
define('ALLOW_HTTP_PARAMTER_OVERWRITE', false); // true: Allow the user to set their own ?w=...&c=... etc.

if (!ALLOW_HTTP_PARAMTER_OVERWRITE) {
	$_REQUEST['L'] = '/var/log/ipfm';
	$_REQUEST['l'] = '10TB'; // LeaseWeb gives us 10 TB free traffic
	$_REQUEST['w'] = '6TB,8TB';
	$_REQUEST['c'] = '8TB,15TB';
}

?><!DOCTYPE HTML>

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<meta http-equiv="refresh" content="300; URL=<?php echo isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; ?>">
	<title>Traffic monitor</title>
	<?php if (USE_DYGRAPH) { ?>
	<!--[if IE]>
		<script type="text/javascript" src="excanvas.js"></script>
	<![endif]-->
	<script type="text/javascript" src="dygraph/dygraph-combined.js"></script>
	<?php } ?>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<meta name="robots" content="noindex">
</head>

<body>

<h1>Traffic monitor</h1>

<?php

require_once __DIR__ . '/../../framework/vnag_framework.inc.php';
require_once __DIR__ . '/IpFlowMonitorCheck.class.php';

$job = new IpFlowMonitorCheck();
$job->http_visual_output    = VNag::OUTPUT_EXCEPTION;
$job->http_invisible_output = VNag::OUTPUT_ALWAYS;
$job->run();
unset($job);

?>
</body>

</html>
