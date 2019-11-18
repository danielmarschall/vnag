<?php /* <ViaThinkSoftSignature>
ycG9mZHfcSTDqS2+0fObOwa/a3LXTPK0tkNZEWg7OcXYcUhc/KJvOSM/O2d5l0HB2
IUwGs8OzGfFT8sGkjoVPRMmhi05iev4hnrAktO1CxXsLcWP4cijATLaxu+sOgNIhL
7c/bSJIctZdt9jl6C37xUaIiGgFjD1YJkk6L4gjBzmx0jeB4w+jIWd9A7bDUciH7l
dPMWFmHNLGXTjd9dgycLR/Jny8AxxvlWzBMZaovyxbhbkpYDVCeYLPeDXEagMr9Rs
Ye6sz2CFeVViat0/JM7cnRfUpdJS6AV/kqvz0r4YzJKIRht+IaoifjsNTglU1d6hI
+RoltKtaLcwsFU+Obykfr3x5PP7p71w8/NlOagaYkaUer001wFO2wNVBAPUmmk+rU
oJLGT6//J1LCLg6i+WkAti9Sn0Ec4jNq7NEjMDLpzQmHavyxmQkfiUwaOLaEKupBb
CXaJK3bwk6bGvywPJ+cj2ktqWFRNhxAyu6mIUTOciKuR1lAheQnlGOJAe824AbQNI
AlEIavBz8dboFSvfmbuJNMex9a+963egDbp/zIkIf9StT9DT/rRTGkfcHf+L15gjO
N+Zdlg5djctscLC/MCf65gRiryVgbj1M8BJZOZbxav+LjVyWpHbRZ6yjNw/B1VU99
SubHkbqvGzsKMVhsbBj4+kMJ3qQFHUW5WC5Gi7aI3zgRDsHRMFirU1dvke+YOpOuv
TcO0S2QTYCldGRrt7aYBOhUATMvIgzdQQ3NVc73a8aFqLBPLLYcVH/OqPVz0Jrbvi
Ovlb340NTXEs/Yym/GRA94Am5z0Do3w58dugHnsWxUFbefBCmk8YGPXmFGpdsWOET
SuFgSCSvs/7gL4gOFuyUCFPF9PeJ20ML78Z4yKOnzldug2W7BMjwvrgEWkJQbtVCo
xGJQ8nh06cvVIhNWNzHmdlDxcIR343mYYhzU07BgUX+jB+ecNUK94hkY7vBNU5DzQ
S/KeaEmbzuD5wNCnrdKmsi7sSQcg4m/MMzId+dz26Xdly5eP1kZlOWsiZr7O9W+8m
9jo30pC7fEfOkkApHjGpOY6azHhZIHxZGbNYniGADMPePxsQFo7xgqidJrKEDdFa+
DA4rb2cK23EWgyViZ4NwfctT6KA8bBqUE2KZ2ncsf8lVQeIyDLFQY3OeLnhNkzDhc
pzQMGdX017pM9Lkd5hJ2rbJv6gEL9ifVts+TFC9mDIwYeCoglG58wJTFpwIVPMhHy
0MBCswq/Wr0Z42paHzDoTvpxfrLXFVJSY9I+JcPecVDwRBPylnr4/n419gDV2dQpq
ZddTLjV/DdGTWBObecZaOSEIQffScHqo39Q4mb1vKuhS2Uw2neUbDK8ZZWDU8kZdG
w==
</ViaThinkSoftSignature> */ ?>
<?php

/*
 * VNag - Nagios Framework for PHP
 * Developed by Daniel Marschall, ViaThinkSoft <www.viathinksoft.com>
 * Licensed under the terms of the Apache 2.0 license
 *
 * Revision 2019-11-18
 */

// This file (index.php) is intended to be called by your browser.
// If you want to access via Nagios/Icinga or CLI, call "check_ping".

declare(ticks=1);

?><!DOCTYPE HTML>

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>Online ping</title>
	<meta name="robots" content="noindex">
</head>

<body>

<h1>Online ping</h1>

<?php

echo '<form action="'.htmlentities($_SERVER['SCRIPT_NAME']).'" method="GET">
Host: <input type="text" name="H" value="'.htmlentities($_REQUEST['H']).'" size="40">
<input type="submit" value="Check">
</form>';

require_once __DIR__ . '/../../framework/vnag_framework.inc.php';
require_once __DIR__ . '/PingCheck.class.php';

$job = new PingCheck();
$job->http_visual_output    = VNag::OUTPUT_EXCEPTION;
$job->http_invisible_output = VNag::OUTPUT_ALWAYS;
$job->run();
unset($job);

?>
</body>

</html>
