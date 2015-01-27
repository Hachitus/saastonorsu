<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * === METHODS ===
 * - fetchedData = We execute the query to get the user-specific settings and then save that to variables
 *      We only need to do this once
 * - showExtraOptions / showDefaultDate = If userSettings have been already retrieved, return the specific setting
 * - setShowExtraOptions / setShowDefaultDate = Sets the specific user setting to database
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