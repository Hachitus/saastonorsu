<?php

include_once($_SERVER["DOCUMENT_ROOT"]."/libraries/KIRJASTOSettings.php");
$settings = new Settings(0);
$auth = new Authenticate($dataSource, $_SERVER['SERVER_NAME'], "budjettiKeksi");

include_once("ajaxes.php");

// WHILE DEBUGGING!
include_once($_SERVER["DOCUMENT_ROOT"]."/libraries/KIRJASTODebugging.php");
$debug = new Debugging();

// If user isn't authenticated he should be directed to login page:
try{
    if(!$auth->authenticate()) {
        $auth->redirect($settings->PATH['site']."login.php");
    }
} catch (Exception $e) {
    if($e->getCode() == 1) {
        echo $e->getMessage();
    } elseif($e->getCode() == 2) {
        echo $e->getMessage();
    } elseif($e->getCode() == 3) {
        echo $e->getMessage();
    } elseif($e->getCode() == 4) {
        echo $e->getMessage();
    } elseif($e->getCode() == 900) {
        $debug->sendMail($e->getMessage());
        $auth->redirect($settings->PATH['site']."login.php");
    }    
    //$debug->sendMail("Ongelma authenticatessa (".$e->getCode()."): ".$e->getMessage());
    $auth->redirect($settings->PATH['site']."login.php");
}

$premadeID = $dataSource->filterVariable($_POST['preID']);
$tulos = mysqli_fetch_row($dataSource->query("SELECT mainCat, cost FROM jotain WHERE ID = '".$premadeID."'"));
$jsonCode = array (
    "mainCatID" => $tulos[0],
    "cost" => $tulos[1]
);
echo json_encode($jsonCode);

?>