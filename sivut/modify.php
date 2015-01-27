<?php

require_once BASE_DIR . "/classes/categories.class.php";
// Create objects:
$categoriesObj = new AllCategory($dataSource, USER_ID);

$newCatName = $_POST['newCatName'] ? $dataSource->filterVariable($_POST['newCatName']) : null;
$newCatType = $_POST['newCatType'] ? $dataSource->filterVariable($_POST['newCatType']): null;
$newPlaceName = $_POST['newPlaceName'] ? $dataSource->filterVariable($_POST['newPlaceName']): null;

$categories = array();
$haku = $dataSource->queryWithExceptions("SELECT cat.ID, cat.name, cat.type, parentCat.name as parentName, parentCat.ID as parentID FROM categories cat LEFT JOIN categories parentCat ON cat.parentCat = parentCat.ID WHERE cat.userID = '".USER_ID."' AND cat.deleted >= 0 ORDER BY cat.type ASC", "Fetching categories");
while($cats = $haku->fetch_row()) {
	$categories[$cats[2]][] = array($cats[0], $cats[1], $cats[3], $cats[4]);
}

$catHTML = array();
$newSubCat_innerHTML = array();
$newSubCatMainCat_innerHTML = "<select name='newMainCatOptions'>";
$mainCatAsJS = " var mainCat = new Array();".PHP_EOL;
// iterate through category-types:
foreach($categories as $catType => $catTypesArray) {
    // iterate through certain category types, individual categories:
    foreach($catTypesArray as $key => $individualCat) {
        $subCatAddHTML = "";
        // Sub categories differ from the other two, since they are dependant on the parent category:
        if ($catType == 3) {
            $newSubCat_innerHTML[$individualCat[3]] .= "<option value='".$individualCat[0]."'>".$individualCat[1]."</option>";
            $subCatAddHTML .= "pääkategoria: ".$categories[3][$key][2]." <button type='button' onClick=\"showOrHide('changeNewMainCatDiv".$categories[3][$key][0]."')\">change</button>
                <div id='changeNewMainCatDiv".$categories[3][$key][0]."' style='display:none;'>
                    <select name='selectNewMainCat[".$individualCat[0]."]'>
                    <option value=0>don't change</option>";
            
                    $fetching = $categoriesObj->fetchAll();
                while($results = $fetching->fetch_assoc()) {
                    $selected = "";
                    if($results['ID'] == $categories[3][$key][0]) {
                        $selected = " selected";
                    }
                    $subCatAddHTML .= "<option value='".$results['ID']."'".$selected.">".$results['name']."</option>";
                }
            $subCatAddHTML .= "</select></div><br />";
        } elseif ($catType == 1) {
            $mainCatAsJS .= "mainCat[".$individualCat[0]."] = '".$individualCat[1]."';".PHP_EOL;
            $newSubCatMainCat_innerHTML .= "<option value='".$individualCat[0]."'>".$individualCat[1]."</option>";
        }
        // insert the html to correct variable (based on earlier foreach-loop:
        $catHTML[$catType] .= "<p>
            kategoria: ".$individualCat[1]."<br />
            ".$subCatAddHTML."
            muokkaa nimeä: <input type='text' name='category[".$individualCat[0]."]' value=''><br />
            Poista kategoria: <input type='checkbox' name='delCat[".$individualCat[0]."]'>
            </p>
        ";
    }
}
$newSubCatMainCat_innerHTML .= "</select>";
// ===================================
// Category editing
// ===================================
try {
    // Deleting categories:
    if($_POST["delCat"]) {
        foreach ($_POST["delCat"] as $key => $var) {
            if($var === "on") {
                $var = $dataSource->filterVariable($var);
                $dataSource->queryWithExceptions("UPDATE categories SET deleted = -1 WHERE ID='".$key."' OR (type=".SUB_CAT." AND parentCat = ".$key.") AND userID = '".USER_ID."'", "categoryDeletion");
            }
        }
    }
    // Modifying categories:
    if($_POST["category"]) {
        
        foreach($_POST["category"] as $key => $var) {
            if(!empty($var)) {
                $dataSource->queryWithExceptions("UPDATE categories SET name='".$var."' WHERE ID='".$key."' AND userID = ".USER_ID, "Modifying category name");
            }
        }
        foreach($_POST["selectNewMainCat"] as $key => $var) {
            if(!empty($var)) {
                $dataSource->queryWithExceptions("UPDATE categories SET parentCat='".$var."' WHERE ID='".$key."' AND userID = ".USER_ID, "Modifying parent category<br ");
            }
        }
    }
    // Addin new categories:
    if((!empty($newCatName) && !empty($newCatType))) {

        // Uutta OOP-settiä, ei vielä käytössä:
        /*$mainCat->setArray( array(
                "name" => $_POST['newMainCategory'],
                "type" => 1,
                "userID" => USER_ID
                )
            );
        $mainCat->insert();
         */
        
        $subCategorySQL = "";
        $newMainCatOptions = $dataSource->filterVariable($_POST["newMainCatOptions"]);
        if(!empty($newMainCatOptions)) {
            $subCategorySQL = ", parentCat = '".$newMainCatOptions."'";
        }
            $dataSource->queryWithExceptions("INSERT INTO categories SET name='".$newCatName."', type='".$newCatType."'".$subCategorySQL.", userID = '".USER_ID."'", "categoryInserted");
    }

    // ===================================
    // Products
    // ===================================

    // ===================================
    // Places
    // ===================================

    // Deleting places:
    if($_POST["delPlace"]) {
        foreach ($_POST["delPlace"] as $key => $var) {
            if($var === "on") {
                $var = $dataSource->filterVariable($var);
                $dataSource->queryWithExceptions("UPDATE premadePlaces SET deleted = -1 WHERE ID='".$key."' AND userID = '".USER_ID."'", "premadePlace Deletion");
            }
        }
    }
    // Modifying places:
    if($_POST["place"]) {
        foreach($_POST["place"] as $key => $var) {
            if(!empty($var)) {
                $var = $dataSource->filterVariable($var);
                $dataSource->queryWithExceptions("UPDATE premadePlaces SET name='".$var."' WHERE ID='".$key."' AND userID = ".USER_ID, "Modifying premadePlace name");
            }
        }
    }
    // Adding new places:
    if(!empty($newPlaceName)) {
        $place = new Places($dataSource, USER_ID);
        $place->insert($dataSource->filterVariable($_POST['newPlace']));
    }

    // ===================================
    
} catch (Exception $e) {
    echo $e;
}
// ===================================
// GENERAL STUFF and also we handle the categories DB queries already here. This is because these will be used
// not only in categories, but also in products. So we can use the same query nicely on both.
// ===================================

?>
<form name="modifying" action="" method='POST'>
<?php

// ===================================
// Categories frontend
// ===================================
echo "<b>Main categories:</b><br>";
if(isset($categories[1])) {
    echo $catHTML[Category::MAIN_CATEGORY];
}
else {
	echo "No main categories<br /><br />";
}

echo "<b>Extra categories:</b><br />";
if(isset($categories[2])) {
    echo $catHTML[Category::EXTRA_CATEGORY];
}
else {
	echo "No extra categories<br /><br />";
}

echo "<b>Sub categories:</b><br />";
if(isset($categories[1])) {
    echo $catHTML[Category::SUB_CATEGORY];
}
else {
	echo "No extra categories<br /><br />";
}

?>
<br>
Insert new category:<br>
<div id="newSubCat_AddedDiv"></div>
Name:<br />
<input name='newCatName' type='text'><br />
Type:<br />
<select name='newCatType' onChange='newCatTypeSelected()'>
	<option value='1'>main</option>
	<option value='3'>sub</option>
	<option value='2'>extra</option>
</select><br /><br />
<?php

// ===================================
// Products frontend
// ===================================


// ===================================
// Places frontend
// ===================================
$haku=NULL;
$haku = $dataSource->queryWithExceptions("SELECT ID, name FROM premadePlaces WHERE userID = '".USER_ID."' AND deleted >= 0 ORDER BY name");

$i=0; // We use the $i-variable so that the delPlace checkboxes will be named nicely and with the checkboxes, we can easily delete the places we want to.

if(($tulokset = $haku->fetch_row())) {
    echo _("Places").":<br>";
    do {
        echo "<p>
            "._("Place").": ".$tulokset[1]."<br />
            "._("Modify").": <input type='text' name='place[".$tulokset[0]."]' value=''><br />
            "._("Remove").": <input type='checkbox' name='delPlace[".$tulokset[0]."]'>
            </p>
        ";
        $i++;
    }while($tulokset = $haku->fetch_row());
}
?>
<br>
<?= _("Insert new place"); ?>:<br>
<?= _("Name"); ?>:<br />
<input type='text' name='newPlaceName' value=''><br />
<?php
// ===================================
// The rest of small stuff in the end
// ===================================
?>
<input type="submit" value="lähetä">
</form>

<script type="text/javascript">
var form = document.forms['modifying'];

<?= $mainCatAsJS ;?>
function newCatTypeSelected () {
    if(form.elements['newCatType'].value == 3) {
        document.getElementById("newSubCat_AddedDiv").innerHTML = "SELECT Main category that the sub belongs to:<br /><?= PHP_EOL.$newSubCatMainCat_innerHTML ;?>";
    } else {
        document.getElementById("newSubCat_AddedDiv").innerHTML = "";
    }
}
function mainCatChanged (mainCatID) {
    removeOptionSelected('subCatID')
    var elSel = form.elements['subCatID'];
    
    if((window['subCats[mainCatID]'] != undefined)) {
        for(var i=0 ; i < subCats[mainCatID].length ; i=i+2) {
            var elOptNew = document.createElement('option');
            elOptNew.text = subCats[mainCatID][i];
            elOptNew.value = subCats[mainCatID][i++];
            elSel.add(elOptNew, null); // standards compliant; doesn't work in IE
        }
    } else {
            var elOptNew = document.createElement('option');
            elOptNew.text = "No category";
            elOptNew.value = null;
            elSel.add(elOptNew, null); // standards compliant; doesn't work in IE
    }
}
function removeOptionSelected(selectField) {
  var elSel = form.elements[selectField];
  var i;
  for (i = elSel.length - 1; i>=0; i--) {
    if (elSel.options[i].selected) {
      elSel.remove(i);
    }
  }
}
</script>