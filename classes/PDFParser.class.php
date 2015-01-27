<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PDF
 *
 * @author Hachi
 */
class PDFParser {
    private 
            $filename = "";
    public
            $origContent = "",
            $filteredContent = "",
            $deposits = array();    
    
    public function __construct($filename) {
        $this->filename = $filename;
        $this->filteredContent = $this->origContent = shell_exec('/usr/bin/pdftotext '.$filename.' -'); //dash at the end to output content
    }
    public function parseTapiola() {
        $i = 0;
        $this->filteredContent = substr($this->filteredContent, stripos($this->filteredContent, "KIRJAUSPÄIVÄ"));
        $this->deposits[$i]['date'] = substr($this->filteredContent, 14, 9);
        $this->filteredContent = substr($this->filteredContent, 24+19);
        $this->filteredContent = preg_replace("/\bBIC\s\w\b/", "", $this->filteredContent);
        $this->filteredContent = preg_replace("/\bIBAN\s\w\b/", "", $this->filteredContent);
        $this->filteredContent = preg_split("/\b\d{18}\b/", $this->filteredContent);
    }
}

?>
