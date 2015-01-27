<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * Structure as follows:
 * 
 * ----- (Abstract) category ----
 *  * handles the basic structure, variables and methods.
 *  * Important ones are the category-type constants: MAIN_/SUB_/EXTRA:_CATEGORY
 *      * These define which int-type is equal to certain category, the database uses these values.
 * 
 * ----- CategoriesWithDatabase -----
 *  * This holds the most methods for accessing and manipulating the database
 *  * IMPLEMENTS FetchValues, CUD - FetchValues for retrieving database records and 
 * CUD = CREATE, UPDATE, DELETE - methods
 * === METHODS ===
   - insert ($catName, $parentCat = 0)
 *      * Inserts category of type $this->type and name= $catName
 *      * If parentCat has been defined we insert that to the Database, making 
 *        the category a subCategory.
    - update(array $values)
 *      * Update the present category with key => value pair. Key is the attribute.
    - delete($ID)
    - fetchCategory($ID)
 *      * Fetch all category-data from DB with the specific database-ID
    - fetchQuery ()
 *      * Fetch all categories from the set user ($this->userID) of certain type ($this->type)
    - fetchArray()
 *      * transforms the query to array-format
    - getLastInsertID ()
 *      * getter for the last insert in database-connection
 * 
 * ----- MainCategory / SubCategory / ExtraCategory -----
 *  * The subClasses we actually use. Contains some extraMethods, but don't override anything.
 * === VARIABLES ===
 * - ParentCat = defines in what categoryID is the subCategorys parent category.
 * * === METHODS ===
 * - setParentCat (MainCategory $parentCat = null)
 *  * Sets the parentCat-variable neede, when inserting subCategories.
 * - (static) linkExtraCategoriesToProduct ($DB, Array $extraCats, $productID)
 *  * links the array of extraCategories IDs to certain product.
 *  * The method is static, because it's not really linked to the instance, I want 
 *      to make that point clear.
 */

abstract class Category
{
    const 
            MAIN_CATEGORY = 1,
            EXTRA_CATEGORY = 2,
            SUB_CATEGORY = 3;
    protected
            $type = 0;
    public
            $userID = null,
            $name = "",
            $ID = 0;
    
    public function __construct($userID, $type)
    {
        $this->type = $type;
        $this->userID = $userID;
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
    class CategoriesWithDatabase extends Category implements FetchValues, CUD
    {
        public
                $dataSource= null;
        const
                TABLE = "categories";

        public function __construct($DB, $userID, $type)
        {
            parent::__construct($userID, $type);
            $this->dataSource = $DB;
        }

        // CUD (create, update, delete) - interface:
        public function insert ($catName, $parentCat = 0)
        {
            return $this->dataSource->queryWithExceptions("
                INSERT INTO ".self::TABLE." 
                    (name,deleted,userID,type,parentCat) 
                    VALUES
                        ('".$catName."', 0, '".$this->userID."', '".$this->type."', '".$parentCat."')");
        }
        public function update(array $values)
        {
            foreach($values as $key => $value) {
                $toBeSet .= "'".$key."'='".$value."'";
            }
            $toBeSet = implode(",", $toBeSet);
            
            $this->dataSource->queryWithExceptions("
                UPDATE ".self::TABLE." 
                    SET ".$toBeSet."'
                        WHERE userID = '".$this->userID."' 
                            AND ID = '".$this->ID."'");
        }
        public function delete($ID)
        {
            $this->dataSource->queryWithExceptions("
                DELETE FROM ".self::TABLE." 
                    WHERE userID = '".$this->userID."'
                        AND ID = '".$ID."'");
        }

        // --------
        public function fetchCategory ($ID)
        {
            $extraSQL = "";
            if($this->parentCat) {
                $extraSQL = " AND parentCat = '".intval($this->parentCat)."'";
            }
            return $this->dataSource->queryWithExceptions("
                SELECT * 
                    FROM ".self::TABLE." 
                    WHERE deleted = 0 
                        AND userID = '".$this->userID."' 
                        AND ID='".$ID."'");
        }

        // FetchValues-interface:
        public function fetchQuery ()
        {
            $extraSQL = "";
            if($this->parentCat) {
                $extraSQL = " AND parentCat = '".intval($this->parentCat->ID)."'";
            }

            return $this->dataSource->queryWithExceptions("
                SELECT * 
                    FROM ".self::TABLE." 
                    WHERE deleted = 0 
                        AND userID = '".$this->userID."' 
                        AND type = '".$this->type."'
                        ".$extraSQL." 
                    ORDER BY parentCat, name");        
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
        public function getLastInsertID () {
            return $this->dataSource->insert_id;
        }
    }

        class MainCategory extends CategoriesWithDatabase
        {
            public function __construct($DB, $userID, $ID = 0, $name = "")
            {
                parent::__construct($DB, $userID, parent::MAIN_CATEGORY);
                $this->name = $name;
                $this->ID = $ID;
            }
        }
        class SubCategory extends CategoriesWithDatabase
        {
            protected 
                    $parentCat = null;

            public function __construct($DB, $userID, MainCategory $mainCat = null, $ID = 0, $name = "")
            {
                parent::__construct($DB, $userID, parent::SUB_CATEGORY);
                $this->name = $name;
                $this->ID = $ID;
                $this->setParentCat($mainCat);
            }
            public function setParentCat (MainCategory $parentCat = null)
            {
                $this->parentCat = $parentCat;
            }
            public function insert ($name)
            {
                return parent::insert($name, $this->parentCat->ID);
            }
        }

        class ExtraCategory extends CategoriesWithDatabase
        {
            const
                    EXTRA_TABLE = "extraCategoriesInProducts";
            
            public function __construct($DB, $userID, $ID = 0, $name = "")
            {
                parent::__construct($DB, $userID, parent::EXTRA_CATEGORY);
                $this->name = $name;
                $this->ID = $ID;
            }
            public static function linkExtraCategoriesToProduct ($DB, Array $extraCats, $productID)
            {
                $extraQuery = "INSERT INTO ".self::EXTRA_TABLE." (extraCatID, productID) VALUES ";

                foreach($extraCats as $var) {
                    $extraQuery .= "('".$var."', '".$productID."'),";
                }

                $extraQuery = substr_replace($extraQuery, "", -1);
                $DB->queryWithExceptions($extraQuery, "extraQuery");
            }
        }
        class AllCategory extends CategoriesWithDatabase
        {
            public 
                    $DB = null,
                    $userID = null;
                    
            public function __construct($DB, $userID)
            {
                $this->DB = $DB;
                $this->userID = $userID;
            }
            public function fetchAll ()
            {
                return $this->DB->queryWithExceptions("
                    SELECT * 
                        FROM categories 
                        WHERE deleted = 0 
                            AND userID = '".$this->userID."' 
                        ORDER BY type, parentCat, name");        
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
        foreach($this->categories as $category) {
            $showThis .= $category->name.$splitter;
        }
        // We strip the last ,-sign away
        $showThis = substr($showThis, 0, -1);
        return $showThis;
    }
}

// "old ones". I started making new one with better logic and efficiency. Old ones are still needed since they are in use:

/*abstract class CategoriesBaseClass extends KIRJASTOVariablesBasedOnDB implements FetchValues
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
}*/
?>