<?php

/**

This script constructs the weekly email alert and sends it, as well as writing a copy of the HTML email to the web
server as a reference

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
if (!defined('THIS_FILE_NAME')) define("THIS_FILE_NAME", "makeweeklyemail");

// require initialisation script
require_once('initialise.php');

// die if this type of output is not enabled
if (FALSE === OUTPUT_WEEKLY_EMAIL) trigger_error('Terminating script because this type of output is not enabled');

// get articles added to the database in the past day
$result = db_query('SELECT * FROM `journals`, `articles` WHERE `articles`.`timestamp` >= "' 
    . date('Y-m-d',date('U')-(60*60*24*7)) . '" AND `articles`.`timestamp` < "' . date('Y-m-d') 
    . '" AND `articles`.`excluded` = 0 AND `articles`.`journal`=`journals`.`id` AND `journals`.`active` = 1'
    . ' ORDER BY `journals`.`impact` DESC, `articles`.`date` ASC');
if ($result === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
}

// don't send email if there are no untweeted articles
if ($result->num_rows<1) {
	log_event('No articles retrieved');
	return; // this ends processing of makedailyemail.php and goes back to the calling script, if any
}
log_event('Retrieved ' . $result->num_rows . ' articles from database');

// feed article information into array
$articles = array();
while ($article = $result->fetch_assoc()) {
	$articles[$article['journal']][] = $article;
}

// initiate HTML and plain text output
$output_html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . SERVICE_NAME 
	. ' weekly update</title>';
$output_html .= '<style>body, p, h1, h2 {font-family: "Helvetica", "Arial", sans-serif;} h1 {font-weight: normal; ' 
    . 'font-size: 2em; margin-bottom: 2em;} h2 {margin-top: 2em; font-size: 1.2em; font-weight: normal; ' 
    . 'border-bottom: 1px solid #999;} p {line-height: 1.3em;} p.note {margin-top: 1em; font-size: 0.8em; color: #999;}' 
    . ' a.title {text-decoration: none; font-weight: normal; color: #333;} a.title:hover{text-decoration: underline;}' 
    . ' span.author{color: #999;} p.sysmessage {padding: 0.5em; color: #000; background-color: #FAEBD7;}</style>';
$output_html .= '</head><body><h1>' . SERVICE_NAME . ' weekly update</h1>';
$welcome_text = 'Welcome to the ' . SERVICE_NAME . ' weekly update for ';
$week_start = gmdate('U')-(60*60*24*7);
if (gmdate('n',$week_start) == gmdate('n')) {
	$welcome_text .= gmdate('j',gmdate('U')-(60*60*24*7)) . ' to ' . gmdate('j F Y');
} elseif (gmdate('Y',$week_start) == gmdate('Y')) {
	$welcome_text .= gmdate('j F',gmdate('U')-(60*60*24*7)) . ' to ' . gmdate('j F Y');
} else {
	$welcome_text .= gmdate('j F Y',gmdate('U')-(60*60*24*7)) . ' to ' . gmdate('j F Y');
}
$welcome_text .= ', containing ' . $result->num_rows . ' articles from ' . count($articles) . ' journals.';
$subject_message = $result->num_rows . ' new articles from ' . count($articles) . ' journals';
$output_html .= '<p>' . $welcome_text . '</p>';
$output_text = "\r\n" . strtoupper(SERVICE_NAME) . ' WEEKLY UPDATE' . "\r\n" 
    . str_repeat('=',mb_strlen(SERVICE_NAME) + 14) . "\r\n\r\n";
$output_text .= $welcome_text . "\r\n\r\n";

// check if there are any pending system messages
$new_messages = db_query('SELECT * FROM `messages` WHERE `weekly` = 1');
if ($new_messages === FALSE) {
	trigger_error(db_error(), E_USER_WARNING);
} elseif ($new_messages->num_rows > 0) {
	while ($new_message = $new_messages->fetch_assoc()) {
		$output_html .= '<p class="sysmessage">' . $new_message['text'] . '</p>';
		$output_text .= "\r\n*****\r\nANNOUNCEMENT\r\n\r\n" . strip_tags($new_message['text']) . "\r\n*****\r\n\r\n";
		$message_ids[] = $new_message['id'];
	}
}

// process articles
foreach ($articles as $journal) {

	// add journal name
	$output_html .= '<h2>' . $journal[0]['name'] . '</h2>';
	$output_text .= "\r\n\r\n\r\n" . $journal[0]['name'] . "\r\n" . str_repeat('-',strlen($journal[0]['name']));
	log_event('Adding articles from ' . $journal[0]['name'] . ' to the email');
	
	foreach ($journal as $article) {
	
		// convert URL to redirect
		$article_url = REDIRECT_URL_BASE . base_convert($article['id'],10,36);

		// HTML
		$output_html .= '<p><a href="' . $article_url . '" title="full text of ' . $article['title'] 
		    . '" class="title">' . $article['title'] . '</a>';
		if ($article['author']!='') {
			$output_html .= ' <span class="author">by ' . $article['author'] . '</span>';
		}
		$output_html .= '</p>';
		
		// plain text
		$output_text .= "\r\n\r\n'" . $article['title'];
		if ($article['author']!='') {
			$output_text .= "' by " . $article['author'];
		}
		$output_text .= "\r\n" . $article_url;
		
		log_event('  Adding article \'' . $article['title'] . '\' to the email');

	}
}

// get number of journals covered by the service
$numjournals = db_query('SELECT * FROM `journals` WHERE `active` = 1');
if ($numjournals === FALSE) {
    trigger_error(db_error() ,E_USER_WARNING);
    $journalcount = 'multiple';
} else {
	$journalcount = $numjournals->num_rows;
}

// finish output
$signoff_text = SERVICE_DESCRIPTION . '. This service checks for new articles on the websites of ' . $journalcount 
    . ' journals. ' . SERVICE_DISCLAIMER . '.';
$output_html .= '<p class="note">' . $signoff_text . '</p>';
$output_text .= "\r\n\r\n\r\n" . $signoff_text;

// write HTML file to server
file_put_contents(PATH_FRONTOFFICE . 'thisweek/index.html', $output_html)
    or trigger_error('Could not write copy of this week\'s email to server', E_USER_WARNING);
file_put_contents(PATH_FRONTOFFICE . 'archive/weekly_update_' . date('Y_m_d') . '.html', $output_html)
    or trigger_error('Could not write copy of weekly email to archive', E_USER_WARNING);

// send email
// $mailcheck = sendMultiPartMail(
// 	MAILING_LIST_WEEKLY, // recipient
// 	SERVICE_NAME . ' weekly update – ' . $subject_message, // subject
// 	$output_html, // HTML body
// 	$output_text // plain-text body
// 	);
// if ($mailcheck !== TRUE) {
// 	trigger_error($mailcheck, E_USER_ERROR);
// }
// log_event('Sent mail successfully');

// get list of email recipients
$recipients = db_query('SELECT * FROM users WHERE `frequency` = \'weekly\' AND `status` = 2');
if ($recipients === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
}

log_event('Sending emails');

// send emails
// THE FOLLOWING CODE COMMENTED OUT WHILE WEEKLY UPDATES ARE SUSPENDED – THIS
// SCRIPT SHOULD STILL RUN OVERALL TO CREATE THE WEEKLY WEBPAGE
//$emailstats = array('success' => 0, 'failure' => 0);
//while ($recipient = $recipients->fetch_assoc()) {
//
//	// pause (to stay below rate limit)
//	sleep(EMAIL_PAUSE);
//	
//	// extend the script execution time
//	set_time_limit((EMAIL_PAUSE * 2 < 30 ? 30 : EMAIL_PAUSE * 2));
//
//	// build unsubscribe link
//	$unsub_html = '<p class="note"><a href="' . SERVICE_URL . 'unsubscribe/?email=' . urlencode($recipient['email']) 
//		. '&token=' . $recipient['token'] . '">Unsubscribe from ' . SERVICE_NAME . ' email updates</a></p>';
//	$unsub_text = "\r\n\r\n" . 'Unsubscribe from '. SERVICE_NAME . ' email updates: ' . SERVICE_URL 
//		. 'unsubscribe/?email=' . urlencode($recipient['email']) . '&token=' . $recipient['token'];
//
//	// send email
//	$mailcheck = sendMultiPartMail(
//		$recipient['email'], // recipient
//		SERVICE_NAME . ' weekly update – ' . $subject_message, // subject
//		$output_html . $unsub_html . '</body></html>', // HTML body
//		$output_text . $unsub_text // plain-text body
//		);
//	if ($mailcheck !== TRUE) {
//		++$emailstats['failure'];
//		log_event('  Email could not be sent to ' . $recipient['email']);
//	} else {
//		++$emailstats['success'];
//		log_event('  Email successfully sent to ' . $recipient['email']);
//	}
//
//}
//log_event('Sent ' . $emailstats['success'] . ' emails successfully with ' . $emailstats['failure'] . ' failures');

// update the status of any system messages that have now been sent
if ($new_messages !== FALSE AND $new_messages->num_rows > 0) {
	$update_messages = db_query('UPDATE `messages` SET `weekly` = 2 WHERE `id` IN (' . implode(',', $message_ids) 
		. ')');
	if ($update_messages === FALSE) {
		trigger_error(db_error(), E_USER_WARNING);
	}
}

log_event('Finished processing file \'' . THIS_FILE_NAME . '\' on ' . date('r'));

?>