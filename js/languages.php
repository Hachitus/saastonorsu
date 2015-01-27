<?php
class JSLanguages {
    public $lang;
    public $path = "languages";
    public $text = "";
    
    public function __construct($lang = "fin") {
        $this->lang = $lang;
    }
    
    public function setupLanguage($lang = null) {
        $lang = $lang ? $lang : $this->lang;
        
        $lang = new Language($lang);
        
        createJS_Variables($lang);
    }
    
    public function createLangFiles() {
        $languages = array(
            "fin",
            "eng"
        );
        
        foreach($languages as $varri) {
            $file = fopen($path."/".$languages.".js", "w");
            fwrite($handle, $this->createJS_Variables($varri));
            fclose($file);
        }
    }
    
    public function createJS_Variables($lang) {
        return $this->text = "
            var texts = {
                noCategory: '".$lang->noCategory."',
                saveReceipt: '".$lang->saveReceipt."',
                cost: '".$lang->cost."',
                info: '".$lang->info."',
                name: '".$lang->name."',
                mainCategory: '".$lang->mainCategory."',
                subCategory: '".$lang->subCategory."',
                extraCategories: '".$lang->extraCategories."',
            };
        ";
    }
    
}

class TestLanguageClass {
    public function test(Language $obj) {
        $vars = get_object_vars($obj);
        $arr = array(
            "noCategory",
            "saveReceipt",
            "cost",
            "info",
            "name",
            "mainCategory",
            "subCategory",
            "extraCategories"
        );
        foreach($arr as $varri) {
            if(!isset($obj->$varri)) {
                throw new Exception("variable not set: ".$varri);
            }
        }
    }
}

class Language {
    public function __construct($lang) {
        $this->$lang();
    }
    public function __call($method) {
        switch ($method) {
            case($method == "fin"):
                $this->noCategory = __("noCategory");
                $this->saveReceipt = __("saveReceipt");
                $this->cost = __("cost");
                $this->info = __("info");
                $this->name = __("name");
                $this->mainCategory = __("mainCategory");
                $this->subCategory = __("subCategory");
                $this->extraCategories = __("extraCategories");
                break;
            case($method == "eng"):
                $this->noCategory = __("noCategory");
                $this->saveReceipt = __("saveReceipt");
                $this->cost = __("cost");
                $this->info = __("info");
                $this->name = __("name");
                $this->mainCategory = __("mainCategory");
                $this->subCategory = __("subCategory");
                $this->extraCategories = __("extraCategories");
                break;
        }
    }
}
?>