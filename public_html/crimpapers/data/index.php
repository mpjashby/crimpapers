<?php

/**

This script generates a CSV file of data about the journals included in the 
service.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "journaldata");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');

// set content type of response to CSV
header("Content-Type: text/csv");

// get data from database
$result = db_query('SELECT * FROM `journals`, `publishers` WHERE `active` = 1 ' 
  . 'AND `journals`.`publisher` = `publishers`.`id` ORDER BY `name`');
if ($result === 'false') {
    trigger_error(db_error(), E_USER_ERROR);
}

// redirect file output to standard output
$out = fopen('php://output', 'w');

// write header row
fputcsv($out, array('journal_name', 'abbreviation', 'publisher', 'rss_url', 
  'home_page'));

// put rows to CSV
while ($journal = $result->fetch_assoc()) {

  fputcsv($out, array(
    $journal['name'],
    $journal['abbreviation'],
    $journal['publisher_name'],
    $journal['url'],
    $journal['homepage']
  ));

}

// close output
fclose($out);

?>