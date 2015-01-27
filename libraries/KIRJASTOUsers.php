<?php

class User
{
    protected 
            $username, $activeUserID, $email, $sessID, $sessName, $sessVariables, $country,
            $powerlevel =  "-500", 
            $userID = 0;
    
    public function __construct ($userID = 0)
    {
        $this->setUserID($userID);
    }
    
    function setSession($sessName, $language = "")
    {
        $this->sessName = $sessName;		
        session_name($sessName);
        session_start();
        $this->language = $language;
    }	
    function setUserName($input)
    {
        $this->username = $input;
    }
    function getUserName()
    {
        return $this->username;
    }
    function setActiveUser($input)
    {
        $this->activeUserID = $input;
    }
    function getActiveUser()
    {
        return $this->activeUserID;
    }
    function setAccessLevel($input)
    {
        $this->powerlevel = $input;
    }
    function getAccessLevel()
    {
        return $this->curLevel;
    }
    function setUserID($input)
    {
        $this->userID = $input;
    }
    function getUserID()
    {
        return $this->userID;
    }
    function setEmail($input)
    {
        $this->Email = $input;
    }
    function getEmail()
    {
        return $this->Email;
    }
    function setSessName($input)
    {
        $this->sessName = $input;
    }
    function getSessName()
    {
        return $this->sessName;
    }
    function setSite($input)
    {
        $this->site = $input;
    }
    function getSite()
    {
        return $this->site;
    }
    function setSessID($input)
    {
        $this->sessID = $input;
    }
    function getSessID()
    {
        return $this->sessID;
    }
    function setSessVariable($varName, $value)
    {
        $this->sessVariables[$varName] = $value;
    }
    function getSessVariable($varName)
    {
        return $this->sessVariables[$varName];
    }
    // Salt is voluntary, if there is no salt we just use this rubbish that we set here:
    static public function createPassword($password, $salt="jds93jlkf")
    {
        return hash('sha512', $salt.hash('sha256', $password));
    }
}

// Legacy-class. Should be deleted later on and use the User-class (which is not ready either yet).
class Users
{
    protected $username, $activeUserID, $email, $sessID, $sessName, $sessVariables, $country;
    protected $powerlevel =  "-500", 
            $userID = 0;
    
    function setSession($sessName, $language = "")
    {
        $this->sessName = $sessName;		
        session_name($sessName);
        session_start();
        $this->language = $language;
    }	
    function setUserName($input)
    {
        $this->username = $input;
    }
    function getUserName()
    {
        return $this->username;
    }
    function setActiveUser($input)
    {
        $this->activeUserID = $input;
    }
    function getActiveUser()
    {
        return $this->activeUserID;
    }
    function setAccessLevel($input)
    {
        $this->powerlevel = $input;
    }
    function getAccessLevel()
    {
        return $this->curLevel;
    }
    function setUserID($input)
    {
        $this->userID = $input;
    }
    function getUserID()
    {
        return $this->userID;
    }
    function setEmail($input)
    {
        $this->Email = $input;
    }
    function getEmail()
    {
        return $this->Email;
    }
    function setSessName($input)
    {
        $this->sessName = $input;
    }
    function getSessName()
    {
        return $this->sessName;
    }
    function setSite($input)
    {
        $this->site = $input;
    }
    function getSite()
    {
        return $this->site;
    }
    function setSessID($input)
    {
        $this->sessID = $input;
    }
    function getSessID()
    {
        return $this->sessID;
    }
    function setSessVariable($varName, $value)
    {
        $this->sessVariables[$varName] = $value;
    }
    function getSessVariable($varName)
    {
        return $this->sessVariables[$varName];
    }
    // Salt is voluntary, if there is no salt we just use this rubbish that we set here:
    static public function createPassword($password, $salt="jds93jlkf")
    {
        return hash('sha512', $salt.hash('sha256', $password));
    }
}