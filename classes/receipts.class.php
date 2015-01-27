<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 */

// Class dependent on KIRJASTO-libraries. Constructor insists this with DB-connection
// You can later on change this to general MySQLI if needed.

/*
 * ----- Receipt -----
 * !! NOTE !! Requires categories.class, path can be defined in constructor
 * 
 * json-requirements need to be listed.
 * 
 * RECEIPT:
 * === METHODS ===
 * - toJSON = We set the  proper variables to a new object that we return after 
 *      the proper variables are tied to the object. This object can then be used 
 *      in json-data transfers.
 */

class Receipt
{
    const 
            MAIN_CAT = Category::MAIN_CATEGORY,
            SUB_CAT = Category::SUB_CATEGORY,
            EXTRA_CAT = Category::EXTRA_CATEGORY;
    private 
            $userID = null,
            $timestamp = null,
            $dataSource = null,
            $countrySettings = array(),
            $DBtable = "receipts";
    public 
            $user = null,
            $ID = null,
            $cost = 0,
            $place = "",
            $whoBought = 0,
            $info = "",
            $products = null,
            $categories = Array(
                "mainCat" => null, 
                "subCat" => null, 
                "extraCats" => Array()
                );
    
    public function __construct($user, $DB, $ID = null)
    {
        $categoryPath = $_SERVER["DOCUMENT_ROOT"]."/classes/categories.class.php";
        if(!require_once $categoryPath) {
            throw new Exception("This class requires certain classes to be loaded and 
                there was problem loading class: ".$categoryPath);
        }
        $this->userID = $user;
        $this->dataSource = $DB;
        $this->ID = $ID;
        $this->countrySettings["dateFormat"] = "d.m.Y";
    }
    public function isValid()
    {
        return true;
    }
    public function setData(array $arrays)
    {
        // Compulsory data:
        $this->timestamp = (isset($arrays["timestamp"])) ? $arrays["timestamp"] : $this->timestamp;
        $this->cost = (isset($arrays["cost"])) ? $arrays["cost"] : $this->cost;
        $this->categories["mainCatID"] = (isset($arrays["mainCatID"])) ? $arrays["mainCatID"] : $categories["mainCatID"];
        
        // voluntarily details:
        $this->categories["subCatID"] = (isset($arrays["subCatID"])) ? $arrays["subCatID"] : $categories["subCatID"];
        $this->categories["extraCatIDs"] = (isset($arrays["extraCatIDs"])) ? explode(",", $arrays["extraCatIDs"]) : $categories["extraCatIDs"];
        
        $this->place = (isset($arrays["place"])) ? $arrays["place"] : $this->place;
        $this->info = (isset($arrays["info"])) ? $arrays["info"] : $this->info;
    }
    public function getProductCount()
    {
        return ( count($this->products)-1 );
    }
    // Date[0] = value
    // Date[1] = date format
    public function setDate($timestamp = null, DateTime $date = null)
    {
        $this->timestamp = $timestamp ? $timestamp : ($date ? strtotime($date->format('d.m.Y')) : $this->timestamp);
    }
    public function getDate($format = "d.m.Y")
    {
        return date($format, $this->timestamp);
    }
    public function getUserID()
    {
        return $this->userID;
    }
    public function getTimestamp()
    {
        return $this->timestamp;
    }
    public function countTotalCost()
    {
        $cost = 0;
        // If no products have been set, the sum is 0:
        if(count($this->products) == 0) {
            return 0;
        }
        
        foreach($this->products as $product) {
            $cost += $product->cost;
        }
        return $cost;
    }
    public function setTotalCost($cost)
    {
        $this->cost = $cost;
    }
    public function toJSON()
    {
        $jsonObj = null;
        $jsonObj->ID = $this->ID;
        $jsonObj->place = $this->place;
        $jsonObj->whoBought = $this->whoBought;
        $jsonObj->info = $this->info;
        $jsonObj->products = $this->products;
        if(is_array($jsonObj->products)) {
            foreach($jsonObj->products as $product) {
                $product->warrantyMonths = $product->warranty->warrantyInMonths();
                unset($product->warranty);
            }
        }
        $jsonObj->categories = $this->categories;
        $jsonObj->date = $this->getDate($this->countrySettings["dateFormat"]);
        $jsonObj->cost = $this->countTotalCost();
        return $jsonObj;
    }
    public function assimilateJSONValues($jsonString)
    {
        $jsonArray = json_decode($jsonString);
        $this->ID = $jsonArray->receiptID;
        $this->cost = $jsonArray->receiptCost;
        $this->setDate(null,DateTime::createFromFormat('d.m.Y', $jsonArray->receiptDate));
        $this->place = $jsonArray->place;
        $this->whoBought = $jsonArray->whoBought;
        $this->info = $jsonArray->receiptInfo;
        $this->categories['mainCat'] = $jsonArray->receiptMainCat;
        $this->categories['subCat'] = $jsonArray->receiptSubCat;
        $this->categories['extraCats'] = $jsonArray->receiptExtraCats;
        $this->products = $jsonArray->products;
    }
    
    function insertOnlyReceiptInfo ()
    {
        $keySQL = "";
        $valueSQL = "";
        $arrays = array(
            "userID" => $this->userID,
            "time" => $this->timestamp,
            "place" => $this->place,
            "whoBought" => $this->whoBought,
            "info" => $this->info
        );

        foreach($arrays as $key => $var) {
            $keySQL .= $this->dataSource->filterVariable($key).",";
            $valueSQL .= "'".$this->dataSource->filterVariable($var)."',";
        }

        //-- strip the last ,-characters:
        $keySQL = substr($keySQL, 0, -1);
        $valueSQL = substr($valueSQL, 0, -1);
        //--

        $query = "INSERT INTO ".$this->dataSource->filterVariable($this->DBtable)." (".$keySQL.") VALUES (".$valueSQL.")";
        
        try {
            $this->dataSource->queryWithExceptions($query);
        } catch (Exception $e) {
            throw new Exception($query."failed inserting data in class: ".get_class($this)." / ".__CLASS__.", line:".__LINE__.PHP_EOL."Exception occured:".PHP_EOL.debug_backtrace(), 400);
        }
        
        $this->ID = $this->dataSource->insert_id;
        return true;
    }
    public function delete(array $IDs)
    {
        $query = "";
        $first = 0;
        foreach ($IDs as $ID) {
            if(!$first) {
                $query = "
                    DELETE FROM ".$this->DBtable."
                        WHERE ID = '".$this->dataSource->filterVariable($ID)."'
                        ";
            } else {
                $query .= " OR ID = '".$this->dataSource->filterVariable($ID)."'";
            }
            $first = 1;
        }

        $this->dataSource->queryWithExceptions($query);
    }
}

class receiptCollection
{
    public 
            $receipts = array();
    
    public function addReceipt(Receipt $rcpt)
    {
        return $this->receipts[] = $rcpt;
    }
    public function getReceiptByData (Receipt $rcptData)
    {
        foreach($this->receipts as $rcpt) {
            if ($rcpt->ID == $rcptData->ID) {
                return $rcpt;
            }
        }
        return false;
    }
    public function toJSON()
    {
        return get_object_vars($object);
    }
}

/*
 * =============================================================================
 * This is the functionality, when the user submits their receipt. The functionality
 * will be prosessed on the same page.
 * =============================================================================
 * 
 * Accepts these POST-variables:
 * Required:
 -  newReceipt (submit-button), datepicker (date for the receipt), receiptCost, mainCatID
 * 
 * Receipt / general:
 -  receiptCost, mainCatID, datepicker, subCatID, extraCatIDs, place, addAsPremadePlace, info
 * Individual products:
 -  productName, productCost, productMainCatID, productSubCatID, productExtraCatIDs, productWarranty, productInfo, newPremadeProduct
 */

class ReceiptFactory
{
    private 
            $debugInfo = "";
    public 
            $dataSource = null,
            $userID = null,
            $variables =  Array(),
            $baseDir = null,
            $startLimit = 0,
            $endLimit = 10;
    
    // In the constructor we pass along the POST-array from the page:
    public function __construct($userID, MySQL $dataSource, $baseDir = null)
    {
        $this->userID = $userID;
        $this->dataSource = $dataSource;
        $this->variables = $_POST;
        $this->baseDir = $baseDir ? $baseDir : $_SERVER["DOCUMENT_ROOT"]."/classes/";
        
        $classArr = array("categories.class", "products.class", "warranties.class");
        foreach ($classArr as $filename) {
            if(!require_once $this->baseDir .  $filename . '.php') {
                throw new Exception("This class requires certain classes to be loaded and 
                    there was problem loading class: ".$this->baseDir.$filename);
            }
        }
    }
    
    /*
     * fetchByID retrieves more data than fetchByLimit. The idea is that with ID fetching
     * we can show the user all the information regarding the receipt andindividual products.
     * If necessary user can then modify the receipt information as he wishes (if that' the case
     * in user interface).
     */
    private function fetchByID($ID)
    {
        return $this->dataSource->queryWithExceptions("
                SELECT re.ID AS receiptID, re.time, re.place, re.info AS receiptInfo, re.whoBought,  
                    pro.ID AS productID, pro.name, pro.cost, pro.mainCat, pro.subCat, pro.info AS productInfo, pro.leftOver, pro.warrantyTill,
                    GROUP_CONCAT(xtra.extraCatID) AS extraCatIDs, GROUP_CONCAT(extraCats.name) AS extraCatNames, 
                    mainCat.name AS mainCatName, subCat.name AS subCatName
                FROM receipts re 
                    LEFT JOIN products pro ON pro.receiptID = re.ID 
                    LEFT JOIN extraCategoriesInProducts xtra ON xtra.productID = pro.ID
                    LEFT JOIN categories mainCat ON mainCat.ID = pro.mainCat
                    LEFT JOIN categories subCat ON subCat.ID = pro.subCat 
                    LEFT JOIN categories extraCats ON extraCats.ID = xtra.extraCatID 
                WHERE re.userID = '" . $this->userID . "' 
                    AND re.ID='" . $ID . "'
                GROUP BY pro.ID 
                ORDER BY pro.leftOver DESC, pro.ID",
            "Fecthing receipts from DB (".__CLASS__."::".__METHOD__.")");
    }
    /*
     * fetchByLimit retrieves less data than fetchByID. The idea is that with Limit fetching
     * we only fetch the necessary data for the user to make up his mind if he wants to see more
     * or modify the data
     */
    private function fetchByLimit($start, $end)
    {
        return $this->dataSource->queryWithExceptions("
                SELECT re.ID as ReceiptID, re.time, SUM(pro.cost) AS cost, re.place, re.info AS receiptInfo, 
                re.whoBought, 
                COUNT(pro.ID) AS productCount,
                cat.name AS mainCatName, cat.ID AS mainCatID 
                FROM receipts re 
                    LEFT JOIN products pro ON pro.receiptID = re.ID 
                    LEFT JOIN categories cat ON cat.ID = pro.mainCat 
                WHERE re.userID = '".$this->userID."' 
                GROUP BY re.ID
                    LIMIT ".$start.",".$end, 
            "Fecthing receipts from DB (".__CLASS__."::".__METHOD__.")");
    }
    public function update(Receipt $rcpt)
    {
        // First the receipt information to Database:

        $this->debugInfo = print_r($rcpt, true);

        $this->dataSource->queryWithExceptions("
            UPDATE receipts re
                SET re.time = '".$rcpt->getTimestamp()."',
                    re.place = '".$rcpt->place."',
                    re.info = '".$rcpt->info."',
                    re.whoBought = '".($rcpt->whoBought ? $rcpt->whoBought : 0)."'
            WHERE re.userID = '".$rcpt->getUserID()."' AND re.ID = '".$rcpt->ID."'"
                , "class:".__class__." method:".__method__." rcpt:".$this->debugInfo);

        $totalCostOfProducts = 0;
        foreach($rcpt->products as $product) {
            $subCatSQL = $product->subCat ? ",pro.subCat = '".$product->subCat."'" : "";

            $totalCostOfProducts += $product->cost;
            $this->dataSource->queryWithExceptions("
                UPDATE products pro, receipts rcpt
                    SET pro.mainCat = '".$product->mainCat."',
                        pro.cost = '".$product->cost."',
                        pro.name = '".$product->name."',
                        pro.info = '".$product->info."',
                        pro.warrantyTill = '".Warranty::monthsToTimestamp($product->warrantyTill)."'
                        ".$subCatSQL."                        
                WHERE pro.leftOver = 0 
                    AND pro.receiptID = '".$rcpt->ID."'
                    AND pro.ID = '".$product->ID."'"
                    , "class:".__class__." method:".__method__." rcpt:".$this->debugInfo);

            // Extracategories. First we remove all and then we set the ones selected:
            $this->setExtraCategories($product->ID, $product->extraCats);
        }

        if(($leftOverCost = ($rcpt->cost - $totalCostOfProducts)) < 0) {
            $leftOverCost = 0;
        }
        
        $subCatSQL = $rcpt->categories['subCat'] ? ",pro.subCat = '".$rcpt->categories['subCat']."'" : "";
        $leftOverProductQ = $this->dataSource->queryWithExceptions("
            SELECT pro.ID
                FROM products pro
                    LEFT JOIN receipts rcpt ON rcpt.ID = pro.receiptID
                WHERE pro.leftOver = 1 
                    AND pro.receiptID = '".$rcpt->ID."'
                    AND rcpt.userID = '".$rcpt->getUserID()."'"
                , "class:".__class__." method:".__method__." rcpt:".$this->debugInfo);
        
        $leftOverProductID = $leftOverProductQ->fetch_row();
        $leftOverProductID = $leftOverProductID[0];

        $this->dataSource->queryWithExceptions("
            UPDATE products pro, receipts rcpt
                SET pro.mainCat = '".$rcpt->categories["mainCat"]."',
                    pro.cost = '".$leftOverCost."'
                    ".$subCatSQL."
            WHERE pro.ID = '".$leftOverProductID."'"
                , "class:".__class__." method:".__method__." rcpt:".$this->debugInfo);

        $this->setExtraCategories($leftOverProductID, $rcpt->categories["extraCats"]);
    }
    private function setExtraCategories($productID, $extraCats)
    {
        if(!is_array($extraCats)) {
            $extraCats = array($extraCats);
        }
        $this->dataSource->queryWithExceptions("
            DELETE FROM extraCategoriesInProducts
                WHERE productID = '".$productID."'"
                , "class:".__class__." method:".__method__." rcpt:".$this->debugInfo);

        $extraCatsSQL = "";
        foreach($extraCats as $extraCat) {
            $extraCatsSQL .= "('".$extraCat."','".$productID."'),";
        }
        $extraCatsSQL = $extraCatsSQL ? substr($extraCatsSQL, 0, -1) : "";

        $this->dataSource->queryWithExceptions("
            INSERT INTO extraCategoriesInProducts (extraCatID, productID)
                VALUES ".$extraCatsSQL
                , "class:".__class__." method:".__method__." rcpt:".$this->debugInfo); 
    }
    
    private function createExtraCatCollection(array $extraCatIDs, array $extraCatNames = null)
    {
        $extraCats = array();
        $extraCatCollection = new CategoryCollection();
        $i = 0;

        for($i=0; $extraCatIDs[$i]; $i++) {
            $extraCats[$i]['IDs'] = $extraCatIDs[$i];
            $extraCats[$i]['Names'] = $extraCatNames[$i] ? $extraCatNames[$i] : "";
        }
        foreach($extraCats as $varri) {
            $extraCatCollection->insertCat(new extraCategory ($this->userID, $varri['IDs'], $varri['Names']));
            echo $varri['IDs'], $varri['Names'];
        }
        return $extraCatCollection;
    }
    private function receiptDataFromResult(mysqli_result $receiptQ, receiptCollection $emptyReceiptColObj)
    {
        $receiptCol = $emptyReceiptColObj;
        $rcpt = null;
        $helpArray = array(
            "productID", "name", "cost", "productInfo", "leftOver", "warrantyTill"
        );
        
            /*
             * ========================
             * Deal with receipt information:
             * ========================
             */
        
        while($results = $receiptQ->fetch_assoc()) {
            if($results["receiptID"] != $rcpt->ID) {
                $rcpt = &$receiptCol->addReceipt(new Receipt(new User($this->userID), $this->dataSource));
                $rcpt->ID = $results["receiptID"];
                $rcpt->info = $results["receiptInfo"];
                $rcpt->place = $results["place"];
                $rcpt->setDate($results["time"]);
                $rcpt->whoBought = $results["whoBought"];
                $rcpt->categories['mainCat'] = new MainCategory ($this->dataSource, $this->userID, $results["mainCat"], $results["mainCatName"]);
                $rcpt->categories['subCat'] = 
                        !empty($results["subCat"])
                        ? new SubCategory ($this->dataSource, $this->userID, $rcpt->categories['mainCat'], $results["subCat"], $results["subCatName"]) 
                        : new SubCategory ($this->dataSource, $this->userID, $rcpt->categories['mainCat']);
                
                $rcpt->categories['extraCats'] = 
                        !empty($results["extraCatIDs"])
                        ? explode(",", $results["extraCatIDs"])
                        : array(0);

                // By starting from -1 we calculate the total number of custom products, since there is
                // always one leftover-product, which is basically the same thing as receipt.
                // Initialize the productCount here, when there is a new receipt in question:
                $productCount = -1;
            }
            /*
             * ========================
             */
            
            /*
             * ===================
             * Deal with products-information here. There is no need for IF-statements, since there are
             * always at least one product in each receipt:
             * ===================
             */
            
            $productCount++;

            $rcpt->products[$productCount]->mainCat = new MainCategory ($this->dataSource, $this->userID, $results["mainCat"], $results["mainCatName"]);
            $rcpt->products[$productCount]->subCat = new subCategory ($this->dataSource, $this->userID, $rcpt->products[$productCount]->mainCat, $results["subCat"], $results["subCatName"]);
            $rcpt->products[$productCount]->warranty = new Warranty (new DateTime("now"), new DateTime(Warranty::timestampToDate($results["warrantyTill"])));

            foreach ($helpArray as $varri) {
                $rcpt->products[$productCount]->$varri = $results[$varri];
            }

            $rcpt->products[$productCount]->extraCats = array_combine(
                explode(",", $results["extraCatIDs"])
                , explode(",", $results["extraCatNames"])
            );
            // ====================
        }
        return $receiptCol;
    }
    
    public function fetchReceiptsByLimit($start, $end)
    {
        $this->startLimit = $start;
        $this->endLimit = $end;
        $receipts = new receiptCollection();
        
        $receiptQ = $this->fetchByLimit($this->startLimit, $this->endLimit);
        
        $this->receiptDataFromResult($receiptQ, $receipts);
        return $receipts;
    }
    public function fetchReceiptByID($ID)
    {
        $receipts = new receiptCollection();
        $receiptQ = $this->fetchByID($ID);
        
        $this->receiptDataFromResult($receiptQ, $receipts);
        
        return $receipts;
    }
    
    // Make sure the compulsory values have been given and are correct:
    public function checkValidity($return = 1)
    {
        $reason = 0;
        $msg = "";
        // The submit form submit-button has not been set, so user has not tried to send the form:
        if(empty($this->variables["datepicker"])) {
            $reason = 100;
            $msg = "datepicker";
        }
        elseif(empty($this->variables["receiptCost"])) {
            $reason = 100;
            $msg = "receiptCost";
        }
        elseif(empty($this->variables["mainCatID"])) {
            $reason = 100;
            $msg = "mainCatID";
        }
        if($return == 1 && $reason == 0) {
            return true;
        }
        $variablesi = print_r($_POST, TRUE);
        throw new Exception($msg.":".PHP_EOL.$variablesi, $reason);
    }

    /* The exceptions need to be catched!
     * Exceptions that can occur:
     -  Database-exceptions (queryWithExceptions-function)
     -  checkValidity-function
     -  Categories-class
     -  Products-class
     */
    // Before starting the create process, you should check the validity, so
    // you don't do unnecessary creation of the receipt:
    public function create(Category $mainCat, SubCategory $subCat = null, $place = "")
    {
        $receiptMainCatID = $this->variables['mainCatID'];
        $mainCat = new MainCategory($this->dataSource, $this->userID, 
                    (!empty($receiptMainCatID) ? ((int) $receiptMainCatID) : 0)
                );
        $subCat = new SubCategory($this->dataSource, $this->userID, $mainCat, (
                !empty($this->variables['subCatID']) ? ((int) $this->variables['subCatID']) : 0)
            );
            
        // if the variable validity-check fails we stop the function:
        if(!$this->checkValidity(1)) {
            return;
        }
        /*--------------------------
        * Variable initialization:
        * -------------------------
        */

        $mainExtraCats = !empty($this->variables['extraCatIDs']) ? explode(",", $this->variables['extraCatIDs']) : array();

        $place = $this->variables['place'];        

        /* We also need to format the variables to correct float-format, this is a pain-in-the-ass with PHP, really
         * sucks imho (spent hours wondering about the conversion, lesson well learned).
         * 
         * We also want to initialize the total cost for all the products inserted, so we can calculate how much is 
         * left for the receipts inserted cost.
         *  Also the leftover cost is about calculating the same costs. The are calculated at the end of this try-phase
         * 
         */
        $totalProductCost = 0.0;
        $receiptCost = (float) str_replace(",", ".", $this->variables['receiptCost']);
        $receiptDate = $this->variables['datepicker'];
        $time = strtotime($receiptDate);
        $receiptInfo = $this->variables['info'];

        /* --------------------------
        * We set and insert the receipt to database and also get the receipt ID from DB insertID
        * --------------------------
        */

        $receipt = new Receipt($this->userID, $this->dataSource);
        $receipt->setDate($time);
        $receipt->place = $place;
        $receipt->info = $receiptInfo;

        $receipt->insertOnlyReceiptInfo();

    /* =====================================
    * Process the individual product-exceptions if the user selected any (most of the time they wont). But for example if somebody wants to set
    * warranty-times to their products, then they have to use separate products.
    * =====================================
    */

        // FOREACH-LOOP HERE! WHEN DEALING WITH INDIVODUAL PRODUCTS, WHY THE HELL DID I NOT USE IT? PFFFF...
        // JUST HAVE TO RECONSTRUCT A LOT OF THIS TO CHANGE IT USING FOREACH

        for($i=0; $this->variables['productCost'][$i] != "" || $this->variables['productName'][$i] != ""; $i++) {

            if(!($this->variables['productCost'][$i] != "" && $this->variables['productName'][$i] != "")) {
                echo $this->variables['productCost'][$i]. "-" .$this->variables['productName'][$i];
                exit("The individual products didn't have name AND cost");
            }

            // We will only deal with mainCategory here, since we need productID to be able to insert extraCategories:
            $productMainCat = !empty($this->variables['productMainCatID'][$i]) 
                ? (new MainCategory($this->dataSource, $this->userID, (int) $this->variables['productMainCatID'][$i])) 
                    : $mainCat;
            $productSubCat = !empty($this->variables['productSubCatID'][$i]) 
                ? (new SubCategory($this->dataSource, $this->userID, $productMainCat, (int) $this->variables['productSubCatID'][$i])) 
                    : $subCat;
            // The extra categories are processed on the submit by javascript and divided by ,-character. We must explode them to array now:
            $productExtraCats = !empty($this->variables['productExtraCatIDs'][$i]) 
                ? explode(",", $this->variables['productExtraCatIDs'][$i]) 
                    : null;

            $premadeCost = 0;
            $productName = $this->variables['productName'][$i];
            $productCost = Product::strToFloat($_POST['productCost'][$i]);
            $productInfo = $this->variables['productInfo'][$i];
            $productWarranty = Warranty::monthsToTimestamp($this->variables['productWarranty'][$i]);

            // -----------

            $product = new Product($this->dataSource, $this->userID);
            $product->insert(
                array(
                    "receiptID", "name", "cost", "mainCat",
                    "subCat", "warrantyTill", "info"),
                array(
                    $receipt->ID, $productName, $productCost, $productMainCat->ID, 
                    $productSubCat->ID, $productWarranty, $productInfo)
            );

            // We need to get the new products ID now, before it's too late to safe the world!
            if(!empty($productExtraCats)) {
                $product->linkExtraCategoriesToProduct($productExtraCats);
            }

            if(!empty($this->variables['premadeProduct'][$i])) {
                $totalProductCost += $premadeCost;
            } else {
                $totalProductCost += $productCost;
            }
        }

        /* ============================
    * Calculating the left-over cost, after the inputted products. If the total sum for receipt is: 
    * 100EUR and the user inputted 10EUR product, then the left over is 90EUR.
    * ============================
    */
        $receiptsLeftoverCost = $receiptCost - $totalProductCost;

        if($receiptsLeftoverCost <= 0) {
            if(!$this->dataSource->query("UPDATE receipts SET cost = '".$totalProductCost."' WHERE id = '".$receipt->ID."'")) {
                echo("We had to modify the receipt sum, because leftover-cost was negative");
            }
        }
        // And we insert the receipts left-over costs to products:
        $leftOverProduct = new Product($this->dataSource, $this->userID);
        $leftOverProduct->insert(
            array(
                "receiptID", "cost", "mainCat", "subCat", "leftOver"),
            array(
                $receipt->ID, $receiptsLeftoverCost, $mainCat->ID, $subCat->ID, "1")
        );

        if(!empty($mainExtraCats[0])) { // Reason for IF: Execute only if user has selected the extra category for receipt, otherwise there will be unnecessary error from this:
            $leftOverProduct->linkExtraCategoriesToProduct($mainExtraCats);
        }
    }
}
?>