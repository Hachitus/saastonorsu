<?php

$classArr = array("products");
foreach ($classArr as $filename) {
    require_once $settings->PATH['classes'] .  $filename . '.php';
}

$categories = null;
try {
    // Inserting new receipts
    $categories = $dataSource->queryWithExceptions("SELECT ID, name, type, parentCat FROM categories WHERE userID = '" . USER_ID . "' AND deleted >= 0 ORDER BY type, parentCat");
} catch (Exception $e) {
    echo $e;
}

$category = $categories->fetch_assoc();

//$mainCat = array();
$subCatAsJS = "var subCats = new Array();".PHP_EOL;
//$extraCats = array();
//$subCats = array();
$oldParentCat = null;

$mainCatObj = new Categories(USER_ID);
$subCatObj = new SubCategories(USER_ID);
$extraCatObj = new ExtraCategories(USER_ID);
$placesObj = new Places($dataSource, USER_ID);
$productMainCatObj = new SubCategories(USER_ID);
$productSubCatObj = new ExtraCategories(USER_ID);

// We create the javascript for the subcategories. This make the maincategories / subcategories selections to change dynamically:
if (($fetched = $subCatObj->fetchQuery())) {
    while($values = $fetched->fetch_assoc()) {
        if($values['parentCat'] != $oldParentCat) {
            $subCatAsJS .= "subCats[".$values['parentCat']."] = new Array();".PHP_EOL;
        }
        $subCatAsJS .= "subCats[".$values['parentCat']."][".$values['ID']."] = '".$values['name']."';".PHP_EOL;
        $oldParentCat = $values['parentCat'];
    }
    $showSubCats .= "</select>";
}

$showExtraOptions['class'] = " class='hidden'";
$showExtraOptions['text'] = _("Always show extra options");
$showExtraOptions['dataValue'] = "0";
if($userSettings->getShowExtraOptions() == "1") {
    $showExtraOptions['class'] = "";
    $showExtraOptions['text'] = _("Don't show extra options");
    $showExtraOptions['dataValue'] = "1";
}