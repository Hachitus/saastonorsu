<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * === METHODS ===
 * - insert
 * SUPPORTS MULTI DIMENSIONAL ARRAYS
 * 
 */

require_once $_SERVER["DOCUMENT_ROOT"].'/libraries/KIRJASTOVariablesBasedOnDB.php';

class Product
{
    const
            LEFT_OVER_NAME = "default",
            TABLE_NAME = "products",
            EXTRA_TABLE_NAME = " extraCategoriesInProducts";
    public 
            $dataSource = null,
            $userID = null,
            $ID = null,
            $name = "",
            $cost = "",
            $mainCat = "",
            $subCat = "",
            $extraCats = array(),
            $productInfo = "",
            $leftOver = 0,
            $warranty = null;
    
    function __construct($DB, $userID)
    {
        $this->userID = $userID;
        $this->dataSource = $DB;
    }
    public function insert(array $keys = null, array $values = null)
    {
        $query = "
            INSERT INTO ".self::TABLE_NAME."
                (";
        
        foreach($keys as $key) {
            $query .= $key.",";
        }
        // Strip the last comma:
        $query = substr($query, 0, -1).") 
            VALUES ";
        
        $parseArray = function($theArray)  use (&$query, &$parseArray)
        {
            if(is_array($theArray[0])) {
                foreach($theArray as $multiDimensionals) {
                    $parseArray($multiDimensionals);
                }
            } elseif(is_array($theArray)) {
                $query .= "(";
                foreach($theArray as $key => $value) {
                    $query .= "'".$value."',";
                    $parseArray($query, $value);
                }
                $query = substr($query, 0, -1)."),";
            }
        };
        $parseArray($values);
        $query = substr($query, 0, -1);
        $this->dataSource->queryWithExceptions($query);
        $this->ID = $this->getLastInsertID();
    }
    public function update() {
        
    }
    public function delete() {
        
    }
    
    public function linkExtraCategoriesToProduct (Array $extraCats)
    {
        $extraQuery = "INSERT INTO ".self::EXTRA_TABLE_NAME." (extraCatID, productID) VALUES ";

        foreach($extraCats as $var) {
            $extraQuery .= "('".$var."', '".$this->ID."'),";
        }

        $extraQuery = substr_replace($extraQuery, "", -1);
        $this->dataSource->queryWithExceptions($extraQuery, "extraQuery");
        var_dump($extraQuery);
    }
    
    public function addWarranty(Warranty $warr)
    {
        $this->warranty = $warr;
    }
    public function setExtraCat(categories $cat) {
        $this->extraCats[] = $cat;
    }
    public function getLastInsertID ()
    {
        return $this->dataSource->insert_id;
    }
    // Very important for the float -> int comparisons
    static function strToFloat ($floatVar)
    {
        $floatVar = str_replace(",",".",$floatVar);
        return (float) $floatVar;
    }
}

class Products extends KIRJASTOVariablesBasedOnDB {
    const
            LEFT_OVER_NAME = "default";
    public 
            $theUserID = null,
            $name = "",
            $cost = "",
            $mainCat = "",
            $subCat = "",
            $extraCats = array(),
            $productInfo = "",
            $leftOver = 0,
            $warrantyTill = "";

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
    function getLastID () {
        return parent::$dataSource->lastInsertID;
    }
    public function getLastInsertID () {
        return $this->dataSource->insert_id;
    }
}

?>