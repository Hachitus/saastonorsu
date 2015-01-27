<?php

/*
 * Handles logging with the single public function log, thus we can later
 * change the functionality and end-logging-point easily.
 * 
 * =METHODS=
 * - log = receives the message to log and the methods in which to log them.
 * *** message = message to log
 * *** methodsWithMsg = array of methods in which way to log the event and the messages:
 *      -- [WRITE], [SHOW] and [EMAIL], uses the class constants as keys and the
 *          values themselves are messages for different logging methods.
 * *** showFormat is another class in this file that handles the output and
 *      responsibility of showing the error, if necessary, to the user.
 * - clearLogFile =  clears the log file.
 * 
 * === LogShowFormatter ===
 * Has one public mecessary method: format(), which format the log-message to be 
 * shown to user (for example popup).
 */

class HACHI_Logging
{
    // === Variables ===
    public
            $email = "janne.hyytia@saunalahti.fi",
            $emailSubject = "error on site",
            $showFormatter = null;
    private
            $lName = "Log",
            $handle = null;
    const 
            WRITE = "write",
            SHOW = "show",
            EMAIL = "email";

    // === Consruct / Destruct ===
    public function __construct($logName = null, HACHI_LogShowFormatter $showFormatter = null, $email = null)
    {
        $this->lName = $logName ? $logName : $this->lName;
        $this->email = $email;
        $this->showFormatter = $showFormatter;
        $this->openLog();
    }
    function __destruct()
    {
           fclose($this->handle);
    }
    
    // === PUBLIC ===
    public function log(array $methodsWithMsg, logFormatIF $showFormatter = null)
    {
        $returnable = true;
        
        if(array_key_exists($methodsWithMsg[WRITE])) {
            $this->writeLog($methodsWithMsg[WRITE]);
        }
        
        if(array_key_exists($methodsWithMsg[SHOW])) {
            $returnable = $showFormatter
                    ? $showFormatter->format($methodsWithMsg[SHOW])
                    : $this->showFormatter->format($methodsWithMsg[SHOW]);
        }
        
        if(array_key_exists($methodsWithMsg[EMAIL])) {
            $this->emailLog($methodsWithMsg[EMAIL]);
        }
        return $returnable;
    }
    public function clearLogFile()
    {
        $this->clearFile();
    }
    
    // === PRIVATE ===
    private function writeLog($message)
    {
        $time = date('d.m.Y @ H:i:s - ');
        fwrite($this->handle, $time . $message . "\n");
    }
    private function emailLog($message)
    {
        mail($this->email, $this->emailSubject, $message);
    }
    private function openLog()
    {
        $today = date('d.m.Y');
        $this->handle = fopen($this->lName . '_' . $today, 'a')
                OR exit("Can't open " . $this->lName . "_" . $today);
    }
    private function clearFile()
    {
        return;
    }
}

interface logFormatIF
{
    public function format($message);
    public function show();
}
class HACHI_LogShowFormatter implements logFormatIF
{
    public
            $msg = "";
    
    public function format($message)
    {
        $this->msg = $message;
    }
    public function show()
    {
        echo "<script>alert(".$this->msg.")</script>";
    }
}
?>
