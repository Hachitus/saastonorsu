<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/libraries/KIRJASTOVariablesBasedOnDB.php";
/*
    Remember that you HAVE TO have database set et the inherited class (VariablesBasedOnDB):
    SetupDB::setDB($dataSource);
*/

abstract class Category
{
    const MAIN_CATEGORY = 1;
    const EXTRA_CATEGORY = 2;
    const SUB_CATEGORY = 3;
    protected $type = 0;
    public $userID = null;
    public $name = "";
    public $ID = 0;
    public function __construct($userID, $ID = 0, $name = "")
    {
        $this->userID = $userID;
        $this->ID = $ID;
        $this->name = $name;
    }
    public function setType ($type)
    {
        if($type) {
            $this->type = $type;
        }
    }
    public function getType ()
    {
        return $this->type;
    }
}
class CategoriesWithDatabase
{
    public $dataSource= "";
    
    public function __construct($database)
    {
        $this->dataSource = $database;
    }
    public function insertCategory ($cat)
    {
        return $this->dataSource->queryWithExceptions("INSERT fsdf categories WHERE deleted = 0 AND userID = '".$cat->userID."' AND ID='".$ID."' ORDER BY parentCat, name");        
    }
    public function fetchCategory ($cat)
    {
        return $this->dataSource->queryWithExceptions("SELECT * FROM categories WHERE deleted = 0 AND userID = '".$cat->userID."' AND ID='".$cat->ID."' ORDER BY parentCat, name");        
    }
    public function fetchAll ($cat, $parentCat = "")
    {
        $extraSQL = "";
        if($parentCat) {
            $extraSQL = " AND parentCat = '".intval($parentCat)."'";
        }
        return $this->dataSource->queryWithExceptions("SELECT ID, name, parentCat FROM categories WHERE deleted = 0 AND userID = '".$cat->userID."' AND type = '".$cat->getType()."'".$extraSQL." ORDER BY parentCat, name");        
    }
}

class MainCategory extends Category
{
    public $name = "";
    
    public function __construct($userID, $ID = 0, $name = "")
    {
        parent::__construct($userID, $ID, $name);
        parent::setType(parent::MAIN_CATEGORY);
    }
    public function insertData (CategoriesWithDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
    public function populateDataByID ($ID, CategoryFromDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
    public function CategoriesWithDatabase ($ID, CategoryFromDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
}
class SubCategory extends Category
{
    private $parentCat = null;
    
    public function __construct($userID, MainCategory $mainCat = null, $ID = 0, $name = "")
    {
        parent::__construct($userID, $ID, $name);
        parent::setType(parent::SUB_CATEGORY);
        $this->parentCat = $mainCat;
    }
    public function setParentCat (MainCategory $parentCat)
    {
        $this->parentCat = $mainCat;
    }
    public function insertData (CategoriesWithDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
    public function populateDataByID ($ID, CategoryFromDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
}

class ExtraCategory extends Category
{
    
    public function __construct($userID, $ID = 0, $name = "")
    {
        parent::__construct($userID, $ID, $name);
        parent::setType(parent::EXTRA_CATEGORY);
    }
    public function insertData (CategoriesWithDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
    public function populateDataByID ($ID, CategoryFromDatabase $databaseObj)
    {
        $databaseObj->insertCategory($this);
    }
    public function showCategoriesAsString (Formatter $formObj) {
        echo "astring";
    }
}

class CategoryCollection
{
    public $categories = Array();
    
    public function __construct(array $collection = array())
    {
        foreach($collection as $individual) {
            $this->insertCat($individual);
        }
    }
    public function insertCat(Category $cat)
    {
        $this->categories[] = $cat;
    }
    public function showCategoriesWithFormatter ($splitter)
    {
        $showThis = "";
        foreach($this->categories as $category) {
            $showThis .= $category->name.$splitter;
        }
        // We strip the last ,-sign away
        $showThis = substr($showThis, 0, -1);
        return $showThis;
    }
}

// "old ones". I started making new one with better logic and efficiency. Old ones are still needed since they are in use:

abstract class CategoriesBaseClass extends KIRJASTOVariablesBasedOnDB implements FetchValues
{
    // This variable contains all the fields in the database:
    const MAIN_CATEGORY = 1;
    const EXTRA_CATEGORY = 2;
    const SUB_CATEGORY = 3;
    protected $type = 1;
    protected $allQuery = "";
    public $theUserID = null;

    public function __construct($userID, $table, $ID = null)
    {
        $this->theUserID = $userID;
        parent::__construct($table, $ID);
    }
    
    public function fetchQuery($parentCat = null)
    {
        if($this->allQuery) {
            $this->allQuery->data_seek(0);
        } else {
            $parentSQL = "";
            if($parentCat) {
                $parentSQL = "AND parentCat = '".$parentCat."' ";
            }
            $this->allQuery = self::$dataSource->queryWithExceptions("SELECT ID, name, type, parentCat FROM categories WHERE userID = '".$this->theUserID."' AND deleted = 0 AND type='".$this->type."' ".$parentSQL."ORDER BY parentCat, name");
        }
        if(self::$dataSource->affected_rows) {
            return $this->allQuery;
        }
        return false;
    }
    public function fetchArray($parentCat = null)
    {
        $retArray = Array();
        if(($query = self::fetchQuery($parentCat))) {
            while($retArray[] = $query->fetch_assoc());
            $retArray = array_slice($retArray, 0, -1);
            return $retArray;
        }
        return false;
    }
    public function setType ($type)
    {
        if($type) {
            $this->type = $type;
        }
    }
    public function delete($ID)
    {
        $query = "UPDATE ".$this->getTable()." SET deleted = 1 WHERE userID = '".$this->theUserID."' AND ID = '".self::$dataSource->filterVariable($ID)."'";
        self::$dataSource->queryWithExceptions($query);
    }
    public function getLastInsertID () {
        return self::$dataSource->insert_id;
    }
}

class Categories extends CategoriesBaseClass
{
    public function __construct($userID, $ID = null)
    {
        try {
            parent::validInt($ID);
            parent::__construct($userID, "categories", $ID);
        } catch (Exception $e) {
            throw new Exception($e);
        }
        $this->setType(self::MAIN_CATEGORY);
    }
    
    // ======
    // Most of the functions come from the inherited class (KIRJASTOVariablesBasedOnDB)
    // ======
    
    public function insert ($name = null, $userID = null)
    {
        if($name && $userID) {
            self::$dataSource->queryWithExceptions("INSERT INTO categories SET userID = '".$this->theUserID."', name='".$name."', type='".self::MAIN_CATEGORY."'");
        }
    }
    
    public function isValid()
    {
        if(($this->ID > 0) && $this->name && $this->type == self::MAIN_CATEGORY) {
            return true;
        }
        return false;
    }
    public function fetchByID ($ID) {
        echo "SELECT * FROM categories WHERE deleted = 0 AND userID = '".$this->theUserID."' AND ID='".$ID."' ORDER BY parentCat, name";
        return self::$dataSource->queryWithExceptions("SELECT * FROM categories WHERE deleted = 0 AND userID = '".$this->theUserID."' AND ID='".$ID."' ORDER BY parentCat, name");
    }
    public function delete ($ID)
    {
        $ID = self::$dataSource->filterVariable($ID);
        $query = "UPDATE ".$this->table." SET deleted = 1 WHERE ID = '".$ID."' OR parentCat = '".$ID."'";
        try {
            self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("UPDATE ".$this->table." SET deleted = 1 WHERE ID = '".$ID."' OR parentCat = '".$ID."'failed deleting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 300);
        }
    }
}

class SubCategories extends CategoriesBaseClass
{
    private $mainCat = null;
    
    public function __construct($userID, Categories $mainCat = null, $ID = null)
    {
        $this->type = 3;
        if($mainCat instanceof Categories && $mainCat->isValid()) {
            $this->mainCat = $mainCat;
            parent::__construct($userID, "categories", $ID);
        } elseif ($mainCat == null) {
            parent::__construct($userID, "categories");
        } else {
            throw new Exception("problem constructing. mainCategory is not valid");
        }
        $this->setType(self::SUB_CATEGORY);
    }
        
    public function insert ($name = null, $userID = null)
    {
        if($name && $userID) {
            self::$dataSource->queryWithExceptions("INSERT INTO categories SET userID = '".$this->theUserID."', name='".$name."', type='".self::SUB_CATEGORY."', parentCat = '".$this->mainCat->ID."'");
        }
    }
    public function isValid()
    {
        if(($this->ID > 0) && $this->name && $this->type == self::SUB_CATEGORY) {
            return true;
        }
        return false;
    }
    public function delete ($ID)
    {
        $ID = self::$dataSource->filterVariable($ID);
        $query = "UPDATE ".$this->table." SET deleted = 1 WHERE ID = '".$ID."'";

        try {
            self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed deleting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 300);
        }
    }
    public function setParentCat($obj) {
        $this->mainCat = $obj;
    }
}

class ExtraCategories extends CategoriesBaseClass
{
    protected $type = 2;

    public function __construct($userID, int $ID = null)
    {
        parent::__construct($userID, "categories", $ID);
        $this->setType(self::EXTRA_CATEGORY);
    }
    public function isValid()
    {
        if(($this->ID > 0) && $this->name && $this->type == self::EXTRA_CATEGORY) {
            return true;
        }
        return false;
    }   
    public function insert ($name = null, $userID = null)
    {
        if($name && $userID) {
            self::$dataSource->queryWithExceptions("INSERT INTO categories SET userID = '".$this->theUserID."', name='".$name."', type='".self::EXTRA_CATEGORY."'");
        }
    }
    public function linkExtraCategoriesToProduct (Array $mainExtraCats, $productID)
    {
        $extraQuery = "INSERT INTO extraCategoriesInProducts (extraCatID, productID) VALUES ";

        foreach($mainExtraCats as $var) {
            $extraQuery .= "('".$var."', '".$productID."'),";
        }
        
        $extraQuery = substr_replace($extraQuery, "", -1);
        self::$dataSource->queryWithExceptions($extraQuery, "extraQuery");
    }
    public function toArray()
    {
        $array = Array();
        $queried = $this->fetchQuery();
        while($tulos = $queried->fetch_assoc()) {
            $array[$tulos['ID']] = $tulos['name'];
        }
        return $array;
    }
    public function delete ($ID)
    {
        $ID = self::$dataSource->filterVariable($ID);
        $query = "UPDATE ".$this->table." SET deleted = 1 WHERE ID = '".$ID."'";

        try {
            self::$dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception("failed deleting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 300);
        }
    }
}
?>