<?php
if(!isset($argv[1])) {
    echo "Provide password as argument".PHP_EOL;
    exit();
}
$password = $argv[1];
require_once("../libraries/KIRJASTOUsers.php");
$var = Users::createPassword($password);
echo $var;
?>
