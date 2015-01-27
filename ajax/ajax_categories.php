<?php

/* 
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * Variables:
 * ==POST==
 * - toDo = determines if the user wants to add a new entry, edit existing, or delete
 *   --- possible values defined in ajaxes.php: NEW_ENTRY, MODIFY_ENTRY, DELETE_ENTRY
 * - ID =  Naturally the ID (int) for the entry being modified or deleted
 * - type = category-type for fetching certain categories or for fetching all the different categories at once
 *   --- possible values: CAT_MAIN/_SUB/_EXTRA/_ALL
 * - parentID = subCategorys possible mainCategory, where the subCategory has been linked with.
 * 
 * ==GET==
 * - listing = Fetch all the premade-entries for the current user
 *   --- possible values: defined in ajaxes.php
 * - type = category-type for fetching certain categories or for fetching all the different categories at once
 *   --- possible values: CAT_MAIN/_SUB/_EXTRA/_ALL
 * - parentID = subCategorys possible mainCategory, where the subCategory has been linked with.
 */

require("ajaxes.php");
include_once($settings->PATH['classes']."categories.class.php");

// ==== VARIABLE-DECLARATION ====
const CAT_MAIN = 1;
const CAT_SUB = 3;
const CAT_EXTRA = 2;
const CAT_ALL = 4;

$type = $dataSource->filterVariable($_POST['type'] ? $_POST['type'] : $_GET['type']);

$parentCat = null;
if($_POST['parentID']) {
    $parentCat = (int) $dataSource->filterVariable($_POST['parentID']);
} elseif ($_GET['parentID']) {
    $parentCat = (int) $dataSource->filterVariable($_GET['parentID']);
}
// ==== VARIABLE END ====


if($_POST['toDo'] == NEW_ENTRY) {
    $parentCat = $parentCat ? ( new MainCategory($dataSource, USER_ID, $parentCat)) : null;
    $name = $dataSource->filterVariable($_POST['name']);
    $category = "";
    
    if($_POST['type'] == CAT_MAIN) {
        $category = new MainCategory($dataSource, USER_ID);
    } elseif($_POST['type'] == CAT_SUB) {
        $category = new SubCategory($dataSource, USER_ID, $parentCat);
    } elseif($_POST['type'] == CAT_EXTRA) {
        $category = new ExtraCategory($dataSource, USER_ID);
    } 
    
    $category->insert($name);
    echo $category->getLastInsertID();
} elseif($_POST['toDo'] == DELETE_ENTRY) {
    /* We break the OOP-design a bit and use MainCategory class to delete ANY category based
     * on it's ID in database. Otherwise it gets too complicated to do it without the subclasses
     */
    $category = new MainCategory($dataSource, USER_ID);
    $ID = trim($_POST['ID']);

    if(ctype_digit($ID)) {
        $ID = Array($ID);
    }
    
    if((is_array($ID) && !empty($ID[0]) )) {
        foreach($ID as $value) {
            $category->delete($dataSource->filterVariable($value));
        }
    }
} elseif ($_GET['listing'] == LISTING) {
    $returnable = null;
    switch ($type) {
        case CAT_MAIN:
            $returnable = fetchCats(new MainCategory($dataSource, USER_ID));
            break;
        case CAT_SUB:
            $subCat = new SubCategory($dataSource, USER_ID, new MainCategory($dataSource, USER_ID, $parentCat));
            $returnable = fetchCats($subCat);
            break;
        case CAT_EXTRA:
            $returnable = fetchCats(new ExtraCategory($dataSource, USER_ID));
            break;
        case CAT_ALL:
            $returnable["mainCategories"] = fetchCats(new MainCategory($dataSource, USER_ID));
            
            $returnable["subCategories"] = fetchCats(new SubCategory($dataSource, USER_ID), $parentCat);

            $returnable["extraCategories"] = fetchCats(new ExtraCategory($dataSource, USER_ID));
            break;
        default:
            exit("no valid category specified. Type:".$type);
            break;
    }
    echo json_encode($returnable);
    
} else {
    echo "Error, no action specified";
}

function fetchCats ($catObj) {
    return $catObj->fetchArray();
}

?>