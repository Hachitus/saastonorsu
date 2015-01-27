<?php

// This file should be in php.ini (or htaccess?) with auto_prepend_file-setting. This adds the file to every script in php-execution:

set_error_handler("errorHandler");
register_shutdown_function("shutdownHandler");

function errorHandler($error_level, $error_message, $error_file, $error_line, $error_context)
{
$error = "lvl: " . $error_level . " | msg:" . $error_message . " | file:" . $error_file . " | ln:" . $error_line;
switch ($error_level) {
    case E_ERROR:
    case E_CORE_ERROR:
    case E_COMPILE_ERROR:
    case E_PARSE:
        log($error, "fatal");
        break;
    case E_USER_ERROR:
    case E_RECOVERABLE_ERROR:
        log($error, "error");
        break;
    case E_WARNING:
    case E_CORE_WARNING:
    case E_COMPILE_WARNING:
    case E_USER_WARNING:
        log($error, "warn");
        break;
    case E_NOTICE:
    case E_USER_NOTICE:
        log($error, "info");
        break;
    case E_STRICT:
        log($error, "debug");
        break;
    default:
        log($error, "warn");
}
}

function shutdownHandler() //will be called when php script ends.
{
$lasterror = error_get_last();
switch ($lasterror['type'])
{
    case E_ERROR:
    case E_CORE_ERROR:
    case E_COMPILE_ERROR:
    case E_USER_ERROR:
    case E_RECOVERABLE_ERROR:
    case E_CORE_WARNING:
    case E_COMPILE_WARNING:
    case E_PARSE:
        $error = "[SHUTDOWN] lvl:" . $lasterror['type'] . " | msg:" . $lasterror['message'] . " | file:" . $lasterror['file'] . " | ln:" . $lasterror['line'];
        log($error, "fatal");
}
}

function log($error, $errlvl)
{
...do whatever you want...
}

?>
