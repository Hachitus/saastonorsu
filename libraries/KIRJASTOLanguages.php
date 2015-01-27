<?php
/*
 * REQUIRES KIRJASTODB-class
 *
 * This is the class for making multi-lingual-sites possible.
 * The class will fetch different languages from the database, including submit-buttons and other elements
 */

class Languages {
    private $DB;
    private $texts = array();
    private $generalTexts = array();
    private $page;
    private $localeVariables;
    private $countryCodes;
    private $country = "";

    function __construct(mysqli $DB, $country="eng", $page="") {
        $this->countryCodes = array(
            "longTag"=>(array(
                "eng"=>"en_EN",
                "fin"=>"fi_FI")
            ),
            "shortTag"=>(array(
                "eng"=>"EN",
                "fin"=>"FI")
            ),
            "countryName"=>(array(
                "eng"=>"England",
                "fin"=>"Finland")
            ));

        $this->country = $country;
        $this->setPage($page);
        $this->setDB($DB);
        $this->setLocaleVars($country);

        $haku = $DB->query("SELECT place, text, page FROM languages WHERE (page = '".$this->DB->filterVariable($page)."' OR page = 'generalTexts') AND language = '".$this->DB->filterVariable($country)."' ORDER BY page");
        while($tiedot = $haku->fetch_row()) {
            if($tiedot[2] == "generalTexts") {
                $this->generalTexts[$tiedot[0]] = $tiedot[1];
            } else {
                $this->texts[$tiedot[0]] = $tiedot[1];
            }
        }
    }

    // This function will make different countryTags possible, like: "eng" AND "EN" AND "en_EN":
	
    function getText ($place) {
        if(!isset($this->texts[$place]))
            return "[text Missing:".$place."]";
        else
            return $this->texts[$place];
    }
    function setText ($place, $value) {
        $this->texts[$place] = $value;
    }
    function getGeneralText ($place) {
        return $this->generalTexts[$place];
    }
    function getShortCountryTag () {
        return $this->countryCodes["shortTag"][$this->country];
    }
    function getLongCountryTag () {
        return $this->countryCodes["longTag"][$this->country];
    }
    function getLocaleVariable ($what) {
        return $this->localeVariables[$what];
    }
    function setLocaleVars ($country) {
        $this->country= $country;
        setlocale(LC_ALL, $this->getLongCountryTag());
        $this->localeVariables = localeconv();
    }
    function setDB (mysqli $DB) {
        $this->DB = $DB;
    }
    function setPage ($page) {
        $this->page = $page;
    }
    function dumpGenTexts () {
    var_dump($this->generalTexts);
    }
}
?>
