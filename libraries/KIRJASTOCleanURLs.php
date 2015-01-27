<?php

/* The script needs htaccess to work and of course apaches rewrite module
	RewriteEngine on
	RewriteRule ^test.cleanurl(\/.*)*$ /test.cleanurl.php
	RewriteRule ^news(\/.*)*$ /news.php 
*/

echo $_SERVER['REQUEST_URI']." ".$_SERVER['SCRIPT_NAME'];
echo ("hehehe" - "hehe");
class CleanURL
{
    private 
            $pagename,
            $arguments,
            $URL;

    public function __construct() {
        $this->URL = $_SERVER['REQUEST_URI'];
        $this->pagename = $_SERVER['SCRIPT_NAME'];
    }

    public function processCleanedURL()
    {
        
        /* get extension */
        $ext = end( explode(".",$this->script) );

        /* if extension is found in URL, eliminate it */
        if(strstr($this->URL,".")) {
            $arr_uri = explode('.', $this->URL);
            /* get last part */
            $last = end($arr_uri);

            if($last == $ext){
                array_pop($arr_uri);
                $this->URL = implode('.', $arr_uri);
            }
        }

        /* pick the name without extension */
        $basename = basename ($this->script, '.'.$ext);
        /* slicing query string */
        $temp  = explode('/',$this->URL);
        $key   = array_search($basename,$temp);
        $parts = array_slice ($temp, $key+1);
        $this->basename = $basename;
        $this->parts = $parts;

    }

    public function setRelative($relativevar)
    {
        /* count the number of slash
           to define relative path */
        $numslash = count($this->parts);
        $slashes="";
        for($i=0;$i<$numslash;$i++){
            $slashes .= "../";
        }
        $this->slashes = $slashes;
        /* make relative path variable available for webpage */
        $this->relativeVars[$relativevar] = $slashes;

    }

    public function setParts()
    {
        /* pair off query string variable and query string value */
        $numargs = func_num_args();
        $arg_list = func_get_args();
        $urlparts = $this->getParts();
        for ($i = 0; $i < $numargs; $i++) {
            /* make them available for webpage */
            $this->arguments[$i] = $urlparts[$i];
        }

    }

    public function makeClean($stringurl)
    {
        /* convert normal URL query string to clean URL */
        $url=parse_url($stringurl);
        $strurl = basename($url['path'],".php");
        $qstring = parse_str($url['query'],$vars);
        while(list($k,$v) = each($vars)) $strurl .= "/".$v;
        return $strurl;

    }
   
    public function getBasename ()
    {
        return $this->basename;   
    }
    public function getParts ()
    {
        return $this->parts;   
    }
    public function getSlashes ()
    {
        return $this->slashes;   
    }
}


?> 