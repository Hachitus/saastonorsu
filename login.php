<?php
function shutdown () {
    echo "shutdown";
};

ini_set('display_errors', 'On');
error_reporting(E_ALL ^E_NOTICE);

/* ==================
 * Include libraries and settings:
 * ==================
 */
define("BASE_DIR", dirname(__FILE__));
require_once BASE_DIR . "/interfaces/FetchValues.php";
require_once BASE_DIR . "/libraries/KIRJASTOSettings.php";
include_once BASE_DIR . "/libraries/KIRJASTOLanguages.php";
require_once BASE_DIR . "/libraries/KIRJASTOAuthenticate.php";
include_once BASE_DIR . "/libraries/KIRJASTOImprovedExceptions.php";
//include_once BASE_DIR . "/libraries/errorHandling/handler.php";
include_once BASE_DIR . "/libraries/HACHI_logging.php";
// ====================
// === SET error and exception handling ===
//$__ErrorHandler = new ErrorHandler;
//set_error_handler(array(&$__ErrorHandler, 'raiseError'));
// Sets the default Exception handler to the Improved Exceptions:
// ImprovedExceptions::setHandler();
// ===

$logFormatter = new HACHI_LogShowFormatter();
$logger = new HACHI_logging("logs/siteLogging", $logFormatter);

$session_on = 0;
$title = _("elefant money saver");
$desc = _("Personal budget managing website");
$keywords = _("personal budget, financial situation, saving money");
$charset = "UTF-8";
$hidden = "1";
$errorLogging = 0;
// NOTE: We activate errors on the operational site
$settings = new Settings($session_on, $title, $desc, $keywords, $charset, $hidden, $errorLogging);

// We setup own variable for data-connection:
$dataSource = &$settings->DBConn;
// We need to set the proper charset-connection
$dataSource->set_charset("utf8");

$loginStatsOn = 1;
$wrongLogin = "";

/* -------------
 * User authentication.
 * -------------
 * The authentication class works with exceptions so we need to catch the whole lot
 * 
 * 1) If he just wants to logout we handle that here also and we do it before the other functionality
 * 2) First we test has he tried to login with this: "if($_POST['username'])"
 * 3) If the user didn't try to login we need to verify if he already has access to the site,
 * in this case we redirect him to the mainview
 * 
 */
$auth = "";
$auth = new Authenticate($dataSource, $_SERVER['SERVER_NAME'], "budjettiKeksi");

// NUMBER 1) First we test if the user wants to logout. No reason to go further in the code, if he doesn't:
if($_GET['logout']==1) {
    $auth->logout($settings->PATH['site']."login.php");
}

try {
    if($_POST['username']) { // NUMBER 2)
        if($auth->login($_POST['username'], $_POST['password'], $loginStatsOn))
        {
            $auth->redirect($settings->PATH['site']."index.php");
        }
        else {
            $wrongLogin = "<p><b>"._("Wrong username or password")."</b></p>";
        }
    }
    elseif($auth->authenticate()) { // NUMBER 3)
        $auth->redirect($settings->PATH['site']."index.php");
    }
} catch (Exception $e) {
    $code = $e->getCode();
    $msg = $e->getMessage();
    if($code == 1) {
        echo $msg;
    } elseif($code == 2) {
        echo $msg;
    } elseif($code == 3) {
        echo $msg;
    } elseif($code == 4) {
        echo $msg;
    } elseif($code == 900) {
        // IP-address has been changed, we deal this with GET-variable error=IP
    } else {
	echo $e;
        //$logger->emailLog(_("Problem in authentication")."(".$code."): ".$msg);
        $auth->redirect($settings->PATH['site']."login.php");
    }
}
// -------------
// Set language to settings class. Need to be done before setting up languages class:
$settings->setLang($_GET['lang']);

// KIRJASTO-PART OVER
// BANNER-PART
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<?php
// HEAD-elements and meta-tags
$settings->metaData(3, "Janne Hyytiä");
?>
<link rel="stylesheet" type="text/css" href="css/saastonorsu.css" />
</head>

<body>
    <div class="websiteContainer">
<?php
// TOP-PART
    include($settings->PATH['parts']."top.php");
// CONTENT-PART
// LOGIN WINDOW
?>
    <form method='POST' action='<?= $settings->PATH['site']."login.php"; ?>'>
        <div class='loginContainer generalBorderStyle'>
            <table>
        <?php

        if($_GET['error'] == "IP") {
            echo "
                <tr>
                    <td colspan='2'>
                        You have been logged out, because your IP-address has been changed
                    </td>
                </tr>
                ";
        }

        if($_GET['retrievePassword'] == 1) {
                include_once($settings->PATH['site']."retrievePassword.php");
        } else {
        ?>
                <?= $wrongLogin; ?>
                <tr>
                    <td colspan="3">
                        <h2>LOGIN</h2>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>
                            <span id='username'>
                                <?= _("username") ;?>
                            </span>
                            <input type='text' name='username' required />
                        </p>
                        <p>
                            <span id='password'>
                                <?= _("password"); ?>
                            </span>
                            <input type='password' name='password' required />
                        </p>
                        <p>
                            <input type='submit' class="blueBtn" name='loginBtn' value="<?= _("login"); ?>" />
                        </p>
                    </td>
                    <td >
                        <div class="extraOptionTD generalBorderStyle">
                        <a href='<?= $settings->PATH['site']."forgot_password" ;?>'>
                            * <?= _("lost password"); ?>
                        </a>
                        <br />
                        <br />
                        <a href='<?= $settings->PATH['site']."" ;?>'>
                            * <?= _("register"); ?>
                        </a></div>
                    </td>
                </tr>
            </table>
        </div>
    </form>
<?php
}
include($settings->PATH['parts']."bot.php");
?>
    </div>
</body>
</html>
