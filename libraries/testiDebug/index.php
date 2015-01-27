<?php
header ('Content-type: text/html; charset=utf-8');
require_once 'class/odebugger.cls.php';

$odebug = new odebugger ('FR'); // French localization
//$odebug = new odebugger ('EN'); // uncomment this one to localize to English

$odebug -> CSS = 'default'; // set the CSS
$odebug -> HTML = 'default'; // set the HTML template

/**
* realtime example
*/
echo 'bla';
echo '<br />', 'bli';
echo $a; // Gasp, undefined!

echo '<br />truc';
echo '<br />oula';
echo $b; // bad girl : again undefined!

//$odebug -> ERROR = false; // test to see if turning off the class error handler is effective. And, indeed, it is :-) (commented line, hey, remove to see)
echo $aTab[nom];
//$odebug -> ERROR = true; // switching it back on (commented line, hey, remove to see)

// Let's see how it handles user defined errors (no translation, no suggestion, of course)
if (!isset ($c)) {
	trigger_error ('La variable c n\'est pas définie', E_USER_WARNING);
}

echo 'LINES : ', $odebug -> LINES;

// Let's see how it handles exceptions !
if (!isset ($e)) {
	throw new Exception('Lancement d\'une exception !');
}

echo file_get_contents ('idonotexist.php'); // this file does not exist!


?>