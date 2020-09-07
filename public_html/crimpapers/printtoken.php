<?php

header('Content-type: text/plain');

/**

This script retrieves the oldest untweeted article and tweets it.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "addtoken");

// require initialisation script
require_once('../../ju-backoffice/initialise.php');

while (TRUE) {
	print PHP_EOL;
	$get_row = db_query('SELECT `email` FROM `users` WHERE token = \'\' ORDER BY `email` ASC LIMIT 1');
	if ($get_row === FALSE) {
		print db_error();
		break;
	} elseif ($get_row->num_rows < 1) {
		print 'No rows to fetch';
		break;
	} else {
		$got_row = $get_row->fetch_assoc();
		$set_row_query = 'UPDATE `users` SET `token` = \'' . db_quote(uniqid()) . '\' WHERE `email` = \'' . $got_row['email'] . '\'';
		$set_row = db_query($set_row_query);
		if ($set_row === FALSE) {
			print db_error();
			break;
		} else {
			print $set_row_query;
		}
	}
	sleep(10);
}

?>