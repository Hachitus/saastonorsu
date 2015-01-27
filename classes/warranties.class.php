<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 * 
 * === VARIABLES ===
 * - startTime = When the product was bought and the warranty started.
 * - endTime = The calculated time based on the amount of months of warranty, when the warranty ends.
 * 
 * === METHODS ===
 * - createEndTime = modifies the given objects time to be the calculated warranties end time.
 * - timestampToDate = creates the formatted date, based on unix timestamp ($time)
 * - warrantyLeftInDays = how many days left till warranty ends, Returns format: +/-[number of days]
 * - warrantyEndDate =  returns the formatted date, when the warranty ends
 * - createWarrantyOptions = Creates the HTML options fields for select-HTML tag, to show
 * the warranty options in select-fields
 */

class Warranty
{
    public $startTime = null;
    public $endTime = null;
    CONST SECS_IN_DAY = 86400;
    CONST SECS_IN_MONTH = 86400;
    
    public function __construct(DateTime $startTime, DateTime $endTime)
    {
        $this->endTime = $endTime;
        $this->startTime = $startTime;
    }
    public static function createEndTime(DateTime $obj, $months)
    {
        $obj->modify("+".$months." months");
    }
    public static function timestampToDate($time)
    {
        return date("d-m-Y", $time);
    }
    public function warrantyLeftInDays()
    {
        $days = date_diff(new DateTime("now"), $this->endTime);
        return $days->format("%R%a");
    }
    public function warrantyInMonths()
    {
        // We need to add something extra to the timestamp! Otherwise it will round down the result and show 1 less month.
        $difference = date_diff($this->startTime, $this->endTime->modify('+1 day'));
        return $difference->format("%m");
    }
    public static function monthsToTimestamp($months)
    {
        return strtotime("+".$months." month");
    }
    public static function timestampToMonths($timestamp)
    {
        return date("m", $timestamp);
    }
    public function warrantyEndDate($format = "d.m.Y")
    {
        return date($format, $this->endTime);
    }
    
    public static function createWarrantyOptions ()
    {
        $HTML = "<option value='0'>no warranty</option>";
        
        for($i=1; $i <= 12; $i++) {
            $HTML .= "<option value='".$i."'>".$i._("short month tag")."</option>";
        }
        for($i=18; $i <= 120; $i += 6) {
            $year = $i / 12;
            $HTML .= "<option value='".$i."'>".$year._("short year tag")."</option>";
        }
        return $HTML;
    }
}

?>
