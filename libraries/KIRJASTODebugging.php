<?php

/*
	Class for sending debugging messages to the developer. In the basic case that something
	doesn't work or you want to find out what is the actual DB-query string at some point
	of the script, you can just input the debugging there and send mail or input the message
	to the database.
	
	Also the same file / subclass handles the timer, for optimizing website and helping in debugging
	
	DB-requirements:
	All the database-variables in the class: DBTable, DBTextField and DBTimeField
	
*/

http://www.devshed.com/c/a/PHP/Error-Handling-in-PHP-Introducing-Exceptions-in-PHP-5/4/

class Debugging
{
	// E-mail-settings for Debugging messages:
    private 
            $adminEmail = "janne.hyytia@level7.fi",
            $mailSubject = "Message from dev-site",
            $msg = "",
            $DB = "",
            $usedMem = array(),
    
    // DB-variables:
            $DBTable="";
    
    public function __construct($adminEmail=NULL, $mailSubject=NULL, mysqli $DB = NULL, $DBTable = NULL)
    {
        if(isset($adminEmail))
                $this->$adminEmail = $adminEmail;
        if(isset($mailSubject))
                $this->$mailSubject = $mailSubject;
        if(isset($DB)) {
            $this->$DB = $DB;
            if(isset($DBTable)) {
                $this->$DBTable = $DBTable;
            }
            if(isset($DBTextField)) {
                $this->$DBTextField = $DBTextField;
            }
            if(isset($DBTimeField)) {
                $this->$DBTimeField = $DBTimeField;
            }
        }
    }

    // These are the timer functions
    public function addMsg($msg)
    {
        $this->msg .= $msg.PHP_EOL;
    }
    public function sendMail($msg="")
    {
        if($msg=="") {
            $msg = $this->msg;
        }
        mail($this->adminEmail, $this->mailSubject, $msg);
    }
    public function insertToDB($DB, $msg="")
    {
        if($msg=="") {
            $msg = $this->msg;
        }
        $DB->query("INSERT INTO ".$this->$DBTable." SET ".$this->$DBTextField."='".$msg."', ".$this->$DBTimeField." = '".time()."'");
    }
    public function echoOnScreen($msg="")
    {
        if($msg=="") {
            $msg = $this->msg;
        }
        echo "<br><br>Debugging mesage:".$msg."<br><br>";
    }
    
    // Setters
    public function setEmailAddress ($maili)
    {
        $this->adminEmail = $maili;
    }
    public function setSubject ($subj)
    {
        $this->mailSubject = $subj;
    }
    
    // Function for setting up a new timer-object:
    public function setupTimer ()
    {
    	return new LittleBenchmark();
    }
}
/*
 * ---------------------------------------------------
 * @author              abernardi77 at gmail dot com
 * @version             0.3.3
 * ---------------------------------------------------
 * 
 * Copyright (c) 2012 andrea bernardi - abernardi77@gmail.com
 * tested with PHP 5.3.8 (cli) (built: Dec  5 2011 21:24:09) on Mac osX 10.6.8 
 *
 * Permission is hereby granted, free of charge,
 * to any person obtaining a copy of this software and associated
 * documentation files (the "Software"),
 * to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.



USAGE:

    // Obviously, include_once this file into your script:
            include_once("path/to/class.LittleBenchmark.php");
        
    // Create an instance and start global timer,
      put this at the top of your script:
            $obj = new LittleBenchmark;
        
    // Start timer:
            $obj->start();
        
    // Stop timer and save message (can be an array of strings/integers):
            $obj->stop("your log string here");
            // or:
            $obj->stop( array("log string 1", "log string 2", 0.049374) );
            
    // Stop global timer, put this at the end of your script:
            $obj->stopGlobalTimer("last log string here");
            // or
            $obj->stopGlobalTimer( array("another message", 1.111223, "another string") );
    
    // Dump results as array:
            print_r($obj->deltas);
        
    // Dump results as JSON object:
            echo json_encode($obj->deltas);
    
*/

class LittleBenchmark {
    
    public 
            $startTime, $globalStartTime, $deltas;
    
    public function __construct()
    {
        $this->deltas = array();
        $this->startGlobalTimer();
    }
    
    private function getMicrotime()
    {
        return microtime(true);
    }
    
    private function deltaTime($stTime)
    {
        return $this->getMicrotime() - $stTime;
    }

    public function start()
    {
        $this->startTime = $this->getMicrotime();    
    }
    
    public function stop($msg="")
    {
        $this->addDelta($msg, $this->deltaTime($this->startTime));
    }
    
    public function startGlobalTimer()
    {
        $this->globalStartTime = $this->getMicrotime();
        $this->addDelta("GLOBAL TIMER STARTED", 0);
    }
    
    public function stopGlobalTimer($msg="")
    {
        $this->addDelta($msg, $this->deltaTime($this->globalStartTime));
    }
    
    private function addDelta($msg="", $deltaT)
    {
        $mem = $this->getRamUsage( memory_get_usage(true) );
        $this->deltas[] = array("EVENT" => $msg, 
                            "DELTA" => "" . round($deltaT, 6) . " s", 
                            "RAM_USAGE" => $mem);
    }
    
    // following method found somewhere into php online manual
    private function getRamUsage($size)
    {
        $unit = array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
     }

    function __destruct()
    {

    }
}

/**
 * Colorful Dumper (part of Lotos Framework)
 *
 * Copyright (c) 2005-2010 Artur Graniszewski (aargoth@boo.pl) 
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * - Neither the name of the Lotos Framework nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Library
 * @package    Lotos
 * @subpackage Dumper
 * @copyright  Copyright (c) 2005-2010 Artur Graniszewski (aargoth@boo.pl)
 * @license    New BSD License
 * @version    $Id$
 */
include_once "array_dump.class.php";