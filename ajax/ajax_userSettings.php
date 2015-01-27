<?php

/* 
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * Variables:
 * ==POST==
 * - showExtraDetails = settings for showing the extraOptions in the front-page / mainview-page
 *   --- possible values defined in ajaxes.php: SHOW_OPTION, DONT_SHOW_OPTIONS
  * - showDefaultDate = settings for showing the present date in the front-page / mainview-page
 *   --- possible values defined in ajaxes.php: SHOW_OPTION, DONT_SHOW_OPTIONS
 */

// ajaxes.php is the same for all the ajax-files and handles the authentication, database-initialization etc.
require "ajaxes.php";
include_once($settings->PATH['classes']."userSettings.php");

const SHOW_OPTION = 1;
const DONT_SHOW_OPTION = 0;

$uSettings = new userSettings(USER_ID, $dataSource);

if(isset($_POST['showExtraDetails'])) {
    if($_POST['showExtraDetails'] == 0 || $_POST['showExtraDetails'] == 1) {
        $uSettings->setShowExtraOptions($_POST['showExtraDetails']);
    } else {
            echo "wrong values entered";
    }
} elseif(isset($_POST['showDefaultDate'])) {
    if($_POST['showDefaultDate'] == 0 || $_POST['showDefaultDate'] == 1) {
            $uSettings->setShowDefaultDate($_POST['showDefaultDate']);
    } else {
            echo "wrong values entered";
    }
} else {
    echo "Error, no action specified";
}

?>