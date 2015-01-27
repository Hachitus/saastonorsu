<?php
/**
* CLASS odebugger
*
* @author : johan <barbier_johan@hotmail.com>
*/
class odebugger {

	/**
	* private (string) sAssertion
	* Check if error comes from an essertion or not, if yes, will conatin the filename of the evaluated script
	*/
	private $sAssertion = null;
	/**
	* private (string) sLang
	* localization string
	*/
	private $sLang = 'EN';

	/**
	* private (int) iNbLines
	* Number of lines displayed before and after the line of the error
	*/
	private $iNbLines = 2;

	/**
	* private (array) aOptions
	* Options array
	*/
	private $aOptions = array (
		'REALTIME' => true,
		'LOG_FILE' => true,
		'ERROR' => true,
		'EXCEPTION' => true
		);

	/**
	* private (string) sTemplateHTML
	* HTML template file for the realtime log
	*/
	private $sTemplateHTML = 'default';
	/**
	* private (string) sTemplateCSS
	* CSS template file for the realtime log
	*/
	private $sTemplateCSS = 'default';
	/**
	* private (string) sTemplateHTMLLOG
	* HTML template file for the whole log file
	*/
	private $sTemplateHTMLLOG = 'default_log';
	/**
	* private (string) sTemplateCSSLOG
	* CSS template file for the whole log file
	*/
	private $sTemplateCSSLOG = 'default_log';

	/**
	* private (string) sCurDir
	* Current directory of the script
	*/
	private $sCurDir = '';

	/**
	* private (string) sCurId
	* Current file unique id
	*/
	private $sCurId = '';

	/**
	* private (object) oXMLDOC
	* XML LOG Object (DOMDocument object)
	*/
	private $oXMLDOC = null;
	/**
	* private (object) oXMLROOT
	* XML LOG root Object (DOMDocument object)
	*/
	private $oXMLROOT = null;
	/**
	* private (object) oCurrentNode
	* Current node Object (DOMDocument object)
	*/
	private $oCurrentNode = null;

	/**
	* private (array) aCanBeSet
	* class properties that can be set via odebugger::__set()
	*/
	private $aCanBeSet = array (
		'LINES' => 'iNbLines',
		'REALTIME' => "aOptions['REALTIME']",
		'LOGFILE' => "aOptions['LOG_FILE']",
		'HTML' => 'sTemplateHTML',
		'CSS' => 'sTemplateCSS',
		'HTMLLOG' => 'sTemplateHTMLLOG',
		'CSSLOG' => 'sTemplateCSSLOG',
		'ERROR' => "aOptions['ERROR']",
		'EXCEPTION' => "aOptions['EXCEPTION']"
		);

	/**
	* private (object) oXMLTYPES
	* XML DOMDocument object with the list of error types and their translation
	*/
	private $oXMLTYPES = null;

	/**
	* private (object) oXMLERRORS
	* XML DOMDocument object with the list of errors and their translation
	*/
	private $oXMLERRORS = null;

	/**
	* private (array) aIndex
	* replacement array for the templates
	*/
	private $aIndex = array (
		0 => array (
			'{DATE_TITRE}',
			'{DATE_VALUE}'
			),
		1 => array (
			'{TYPE_TITRE}',
			'{TYPE_VALUE}'
			),
		2 => array (
			'{MSG_TITRE}',
			'{MSG_VALUE}'
			),
		3 => array (
			'{FILE_TITRE}',
			'{FILE_VALUE}'
			),
		4 => array (
			'{LINE_TITRE}',
			'{LINE_VALUE}'
			),
		5 => array (
			'{MEM_TITRE}',
			'{MEM_VALUE}'
			),
		6 => array (
			'{TRANS_TITRE}',
			'{TRANS_VALUE}'
			),
		7 => array (
			'{SUGG_TITRE}',
			'{SUGG_VALUE}'
			),
		8 => array (
			'{CONTEXT_TITRE}',
			'{CONTEXT_VALUE}'
			),
		9 => array (
			'{SOURCE_TITRE}',
			'{SOURCE_VALUE}'
			),
		100 => '{TOTAL_STATS}',
		101 => '{PHP_VERSION}'
		);

	/**
	* public function __construct ()
	* contsructor
	* sets the error_reporting to 0
	* gets the localization dir
	* import all the xml files
	* set the error handler
	* @Param (string) sLang : the localization used
	*/
	public function __construct ($sLang = 'EN') {
		@error_reporting (0);

                $tempdir = getcwd();
                chdir("/var/www/public_html/saastonorsu/test/libraries/testiDebug/");
		$aLnDir = scandir ('xml');
		if (in_array ($sLang, $aLnDir)) {
			$this -> sLang = $sLang;
		}
		$this -> oXMLERRORS = DOMDocument::load ('xml/'.$this -> sLang.'/errors.xml');
		$this -> oXMLTYPES = DOMDocument::load ('xml/'.$this -> sLang.'/types.xml');

		$this -> oXMLDOC = new DOMDocument ('1.0', 'utf-8');
		$root = $this -> oXMLDOC -> createElement ('ERRORLOG');
		$this -> oXMLROOT = $this -> oXMLDOC -> appendChild ($root);
		if (!is_dir ('logs')) {
			@mkdir ('logs', 0744);
		}
		$sTemp = dirname (__FILE__);
		$aTemp = explode ('\\', $sTemp);
		array_pop ($aTemp);
		$this -> sCurDir = implode ('/', $aTemp).'/';
		$this -> sCurId = date ('Ymd').'_'.uniqid();

		set_error_handler (array ($this, 'myErrorHandler'));
		set_exception_handler (array ($this, 'myExceptionHandler'));
                chdir($tempdir);
	}

	/**
	* public function checkCode ()
	* use the assert () function to get the errors in a given string, or a given file
	* @Param (string) sString : the string with the PHP code to evaluate, or the file to evaluate. Usually, it will come from a file via file_get_contents () for example
	* @Return : false if given parameter is not a string.
	*/
	public function checkCode ($sCode) {
		if (file_exists ($sCode)) {
			$sString = file_get_contents ($sCode);
			$this -> sAssertion = $sCode;
		} elseif (!is_string ($sCode)) {
			return false;
		} else {
			$sString = $sCode;
		}
		$sString = str_replace (array ('<?php', '<?', '?>'), '', $sString);
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		//assert_options (ASSERT_CALLBACK, array ($this, 'myAssertHandler')); Waiting a bit to improve this part
		assert ($sString);
		assert_options(ASSERT_ACTIVE, 0);
	}

	/**
	* public function myAssertHandler ()
	* activate the assertion. Right now, does nothing...and is not used.
	* @Param (string) file : the file from which comes the code
	* @Param (int) line : the error line
	* @Param (string) code : the error code
	* @Return : true
	*/
	public function myAssertHandler ($file, $line, $code){
		return true;
	}

	/**
	* private function checkErrorMessage ()
	* try to find the correct trsnalation and suggestion from a given error message
	* @Param (string) sMsg : the PHP error message
	* @Return (array) aTempArr : array with the translation and the suggestion found
	*/
	private function checkErrorMessage ($sMsg) {
		$iLength = strlen ($sMsg);
		$xpath = new DOMXPath($this -> oXMLERRORS);
		$sQueryLabel = '//error/label';
		$oLabelLists = $xpath -> query ($sQueryLabel);
		$aMsg = explode (' ', $sMsg);
		foreach ($oLabelLists as $oLabel) {
			$aLabel = explode (' ', $oLabel -> nodeValue);
			$aDiff = array_diff ($aLabel, $aMsg);
			if (empty ($aDiff)) {
				$aTempArr['TRANSLATION'] = $oLabel -> nextSibling -> nextSibling -> nodeValue;
				$aTempArr['SUGGESTION'] = $oLabel -> nextSibling -> nextSibling -> nextSibling -> nextSibling -> nodeValue;
				return $aTempArr;
			}
		}
	}

	/**
	* private function checkTypeTrans ()
	* try to find the error type translation
	* @Param (int) cErrno : the PHP constant error type code
	* @Return (string) nodeValue : the translated error type
	*/
	private function checkTypeTrans ($cErrno) {
		$xpath = new DOMXPath($this -> oXMLTYPES);
		$sQueryLevel = '//type/level';
		$oLevelList = $xpath -> query ($sQueryLevel);
		foreach ($oLevelList as $oLevel) {
			if (constant ($oLevel -> nodeValue) === $cErrno) {
				return $oLevel -> nextSibling -> nextSibling -> nodeValue;
			}
		}
	}

	/**
	* public function myExceptionHandler ()
	* the exception handler : builds the XML error log
	* @Param (object) e : the Exception object
	*/
	public function myExceptionHandler ($e) {
		$sErrStr = $e -> getMessage ();
		$iErrLine = $e -> getLine ();
		$sType = 'Exception '.$e -> getCode ();
		if (is_null ($this -> sAssertion)) {
			$sErrFile = $e -> getFile ();
		} else {
			$sErrFile = $this -> sAssertion;
			$this -> sAssertion = null;
		}
		$sVars = $e -> getTraceAsString ();
		$aTempArr = array ('TRANSLATION' => '', 'SUGGESTION' => '');

		$this -> buildLog ($sType, $sErrStr, $sErrFile, $iErrLine, $aTempArr, $sVars);
	}

	/**
	* public function myErrorHandler ()
	* the error handler : builds the XML error log
	* @Param (int) cErrno : the PHP constant error type code
	* @Param (string) sErrStr : the PHP error message
	* @Param (string) sErrFile : the file in which the error has been detected
	* @Param (int) iErrLine : the line of the error
	* @Param (array) mVars : the context
	*/
	public function myErrorHandler ($cErrno, $sErrStr, $sErrFile, $iErrLine, $mVars) {
		$aTempArr = $this -> checkErrorMessage ($sErrStr);
		$sType = $this -> checkTypeTrans ($cErrno);
		$sVars = 'n/a';
		if (!is_null ($this -> sAssertion)) {
			$sErrFile = $this -> sAssertion;
			$this -> sAssertion = null;
		}
		$this -> buildLog ($sType, $sErrStr, $sErrFile, $iErrLine, $aTempArr, $sVars);
	}

	/**
	* private function buildLog ()
	* the error handler : builds the XML error log
	* @Param (string) sType : The type of error/exception
	* @Param (string) sErrStr : the PHP error message
	* @Param (string) sErrFile : the file in which the error has been detected
	* @Param (int) iErrLine : the line of the error
	* @Param (string) sVars : the context
	*/
	private function buildLog ($sType, $sErrStr, $sErrFile, $iErrLine, $aTempArr, $sVars) {
		$iErrLine --;
		if ($iErrLine < 0) {
			$iErrLine = 0;
		}
		$oNewLog = $this -> oXMLDOC -> createElement ('ERROR');
		$dump = $this -> oXMLROOT -> getElementsByTagName('ERROR');
		$iNewId = $dump -> length + 1;
		$oNewLog = $this -> oXMLROOT -> appendChild ($oNewLog);
		$oNewLog -> setAttribute ('xml:id', '_'.$iNewId);

		$aElem[] = $this -> oXMLDOC -> createElement ('DATE', date ('d-m-Y H:i:s'));
		$aElem[] = $this -> oXMLDOC -> createElement ('TYPE', $sType);
		$sErrStr = utf8_encode ($sErrStr);
		$aElem[] = $this -> oXMLDOC -> createElement ('PHP_MESSAGE', $sErrStr);
		$aElem[] = $this -> oXMLDOC -> createElement ('FILE', $sErrFile);
		$aElem[] = $this -> oXMLDOC -> createElement ('LINE', $iErrLine);
		if (function_exists ('memory_get_usage')) {
			$iMemory = @memory_get_usage ();
		} else {
			$iMemory = 'n/a';
		}
		$aElem[] = $this -> oXMLDOC -> createElement ('MEMORY', $iMemory);
		$aElem[] = $this -> oXMLDOC -> createElement ('TRANSLATION', $aTempArr['TRANSLATION']);
		$aElem[] = $this -> oXMLDOC -> createElement ('SUGGESTION', $aTempArr['SUGGESTION']);

		$aElem[] = $this -> oXMLDOC -> createElement ('CONTEXT', $sVars);
		$oSource = $this -> oXMLDOC -> createElement ('SOURCE');
		$aSourceElem = array ();
		foreach ($this -> getLine ($sErrFile, $iErrLine) as $iLine => $sLine) {
			$sLine = utf8_encode ($sLine);
			if ($iLine === ($iErrLine)) {
				$aSourceElem[] = $this -> oXMLDOC -> createElement ('SOURCE_LINE_ERROR', ' /** ERROR AROUND THIS LINE => */ '.$sLine);
			} else {
				$aSourceElem[] = $this -> oXMLDOC -> createElement ('SOURCE_LINE', $sLine);
			}
		}
		foreach ($aSourceElem as $oSourceElem) {
			$oSource -> appendChild ($oSourceElem);
		}
		foreach ($aElem as $oElem) {
			$oNewLog -> appendChild ($oElem);
		}
		$oNewLog -> appendChild ($oSource);
		$this -> oCurrentNode = $oNewLog;
		if (true === $this -> aOptions['REALTIME']) {
			$this -> printMe ();
		}
	}
	/**
	* private function getLine ()
	* method to get the lines around the detected error
	* @Param (string) sErrFile : the file in which the error has been detected
	* @Param (int) iErrLine : the line of the error
	* @Return (array) aSource : array with each line
	*/
	private function getLine ($sErrFile, $iErrLine) {
		$aSource = array ();
		if (file_exists ($sErrFile)) {
			$aLines = file ($sErrFile);
			for ($i = $iErrLine - $this -> iNbLines; $i<= $iErrLine + $this -> iNbLines; $i ++) {
				if (isset ($aLines[$i])) {
					$aSource[$i] = $aLines[$i];
				}
			}
		}
		return $aSource;
	}

	/**
	* public function loadXML ()
	* loads an external error log
	* @Param (string) sFile : the error log file to be loaded
	*/
	public function loadXML ($sFile) {
		if (!file_exists ('logs/'.$sFile)) {
			return false;
		}
		$this -> oXMLDOC -> load ('logs/'.$sFile);
	}

	/**
	* public function showAll ()
	* show the whole current xml log
	*/
	public function showAll () {
		$xpath = new DOMXPath($this -> oXMLDOC);
		$sQuery = '//ERROR';
		$oNodeLists = $xpath -> query ($sQuery);
		foreach ($oNodeLists as $oNodeList) {
			$this -> oCurrentNode = $oNodeList;
			$this -> printMe ();
		}
	}

	/**
	* public function showLog ()
	* show the whole current log in a table, with stats (best used after odebugger::loadXML())
	* @Return (string) sHtml : the generated HTML
	*/
	public function showLog () {
		$sBaseHtml = file_get_contents ('templates/'.$this -> sTemplateHTMLLOG.'.dat');
		$iStartPos = strpos ($sBaseHtml, '<!-- LINES HERE -->');
		$sHtml = substr ($sBaseHtml, 0, $iStartPos);
		$iEndPos = strpos ($sBaseHtml, '<!-- STATS -->');
		$iLength = strlen ($sBaseHtml);
		$sTempHtml = substr ($sBaseHtml, $iStartPos,   - ($iLength - $iEndPos));
		$sTempHtmlTotal = '';
		$xpath = new DOMXPath($this -> oXMLDOC);
		$sQuery = '//ERROR';
		$oNodeLists = $xpath -> query ($sQuery);
		foreach ($oNodeLists as $oNodeList) {
			$this -> oCurrentNode = $oNodeList;
			$sTempHtmlTotal .= $this -> printMeLog ($sTempHtml);
		}
		$sHtml .= $sTempHtmlTotal;
		$sQuery = '//ERROR/TYPE';
		$oNodeLists = $xpath -> query ($sQuery);
		foreach ($oNodeLists as $oNodeList) {
			$aTypes[] = $oNodeList  -> nodeValue;
		}
		$sHtml .= substr ($sBaseHtml, $iEndPos, ($iLength - 1));
		$aCountType = array_count_values ($aTypes);
		$sCountType = '';
		foreach ($aCountType as $kType => $vType) {
			$sCountType .= $kType.' : '.$vType.'<br />';
		}
		$sVersion = @phpversion ();
		$sHtml = str_replace ($this -> aIndex[100], $sCountType, $sHtml);
		$sHtml = str_replace ($this -> aIndex[101], $sVersion , $sHtml);
		return $sHtml;
	}

	/**
	* private function printMe ()
	* display a caught error
	* @Return (string) sHtml : the generated HTML
	*/
	private function printMe () {
		$sHtml = file_get_contents ('templates/'.$this -> sTemplateHTML.'.dat');
		$nodeList = $this -> oCurrentNode -> childNodes;
		$iId = $this -> oCurrentNode -> getAttribute ('id');
		for ($i = 0; $i < $nodeList -> length; $i++) {
			$sName = $nodeList -> item($i) -> nodeName;
			if ($sName === 'SOURCE') {
				$sourceNodeList = $nodeList -> item($i) -> childNodes;
				$sValeur = '';
				for ($j = 0; $j < $sourceNodeList -> length; $j++) {
					$sValeur .= str_replace (array ('<?php', '?>', '<?'), '', $sourceNodeList -> item($j) -> nodeValue);
				}
				$sValeur = highlight_string ('<?php '."\r\n".$sValeur.'?>', true);
			} else {
				$sValeur = $nodeList -> item($i) -> nodeValue;
			}
			$sId = uniqid().'_'.$iId;
			$aReplacement = array ($sName, $sValeur);
			$sHtml = str_replace ($this -> aIndex[$i], $aReplacement, $sHtml);
			$sHtml = str_replace ('{ID}', $sId, $sHtml);
		}
		echo $sHtml;
	}

	/**
	* private function printMeLog ()
	* display a caught error, used by odebugger::showLog()
	* @Return (string) sHtml : the generated HTML
	*/
	private function printMeLog ($sHtml) {
		$nodeList = $this -> oCurrentNode -> childNodes;
		for ($i = 0; $i < $nodeList -> length; $i++) {
			if ($nodeList -> item($i) -> nodeName === 'SOURCE') {
				$sourceNodeList = $nodeList -> item($i) -> childNodes;
				$sValeur = '';
				for ($j = 0; $j < $sourceNodeList -> length; $j++) {
					$sValeur .= str_replace (array ('<?php', '?>', '<?'), '', $sourceNodeList -> item($j) -> nodeValue);
				}
				$sValeur = highlight_string ('<?php '."\r\n".$sValeur.'?>', true);
			} else {
				$sValeur = $nodeList -> item($i) -> nodeValue;
			}
			$sHtml = str_replace ($this -> aIndex[$i][1], $sValeur, $sHtml);
		}
		return $sHtml;
	}

	/**
	* public function saveToFile ()
	* save the current log to a given file
	* @Param (string) sFile : name of the log file
	*/
	public function saveToFile ($sFile = null) {
		if ($sFile === null) {
			$sFile = $this -> sCurId.'_error_log.xml';
		}
		$this -> oXMLDOC -> save ($this -> sCurDir.'logs/'.$sFile);
	}

	/**
	* public function __destruct ()
	* destructor
	* will save the log to a file if the LOG_FILE option is set to true
	*/
	public function __destruct () {
		if (true === $this -> aOptions['LOG_FILE']) {
			$this -> saveToFile ();
		}
	}

	/**
	* public function __set ()
	* allows some properties to be set
	* @Param (string) sProp : name of the property
	* @Param (mixed) mVal : the value to be given to the property
	* @Return (boolean) false if failed, true if succeeded
	*/
	public function __set ($sProp, $mVal) {
		if (false === array_key_exists ($sProp, $this -> aCanBeSet)) {
			return false;
		}
		switch ($sProp) {
			case 'LINES' :
				if (!is_int ($mVal)) {
					return false;
				}
				$this -> iNbLines = $mVal;
				return true;
				break;
			case 'HTML' :
				if (!file_exists ('templates/'.$mVal.'.dat')) {
					return false;
				}
				$this -> sTemplateHTML = $mVal;
				return true;
				break;
			case 'HTMLLOG' :
				if (!file_exists ('templates/'.$mVal.'.dat')) {
					return false;
				}
				$this -> sTemplateHTMLLOG = $mVal;
				return true;
				break;
			case 'CSS' :
				if (!file_exists ('css/'.$mVal.'.dat')) {
					return false;
				}
				$this -> sTemplateCSS = $mVal;
				readfile ('css/'.$mVal.'.dat');
				return true;
				break;
			case 'CSSLOG' :
				if (!file_exists ('css/'.$mVal.'.dat')) {
					return false;
				}
				$this -> sTemplateCSSLOG = $mVal;
				readfile ('css/'.$mVal.'.dat');
				return true;
				break;
			case 'REALTIME' :
				if (!is_bool ($mVal)) {
					return false;
				}
				$this -> aOptions['REALTIME'] = $mVal;
				return true;
				break;
			case 'LOGFILE' :
				if (!is_bool ($mVal)) {
					return false;
				}
				$this -> aOptions['LOG_FILE'] = $mVal;
				return true;
				break;
			case 'ERROR' :
				if (!is_bool ($mVal)) {
					return false;
				}
				$this -> aOptions['ERROR'] = $mVal;
				if (true === $mVal) {
					set_error_handler (array ($this, 'myErrorHandler'));
				} else {
					restore_error_handler ();
				}
				return true;
				break;
			case 'EXCEPTION' :
				if (!is_bool ($mVal)) {
					return false;
				}
				$this -> aOptions['EXCEPTION'] = $mVal;
				if (true === $mVal) {
					set_exception_handler (array ($this, 'myExceptionHandler'));
				} else {
					restore_exception_handler ();
				}
				return true;
				break;
			default:
				return false;
		}
	}

	/**
	* public function __get ()
	* allows some properties to be get
	* @Param (string) sProp : name of the property
	* @Return (boolean) false if failed, value of the property if succeeded
	*/
	public function __get ($sProp) {
		if (false === array_key_exists ($sProp, $this -> aCanBeSet)) {
			return false;
		}
		$sRealProp = $this -> aCanBeSet[$sProp];
		return $this -> $sRealProp;
	}
}
?>