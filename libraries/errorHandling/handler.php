<?php
/**
* @project mygosuLib
* @package ErrorHandler
* @version 2.0.1
* @license BSD
* @copyright (c) 2003,2004 Cezary Tomczak
* @link http://gosu.pl/software/mygosulib.html
 * 
 * Added more php ERROR-levels to the class (Strict - User_Deprecated)
 * 
*/

/**
* @access public
* @package ErrorHandler
*/
 
class ErrorHandler {

    private static $ERROR_HANDLER_ROOT;
    /**
    * Constructor
    * @access public
    */
    function __construct() {
        self::$ERROR_HANDLER_ROOT = dirname(__FILE__);
        ini_set('docref_root', null);
        ini_set('docref_ext', null);
    }

    /**
    * @param int $errNo
    * @param string $errMsg
    * @param string $file
    * @param int $line
    * @return void
    * @access public
    */
    function raiseError($errNo, $errMsg, $file, $line) {

        if (! ($errNo & error_reporting())) {
            return;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $errType = array (
            1    => "Error",
            2    => "Warning",
            4    => "Parsing Error",
            8    => "Notice",
            16   => "Core Error",
            32   => "Core Warning",
            64   => "Compile Error",
            128  => "Compile Warning",
            256  => "User Error",
            512  => "User Warning",
            1024 => "User Notice",
            2048 => "Strict error",
            4096 => "Recoverable / Catchable error",
            8192 => "Deprecated code",
            16384 => "User triggered error"
        );
        
        $info = array();

        if (($errNo & E_USER_ERROR) && is_array($arr = @unserialize($errMsg))) {
            foreach ($arr as $k => $v) {
                $info[$k] = $v;
            }
        }
        
        $trace = array();

        if (function_exists('debug_backtrace')) {
            $trace = debug_backtrace();
            array_shift($trace);
        }

        include self::$ERROR_HANDLER_ROOT . '/error.tpl';
    }
}

?>