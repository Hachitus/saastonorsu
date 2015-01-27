<?php

/*
 * Copyright (C) 2013 Janne Hyytiä (excluding the possible open source code taken online and separately mentioned)
 */

class paymentMethods extends KIRJASTOVariablesBasedOnDB implements FetchValues
{
    public function __construct()
    {
        parent::__construct("paymentMethods");
    }
    public function fetchQuery()
    {
        if($this->allQuery) {
            $this->allQuery->data_seek(0);
        } else {
            $this->allQuery = self::$dataSource->queryWithExceptions("SELECT ID, name FROM paymentMethods WHERE deleted >= 0 ORDER BY name");
        }
        if(self::$dataSource->affected_rows) {
            return $this->allQuery;
        }
        return false;
    }
    public function fetchArray()
    {
        $retArray = Array();
        if(($query = self::fetchQuery())) {
            while($retArray[] = $query->fetch_assoc());
            $retArray = array_slice($retArray, 0, -1);
            return $retArray;
        }            
        return false;
    }
    public function isValid()
    {
        return true;
    }
}
?>
