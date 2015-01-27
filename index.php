<?php
/*
function shutdown () {
    echo "Shutdown";
};
register_shutdown_function("shutdown");
*/

ini_set('display_errors', 'On');
error_reporting(E_ALL ^E_NOTICE);

/* ==================
 * Include libraries and settings:
 * ==================
 */
define("BASE_DIR", dirname(__FILE__));
// --- Interfaces
require_once BASE_DIR . "/interfaces/FetchValues.php";
require_once BASE_DIR . "/interfaces/CUD.php";
// --- Libraries
require_once BASE_DIR . "/libraries/KIRJASTOSettings.php";
require_once BASE_DIR . "/libraries/KIRJASTOAuthenticate.php";
include_once BASE_DIR . "/libraries/KIRJASTOImprovedExceptions.php";
include_once BASE_DIR . "/libraries/KIRJASTOVariablesBasedOnDB.php";
include_once BASE_DIR . "/libraries/HACHI_logging.php";
// --- site-classes
require_once BASE_DIR . "/classes/userSettings.class.php";
// ====================
// === SET error and exception handling ===
// The error handler only shows one error correctly :(
//$__ErrorHandler = new ErrorHandler;
//set_error_handler(array(&$__ErrorHandler, 'raiseError'));
// Sets the default Exception handler to the Improved Exceptions:
//ImprovedExceptions::setHandler();
//register_shutdown_function('shutdown');
// ===

$logFormatter = new HACHI_LogShowFormatter();
$logger = new HACHI_logging(BASE_DIR."/logs/siteLogging", $logFormatter);

$session_on = 0;
$title = _("elefant money saver");
$desc = _("Personal budget managing website");
$keywords = _("personal budget, financial situation, saving money");
$charset = "UTF-8";
$hidden = 1;
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
        $logger->emailLog(_("Problem in authentication")."(".$code."): ".$msg);
        $auth->redirect($settings->PATH['site']."login.php");
    }    
}

// We define the user ID as a constant that we use throughout the program:
define ('USER_ID', $auth->getUserID());

// We make the object for user settings (like handling internationaliztaions):
$userSettings = new UserSettings(USER_ID, $dataSource);

// Set language to settings class:
// The first line is simply to follow coding-rules and suppress PHP errors:
$setLang = (!empty($_GET['lang'])) ? $_GET['lang'] : NULL;
$settings->setLang($setLang);

// ----------------- Check the validity of requested site otherwise send user to login-screen
// First we check the normal sites:
$validSivu = 0;
$sivu = null;
if(!empty($_GET['s'])) {
    foreach($settings->SALLITUT_SIVUT as $sivut) {
        if($sivut == $_GET['s']) {
            $sivu = $settings->PATH['frontend'].$_GET['s'].".php";
            $validSivu = 1;
            $settings->setSivu = $_GET['s'];
        }
    }
}
// Since the user has been authenticated and it seems the page is not set right, it is assumed to be mainview-page
if($sivu === null) {
    $sivu = $settings->PATH['frontend']."mainview.php";
    $validSivu = 1;
}

// If the are simply no pages being accessed that we have:
if($validSivu===0) {
    $auth->redirect($settings->PATH['site']."login.php");
}

$shortCountryTag = "fi";
$locale = "fi_FI";
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain("messages", $settings->PATH['absolute']."locale");
bind_textdomain_codeset("messages", 'UTF-8');
textdomain("messages");

// HEAD-elements and meta-tags
$settings->metaData(3, "Janne Hyytiä");
?>
<link rel="stylesheet" type="text/css" href="<?= $settings->PATH['site'] ;?>css/theme/jquery-ui-1.9.2.custom.min.css" />
<link rel="stylesheet" type="text/css" href="<?= $settings->PATH['site'] ;?>css/saastonorsu.css" />
</head>
<body>
    <div class="websiteContainer">
<?php

// TOP-PART
include_once($settings->PATH['parts']."top.php");

// CONTENT-PART
?>
    <div class='content'>
<?php
    include_once($sivu);
    include_once($settings->PATH['parts']."bot.php");
?>
    </div>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/jquery-ui.min.js"></script>
    <script src="http://testi.saastonorsu.fi/js/jquery-blockUI.js"></script>
    <script src="http://testi.saastonorsu.fi/js/datepicker/jquery.ui.datepicker-fi.js"></script>
    <script src="http://testi.saastonorsu.fi/js/libraries.js"></script>
    <script src="http://testi.saastonorsu.fi/js/languages/<?= $locale ;?>.js"></script>
    <script src="http://testi.saastonorsu.fi/js/saastonorsu.js"></script>
    </div>
</body>
</html>