<?php

/**

This script deletes log files that are older than a certain age.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
if (!defined('THIS_FILE_NAME')) define("THIS_FILE_NAME", "cleanlogs");

// require initialisation script
require_once('initialise.php');

// specify days of logs to keep
$days_to_keep = 28;

// this code adapted from http://biostall.com/php-snippet-deleting-files-older-than-x-days/
if ($dir = opendir(PATH_LOG)) {
    
    while (false !== ($file = readdir($dir))) {
        
        if (
            is_file(PATH_LOG . $file) 
            AND filemtime(PATH_LOG . $file) < (time() - (60 * 60 * 24 * $days_to_keep))
        ) {
            
            unlink(PATH_LOG . $file);
            
            log_event("Deleted log file " . PATH_LOG . $file);
        
        }
        
    }
    
} else {
    
    trigger_error("Could not open log directory at " . PATH_LOG);
    
}

?>