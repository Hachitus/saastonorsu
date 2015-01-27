<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 */

class Vat {
    static public function checkVatValue($vat) {
        if(!is_int($vat)) {
            if(0 <= $vat && $vat >= 100) {
                return false;
            }
        }
        return true;
    }
}
?>