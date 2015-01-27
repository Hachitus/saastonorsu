<?php

include_once "array_dump.class.php";

class ImprovedExceptions extends Exception {
    private $msg = "";
    private $adminEmail = "janne.hyytia@gmail.com";
    private $mailSubject = "Error from site";
    const METHOD_HTML = 1;
    const METHOD_MAIL = 2;
    private $method_html = "<br />";
    private $method_mail = PHP_EOL;
    public $debugMessage = "";
    static $methodUsed = "";
    
    function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    
    static function setHandler () {
        set_exception_handler (array ('ImprovedExceptions', 'customExceptionHandler'));
        set_error_handler(array('ImprovedExceptions', 'customErrorHandler'));
    }
    static function customExceptionHandler ($e) {
        $e -> getMessage = "UNCAUGHT EXCEPTION ".$e->getMessage;
        self::parseException_Backtrace($e -> getMessage ());
        echo $this->debugMessage;
    }
    
    static function customErrorHandler ($errno, $errstr,  $errfile, $errline, $errcontext) {
        
            global $error_type;
    $error_name = $error_type[$errno];
        
	$errorLineImproved = null;
        if(strstr($errstr, "Parse error")) {
            $errorLineImproved = parseErrorFormat($errfile);
        }
        include_once dirname(__FILE__)."/array_dump.class.php";
        // $array_dump = new array_dump();
        // echo is_array($errcontext);
        echo " ERRORnumber (".$error_name.$errno.") in file (".$errfile.") on line (".$errline."), ".$errstr." <br />".Dumper::dump($errcontext);
    }
    
    function showToUser () {
        echo "<br><br>".$this->debugMessage."<br><br>";
    }
    function sendMail() {
        mail($this->adminEmail, $this->mailSubject, $this->debugMessage);
    }
    
    function parseException_Backtrace ($debugMessage, $method = METHOD_HTML) {

        parseException_ArrayLoopHelper($debugMessage);

        switch ($method) {
            case METHOD_HTML:
                self::formatHtml();
                break;
            case METHOD_MAIL:
                self::formatMail();
                break;
        }
    }
    private function parseException_ArrayLoop ($arr) {
        if(is_array($arr)) {
            $this->debugMessage .= "<<<";
            foreach($arr as $key2 => $arr2) {
                self::parseException_ArrayLoop($arr2);
                $this->debugMessage .= $key2.":::".$arr2.";;;";
            }
            $this->debugMessage .= ">>>";
        }
    }
    private function formatHtml () {
        $this->debugMessage = str_replace(":::", " => ", $this->debugMessage);
        $this->debugMessage = str_replace(";;;", "<br />", $this->debugMessage);
        $this->debugMessage = str_replace("<<<", "<div style='padding:10px>", $this->debugMessage);
        $this->debugMessage = str_replace(">>>", "</div>", $this->debugMessage);
        $this->debugMessage = "<div style='background-color:D0FFD0'>".$this->debugMessage."</div>";
    }
    private function formatMail () {
        $this->debugMessage = str_replace(":::", " => ", $this->debugMessage);
        $this->debugMessage = str_replace(";;;", PHP_EOL, $this->debugMessage);
        $this->debugMessage = str_replace("<<<", "===>", $this->debugMessage);
        $this->debugMessage = str_replace(">>>", "<===", $this->debugMessage);
    }
    private function parseErrorFormat ($errfile) {
        $fh = file_get_contents($errfile);
        if(!(($match[0] = substr_count("\"") % 2))) {
            return "Uneven amount of double quotes (\"\")";
        }
        if(!(($match[1] = substr_count("'")))) {
            return "Uneven amount of single quotes ('')";
        }
        if(!(($match[2] = preg_match_all($fh, "[\{\}]{1}")))) {
            return "Uneven amount of curly brackets ({})";
        }
        if(!(($match[3] = preg_match_all($fh, "[\[\]]{1}")))) {
            return "Uneven amount of brackets ([])";
        }
        return $match;
    }
}

class ClassException extends Exception {
    private $msg = "";
    private $adminEmail = "janne.hyytia@gmail.com";
    private $mailSubject = "Error from site";
    const METHOD_HTML = 1;
    const METHOD_MAIL = 2;
    private $method_html = "<br />";
    private $method_mail = PHP_EOL;
    public $debugMessage = "";
    static $methodUsed = "";
    private $classObject = null;
    
    function __construct($classObject, $message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->classObject = $classObject;
    }
    
    static function setHandler () {
        set_exception_handler (array (self, 'customExceptionHandler'));
        set_error_handler('customErrorHandler');
    }
    static function customExceptionHandler ($e) {
	$iErrLine = $e -> getLine ();
        $e -> getMessage = "UNCAUGHT EXCEPTION ".$e->getMessage;
        self::parseException_Backtrace($e -> getMessage ());
        echo $this->debugMessage;
    }
    
    function customErrorHandler ($errno, $errstr,  $errfile = null, $errline = null, $errcontext = null) {
        $e -> getMessage = "ERROR ".$e->getMessage;
        echo $this->debugMessage.$errstr;
    }
    
    function showToUser () {
        echo "<br><br>".$this->debugMessage."<br><br>";
    }
    function sendMail() {
        mail($this->adminEmail, $this->mailSubject, $this->debugMessage);
    }
    
    function parseException_Backtrace ($debugMessage, $method = METHOD_HTML) {

        parseException_ArrayLoopHelper($debugMessage);

        switch ($method) {
            case METHOD_HTML:
                self::formatHtml();
                break;
            case METHOD_MAIL:
                self::formatMail();
                break;
        }
    }
    private function parseException_ArrayLoop ($arr) {
        if(is_array($arr)) {
            $this->debugMessage .= "<<<";
            foreach($arr as $key2 => $arr2) {
                self::parseException_ArrayLoop($arr2);
                $this->debugMessage .= $key2.":::".$arr2.";;;";
            }
            $this->debugMessage .= ">>>";
        }
    }
    private function formatHtml () {
        $this->debugMessage = str_replace(":::", " => ", $this->debugMessage);
        $this->debugMessage = str_replace(";;;", "<br />", $this->debugMessage);
        $this->debugMessage = str_replace("<<<", "<div style='padding:10px>", $this->debugMessage);
        $this->debugMessage = str_replace(">>>", "</div>", $this->debugMessage);
        $this->debugMessage = "<div style='background-color:D0FFD0'>".$this->debugMessage."</div>";
    }
    private function formatMail () {
        $this->debugMessage = str_replace(":::", " => ", $this->debugMessage);
        $this->debugMessage = str_replace(";;;", PHP_EOL, $this->debugMessage);
        $this->debugMessage = str_replace("<<<", "===>", $this->debugMessage);
        $this->debugMessage = str_replace(">>>", "<===", $this->debugMessage);
    }
}

 /*
  * 
// NOT DONE OR USED YET!

// http://www.devshed.com/c/a/PHP/Error-Handling-in-PHP-Introducing-Exceptions-in-PHP-5/4/

class CustomException extends Exception {
    function __construct () {
    }
}
class FileException extends CustomException {
    function __construct () {
    }
}
class MailException extends CustomException {
    function __construct () {
    }
}
class MySQLException extends CustomException {
    function __construct () {
    }
}

define( 'DEBUG', true );

class ErrorOrWarningException extends Exception
{
    protected $_Context = null;
    public function getContext()
    {
    	return $this->_Context;
    }
    public function setContext( $value )
    {
    	$this->_Context = $value;
    }

    public function __construct( $code, $message, $file, $line, $context )
    {
    	parent::__construct( $message, $code );

    	$this->file = $file;
    	$this->line = $line;
    	$this->setContext( $context );
    }
}

function error_to_exception( $code, $message, $file, $line, $context )
{
    throw new ErrorOrWarningException( $code, $message, $file, $line, $context );
}
set_error_handler( 'error_to_exception' );

function global_exception_handler( $ex )
{
    ob_start();
    dump_exception( $ex );
    $dump = ob_get_clean();
    // send email of dump to administrator?...

    // if we are in debug mode we are allowed to dump exceptions to the browser.
    if ( defined( 'DEBUG' ) && DEBUG == true )
    {
    	echo $dump;
    }
    else // if we are in production we give our visitor a nice message without all the details.
    {
    	echo file_get_contents( 'static/errors/fatalexception.html' );
    }
    exit;
}

function dump_exception( Exception $ex )
{
    $file = $ex->getFile();
    $line = $ex->getLine();

    if ( file_exists( $file ) )
    {
    	$lines = file( $file );
    }

?><html>
    <head>
    	<title><?= $ex->getMessage(); ?></title>
    	<style type="text/css">
    		body {
    			width : 800px;
    			margin : auto;
    		}

    		ul.code {
    			border : inset 1px;
    		}
    		ul.code li {
    			white-space: pre ;
    			list-style-type : none;
    			font-family : monospace;
    		}
    		ul.code li.line {
    			color : red;
    		}

    		table.trace {
    			width : 100%;
    			border-collapse : collapse;
    			border : solid 1px black;
    		}
    		table.thead tr {
    			background : rgb(240,240,240);
    		}
    		table.trace tr.odd {
    			background : white;
    		}
    		table.trace tr.even {
    			background : rgb(250,250,250);
    		}
    		table.trace td {
    			padding : 2px 4px 2px 4px;
    		}
    	</style>
    </head>
    <body>
    	<h1>Uncaught <?= get_class( $ex ); ?></h1>
    	<h2><?= $ex->getMessage(); ?></h2>
    	<p>
    		An uncaught <?= get_class( $ex ); ?> was thrown on line <?= $line; ?> of file <?= basename( $file ); ?> that prevented further execution of this request.
    	</p>
    	<h2>Where it happened:</h2>
    	<? if ( isset($lines) ) : ?>
    	<code><?= $file; ?></code>
    	<ul class="code">
    		<? for( $i = $line - 3; $i < $line + 3; $i ++ ) : ?>
    			<? if ( $i > 0 && $i < count( $lines ) ) : ?>
    				<? if ( $i == $line-1 ) : ?>
    					<li class="line"><?= str_replace( PHP_EOL, "", $lines[$i] ); ?></li>
    				<? else : ?>
    					<li><?= str_replace( PHP_EOL, "", $lines[$i] ); ?></li>
    				<? endif; ?>
    			<? endif; ?>
    		<? endfor; ?>
    	</ul>
    	<? endif; ?>

    	<? if ( is_array( $ex->getTrace() ) ) : ?>
    	<h2>Stack trace:</h2>
    		<table class="trace">
    			<thead>
    				<tr>
    					<td>File</td>
    					<td>Line</td>
    					<td>Class</td>
    					<td>Function</td>
    					<td>Arguments</td>
    				</tr>
    			</thead>
    			<tbody>
    			<? foreach ( $ex->getTrace() as $i => $trace ) : ?>
    				<tr class="<?= $i % 2 == 0 ? 'even' : 'odd'; ?>">
    					<td><?= isset($trace[ 'file' ]) ? basename($trace[ 'file' ]) : ''; ?></td>
    					<td><?= isset($trace[ 'line' ]) ? $trace[ 'line' ] : ''; ?></td>
    					<td><?= isset($trace[ 'class' ]) ? $trace[ 'class' ] : ''; ?></td>
    					<td><?= isset($trace[ 'function' ]) ? $trace[ 'function' ] : ''; ?></td>
    					<td>
    						<? if( isset($trace[ 'args' ]) ) : ?>
    							<? foreach ( $trace[ 'args' ] as $i => $arg ) : ?>
    								<span title="<?= var_export( $arg, true ); ?>"><?= gettype( $arg ); ?></span>
    								<?= $i < count( $trace['args'] ) -1 ? ',' : ''; ?> 
    							<? endforeach; ?>
    						<? else : ?>
    						NULL
    						<? endif; ?>
    					</td>
    				</tr>
    			<? endforeach;?>
    			</tbody>
    		</table>
    	<? else : ?>
    		<pre><?= $ex->getTraceAsString(); ?></pre>
    	<? endif; ?>
    </body>
</html><? // back in php
}
set_exception_handler( 'global_exception_handler' );

class X
{
    function __construct()
    {
    	trigger_error( 'Whoops!', E_USER_NOTICE );		
    }
}

$x = new X();

throw new Exception( 'Execution will never get here' );

*/