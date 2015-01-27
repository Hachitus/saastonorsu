<?php

/* 
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 */

/*
 * This class generates the javascript languages files for different languages. 
 * We use this instead of manual javascript-files to be able to take advantage of
 * the getText-language function. Now we have all the language-variables in PHP
 * pretty much centralized.
 * 
 * The texts can be generated simply by constructing and calling createLangFiles();
 * This needs to be timed or done always after language-files have been changed.
 * 
 * For example just:
 * $obj = new JSLanguages;
 * $obj->createLang();
 */

class JSLanguages
{
    public 
            $lang,
            $path = "../js/languages",
            $text = "";
    
    public function __construct($lang = "fin")
    {
        $this->lang = $lang;
    }
    
    public function setupLanguage($lang = null)
    {
        $lang = $lang ? $lang : $this->lang;
        
        $langObj = new Language($lang);
        
        createJS_Variables($langObj);
    }
    
    public function createLangFiles()
    {
        $languages = array(
            "fi_FI" => "fi",
            "en_GB" => "en"
        );
        
        foreach($languages as $locale => $lang) {
            putenv("LC_ALL=$locale");
            setlocale(LC_ALL, $locale);
            bindtextdomain("messages", $_SERVER["DOCUMENT_ROOT"]."/"."locale");
            bind_textdomain_codeset("messages", 'UTF-8');
            textdomain("messages");
            
            $filename = $this->path."/".$locale.".js";
            $file = fopen($filename, "w");
            $langObj = new Language($locale);
            fwrite($file, $this->createJS_Variables($langObj));
            fclose($file);
        }
    }
    
    public function createJS_Variables(Language $lang)
    {
        $this->text = "var texts = {";
        foreach($lang->words as $key => $word) {
            $this->text .= $key.": \"".$word."\",".PHP_EOL;
        }
        $this->text = substr($this->text, 0, -1);
        $this->text .= "};";
        
        return $this->text;
    }
    
}

class Language
{
    public  
            $words = null,
            $wordsTranslated = array();
    
    public function __construct($lang)
    {
        $this->setupLang($lang);
    }
    public function setupLang()
    {
        $this->words = array(
            "noCategory" => _("no category"),
            "saveReceipt" => _("save receipt"),
            "cost" => _("cost"), 
            "info" => _("extra information"), 
            "name" => _("name"), 
            "mainCategory" => _("main category"), 
            "subCategory" => _("sub category"), 
            "extraCategories" => _("extra categories"), 
            "warranty" => _("warranty"), 
            "notChosen" => _("not chosen"),
            "noMainCatInReceipt" => _("no main in receipt"), 
            "noCostInReceipt" => _("no cost in receipt"),
            "noDateInReceipt" => _("no date in receipt"), 
            "showExtraOptions" => _("show extra options"), 
            "dontShowExtraOptions" => _("dont show extra options"));
    }
}
?>