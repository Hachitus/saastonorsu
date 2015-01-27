<?php

/* First you should change the "pageNotFound"-function suitable to your needs
 *
 * When using built-in error-management in PHP, you have command the script to
 * use the custom error-handler, like this: set_error_handler("customErrHandler");
 *
 * Errors can be triggered in several ways:
 * 1. Using command: trigger_error();
 *
 *
 *
 * If you don't want to use database to view error-codes, leave DBConnection to default
 
 FIGURE OUT the PHP-constants and use them also:
 http://php.net/manual/en/language.constants.predefined.php
 */

class ErrorHandling
{
    private
            $administratorEmail = "janne.hyytia@saunalahti.fi";

    public function __construct ()
    {
        set_error_handler($this->errorHandler());
    }

    public function errorHandler($errNumber=0, $errMessage="", $errFile="", $errLine=0, $errContext="")
    {
        $query = "";
        $DBErrorString = "";

        if (preg_match('/^(sql)$/i', $errMessage)) {
            $MYSQL_ERRNO = mysql_errno();
            $MYSQL_ERROR = mysql_error();
            $query = "MySQL error: ".$MYSQL_ERRNO." : ".$MYSQL_ERROR.PHP_EOL;
        } else {
            $query = "";
        }

        $errorString = date('Y-m-d H:i:s').PHP_EOL.
            "Fatal Error [".$errNumber."]: ".$errMessage.PHP_EOL.
            $query.
            "Error in line ".$errLine." of file ".$errFile.PHP_EOL."
            IP-Osoite: ".$_SERVER['REMOTE_ADDR'].PHP_EOL."
            Script: ".$_SERVER['PHP_SELF']."'.".PHP_EOL.PHP_EOL.$errContext;

        // Lähetetään tiedot tietokantaan, jos sellainen on määritelty:
        try {
            $DBLogging->query(array("INSERT INTO ".$DB_ERR_Name." (".$DB_ERR_FIELDS['number'].",".$DB_ERR_FIELDS['message'].",".$DB_ERR_FIELDS['file'].",".$DB_ERR_FIELDS['line'].",".$DB_ERR_FIELDS['DBError'].",".$DB_ERR_FIELDS['all'].") VALUES (".$errNumber.",".$errMessage.",".$errFile.",".$errLine.",".$query.",".$errorContext.")"));
        } catch (Exception $e) {
            $DBErrorString = PHP_EOL.PHP_EOL."AND problem with DB connectivity in error handling class:".PHP_EOL.$e->getTraceAsString();
        }

        // Lähetetään tiedot myös administratorille:
        error_log($errorString, 1, $this->administratorEmail.$DBErrorString);
          
        /*  And then we will decide which errors are dangerous enough, that
         *  we have to stop the code from proceeding further and break the
         *  execution of the page.
         */
        if ($errMessage == "Page not found") {
            pageNotFound();
        } else {
            switch ($errNumber)
            {
                // Non-critical errors are handled using exceptions:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                case E_WARNING:
                case E_NOTICE:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                    break;
                // Critical errors are handled here:
                case E_USER_ERROR:
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                    session_start();
                    session_unset();
                    session_destroy();
                    die();
                default:
                    break;
            } // switch
        } // if-else
    } // errorHandler

    /*
     * Here you will code to total appearance of your Page Not Found Page:
     */
    private function pageNotFound () {
        echo $PageNotFound;
    }
}
?>
