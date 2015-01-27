<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 */

require_once $_SERVER["DOCUMENT_ROOT"].'/libraries/KIRJASTOVariablesBasedOnDB.php';

class Product {
    public $userID = null;
    public $name = "";
    public $cost = "";
    public $mainCat = "";
    public $subCat = "";
    public $extraCats = array();
    public $productInfo = "";
    public $leftOver = 0;
    private $warranty = null;
    
    public function addWarranty(Warranty $warr) {
        $this->warranty = $warr;
    }
}

class Products extends KIRJASTOVariablesBasedOnDB {
    const LEFT_OVER_NAME = "default";
    public $theUserID = null;
    public $name = "";
    public $cost = "";
    public $mainCat = "";
    public $subCat = "";
    public $extraCats = array();
    public $productInfo = "";
    public $leftOver = 0;
    public $warrantyTill = "";

    function __construct($userID, $ID = null) {
        $this->theUserID = $userID;
        parent::validInt($ID);
        parent::__construct("products", $ID);
    }
    
    function isValid() {
        return true;
    }
    
    /*
     * Returns true on success and false on fail. If there are no categories that 
     * have been linked with product (with setExtraCategoryLinks-function), the function
     * will fail
     */
    
    function linkExtraCategoriesToProduct ($extrasToInsert) {
        $keys = "(productID, extraCatID)";
        $values = "";

        // We add the values to the SQL-string
        foreach($extrasToInsert as $prodID => $extraArray) {
            foreach($extraArray as $extras) {
                $values .= "(".$prodID.",".$extras."),";
            }
        }
        
        // If there are no values to be inserted, we fail the function:
        if(!isset($values)) {
            return false;
        }
        
        // Remove the last ,-character:
        $values = substr($values, 0, -1);

        self::$dataSource->queryWithExceptions("INSERT INTO extraCategoriesInProducts ".$keys." VALUES ".$values);
        
        return true;
    }
    public function setExtraCat(categories $cat) {
        $this->extraCats[] = $cat;
    }
    static function strToFloat ($floatVar) {
        $floatVar = str_replace(",",".",$floatVar);
        return (float) $floatVar;
    }
    function getLastID () {
        return parent::$dataSource->lastInsertID;
    }
}
class PremadeProducts extends KIRJASTOVariablesBasedOnDB implements FetchValues {
    protected $table = "premadeProducts";
    public $theUserID = null;

    function __construct($userID, $ID = null) {
        $this->theUserID = $userID;
        parent::validInt($ID);
        parent::__construct("premadeProducts", $ID);
    }
    
    function fetchQuery() {
        if($this->allQuery) {
            $this->allQuery->data_seek(0);
        } else {
            $this->allQuery = self::$dataSource->queryWithExceptions("SELECT * FROM ".$this->table." WHERE userID='".$this->theUserID."' ORDER BY name");
        }
        if(self::$dataSource->affected_rows) {
            return $this->allQuery;
        }
        return false;
    }
    public function fetchArray() {
        $retArray = Array();
        if(($query = self::fetchQuery())) {
            while($retArray[] = $query->fetch_assoc());
            $retArray = array_slice($retArray, 0, -1);
            return $retArray;
        }
        return false;
    }
    function isValid() {
        return true;
    }
    public function getByID ($ID) {
        return self::$dataSource->queryWithExceptions("SELECT * FROM ".$this->table." WHERE userID='".$this->theUserID."' AND ID = '".$ID."' ORDER BY name")->fetch_assoc();
    }
}

?>