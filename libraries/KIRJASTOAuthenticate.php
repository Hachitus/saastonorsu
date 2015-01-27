<?php
/*
 * DB requirements:
 * DB Table Users
 * - powerlevel, ID, email, password, IP, timestamp, name,
 * DB Table sessions
 * - userID, time, sessID
 * 
 * ALL default-values should be set in the contructor, the values set at the
 * beginning are all fixed, they are not supposed to be changed
 * 
 * Login() - throws exceptions also:
 *  100 - Username or password was wrong.
 *  200 - 
 *  300 - 
 *  400 - 
 * 
 *  * Authenticate() - throws exception with 4 different error codes.
 *  500 - There was a cookie present, but the cookies value was empty
 *  600 - 2 users had the same cookie value or user had 2 entries for the same cookie in DB.
 *  700 - Nothing was found with DB-query
 *  800 - Not enough rights to access the site
 *  900 - IP address changed, so we devalidate user-session
 */



// Class that handles users variables and user operations. Not used elsewhere, I think.
require_once("KIRJASTOUsers.php");

class Authenticate extends Users
{
    // Salt is special and should be changed as different for every site. You can also make indivicual salts for users with the function changeSalt();
    private 
            $salt = "jds93jlkf",
/* If desired, set the default values to the variables 
 * Variables can be passed
 */
            $DBConn = null,
            $site = "",
            $cookieName = "", 
            $cookieID = "",
            $validCookieTime = 0, // Default defined in constructor!
            $defaultCookieTime = 31536000,
            $DBUser_LevelMin = 0,
            $unactivatedAccount = 1,
            $timeBetweenAttempts = 86400, // This defines, how much pause there is, when user fails to login to site and the user is "locked"
            $maxAttempts = 5, // This defines, how many times a user can try to login to the site before time restrictions

    /* for logging statistics
	These could be actually moved to debugging
    */
            $DBStatsTableName,
            $DBStatsRealIPField,
            $DBStatsTimestampField,
            $DBStatsUsernameField, 
            $DBStatsUserIDField,
            $time=0,
            $debug = "",
            $debugHelp = array();

    public function __construct (mysqli $DB, $site, 
            $cookieName = "", $DBUser_LevelMin = 0, 
            $validCookieTime = 31536000, $maxAttempts = 5, 
            $timeBetweenAttempts = 86400, $unactivatedAccount = 1)
    {

        // while debugging:
        $this->debug = new Debugging();
        
        $this->time=time();
        $this->DBConn = $DB;
        if($site) {
            $this->site= $site;
        }
        if($cookieName) {
            $this->cookieName = $cookieName;
        }
        if($_COOKIE[$this->cookieName]) {
            $this->cookieID = $this->DBConn->filterVariable($_COOKIE[$this->cookieName]);
        }

        if($validCookieTime) {
            $this->validCookieTime = $validCookieTime + $this->time;
        } else {
            $this->validCookieTime = $this->defaultCookieTime + $this->time;
        }
        $this->DBUser_LevelMin = $DBUser_LevelMin;
        
        $this->maxAttempts= $maxAttempts;
        $this->timeBetweenAttempts = $timeBetweenAttempts;
        $this->unactivatedAccount = $unactivatedAccount;
    }
    // ------------
    // The main methods 1/3. login
    // ------------
    function login ($user, $password, $stats = 0)
    {
        $password = Users::createPassword($password);
        $user = $this->DBConn->filterVariable($user);
        $tulos="";

        if(!($haku = $this->DBConn->query("
                SELECT u.ID, u.email, u.powerlevel, u.loginAttempts, u.attemptTime, u.IP 
                    FROM users u 
                        WHERE u.email='".$user."' 
                            AND u.password=PASSWORD('".$password."')"
                ))) {
            
            throw new Exception("query failed. MySQL-error:".$this->DBConn->error, 20);
            return FALSE;
        } elseif(!($tulos = $haku->fetch_row())) {
            throw new Exception("Username was not found or password was wrong. MySQL-error:".$this->DBConn->error, 100);
            return FALSE;
        }

        // The user can not be found from database ($tulos[1]), the user doesn't have enough privileges ($tulos[2]) or the users logins are restricted due to too many logins in too short time ($tulos[3] / $tulos[4]):
        if(!$tulos[1] || 
                $tulos[2] < $this->DBUser_LevelMin || 
                ($tulos[3] >= $this->maxAttempts && ($tulos[4]+$this->timeBetweenAttempts) < $this->time)) {
            
            if (!$tulos[1]) {
                throw new Exception("Username not found", 200);
            } elseif ($tulos[2] < $this->DBUser_LevelMin) {
                throw new Exception("Not enough access rights", 300);
            } elseif($tulos[3] >= $this->maxAttempts && ($tulos[4]+$this->timeBetweenAttempts) < $this->time ) {
                throw new Exception("You have failed to login to the site too many times. There will be a time limit, after which you can login again. Please try again later.", 800);  
            }
            else {
                throw new Exception("Something else", 400);
            }
            
            $this->delCookie();
            return FALSE;
      	}
        else {
            // If the user doesn't have enough rights to access the site / the user is banned:
            // Katsotaan onko asetuksissa loginit sallittuja:
            if (!$allow_logins=mysqli_fetch_row($this->DBConn->query("SELECT allowLogins FROM settings WHERE allowLogins = 1 OR allowLogins = 2"))) {
                $this->dontAllowLogins(0);
            } elseif ($allow_logins[0] == 2 && $tulos[2] < 80) {
                $this->dontAllowLogins(279);
            } elseif ($allow_logins[0] == 2 && $tulos[2] >= 80) {
                $this->dontAllowLogins(280);
            }
            if($tulos[2]<$this->DBUser_LevelMin) { // the user has been banned
                $this->insufficientPowerLevel(-1);
            } elseif($tulos[2]==$this->unactivatedAccount) {// Käyttäjätiliä ei ole aktivoitu
                $this->insufficientPowerLevel(1);
            }

            if($tulos[3]>9) { // Käyttäjällä on yritetty kirjautua sisään 10 kertaa peräkkäin onnistumatta.
                if(!$this->DBConn->query("UPDATE sessions SET loginAttempts=loginAttempts + 1, attemptTime=NOW()")) {
                    echo "error1235711";
                    exit("error1235711");
                }
            }
                    
            if($tulos[2] < $this->DBUser_LevelMin) {
                $this->delCookie();
            }
            else
            {
                $this->setUserName($tulos[1]);
                $this->setAccessLevel($tulos[2]);
                $this->setUserID($tulos[0]);

                $this->cookieID = $this->setCookieID();
                setcookie($this->cookieName,$this->cookieID,$this->validCookieTime,'/',$this->site);
                
                // Update session ID to database, so that authentication works.
                // First we should DELETE / Clear and make sure there are no old DB-inserts that screw up the system and after that we insert a new one:
                
                $this->setSessionToDB($this->cookieID, $this->getUserID());

                // Update users login attempts information:
                if(!$this->DBConn->query("UPDATE users SET loginAttempts=0, attemptTime=NOW()")) {
                    throw new Exception("QUERY -- UPDATE users SET loginAttempts=0, attemptTime=".$this->time." -- MySQL Error:".$this->DBConn->error, 20);
                }
                
                // We change the IP at the users table, for validation:
                $IP = preg_replace('/[^0-9a-f.:]/','',$_SERVER['REMOTE_ADDR']);
                $this->DBConn->query("UPDATE users SET IP = '".$IP."' WHERE ID = '".$this->getUserID()."'");
                    
                // If login statistics have been enabled
                if($stats) {
                    $this->DBConn->query("
                        INSERT INTO ".$this->DBStatsTableName." 
                            SET ".$this->DBStatsRealIPField."=".$this->getRealIpAddr().", ".$this->DBStatsTimestampField."='".$this->time."', 
                                ".$this->DBStatsUsernameField."='".$this->getUserName()."', ".$this->DBStatsUserIDField."='".$this->getUserID()."', 
                                    sessID='".$this->cookieID."'");
                }
                return TRUE;
            }
        }
	return FALSE;
    }
    // ---------
    // 2/3. Authentication by a cookie:
    //----------
    public function authenticate ($cookieID = "", $loginPage=0)
    {
        // The validCookieTime is time + the validTime. So we have to reduce the time 
        // from the validCookieTime first and after that we can reduce it from the time:
        $validTime = (($this->time*2)-$this->validCookieTime);
        
        $tulos = "";
        $kysely = "";
        // ---- If the ID variable for the cookie is not set, we FAIL the function. So for example the login-page continues to show normally:
        if($this->cookieID === "" && $cookieID === "") {
            $this->delCookie();
            return FALSE;
        }
        // If the cookieID was not passed as a variable we set the variable to the one we set with the constructor:
        elseif($cookieID === "") {
            $cookieID = $this->cookieID;
        }
        // ---- 
        
        // ---- Make sure that DB-query succeeds and there are only 1 record found, else:
        // 1) Check that there are not more than 1 entries found with the query
        // 2) That there not less than 1 entries found with the query
        // 3) If the query didn't succeed
        $haku = "SELECT u.ID, u.email, u.powerlevel, u.IP FROM users u, sessions s WHERE u.powerlevel > ".$this->DBUser_LevelMin." AND s.sessID='".$cookieID."' AND s.time >= '".$validTime."' AND s.userID = u.ID";

        if((!$kysely = $this->DBConn->query($haku)) || $kysely->num_rows !== 1)
        {
            $this->delCookie();
            
            if($kysely->num_rows < 1) { // NUMBER 1
                return FALSE;
            } elseif ($kysely->num_rows > 1) { // NUMBER 2
                $this->DBConn->query("DELETE FROM sessions WHERE sessID='".$cookieID."'");
                throw new Exception("There was more than 1 entry for the cookie in DB. Deleting the cookie and the DB-enty. MySQL ERROR:".$kysely->error.PHP_EOL."<br>Original query:".$haku, 500);
            } else { // NUMBER 3
                throw new Exception("There was a possible duplicate cookie present. Deleting the cookie and the DB-enty. MySQL ERROR:".$kysely->error.PHP_EOL."<br>Original query:".$haku, 600);
            }            
        }
                // Execute the query and check that there is a user found with the query ($tulos[1]):
        if((!$tulos = $kysely->fetch_row()) || !$tulos[1]) {
            echo "The session is not working for user: ".$tulos[1];
            // DEBUGGING, replace with exception when in production
            $this->debug->sendMail("Ongelma authenticatessa. Sessiota ei löytynyt käyttäjlle: ".$tulos[1]);
            throw new Exception("Nothing found with DB-query", 700);
        }
        // ---- Everything seems to be in order regarding the cookie-authentication
        // so we process the authentication for the rest of the function:
        else {
            // --- If the user doesn't have enough rights to access the site / is banned:
            if($tulos[2] < $this->DBUser_LevelMin) {
                $this->delCookie();
                throw new Exception("not enough rights to access the site", 800);
            } elseif (($tulos[3] != $_SERVER['REMOTE_ADDR'])) { // If the user is authenticated with a changed IP-address, we devalidate him:
                $this->DBConn->query("UPDATE users SET IP = '' WHERE ID = '".$tulos[0]."'");
                throw new Exception("IP address changed".$tulos[3]." - ".$_SERVER['REMOTE_ADDR'], 900);
            }
            // -- The user has enough access rights, so we authenticate him to the site:
            else {
                $this->setUserName($tulos[1]);
                $this->setAccessLevel($tulos[2]);
                $this->setUserID($tulos[0]);

                $this->setSessionToDB($cookieID, $this->getUserID());

                return true;
            }
        }
        // If the requirements are not met, then we will return failed for the authentication
        return FALSE;
    }
    // ---------
    // 3/3. The simples method logout:
    //----------
    public function logout ($loginPage)
    {
        $this->delCookie();
        $this->setUserName("");
        $this->setAccessLevel("");
        $this->setUserID("");
        $this->DBConn->query("DELETE FROM sessions WHERE sessID='".$this->cookieID."'");
        self::redirect($loginPage);
    }
    // ----------Other small methods------------

    public function changeSalt($salt)
    {
        $this->salt = $salt;
    }
    public function getCookieID($id=NULL)
    {
        return $this->cookieID;
    }    
    public function setCookieID($id=NULL)
    {
        /* Generates the cookies session ID */	
        if($id!==NULL) {
            $this->cookieID = $id;
        } else {
            $this->cookieID = md5($this->time . $this->username . mt_rand(1,25) . $this->getRealIpAddr());
        }
        return $this->cookieID;
    }    
    public function delCookie()
    {
        setcookie($this->cookieName,"",1,'/',$this->site);
        $this->setUserName("");
        $this->setAccessLevel("");
        $this->setUserID("");
    }
    
    public function insufficientPowerLevel($attemptedPowerlevel)
    {

        if($attemptedPowerlevel==-1) // Käyttäjää ei ole tai tunnus / salasana väärin
            echo "Käyttäjätunnus estetty";
        elseif($attemptedPowerlevel==0) // Käyttäjätilin kirjautuminen on estetty (alikäyttäjä)
            echo "Käyttäjätunnus ";
        elseif($attemptedPowerlevel==1) // Useraccount has not been activated
            echo "";
    }
    public function dontAllowLogins(int $value)
    {
        if($value==280) {
            return;
        }
        elseif($value==279) {
            echo "Only admins are allow to login";
        }
        elseif($value==0) {
            echo "Logins not allowed";
        }
    }
    public static function redirect ($page)
    {
        if(!headers_sent()) {
            self::headerRedirect($page);
        } else {
            self::JSRedirect($page);
        }
    }
    private static function JSRedirect ($site)
    {
        echo"
            <script type='text/javascript' language='JavaScript'>
            <!--
            document.location.href='", $site, "';
            -->
            </script> ";
    }
    private static function headerRedirect ($site)
    {
        header("location:".$site);
    }
    private function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   // check ip from share internet
      	$ip=$_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   // to check ip is pass from proxy
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip=$_SERVER['REMOTE_ADDR'];

   	return $ip;
    }
    private function setSessionToDB($cookieID, $user)
    {
        $haku = "
            INSERT INTO sessions 
                SET time='".$this->time."', sessID='".$cookieID."', userID='".$user."' 
                    ON DUPLICATE KEY UPDATE time='".$this->time."', userID='".$user."'";
        $this->DBConn->query($haku);
        if(!$user){
            echo "HUMPS ".var_dump($this->debugHelp)." HOMPS".$this->getUserID();
            $palautus = debug_backtrace();
            echo $palautus["function"]."<br>sdsdsd<br>".var_dump($palautus);
            exit();
        }
        return TRUE;
    }
}