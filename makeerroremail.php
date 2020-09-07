<?php

/**

This script emails details of all feeds that have been sinbinned in the past
24 hours.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
if (!defined('THIS_FILE_NAME')) define("THIS_FILE_NAME", "emailerrors");

// require initialisation script
require_once('initialise.php');

// get list of feeds with errors
$result = db_query('SELECT `name`, `url`, `updated`, `sinbin`, `reason` from ' . 
  '`journals` WHERE `active` = 1 AND `updated` < "' . 
  date('Y-m-d H:i:s', date('U') - (60 * 60 * 24)) . '" ORDER BY `name` ASC');
if ($result === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
}

// don't send email if there are no feeds with errors
if ($result->num_rows<1) {
	log_event('No feeds retrieved');
	// calling 'return' ends processing of this script and goes back to the 
	// calling script, if any
	return; 
}
log_event('Retrieved ' . $result->num_rows . ' feeds with recent errors');

// initiate email text
$output_text = "\r\n" . strtoupper(SERVICE_NAME) . ' ERRORS' . "\r\n" 
  . str_repeat('=',mb_strlen(SERVICE_NAME) + 7) . "\r\n\r\n" . 
  'The following active RSS feeds have not been updated for more than 24 ' . 
  'hours.' . "\r\n\r\n";

// format errors into email text
while ($feed = $result->fetch_assoc()) {

  // feed details
  $output_text .= "\r\n\r\n" . $feed['name'] . "\r\n" . 
    str_repeat('-', strlen($feed['name'])) . "\r\n" . 'This feed was last ' . 
    'updated on ' . date('j F Y', strtotime($feed['updated'])) . ' at ' . 
    date('H:i', strtotime($feed['updated'])) . ', ';
    
  // when feed was last successfully read
  $secsago = time() - strtotime($feed['updated']);
  if ($secsago > (60 * 60 * 24 * 10)) {
    $output_text .= round($secsago / (60 * 60 * 24), 0) . ' days ';
  } elseif ($secsago > (60 * 60 * 24)) {
    $output_text .= round($secsago / (60 * 60 * 24), 1) . ' days ';
  } elseif ($secsago > (60 * 60)) {
    $output_text .= round($secsago / (60 * 60), 1) . ' hours ';
  } else {
    $output_text .= round($secsago / (60), 1) . ' minutes ';
  }
  
  // error details
  $output_text .= 'ago.' . "\r\n" . 'The most-recent error recorded for this ' . 
  'feed was: "' . trim($feed['reason']) . '".' . "\r\n";
  
  log_event('Reported error with ' . $feed['name']);

}

// send email
if (mail(ADMIN_ERROR_EMAIL, SERVICE_NAME . " feed errors", $output_text) 
  !== TRUE) {
  log_event('Email could not be sent');
} else {
  log_event('Email successfully sent');
}

// record copy of email in log
log_event('Copy of email:' . "\n\n" . str_replace("\r\n", "\n", $output_text));

?>