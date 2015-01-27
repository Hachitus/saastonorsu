<?php
header ('Content-type: text/html; charset=utf-8');
require_once 'class/odebugger.cls.php';
$odebug = new odebugger ('FR');

$odebug -> HTMLLOG = 'default_log'; // set the HTML
$odebug -> CSSLOG = 'default_log'; // set the CSS
$odebug -> REALTIME = false; // set realtime off
$odebug -> LOGFILE = false; // set log to file off

/**
* example of how to display an existing log file
*/
$odebug -> loadXML ('20060614_44902a9c9cee1_error_log.xml'); // here, put an existing log file name (the basename).

echo $odebug -> showLog ();
?>