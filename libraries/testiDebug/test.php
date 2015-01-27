<?php
header ('Content-type: text/html; charset=utf-8');
require_once 'class/odebugger.cls.php';

$odebug = new odebugger ('EN'); // French localization
//$odebug = new odebugger ('EN'); // uncomment this one to localize to English

$odebug -> CSS = 'default'; // set the CSS
$odebug -> HTML = 'default'; // set the HTML template
mysql_fetch_assoc ();
?>