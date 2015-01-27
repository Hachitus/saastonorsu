<?php

class Files {
    private $filePaths = array();
    private $fileNames = array();
    private $fileTypes = array();
    private $fileSizes = array();
    private $totalFileSize = 0;
    private $maxSize = null;
    private $sizeContainment = 0;

    function Files ($filePathsArray, $maxSize) {
        $this->filePaths = strtolower($filePathsArray);
        $this->maxSize = (int) $maxSize;
        extractFileNames();
        extractFileTypes();
        $this->totalFileSize = 0;
        $this->sizeContainment = $this->maxSize - $this->totalFileSize;
    }
    private function extractFileNames () {
        $linuxStyle = strrpos($this->filePaths[$i], "/");
        $windowsStyle = strrpos($this->filePaths[$i], "\\");
        if($linuxStyle > $windowsStyle) {
            $this->fileNames[$i] = substr($this->filePaths[$i], $linuxStyle+1);
        } else {
            $this->fileNames[$i] = substr($this->filePaths[$i], $windowsStyle+1);
        }
    }
    private function extractFileTypes () {
        $fileType = filetype($this->filePaths[$i]);

        for($i=0; $this->fileNames[$i]; $i++) {
            if($fileType == "file") {// Selvitetään mikä tiedostotyyppi on kyseessä. Tiedosto:
                $this->fileTypes[$i] = substr(strtolower($this->fileNames[$i]), strrpos($this->fileNames[$i], ".")+1);
            }
            elseif ($fileType == "dir") // Kyseessä hakemisto:
                $this->fileTypes[$i] = $muuttuja;
            elseif ($fileType == "link") // Kyseessä linkki:
                $this->fileTypes[$i] = $muuttuja;
            else // tiedostomuoto jotain muuta:
                $this->fileTypes[$i] = false;
        }
    }
    private function extractFileSizes () {
        for($i=0; $this->filePaths[$i]; $i++) {
            $this->fileSizes[$i] = round(filesize($this->filePaths[$i]) / 1024, 1);
            $this->totalFileSize += $this->fileSizes[$i];
        }
    }
    protected function fileSize ($file) {
        return round(filesize($file) / 1024, 1);
    }
}
class Upload extends Files {
    private $allowedFileTypes = array();
    private $uploadFolder = "./uploads/";
    private $maxSize = 300;
    private $minSize = 0;

    function Upload ($fileTypesArray, $maxSize, $minSize = 0, $uploadFolder) {
        $this->allowedFileTypes = $fileTypesArray;
        $this->uploadFolder = $uploadFolder;
        $dirCharAtEnd = substr($this->uploadFolder, strlen($this->uploadFolder)-1,1);
        if(!($dirCharAtEnd == "\\" or $dirCharAtEnd == "/"))
            $this->uploadFolder .= "/";
        $this->maxSize = $maxSize;
        $this->minSize = $minSize;
    }

    function uploadFile () {
        move_uploaded_file($_FILES['file']['tmp_name'], $this->uploadFolder.$_FILES['file']['name']);
        deleteFile($_FILES['file']['tmp_name']);
    }
    function uploadPicture ($picMaxWidth, $picMaxHeight) {
        if ($picInfo = getimagesize($file)) {
            $picWidth = $picInfo[0];
            $picHeight = $picInfo[1];
        }
    }

}

class FileOperations extends Files {
    function copyFile () {

    }
    function copyFiles () {

    }
    function deleteFile () {

    }
    function deleteFiles () {

    }
    function renameFile () {
        // Myös kansion nimeäminen!
    }
}
class ShowFilesGUI extends Files {
    // Ei toteuteta vielä
}

//Asetukset alkavat
  //Sallittuihin päätteisiin avaimeksi täältä saatu avain halutuille kuvatyypeille:
  // http://fi.php.net/manual/fi/function.getimagesize.php
$asetukset['sallitut_päätteet'] = array(1 => "gif", 3 => "png", 2 => "jpg");
$asetukset['kuvan_maxleveys'] = 400;
$asetukset['kuvan_maxkorkeus'] = 300;
$asetukset['kuvan_maxkoko'] = 20; //Kilotavuina
$asetukset['latauskansio'] = './uploads/';
//Asetukset päättyvät

if (!empty($_FILES['file']['name'])) {
  //Tarkistukset alkavat

    //Kuvan päätteen tarkistus alkaa
    //Haetaan viimeisen pisteen jälkeen tulevat merkit, jotka siis määräävät kuvan päätteen
  $kuvan_pääte = substr(strtolower($_FILES['file']['name']), strrpos($_FILES['file']['name'], ".")+1);

  if (in_array($kuvan_pääte, $asetukset['sallitut_päätteet']) === false) {
    $virhe .= "Väärä pääte, sinun oli $kuvan_pääte kun sallitut päätteet ovat ";
    $virhe .= implode(", ", $asetukset['sallitut_päätteet']).".<br />".PHP_EOL;
  }
  //Kuvan päätteen tarkistus päättyy

  //Kuvan koon tarkistus ja validointi alkaa
    //Getimagesize hakee kuvasta erinäisiä tietoja, kuten sen fyysisen koon.
    //Samoin se palauttaa virheen mikäli kuva ei ole kuva laisinkaan
  if ($kuvan_tiedot = getimagesize($_FILES['file']['tmp_name'])) {
    $kuvan_leveys = $kuvan_tiedot[0];
    $kuvan_korkeus = $kuvan_tiedot[1];
    $kuvan_tyyppi = $asetukset['sallitut_päätteet'][$kuvan_tiedot[2]];
    $kuvan_koko = round(filesize($_FILES['file']['tmp_name']) / 1024, 1);

    //Leveyden tarkistus
    if ($kuvan_leveys > $asetukset['kuvan_maxleveys'])
      $virhe .= "Kuva on liian leveä, {$kuvan_leveys} px kun maksimileveys on {$asetukset['kuvan_maxleveys']} px.<br />".PHP_EOL;

    //Korkeuden tarkistus
    if ($kuvan_korkeus > $asetukset['kuvan_maxkorkeus'])
      $virhe .= "Kuva on liian korkea, {$kuvan_leveys} px kun maksimikorkeus on {$asetukset['kuvan_maxkorkeus']} px.<br />".PHP_EOL;

    //Koon tarkistus
    if ($kuvan_koko > $asetukset['kuvan_maxkoko'])
      $virhe .= "Kuva on liian suuri, $kuvan_koko Kt kun maksimikoko on {$asetukset['kuvan_maxkoko']} Kt.<br />".PHP_EOL;

    //Tyypin tarkistus
    if (in_array($kuvan_tyyppi, $asetukset['sallitut_päätteet']) === false)
      $virhe .= "Kuvan tyyppi ei ole tuettu, sallitut päätteet ovat ".implode(", ", $asetukset['sallitut_päätteet']).".<br />".PHP_EOL;

    //Tarkistetaan ettei kuvan nimi ole jo käytössä
    if (is_file($asetukset['latauskansio'].$_FILES['file']['name']))
      $virhe .= "Tällä nimellä on jo tiedosto ladattuna, valitse eri nimi.<br />".PHP_EOL;
  }
  else
    exit('Kuvaa ei tunnistettu kuvaksi.');
  //Kuvan koon tarkistus ja validointi päättyy

  if ($virhe)
    exit($virhe);
  //Tarkistukset päättyvät

  //Kuvan siirtäminen haluttuun paikkaan alkaa
  if (move_uploaded_file($_FILES['file']['tmp_name'], $asetukset['latauskansio'].$_FILES['file']['name']))
    echo "Tiedosto siirretty onnistuneesti. ";
  else {
    //Poistetaan väliaikaistiedosto
    unlink($_FILES['file']['tmp_name']);
    exit("Tiedoston siirtäminen lopulliseen kansioon epäonnistui.");
  }
  //Kuvan siirtäminen haluttuun paikkaan päättyy
}
?>