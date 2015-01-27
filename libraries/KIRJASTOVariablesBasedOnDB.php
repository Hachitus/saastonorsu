<?php

/*
 * This class is for fetching data and initializing variables in nested Classes
 * The variables are based on the fields in the database.
 * 
 * USAGE:
 * Using magic methods, so you can set and get values by __set and __get. Insert will insert these values to DB
 * 
 */

class SetupDB extends KIRJASTOVariablesBasedOnDB {
    static function setDB(MySQL $DB) {
        parent::$dataSource = $DB;
    }
    function isValid() {
        return true;
    }
}

abstract class KIRJASTOVariablesBasedOnDB {
    // This variable contains all the fields in the database:
    protected $DBFields = null;
    protected $DBFieldsModified = null;
    protected $table = null;
    public $lastInsertID = null;
    protected static $dataSource = null;
    public $allResults = null;
    const EXISTING = "ThisValueExistsAndWeMarkItLikeThis";
    
    function __construct($table, $ID = null) {
        if(!self::validInt($ID)) {
            throw new Exception("Int not given when making calling a contructor in: ".get_class($this)." / ".__CLASS__.", line: ".__LINE__."<br>".PHP_EOL, 100057);
        }
        $this->table = $table;

        $this->initVariables();

        if($ID) {
            $this->SelectByID($ID);
        }
    }
    public static function setDB(MySQL $DB) {
        self::$dataSource = $DB;
    }
    // ======
    // This is to initialize variables, that are based on the database-fields:
    // ======
    private function initVariables () {
        $this->DBFieldsModified = array();
        $result = "";
        $this->DBFields = array();
        $query = "SELECT * FROM ".$this->table;

        try {
            $this->allResults = self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed initializing category in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.", Query:".$query.PHP_EOL."Exception occured:".PHP_EOL.$e, 100);
        }

        if($this->allResults->num_rows < 1) {
            return FALSE;
        } elseif (($fetched = $this->allResults->fetch_assoc())) {
            foreach($fetched as $key => $var) {
                $this->DBFields[$key.self::EXISTING] = 1;
                $this->DBFields[$key] = null;
            }
            $this->allResults->data_seek(0);
        }
    }
    // ======
    // These are for retrieving stuff from database:
    // ======
    public function selectByID ($ID) {
        $IDQuery = null;
        $query = "SELECT * FROM ".$this->table." WHERE ID = '".$ID."'";

        try {
            $IDQuery = self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed fetching data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 200);
        }
        
        if($IDQuery->num_rows > 1) {
            throw Exception("Number of rows over 1, when fetching single row [".$query."]",210);
        } elseif ($IDQuery->num_rows <= 0) {
            return false;
        }
        
        // If the variables have not yet been initialized:
        if($this->DBFields === null) {
            $this->initVariables();
        }
        if(($fetched = $IDQuery->fetch_assoc())) {
            foreach($fetched as $key => $var) {
                $this->DBFields[$key] = $var;
            }
        }
    }
    // ======
    
    function delete ($ID) {
        $query = "DELETE FROM ".$this->table." WHERE ID = '".$dataSource->filterVariable($ID)."'";

        try {
            self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed deleting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 300);
        }
        
        if($ID == $this->ID) {
            $this->DBFields[$key] = initVariables ();
        }
    }
    function update ($ID, $keys) {
        $valueSQL = "";
        $query = "";

        if(is_array($this->DBFieldsModified)) {
            foreach($this->DBFieldsModified as $key => $var) {
                    $valueSQL .= self::$dataSource->filterVariable($key)."=".$dataSource->filterVariable($var).",";
                    $this->DBFields[$key] = $var;
                    return true;
            }

            // strip the last ,-char:
            $valueSQL = substr($valueSQL, 0, -1);
            $query = "UPDATE ".self::$dataSource->filterVariable($this->table)." SET ".$valueSQL." WHERE ID = '".$ID."'";
        } else {
            return false;
        }
        
        try {
            self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed updating data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 400);
        }
    }
    function insert () {
        $keySQL = "";
        $valueSQL = "";
        
        if(is_array($this->DBFieldsModified)) {
            foreach($this->DBFieldsModified as $key => $var) {
                $keySQL .= self::$dataSource->filterVariable($key).",";
                $valueSQL .= "'".self::$dataSource->filterVariable($var)."',";
                $this->DBFields[$key] = $var;
            }

            //-- strip the last ,-characters:
            $keySQL = substr($keySQL, 0, -1);
            $valueSQL = substr($valueSQL, 0, -1);
            //--

            $query = "INSERT INTO ".self::$dataSource->filterVariable($this->table)." (".$keySQL.") VALUES (".$valueSQL.")";
        } else {
            return false;
        }

        try {
            self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed inserting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 400);
        }
        
        $this->lastInsertID = self::$dataSource->insert_id;
        return true;
    }
    
    abstract function isValid();
    
    function setArray($values) {
        
        if(is_array($values)) {
            foreach($values as $key => $var) {
                $this->DBFieldsModified[$key] = $var;
            }
            return true;
        }
        throw new Exception("failed setting properties in subClass: ".get_class($this)." / ".__CLASS__." (line:".__LINE__.", value:".$property.")".  var_dump(debug_backtrace()));
        return false;
    }
    function __set($property, $value) {
        $this->DBFieldsModified[$property] = $value;
        $this->DBFields[$property] = $value;
        return true;
    }
    function __get($property) {
        return $this->DBFieldsModified[$property] ? $this->DBFieldsModified[$property] : $this->DBFields[$property];
    }
    
    function __call($name, $arguments) {
        throw new Exception("unidentified method in ".__CLASS__."-class, line:".__line__.", name:".$name.", (arguments var dumped)".var_dump($arguments));
    }
    
    static function __callStatic($name, $arguments) {
        throw new Exception("unidentified static-method in ".__CLASS__."-class, line:".__line__.", name:".$name.", (arguments var dumped)".var_dump($arguments));
    }
    
    static protected function validInt($ID) {
        if(!is_int($ID) && $ID != null) {
            throw new Exception("not int:".$ID);
        }
        return true;
    }
    function getTable() {
        return $this->table;
    }
}
?>