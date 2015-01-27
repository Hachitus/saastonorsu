<?php

/* 
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * VARIABLES:
 * ==POST==
 * - toDo = determines if the user wants to add a new entry, edit existing, or delete
 *   --- possible values defined in ajaxes.php: NEW_ENTRY, MODIFY_ENTRY, DELETE_ENTRY
 * - ID =  Naturally the ID (int) for the entry being modified or deleted
 * - When modifying or inserting new receipt, the following JSON-variables should be followed:
 * Required...
 * * receiptID, 
 * * receiptCost,
 * * receiptDate, 
 * * receiptMainCat
 * 
 * Optional...
 * * place, 
 * * whoBought, 
 * * receiptInfo, 
 * * receiptSubCat, 
 * * receiptExtraCats [IDs as array],
 * * products 
 *   {
 *      cost, 
 *      info, 
 *      name, 
 *      warranty, 
 *      ALV, 
 *      mainCat, 
 *      subCat, 
 *      extraCats [IDs as array]
 *   }
 * 
 * ==GET==
 * - listing = Fetch all the premade-entries for the current user
*   --- possible values: defined in ajaxes.php
 * - ID =  Naturally the ID (int) for the entry being retrieved
 */

require "ajaxes.php";
$classArr = array("receipts.class", "userSettings.class", "categories.class");
foreach ($classArr as $filename) {
    require_once $settings->PATH['classes'] .  $filename . '.php';
}

define(LIST_ALL, "1");
define(LIST_INDIVIDUAL, "2");

$user = new User(USER_ID);
$receiptPost = $_POST['receipt'];
if($_POST['ID']) {
    $rcptID = (int) $dataSource->filterVariable($_POST['ID']);
} elseif ($_GET['ID']) {
    $rcptID = (int) $dataSource->filterVariable($_GET['ID']);
}

$receipts = new ReceiptFactory(USER_ID, $dataSource);

// The form was submitted as expected. So we start to do things:

// Create variables to be passed on:

$mainCat = new MainCategory($dataSource, USER_ID, (int) $receipts->variables['mainCatID']);
$subCat = $receipts->variables['subCatID'] ? new SubCategory($dataSource, USER_ID, $mainCat, (int) $receipts->variables['subCatID']) : null;

// ==== For testing purposes IF / WHEN needed. We add debugging info to file later, since ajax requests are difficult in this way ====
$filename = "../logs/debugging.log";
$fh = fopen($filename, "w");
// ==== end ====

if($_GET['listing'] == LIST_ALL) {

} elseif($_GET['listing'] == LIST_INDIVIDUAL) {

    $fetchedRcpts = $receipts->fetchReceiptByID($rcptID);
    $rcpt = $fetchedRcpts->getReceiptByData(new Receipt(USER_ID, $dataSource, $rcptID))->toJSON();
    echo json_encode($rcpt);
    
} elseif($_POST['toDo'] == NEW_ENTRY) {
    // First check that the submitted values are enough to create a simple receipt:
    try {
        $receipts->checkValidity();
    } catch (Exception $e) {
        if ($e->getCode() == 100) {
            // Some required information was missing:
            exit( "not enough information given".$e->getMessage());
        } else {
            throw new Exception($e->getMessage());
        }
        exit();
    }

    // Create the receipt with the information from the form:
    $receipts->create($mainCat, $subCat, $receipts->variables['place']);

} elseif($_POST['toDo'] == DELETE_ENTRY) {
    $IDsToDelete = $_POST['IDs'];
    
    $rcpt = new Receipt(USER_ID, $dataSource);
    $rcpt->delete($IDsToDelete);
    
} elseif($_POST['toDo'] == MODIFY_ENTRY) {
    /*
     * This information should be transmitted in JSON-format
     * 
     * Modify requires the receipts information the required ones are:
     * receiptID, 
     * receiptCost,
     * receiptDate, 
     * receiptMainCat
     * 
     * Optional ones are:
     * place, 
     * whoBought, 
     * receiptInfo, 
     * receiptSubCat, 
     * receiptExtraCats [IDs as array],
     * products 
     * {
     *      cost, 
     *      info, 
     *      name, 
     *      warranty, 
     *      ALV, 
     *      mainCat, 
     *      subCat, 
     *      extraCats [IDs as array]
     * }
     */
    
    $rcpt = new Receipt(USER_ID, $dataSource);
    $rcpt->assimilateJSONValues($receiptPost);

    $rcptModify = new ReceiptFactory($user, $dataSource);
    
    try {
        $rcptModify->update($rcpt);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} else {
    echo "Error, no action specified";
}
?>