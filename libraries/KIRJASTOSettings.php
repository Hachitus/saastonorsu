<?php
/*
 * This holds the main settings for the library, which most are meant to be
 * global, to be used in different occasions and mostly constants.
 * 
 * This class is the one that holds a lot of the other classes, in a way it's the master class.
 * The class has to be changed a lot when a different project is used.
 */

// - Class for sending debugging messages by e-mail or DB etc.
// Used most when testing and developingan open site 
include_once("KIRJASTODebugging.php");
// - Database class:
include_once("KIRJASTODB.php");
// - Error or Exception - handling classes:
include_once("KIRJASTOErrorHandling.php");
// - Logging in and authentication. Includes Users-subclass.
include_once("KIRJASTOAuthenticate.php");
// - Custom functions:
include_once("KIRJASTOFunktioita.php");

class Settings {
   public $ADMINISTRATOR = "Janne Hyytiä";
   public $ADMINISTRATOR_EMAIL = "japetus@saunalahti.fi";

   private $page;

   public $LANG;

   // Paths are defined in constructor, because array-function does not like
   // SERVER-variables to be used here.
   public $PATH;

   // Defines if the site is operational or not, or if access is restricted:
   public $ONLINE = true;
   public $RESTRICTED_ACCESS = true;

   // Allowed subPages and allowed PopupPages. We list them separately a bit lower:
   public $SALLITUT_SIVUT;
   public $POPUP_SIVUT;
   
   /* LOGGING SETTINGS:
    * In the program you should set DBLogging as the object for the DBConnection
    * where ever you want your datebase logs to go.
    */
   public $DBLogging=NULL;

   // We initialize the Database connection handler:
   public $DBConn=NULL;
   
    // This will set the file where we will fetch the DBinfo. We set the variable in constructor:
   private $DBFile = "";
   
   // We set this to differentiate test-site and operational-enviroments. Now we don't need to edit the class to seaprate
   // for example databases on test-site and operational site:
   private $testSites = array("testi.saastonorsu.fi");

   // We only initialize the DB-variable here and in constructor we add the settings:
   private $DB;

   // include('/var/www/non-public/DB.php');

   /*
    * Set the databases fields names as you have them in your database. Example:
    * Your database might have a logging table named: Company_error_logs, which
    * has fields: ID, errorName, ErrorNumber in it...
    * So:
    * $DB_Name = "Company_error_logs"
    * $DB_FIELDS['ErrNumber'] => "ErrorNumber";
    * $DB_FIELDS['ErrMessage'] => "errorName";
    * and if you don't DB-structure for all the information in your database,
    * leave the variable as: null;
    */

   public $DB_ERR_Name = null;
   public $DB_ERR_Fields = array(
   	'number' => null,
      'message' => null,
      'line' => null,
      'file' => null,
      'DBError' => null,
      'all' => null);

   /*
    *  HTML metadata array is being assigned in contructor, since it's not
    *  allowed here
    */
   private $HTMLMetaData;
   public $browser;

   /*
    * These are the object to the other classes, that will be instantiated at contructor:
    */
   public $ERRORS;
   public $URLObject;
   public $mysqlii;

   // Definition of system paths and other variable definitions that can't be done in class definitions
   function __construct ($session, $title = "", $description = "", $keywords = "", $charSet = "UTF-8", $hidden = 0, $errorLogging = 1) {
        $SESSION_ON = 1;

        //  Setup the correct connection options. We created the variable before
        //  and here include another file, located in non-public-location.
        //  This way the file that has the settings is more secure from intruders.
        // The file needs to setup DB-variable as an array ie:
        /* <?php
            * $DB = array(
            * 'host' => 'localhost',
            * 'userName' => 'user123',
            * 'password' => 'pass456',
            * 'database' => 'webDatabase',
            * 'port' => '3306');
        * ?>
        */
    if (in_array($_SERVER['SERVER_NAME'], $this->testSites)) {
        $this->DBFile = '/var/www/saastonorsu/non-public/DB_test.php';
    } else {
        $this->DBFile = '/var/www/saastonorsu/non-public/DB.php';
    }
        
    include($this->DBFile);
        
    $this->PATH['site'] = "http://".$_SERVER['SERVER_NAME']."/";
    $this->PATH['absolute'] = $_SERVER["DOCUMENT_ROOT"]."/";
    $this->PATH['parts'] = $_SERVER["DOCUMENT_ROOT"]."/parts/";
    $this->PATH['libraries'] = $_SERVER["DOCUMENT_ROOT"]."/libraries/";
    $this->PATH['classes'] = $_SERVER["DOCUMENT_ROOT"]."/classes/";
    $this->PATH['frontend'] = $_SERVER["DOCUMENT_ROOT"]."/sivut/";
    $this->PATH['backend'] = $_SERVER["DOCUMENT_ROOT"]."/backend/";
    $this->PATH['images'] = $this->PATH['site']."images/";
    $this->HTMLMetaData = array(
        'description' => $description,
         'keywords' => $keywords,
         'charSet' => $charSet,
         'title' => $title,
         'hidden' => $hidden);

    $this->SALLITUT_SIVUT = array(
        'mainview',
        'charts',
        'settings',
       	'modify',
        'receipts');

    $this->browser = $_SERVER['HTTP_USER_AGENT'];

      /*
       *  when instantiating we will also use the rest of the class, so that
       *  the user doesn't need to do these manually
	 	 * When you want to use the other classes, uncomment / comment below as desired
      */
        
        // $apu = new CleanURLs();
      // $this->URLObject = $apu->getURLs();

    // With a p-string before the host we are able to generate a persistent connection (the same as mysql_pconnect-funtion:
      $this->DBConn = new MySQL("p:".$this->DB['host'], $this->DB['userName'], $this->DB['password'], $this->DB['database']);
      //$this->DBConn->connect();
        
      // If the site is offline, then don't proceed any further:
      $haku = $this->DBConn->query("SELECT online FROM settings WHERE online=1");
      if(($online = $haku->fetch_row()) && $online[0]===1) {
      	echo "site offline";exit();
      }
      
        if($errorLogging == 1) {
            $this->errors = new ErrorHandling();
	
                if($this->DBLogging == null) /* if not null, using the error-logging DB set earlier */ {
                    $this->DBLogging = $this->$DBconn;
                }
        } else {
            $this->errors = null;
            $this->DBLogging = null;
        }
        if ($session == $SESSION_ON) {
                if (session_start() == false) {
                    trigger_error ("session could not be started", E_USER_ERROR);
            }
        }
        /* This is the total layout of your page not found - page */
        include ("KIRJASTOErrorPages.php");
    }

   /* docType is 1=transitional, 2=strict, 3=frameSet
    *
    */
   function metaData ($docType, $copyright="") {
   	switch($docType) {
      	case 1:
         	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
            break;
         case 2:
            echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
            break;
         case 3:
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
            break;
      } // end switch
		
		echo "<html><head>";
      echo PHP_EOL."<TITLE>".($this->HTMLMetaData['title'])."</TITLE>".PHP_EOL;
      if($this->HTMLMetaData['charSet'])
         echo "<META http-equiv='content-type' content='text/html;charset=".$this->HTMLMetaData['charSet']."' />".PHP_EOL;
      if($copyright)
         echo "<META name='copyright' content='$copyright' />".PHP_EOL;
      if($this->HTMLMetaData['description'])
         echo "<META name='description' content='".$this->HTMLMetaData['description']."' />".PHP_EOL;
      if($this->HTMLMetaData['keywords'])
         echo "<META name='keywords' content='".$this->HTMLMetaData['keywords']."' />".PHP_EOL;
      // For NOT indexing to search engines / keeping the pages hidden
      if($this->HTMLMetaData['hidden'] == 1)
         echo "<META name='robots' content='noindex,nofollow,noarchive' />".PHP_EOL;
	} // end function
	function getLink($page, $value1 = "") {
		$this->PATH['site']."/".$page."/".$value1."/";
	}
    function getPage () {
	return $this->page;
    }
    function setPage ($page) {
	$this->page = $page;
    }
    function setLang ($lang) {
        
        if(empty($lang)) {
            $this->lang = "fin";
        } else {
            $this->lang = $lang;
        }
    }
}
?>
