<?php

/**
 * @author Janne Hyytiä
 * Note that the HTMLFunctions-> doSelect needs ID at the database, which it uses to fetch the value
 */

class HTMLFunctions {
    
    // The first parameter requirest the array in array[$ID] = $name - format
    // Where ID is the value for the option and name is the visible text for the option
    public function showSelectUsingArray(Array $arr, $selectName, $defaultOption = null, $extra = null, $selected = null, $valueTag = "ID") {
        return showSelect($arr, $selectName, $defaultOption, $extra, $selected, $valueTag);
    }
    public function showSelectUsingQuery(mysqli_result $obj, $selectName, $defaultOption = null, $extra = null, $selected = null, $valueTag = "ID") {
        $one = null;
        $arr = array();
        while($one = $obj->fetch_row()) {
            $arr[($one[0])] = $one[1];
        }
        
        return $this->showSelect($arr, $selectName, $defaultOption, $extra, $selected, $valueTag);
    }
    private function showSelect (Array $arr, $selectName, $defaultOption = null, $extra = null, $selected = null, $valueTag = "ID") {

        if(!is_array($selected) && (is_numeric($selected) || is_string($selected))) {
            $selected = array($selected);
        }
        
        $showExtra = $extra ? $extra : "";
        
            $retString = "<select name='".$selectName."' ".$showExtra.">";
            $retString .= $defaultOption ? "<option value='0'>".$defaultOption."</option>" : "";

            foreach ($arr as $value => $text) {
                $showSelected = "";
                if(isset($selected) && (in_array($value, $selected) || in_array($text, $selected))) {
                    $showSelected = " selected";
                }
                $retString .= "<option value='".$value."'".$showSelected.">" . $text . "</option>";
            }

            $retString .= "</select>";
            return $retString;
    }
    // OLD version with wrong interface:
    // CHANGE THIS IN THE CODE TO THE NEWER ONE, it's deprecated!
    function doSelect (FetchValues $obj, $selectName, $defaultOption = null, $extra = null, $selected = null, $valueTag = "ID") {

        if(!is_array($selected) && (is_numeric($selected) || is_string($selected))) {
            $selected = array($selected);
        }
        
        $showExtra = $extra ? $extra : "";
        if (($fetched = $obj->fetchArray())) {
            $retString = "<select name='".$selectName."' ".$showExtra.">";
            $retString .= $defaultOption ? "<option value='0'>".$defaultOption."</option>" : "";

            foreach($fetched as $values) {
                $showSelected = "";
                if(isset($selected) && in_array($values[$valueTag], $selected)) {
                    $showSelected = " selected";
                }
                $retString .= "<option value='".$values['ID']."'".$showSelected.">" . $values['name'] . "</option>";
            }

            $retString .= "</select>";
            return $retString;
        }
        return false;
    }
}

?>
