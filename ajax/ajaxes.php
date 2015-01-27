<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * This is the base-class for all ajax-classes. Should be required at the start of an individual 
 * ajax-page. This will handle at least:
 * - the user authentication
 * - Database init
 * - Fetching and setting user-specific settings / options (like language / country)
 * - Fetching and setting site-specific settings / options
 * 
 * Pretty similar to the index-file actually.
 * 
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL ^E_NOTICE);

/* ==================
 * Include libraries and settings:
 * ==================
 */
define("BASE_DIR", $_SERVER["DOCUMENT_ROOT"]);
require_once BASE_DIR . "/interfaces/FetchValues.php";
require_once BASE_DIR . "/interfaces/CUD.php";
require_once BASE_DIR . "/libraries/KIRJASTOSettings.php";
require_once BASE_DIR . "/libraries/KIRJASTOAuthenticate.php";
require_once BASE_DIR . "/libraries/KIRJASTOImprovedExceptions.php";
require_once BASE_DIR . "/libraries/KIRJASTOUsers.php";
//include_once BASE_DIR . "/libraries/errorHandling/handler.php";
// ====================
// === SET error and exception handling ===
// The error handler only shows one error correctly :(
//$__ErrorHandler = new ErrorHandler;
//set_error_handler(array(&$__ErrorHandler, 'raiseError'));
// Sets the default Exception handler to the Improved Exceptions:
//ImprovedExceptions::setHandler();
//register_shutdown_function('shutdown');
// ===

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
// We need to set the proper charset-connection
$dataSource->set_charset("utf8");

// Authentication is a must:
$auth = new Authenticate($dataSource, $_SERVER['SERVER_NAME'], "budjettiKeksi");

// If user isn't authenticated he should be denied of ajax:
try{
    if(!$auth->authenticate()) {
        exit("not authenticated");
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
        exit("not authenticated");
    } else {
        $debug->sendMail("Ongelma authenticatessa (".$code."): ".$msg);
        exit("not authenticated");
    }    
}

// We define the user ID as a constant that we use throughout the program:
define ('USER_ID', $auth->getUserID());

const NEW_ENTRY = "new";
const MODIFY_ENTRY = "mod";
const DELETE_ENTRY = "del";

const LISTING = 1;

?>