<?php
// Class dependent on KIRJASTO-libraries. Constructor insists this with DB-connection

class Receipt extends KIRJASTOVariablesBasedOnDB {
    public $theUserID = null;
    public $date = null;
    public $cost = null;
    public $categories = Array(
        "mainCatID" => null, 
        "subCatID" => null, 
        "extraCatIDs" => Array()
        );
    const MAIN_CAT = CategoriesBaseClass::MAIN_CATEGORY;
    const SUB_CAT = CategoriesBaseClass::SUB_CATEGORY;
    const EXTRA_CAT = CategoriesBaseClass::EXTRA_CATEGORY;
    
   public function __construct($userID, $ID = null) {
        $this->theUserID = $userID;
        parent::validInt($ID);
        parent::__construct("receipts", $ID);
    }
    public function isValid() {
        return true;
    }
    public function setData(array $arrays) {
        // Compulsory data:
        $this->date = (isset($arrays["date"])) ? $arrays["date"] : $date;
        $this->cost = (isset($arrays["cost"])) ? $arrays["cost"] : $cost;
        $this->categories["mainCatID"] = (isset($arrays["mainCatID"])) ? $arrays["mainCatID"] : $categories["mainCatID"];
        
        // voluntarily details:
        $this->categories["subCatID"] = (isset($arrays["subCatID"])) ? $arrays["subCatID"] : $categories["subCatID"];
        $this->categories["extraCatIDs"] = (isset($arrays["extraCatIDs"])) ? explode(",", $arrays["extraCatIDs"]) : $categories["extraCatIDs"];
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
 -  receiptCost, mainCatID, datepicker, subCatID, extraCatIDs, place, addAsPremadePlace, paymentID, info
 * Individual products:
 -  productName, productCost, productMainCatID, productSubCatID, productExtraCatIDs, productWarranty, productVAT, productInfo, newPremadeProduct
 */

// TÄMÄN VOISI TOTEUTTAA FACTORY / DECORATOR DESING PATTERNILLA?

class ReceiptBuilder
{
    public $dataSource = null;
    public $userID = null;
    public $variables =  Array();
    public $baseDir = null;
    
    // In the constructor we pass along the POST-array from the page:
    public function __construct($userID, MySQL $dataSource, $baseDir = null)
    {
        $this->userID = $userID;
        $this->dataSource = $dataSource;
        $this->variables = $_POST;
        $this->baseDir = $baseDir ? $baseDir : $_SERVER["DOCUMENT_ROOT"]."/classes/";
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
        throw new Exception($msg, $reason);
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
    public function create(Categories $mainCat, $usersDefaultVAT, SubCategories $subCat = null, $place = "") {
        
        // if the variable validity-check fails we stop the function:
        if(!$this->checkValidity(1)) {
            return;
        }
        
        $classArr = array("categories", "products", "vat");
        foreach ($classArr as $filename) {
            require_once $this->baseDir .  $filename . '.php';
        }
                
        $mainCat->isValid();
        
        /*--------------------------
         * Variable initialization:
         * -------------------------
         */
        $mainCat = new Categories(USER_ID, 
                (!empty($this->variables['mainCatID']) ? ((int) $this->variables['mainCatID']) : 0)
            );
        $subCat = new SubCategories(USER_ID, $mainCat, (
                !empty($this->variables['subCatID']) ? ((int) $this->variables['subCatID']) : 0)
            );
        $mainExtraCats = !empty($this->variables['extraCatIDs']) ? explode(",", $this->variables['extraCatIDs']) : array();
        // Setting user specific VAT:
        $mainVAT = $usersDefaultVAT;
        
        $place = $this->variables['place'];
        // We need to format the time to unix timestamp first
        $time = strtotime($this->variables['datepicker']);
        // We also want to initialize the total cost for all the products inserted, so we can calculate how much is left for the receipts inserted cost.
        // Also the leftover cost is about calculating the same costs. The are calculated at the end of this try-phase
        $totalProductCost = 0.0;
        $receiptsLeftoverCost = 0.0;
        $receiptCost = (float) str_replace(",", ".", $this->variables['receiptCost']);
        $receiptInfo = $this->variables['info'];

        /* --------------------------
         * We set and insert the receipt to database and also get the receipt ID from DB insertID
         * --------------------------
         */

        $receipt = new Receipt(USER_ID);
        $receipt->setArray( array(
            "time" => $time,
            "userID" => USER_ID,
            "place" => $place,
            "paymentMethodID" => $this->variables["paymentID"],
            "info" => $receiptInfo,
            )
        );

        $receipt->insert();
        $receipt->ID = $receipt->lastInsertID;

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
                ? (new Categories(USER_ID, (int) $this->variables['productMainCatID'][$i])) 
                    : $mainCat;
            $productSubCat = !empty($this->variables['productSubCatID'][$i]) 
                ? (new SubCategories(USER_ID, $mainCat, (int) $this->variables['productSubCatID'][$i])) 
                    : $subCat;
            // The extra categories are processed on the submit by javascript and divided by ,-character. We must explode them to array now:
            $productExtraCats = !empty($this->variables['productExtraCatIDs'][$i]) 
                ? explode(",", $this->variables['productExtraCatIDs'][$i]) 
                    : null;
            echo $productExtraCats;
            if (empty($this->variables['productVAT'][$i])) {
                $productVat = $mainVAT;
            } else {
                 $productVat = Vat::checkVatValue($_POST['productVAT'][$i]) 
                         ? $this->variables['productVAT'][$i] : $usersDefaultVAT;
            }
            $premadeCost = 0;
            $productID = null;
            $productName = $this->variables['productName'][$i];
            $productCost = Products::strToFloat($_POST['productCost'][$i]);
            $productInfo = $this->variables['productInfo'][$i];
            $productWarranty = $this->variables['productWarranty'][$i];
            // -----------

            // Insert the inputted product as a new premade:
            if(!empty($this->variables['newPremadeProduct'][$i])) {
                $newPremadeProduct = new premadeProducts(USER_ID);
                $newPremadeProduct->setArray(array(
                    "name" => $this->variables['productName'][$i],
                    "userID" => USER_ID,
                    "cost" => $productCost,
                    "mainCat" => $productMainCat->ID,
                    "subCat" => $productSubCat->ID,
                    "extraCats" => $this->variables['productExtraCatIDs'][$i], // this is the unexploded version, so rather than imploding we use the POST-variable
                    "VAT" => $productVat
                    ));
                $newPremadeProduct->insert();
                // When we insert it, we also have to fetch the premadeProducts again, since one is missing from the list:
                $premadeProductsQ = $this->dataSource->queryWithExceptions("SELECT ID, name, cost, mainCat, VAT FROM premadeProducts WHERE userID = '".USER_ID."'", "preload1");
            }

            $product = new Products(USER_ID);
            $product->setArray( array(
                "receiptID" => $receipt->ID,
                "name" => $productName,
                "mainCat" => $productMainCat->ID,
                "subCat" => $productSubCat->ID,
                "cost" => $productCost,
                "VAT" => $productVat                    
                )
            );

            if(!empty($productWarranty) ? $productWarranty != "no" : false) {
                $product->info = $productInfo;
                $product->warrantyTill = $productWarranty;
            }

            $product->insert();

            // We need to get the new products ID now, before it's too late to safe the world!
            $productID = $product->lastInsertID;

            $extrasToInsert = null;
            if (!empty($productExtraCats)) {
                foreach($productExtraCats as $var) {
                    $extrasToInsert[$productID][] = $var;
                }
            }

            if(!empty($extrasToInsert)) {
                $product->linkExtraCategoriesToProduct($extrasToInsert);
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
        echo $receiptCost." -". $totalProductCost;
        
        if($receiptsLeftoverCost <= 0) {
            if(!$this->dataSource->query("UPDATE receipts SET cost = '".$totalProductCost."' WHERE id = '".$receipt->ID."'")) {
                echo("We had to modify the receipt sum, because leftover-cost was negative");
            }
        }
        // And we insert the receipts left-over costs to products:
        if($receiptsLeftoverCost > 0) {
            $leftOverProduct = new Products(USER_ID);
            $leftOverProduct->setArray( array(
                "receiptID" => $receipt->ID,
                "name" => $product->LEFT_OVER_NAME,
                "mainCat" => $mainCat->ID,
                "subCat" => $subCat->ID,
                "cost" => str_replace(",", ".", $receiptsLeftoverCost),
                "VAT" => $mainVAT,
                "info" => $receiptInfo,
                "leftOver" => 1
                 )
            );
            $leftOverProduct->insert();

            if(!empty($mainExtraCats[0])) { // Reason for IF: Execute only if user has selected main category, otherwise there will be unnecessary error from this:
                $extraCategory = new ExtraCategories(USER_ID, $category);
                $extraCategory->linkExtraCategoriesToProduct($mainExtraCats, $leftOverProduct->lastInsertID);
            }
        }

    }
}
?>