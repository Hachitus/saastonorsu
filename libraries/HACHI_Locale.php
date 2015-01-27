<?php

// Requires the intl PECL-extension for PHP
class HACHI_Locale
{
    private
            $locale = "en_US",
            $HTMLdefault = false;
    public
            $countryTag = "en",
            $countryName = "United Kingdom",
            $cultureTag = "US",
            $dateFormat = "";
    
    public function setLocale($locale = null)
    {
        $this->locale = $locale;
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
    }
    public function getLocale()
    {
        return $this->locale;
    }
    public function countryName($HTMLdefault = false)
    {
        Locale::getDisplayLanguage($this->locale, Locale::getPrimaryLanguage($this->locale));
    }
    public function countryTag($amount, $HTMLdefault = false)
    {
        Locale::getDisplayLanguage($this->locale, Locale::getPrimaryLanguage($this->locale));
    }
    public function formatCurrency($amount, $HTMLdefault = false)
    {
        NumberFormatter::formatCurrency($amount);
    }
    public function formatDate(IntlDateFormatter $date, $HTMLdefault = false)
    {
        IntlDateFormatter($date);
    }
    public function formatNumber($amount, $HTMLdefault = false)
    {
        
    }
    public function getCurrencySymbol()
    {
        
    }
}
// ==========================
class Language
{
    
}
?>
