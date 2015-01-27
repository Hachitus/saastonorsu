<?php
/*
 * Accepts these POST-variables:
 * Receipt / general:
 *    receiptCost, mainCatID, datepicker, subCatID, extraCatIDs, place, addAsPremadePlace, paymentID, info
 * Individual products:
 *    productName, productCost, productMainCatID, productSubCatID, productExtraCatIDs, productWarranty, productVAT, productInfo, newPremadeProduct
 * - When individual products are given, PHP will require productName AND productCost
 */
// var_dump($_POST);
ini_set('display_errors', 'On');
error_reporting(E_ALL ^E_NOTICE);

/* ==================
 * Include libraries and settings:
 * ==================
 */
define("BASE_DIR", $_SERVER["DOCUMENT_ROOT"]);
require_once BASE_DIR . "/interfaces/FetchValues.php";
require_once BASE_DIR . "/libraries/KIRJASTOSettings.php";
include_once BASE_DIR . "/libraries/KIRJASTOLanguages.php";
require_once BASE_DIR . "/libraries/KIRJASTOAuthenticate.php";
require_once BASE_DIR . "/classes/userSettings.php";
include_once BASE_DIR . "/libraries/KIRJASTOImprovedExceptions.php";
//include_once BASE_DIR . "/libraries/errorHandling/handler.php";
include_once BASE_DIR . "/libraries/KIRJASTOVariablesBasedOnDB.php";
// ====================
// === SET error and exception handling ===
// The error handler only shows one error correctly :(
//$__ErrorHandler = new ErrorHandler;
//set_error_handler(array(&$__ErrorHandler, 'raiseError'));
// Sets the default Exception handler to the Improved Exceptions:
//ImprovedExceptions::setHandler();
//register_shutdown_function('shutdown');
// ===

$debug = new Debugging();

$session_on = 0;
$title = "Säästönorsu";
$desc = "Henkilökohtainen budjettisivusto";
$keywords = "budjetti, taloustilanne";
$charset = "UTF-8";
$hidden = "1";
$errorLogging = 0;
// NOTE: We activate errors on the operational site
$settings = new Settings($session_on, $title, $desc, $keywords, $charset, $hidden, $errorLogging);
// We setup own variable for data-connection:
$dataSource = &$settings->DBConn;
SetupDB::setDB($dataSource);
// We need to set the proper charset-connection
$dataSource->set_charset("utf8");

// Authentication is a must:
$auth = new Authenticate($dataSource, $_SERVER['SERVER_NAME'], "budjettiKeksi");

// If user isn't authenticated he should be directed to login page:
try{
    if(!$auth->authenticate()) {
        $auth->redirect($settings->PATH['site']."login.php");
    }
} catch (Exception $e) {
    $code = $e->getCode();
    $msg = $e->getMessage();
    $trace = $e->getTrace();

    if($code == 1) {
        echo $msg;
    } elseif($code == 2) {
        echo $msg;
    } elseif($code == 3) {
        echo $msg;
    } elseif($code == 4) {
        echo $msg;
    } elseif($code === 900) {
        $auth->redirect($settings->PATH['site']."login.php?error=IP");
    } else {
        $debug->sendMail("Ongelma authenticatessa (".$code."): ".$msg);
        $auth->redirect($settings->PATH['site']."login.php");
    }    
}

// We define the user ID as a constant that we use throughout the program:
define ('USER_ID', $auth->getUserID());










include_once($settings->PATH['classes']."products.php");
$premades = new PremadeProducts(USER_ID);
include_once($settings->PATH['classes']."categories.php");
$extraCategories = new ExtraCategories(USER_ID);
// WHILE DEBUGGING!
include_once($settings->PATH['libraries']."KIRJASTODebugging.php");
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

$tulos = $premades->getByID($dataSource->filterVariable($_GET['ID']));
$jsonCode = array (
    "name" => $tulos['name'],
    "cost" => $tulos['cost'],
    "mainCat" => $tulos['mainCat'],
    "subCat" => $tulos['subCat'],
    "extraCats" => $tulos['extraCats'],
    "VAT" => $tulos['VAT']
);

echo json_encode($jsonCode);

?>