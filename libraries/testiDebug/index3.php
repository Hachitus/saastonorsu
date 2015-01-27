<?php
header ('Content-type: text/html; charset=utf-8');
require_once 'class/odebugger.cls.php';
$odebug = new odebugger ('FR');
$odebug -> CSS = 'default';
$odebug -> HTML = 'default';

$odebug -> LOGFILE = false; // set log to file off

/**
* example on how to check a given code (here, odebugger will evaluate the code in the 'test.php' file)
*/
$odebug -> checkCode ('test.php'); // or
//$odebug -> checkCode (file_get_contents ('test.php'));
?>