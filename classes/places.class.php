<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * === VARIABLES ===
 * - table =  the database table that will be manipulated
 * 
 */

class Places implements FetchValues
{
    public
            $userID = null,
            $name = "";
    private
            $table = "premadePlaces",
            $DB = null;
    
    public function __construct($DB, $userID)
    {
        $this->DB = $DB;
        $this->userID = $userID ? $userID : null;
        $this->name = ($name != null) ? $name : null;
    }    
    public function insert ($name = null)
    {
        if($name != null) {
            $this->name = $name;
        }
        $query = "INSERT INTO ".$this->table." (name, userID) VALUES ('".$this->name."', '".$this->userID."')";

        try {
            $this->DB->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed inserting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."SQL: INSERT INTO premadePlaces (name, userID) VALUES (".$this->name.", ".$this->userID.")".PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 400);
        }
        
        return $this->DB->insert_id;
    }
    public function fetchQuery()
    {
        if($this->allQuery) {
            $this->allQuery->data_seek(0);
        } else {
            $this->allQuery = $this->DB->queryWithExceptions("SELECT * FROM ".$this->table." WHERE userID='".$this->userID."' AND deleted = 0 ORDER BY name");
        }
        if($this->DB->affected_rows) {
            return $this->allQuery;
        }
        return false;
    }
    public function fetchArray()
    {
        $retArray = Array();
        if(($query = $this->fetchQuery())) {
            while($retArray[] = $query->fetch_assoc());
            $retArray = array_slice($retArray, 0, -1);
            return $retArray;
        }            
        return false;
    }
    public function delete($ID)
    {
        $query = "UPDATE ".$this->table." SET deleted = 1 WHERE userID = '".$this->userID."' AND ID = '".$this->DB->filterVariable($ID)."'";
        return $this->DB->queryWithExceptions($query);
    }
    public function getLastInsertID() {
        $this->DB->insert_id;
    }
}

?>