<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * === METHODS ===
 * - fetchData = We execute the query to get the user-specific settings and then save that to variables
 *      We only need to do this once
 * - The rest of the methods are getter and setters for the attributes the user can have:
 *  * At the moment the attributes are...
 *      1) Showing a default date when adding receipt
 *      2) Showing the extra options-blokc in the mainview (extraCat, place, info).
 */

class userSettings
{
    public $userID;
    public $DB;
    public $fetchedData = null;
    private $showExtraOptions = null;
    private $showDefaultDate = null;
    
    public function __construct($userID = null, $DB = null)
    {
        $this->userID = $userID;
        $this->DB = $DB;
    }
    private function fetchData()
    {
        $this->fetchedData = $this->DB->queryWithExceptions("SELECT * FROM users WHERE ID = '".$this->userID."'")->fetch_assoc();
        $this->showExtraOptions = $this->fetchedData["showExtraOptions"];
        $this->showDefaultDate = $this->fetchedData["showDefaultDate"];
    }
    public function getShowDefaultDate()
    {
        if(is_null($this->showDefaultDate)) {
            $this->fetchData();
        }
        return $this->showDefaultDate;
    }
    public function getShowExtraOptions() 
    {
        if(is_null($this->showExtraOptions)) {
            $this->fetchData();
        }
        return $this->showExtraOptions;
    }
    public function setShowExtraOptions($show)
    {
        if(!empty($this->DB) && !empty($this->userID)) {
            if($show == 1) {
                $this->DB->queryWithExceptions("UPDATE users SET showExtraOptions = 1 WHERE ID = '".$this->userID."'");
            } elseif($show == 0) {
                $this->DB->queryWithExceptions("UPDATE users SET showExtraOptions = 0 WHERE ID = '".$this->userID."'");
            } else {
                return false;
            }
            $this->showExtraOptions = $show;
        }
    }
    public function setShowDefaultDate($show)
    {
        if(!empty($this->DB) && !empty($this->userID)) {
            if($show == 1) {
                $this->DB->queryWithExceptions("UPDATE users SET showDefaultDate = 1 WHERE ID = '".$this->userID."'");
            } elseif($show == 0) {
                $this->DB->queryWithExceptions("UPDATE users SET showDefaultDate = 0 WHERE ID = '".$this->userID."'");
            } else {
                return false;
            }
            $this->showDefaultDate = $show;
        }
    }
}

?>