<?php

/* 
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * VARIABLES:
 * ==POST==
 * - toDo = determines if the user wants to add a new entry, edit existing, or delete
 *   --- possible values defined in ajaxes.php: NEW_ENTRY, MODIFY_ENTRY, DELETE_ENTRY
 * - ID =  Naturally the ID (int) for the entry being modified or deleted
 * - name = name of the new place to be inserted
 *   --- possible values: All strings (filtered for SQL)
 * 
 * ==GET==
 * - listing = Fetch all the premade-entries for the current user
 *   --- possible values defined in ajaxes.php NEW_ENTRY, MODIFY_, DELETE_
 * 
 * METHODS:
 * newEntry($obj, $name)
 * - $obj = place-object
 * - $name = name of new place
 * - Returns the ID of inserted value and this value should be returned with the request
 * 
 * deleteEntry($ID)
 * - $obj = place-object
 * - $name = name of new place
 * - Returns the ID of inserted value and this value should be returned with the request
 */

require "ajaxes.php" ;
include_once($settings->PATH['classes']."places.class.php");

$place = new Places($dataSource, USER_ID);

if($_POST['toDo'] == NEW_ENTRY) {    
    echo $place->insert($dataSource->filterVariable($_POST['name']));
} elseif($_POST['toDo'] == DELETE_ENTRY) {
    echo deleteEntry($place, $_POST['ID'], $dataSource->filterVariable);    
} elseif ($_GET['listing'] == LISTING) {    
    echo json_encode($place->fetchArray());    
} else {    
    echo "Error, no action specified";    
}

function deleteEntry($obj, $ID, $filterFunction) {
    $ID = trim($ID);

    if(ctype_digit($ID)) {
        $ID = Array($ID);
    }
    
    if((is_array($ID) && !empty($ID[0]) )) {
        foreach($ID as $value) {
            $value = $filterFunction($value);
            $success = $obj->delete($value);
        }
        return true;
    }
    return false;
}
?>