<?php

/**

This script controls the execution of other scripts that must run periodically. This script is called by a cron job,
so that it can in turn call all the other scripts.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "controller");

// require initialisation script
require_once('initialise.php');

function update_timestamps ($scripts = array()) {

	if (count($scripts) > 0) {
	
		$updated_calls = db_query('UPDATE `controls` SET `timestamp` = \'' . date('Y-m-d H:i:s') 
			. '\' WHERE `output` IN (\'' . implode('\', \'', $scripts) . '\')');
		if ($updated_calls === FALSE) {
			trigger_error(db_error(), E_USER_ERROR);
		} else {
			log_event('Updated last-executed timestamps for processes ' . implode(', ', $scripts));
		}
	
	}

	log_event('Finished processing file \'' . THIS_FILE_NAME . '\' on ' . gmdate('r'));

}
// register_shutdown_function('update_timestamps', $called_scripts);

// get current time
$timenow = time();
log_event('Time at server now is ' . gmdate('r', $timenow));

// get from DB most-recent time that scripts were run
$most_recent_data = db_query('SELECT * FROM `controls`');
if ($most_recent_data === FALSE) {
	trigger_error(db_error(), E_USER_ERROR);
}
$most_recent = array();
while ($most_recent_this_data = $most_recent_data->fetch_assoc()) {
	$most_recent[$most_recent_this_data['output']] = ($timenow - strtotime($most_recent_this_data['timestamp']))/60;
}
//$called_scripts = array();

// call makeweeklyemail.php at 0905 every Monday
if (
        OUTPUT_WEEKLY_EMAIL === TRUE AND 
        ((gmdate('l Hi', $timenow) == 'Monday 0905') OR $most_recent['weekly_email'] > (60 * 24 * 7))
) {
    log_event('Calling makeweeklyemail.php');
    update_timestamps(array('weekly_email'));
    ob_start();
    include('makeweeklyemail.php');
    ob_end_clean();
    log_event('Finished calling makeweeklyemail.php');
//    $called_scripts[] = 'weekly_email';
} elseif (OUTPUT_WEEKLY_EMAIL === TRUE) {
	log_event('Not calling makeweeklyemail.php because time is not right');
} else {
	log_event('Not calling makeweeklyemail.php because OUTPUT_WEEKLY_EMAIL == FALSE');
}

// call generatewebpage.php at 0010 every day
if (OUTPUT_WEB === TRUE AND (gmdate('Hi', $timenow) == '0010' OR $most_recent['webpage'] > (60 * 24))) {
    log_event('Calling generatewebpage.php');
    update_timestamps(array('webpage'));
    ob_start();
    include('generatewebpage.php');
    ob_end_clean();
    log_event('Finished calling generatewebpage.php');
//    $called_scripts[] = 'webpage';
} elseif (OUTPUT_WEB === TRUE) {
	log_event('Not calling generatewebpage.php because time is not right');
} else {
	log_event('Not calling generatewebpage.php because OUTPUT_WEB == FALSE');
}

// call makedailyemail.php at 0900 every weekday
if (
    OUTPUT_DAILY_EMAIL === TRUE 
//    AND intval(gmdate("N", $timenow)) <= 5 
    AND (gmdate("Hi", $timenow) == '0900' OR $most_recent['daily_email'] > (60 * 24))
) {
    log_event('Calling makedailyemail.php');
    update_timestamps(array('daily_email'));
    ob_start();
    include('makedailyemail.php');
    ob_end_clean();
    log_event('Finished calling makedailyemail.php');
//     $called_scripts[] = 'daily_email';
} elseif (OUTPUT_DAILY_EMAIL === TRUE) {
	log_event('Not calling makedailyemail.php because time (' . date('Hi', $timenow) . ') is not right');
} else {
	log_event('Not calling makedailyemail.php because OUTPUT_DAILY_EMAIL == FALSE');
}

// call getfeed.php every 15 minutes
if (in_array(intval(gmdate("i", $timenow)), array(0,15,30,45)) OR $most_recent['get_feed'] > 15) {
    log_event('Calling getfeed.php');
    update_timestamps(array('get_feed'));
    ob_start();
    include('getfeed.php');
    ob_end_clean();
    log_event('Finished calling getfeed.php');
//    $called_scripts[] = 'get_feed';
} else {
    log_event('Not calling getfeed.php because time (' . date('i', $timenow) . ') is not right');
}

// call sendtweet.php every 5 minutes
if (OUTPUT_TWITTER === TRUE AND (intval(gmdate("i", $timenow)) % 5 == 0 OR $most_recent['twitter'] > 5)) {
    log_event('Calling sendtweet.php');
    update_timestamps(array('twitter'));
    ob_start();
    include('sendtweet.php');
    ob_end_clean();
    log_event('Finished calling sendtweet.php');
//    $called_scripts[] = 'twitter';
} elseif (OUTPUT_TWITTER === TRUE) {
	log_event('Not calling sendtweet.php because time is not right');
} else {
	log_event('Not calling sendtweet.php because OUTPUT_TWITTER == FALSE');
}

// clear logs once a week
if (gmdate("l Hi", $timenow) == "Sunday 0005") {
    ob_start();
    include("cleanlogs.php");
    ob_end_clean();
    log_event('Finished calling cleanlogs.php');
}

?>