<?php

/**

This file calls the config file and required libraries, then executes functions (e.g. database connection) that are
needed to do anything else. This file is included in all the other files that make up the service.

This file should be in the backoffice folder.

**/

// require configuration variables
require_once('config.php');

// set timezone (otherwise PHP will generate a warning every time any script is run
date_default_timezone_set(SERVICE_TIMEZONE);

// require utility functions
require_once('utility_functions.php');

// require external libraries
require_once(PATH_TO_SIMPLEPIE); // SimplePie for processing RSS feeds

// log start of processing of the script
log_event(PHP_EOL . 'Started processing file \'' . THIS_FILE_NAME . '\' on ' . gmdate('r'));

?>