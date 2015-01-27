<?php

require_once "ajaxes.php";

$classArr = array("vat", "userSettings");
foreach ($classArr as $filename) {
    require_once $settings->PATH['classes'] .  $filename . '.php';
}

$userSettings = new UserSettings(USER_ID, $dataSource);
$userSettings->fetchData();

// Include several with foreach:
$classArr = array("categories", "products", "vat", "receipts", "places", "paymentMethods");
foreach ($classArr as $filename) {
    require_once $settings->PATH['classes'] .  $filename . '.php';
}

// Ladataan etukäteen kaikki premadejen tiedot, jotta ei tule ylimääräisiä tietokanta-kyselyitä
// The information is fetched, when a new receipt is added, so I would think it's pretty ok to fetch them always anyway:
$prefetchedPremadeProducts = NULL;
try {
    $premadeProductsQ = $dataSource->queryWithExceptions("SELECT ID, name, cost, mainCat, VAT, subCat, extraCats FROM premadeProducts WHERE userID = '".USER_ID."'", "preload1");
} catch (Exception $e) {
    echo $e;
}
while($fetched = $premadeProductsQ->fetch_row()) {
    $prefetchedPremadeProducts[$fetched[0]] = array ("name" => $fetched[1], "cost" => $fetched[2], "mainCat" => $fetched[3], "VAT" => $fetched[4], "subCat" => $fetched[5], "extraCats" => $fetched[6]);
}

/*
 * =============================================================================
 * This is the functionality, when the user submits their receipt. The functionality
 * will be prosessed on the same page.
 * =============================================================================
 */
try{  // We catch all the error, rather than start iffing them all the way, that would be bothersome :)
    // Not the queryWithExceptions-function throws exceptions on failure. If you need to continue the flow as normal, but inform the use of something then don't use it. Use normal query instead.
    
    if(!empty($_POST['datepicker']) 
            && (isset($_POST['mainCatID'])) 
            && !empty($_POST['receiptCost'])) {

        /* =====================
         * Setting up (and inserting) receipt and categories:
         * =====================
         */
        
        /*--------------------------
         * Variable initialization:
         * -------------------------
         */
        $mainCat = new Categories(USER_ID, 
                (!empty($_POST['mainCatID']) ? ((int) $_POST['mainCatID']) : 0)
            );
        $subCat = new SubCategories(USER_ID, $mainCat, (
                !empty($_POST['subCatID']) ? ((int) $_POST['subCatID']) : 0)
            );
        $mainExtraCats = !empty($_POST['extraCatIDs']) ? explode(",", $_POST['extraCatIDs']) : array();
        // Setting user specific VAT:
        $mainVAT = $defaultVAT = $userSettings->getVat();
        
        $place = $_POST['place'];
        // We need to format the time to unix timestamp first
        $time = strtotime($_POST['datepicker']);
        // We also want to initialize the total cost for all the products inserted, so we can calculate how much is left for the receipts inserted cost.
        // Also the leftover cost is about calculating the same costs. The are calculated at the end of this try-phase
        $totalProductCost = 0.0;
        $receiptsLeftoverCost = 0.0;
        $receiptCost = (float) str_replace(",", ".", $_POST['receiptCost']);
        $receiptInfo = $_POST['info'];

        /* --------------------------
         * We set and insert the receipt to database and also get the receipt ID from DB insertID
         * --------------------------
         */

        $receipt = new Receipt(USER_ID);
        $receipt->setArray( array(
            "time" => $time,
            "userID" => USER_ID,
            "place" => $place,
            "paymentMethodID" => $_POST["paymentID"],
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
        
        for($i=0; $_POST['productCost'][$i] != "" || $_POST['productName'][$i] != ""; $i++) {

            if(!($_POST['productCost'][$i] != "" && $_POST['productName'][$i] != "")) {
                echo $_POST['productCost'][$i]. "-" .$_POST['productName'][$i];
                exit("The individual products didn't have name AND cost");
            }

            // We will only deal with mainCategory here, since we need productID to be able to insert extraCategories:
            $productMainCat = !empty($_POST['productMainCatID'][$i]) 
                ? (new Categories(USER_ID, (int) $_POST['productMainCatID'][$i])) 
                    : $mainCat;
            $productSubCat = !empty($_POST['productSubCatID'][$i]) 
                ? (new SubCategories(USER_ID, $mainCat, (int) $_POST['productSubCatID'][$i])) 
                    : $subCat;
            // The extra categories are processed on the submit by javascript and divided by ,-character. We must explode them to array now:
            $productExtraCats = !empty($_POST['productExtraCatIDs'][$i]) 
                ? explode(",", $_POST['productExtraCatIDs'][$i]) 
                    : null;
            echo $productExtraCats;
            if (empty($_POST['productVAT'][$i])) {
                $productVat = $mainVAT;
            } else {
                 $productVat = Vat::checkVatValue($_POST['productVAT'][$i]) 
                         ? $_POST['productVAT'][$i] : $defaultVAT;
            }
            $premadeCost = 0;
            $productID = null;
            $productName = $_POST['productName'][$i];
            $productCost = Products::strToFloat($_POST['productCost'][$i]);
            $productInfo = $_POST['productInfo'][$i];
            $productWarranty = $_POST['productWarranty'][$i];
            // -----------

            // Insert the inputted product as a new premade:
            if(!empty($_POST['newPremadeProduct'][$i])) {
                $newPremadeProduct = new premadeProducts(USER_ID);
                $newPremadeProduct->setArray(array(
                    "name" => $_POST['productName'][$i],
                    "userID" => USER_ID,
                    "cost" => $productCost,
                    "mainCat" => $productMainCat->ID,
                    "subCat" => $productSubCat->ID,
                    "extraCats" => $_POST['productExtraCatIDs'][$i], // this is the unexploded version, so rather than imploding we use the POST-variable
                    "VAT" => $productVat
                    ));
                $newPremadeProduct->insert();
                // When we insert it, we also have to fetch the premadeProducts again, since one is missing from the list:
                $premadeProductsQ = $dataSource->queryWithExceptions("SELECT ID, name, cost, mainCat, VAT FROM premadeProducts WHERE userID = '".USER_ID."'", "preload1");
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

            if(!empty($_POST['premadeProduct'][$i])) {
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
            if(!$dataSource->query("UPDATE receipts SET cost = '".$totalProductCost."' WHERE id = '".$receipt->ID."'")) {
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
    /* =============================================================================
     * If the user was missing some variables from the form:
     * =============================================================================
     */
    elseif (!empty($_POST['submitNewEvent'])) {
        if (!isset($_POST['datepicker']))
            echo "date not set";
        elseif (!isset($_POST['mainCatID']) || !isset($_POST['newMainCategory']))
            echo "main category not set";
        elseif ((!isset($_POST['productCost'][0]) && !isset($_POST['productName'][0])) || $_POST['premadeProduct'][0])
            echo "product not set";
    }
} catch (ImprovedExceptions $e) {
    // If there was non-int value passed to the constructor in KIRJASTOVariableBasedOnDB-inherited classes.
    echo $e->getTraceAsString();
    if($e->getCode() == 100057) {
        $e->sendMail("User did not input INT:".$e->getMessage());
        echo "Wrong value given";
    } else {
        $e->showHTML($e->getMessage());
    }
} Catch (Exception $e) {
    // If there was non-int value passed to the constructor in KIRJASTOVariableBasedOnDB-inherited classes.
    echo $e->getTraceAsString();
    if($e->getCode() == 100057) {
        Dumper::dump($e);
        echo "Wrong value given";
    } else {
        echo "EXCEPTION THROWN (dumped)";
        Dumper::dump($e);
    }
}

$categories = null;
try {
    // Prefetch paymentMethods:
    $paymentMethods = $dataSource->queryWithExceptions("SELECT ID, name FROM paymentMethods", "preload3")->fetch_all(MYSQLI_ASSOC);

    // Inserting new receipts
    $categories = $dataSource->queryWithExceptions("SELECT ID, name, type, parentCat FROM categories WHERE userID = '" . USER_ID . "' AND deleted >= 0 ORDER BY type, parentCat");
} catch (Exception $e) {
    echo $e;
}
?>