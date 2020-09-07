<?php

/**

This script constructs the daily email alert and sends it, as well as writing a copy of the HTML email to the web
server as a reference

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "subscribe");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');


// This script has four step:
//  1. presents a form for the user to enter their email address and choose a frequency of alerts (if available)
//  2. generates a confirmation email, sends it and tells the user to check their email for a confirmation message
//  3. accepts a confirmation token and confirms the user's subscription
// Any errors should be appended to $user_errors. If an error is permanent, invite the user to start again by setting
// $step = 1, which will present a new submisson form. Successful completion of any stage should result in exactly one 
// success message being written to $user_info.

// get and sanitise any input vars
// also set flag to determine which step to execute
if (filter_has_var(INPUT_GET, 'email') AND filter_has_var(INPUT_GET, 'token')) {
	$user_email = trim(filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL));
	$user_token = trim(filter_input(INPUT_GET, 'token', FILTER_CALLBACK, array('options' => 'filterAlpha')));
	$step = 3;
} elseif (filter_has_var(INPUT_POST, 'email') AND filter_has_var(INPUT_POST, 'frequency')) {
	$user_email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
	$user_freq = trim(filter_input(INPUT_POST, 'frequency', FILTER_CALLBACK, array('options' => 'filterAlpha')));
	$user_ip = $_SERVER['REMOTE_ADDR'];
	$step = 2;
} else {
	$step = 1;
}

// check for errors in input vars
$user_errors = array(); // set up an empty array for storing detected errors to inform the user of
if ($step == 2 AND filter_var($user_email, FILTER_VALIDATE_EMAIL) === FALSE) {
	$user_errors[] = 'The email address that you entered was not a valid email address.';
	$step = 1;
}
if ($step == 1 AND filter_has_var(INPUT_POST, 'frequency') AND !filter_has_var(INPUT_POST, 'email')) {
	$user_errors[] = 'You must include a valid email address for ' . SERVICE_NAME . ' alerts to be sent to.';
}
if (($step == 2 AND !in_array($user_freq, array('daily', 'weekly')))
	OR ($step == 2 AND $user_ip == FALSE)
    OR ($step == 1 AND (filter_has_var(INPUT_GET, 'email') OR filter_has_var(INPUT_GET, 'token')))
    ) {
	$user_errors[] = 'There was a problem with the information submitted.';
	$step = 1;
}

// get information from DB
if ($step == 3) {
	$user_info = db_query('SELECT * FROM `users` WHERE `email` = \'' . db_quote($user_email) . '\' AND `token` = \'' 
	    . db_quote($user_token) . '\'');
} elseif ($step == 2) {
	$user_info = db_query('SELECT * FROM `users` WHERE `email` = \'' . db_quote($user_email) . '\'');
}
if (in_array($step, array(2,3)) AND $user_info === FALSE) {
	trigger_error(db_error(), E_USER_ERROR);
}
if ($user_info->num_rows > 0) {
	$user_dets = $user_info->fetch_assoc();
}

// check if information supplied by user matches information in DB
if ($step == 3) {

	// maybe confirmation link was garbled and the subscription details were not in the DB
	if ($user_info->num_rows < 1) {
		$user_errors[] = 'We could not find the subscription that you were trying to confirm.';
		$step = 1;
	}

	// maybe the token has expired
	if ($user_info->num_rows > 0 AND strtotime($user_dets['token_sent'] < strtotime('-3 days'))) {
		$user_errors[] = 'This subscription link has expired and so cannot be used.';
		$step = 1;
	}

}
if ($step == 2 AND $user_info->num_rows > 0) {
	
	// maybe the user tried to subscribe with an address that was already subscribed
	if ($user_email == $user_dets['email'] AND $user_dets['status'] == 2) {
		$user_errors[] = 'This email address is already subscribed to ' . $user_freq . ' updates from ' 
		    . SERVICE_NAME . '. If you intended to switch to ' . ($user_freq == 'daily' ? 'weekly' : 'daily') 
		    . ' updates, please unsubscribe (by clicking on the link at the bottom of any ' . SERVICE_NAME 
		    . ' email) and then subscribe again on this page.';
		$step = 1;
	}

	// maybe the user tried to subscribe with an address for which there is already a subscription pending confirmation
	// make sure that if the email is already in the DB then another token isn't sent too soon, to prevent DNS attacks
	if ($user_email == $user_dets['email'] AND $user_dets['status'] == 1 
	    AND strtotime($user_dets['token_sent']) > strtotime('-3 days')) {
		if ($user_dets['token_sent'] > strtotime('-1 hour')) {
			$user_errors[] = 'You have attempted to subscribe with an email address that has already been subscribed '
				. 'to this service. However, this subscription is not yet active because it has not been confirmed. '
				. 'To confirm this subscription, please click on the link in the email that we have sent to you. If '
				. 'you have not received the email in one hour, please try to subscribe again and we will send you a '
				. 'new confirmation email.';
		} else {
			$user_errors[] = 'You have already tried to subscribe that email address to ' . SERVICE_NAME . ', but you '
				. 'have not yet confirmed your subscription by clicking on the link that we sent you by email. In case '
				. 'you have not received the confirmation email, we will send you a new email shortly.';
		}
	}
	
}
if ($step == 2) {

	// maybe user is trying to subscribe lots of addresses using a script – to (try to) prevent this, check if anyone
	// has subscribed a user from the same IP address in the past 10 seconds
	$same_ip = db_query('SELECT `ip` FROM `users` WHERE `ip` = ' . $user_ip 
	    . ' AND `timestamp` > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 10 second)');
	if ($same_ip === FALSE) {
	}
	if ($same_ip->num_rows > 0) {
		$user_errors[] = 'Too many subscription requests have been submitted from your computer in the past few' 
		    . ' seconds. Please wait a few minutes and try again.';
		$step = 1;
	}

}

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Subscribe to <?= SERVICE_NAME ?> email updates</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="<?= SERVICE_URL ?>/normalize.css">
        <link rel="stylesheet" href="<?= SERVICE_CSS ?>">
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please 
            <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

    <h1><?= SERVICE_NAME ?></h1>
    
    <h2>Subscribe to email updates</h2>

<?php

// subscribe the person once they have clicked on the confirmation link
if ($step == 3) {

	// update database to confirm subscription	
	$sub_confirm = db_query('UPDATE `users` SET `status` = 2 WHERE `email` = \'' . db_quote($user_email) 
	    . '\' AND `token` = \'' . db_quote($user_token) . '\'');
	if ($sub_confirm === FALSE) {
		$user_errors[] = 'Your subscription could not be confirmed because of a database error. The system ' 
    		. 'administrator has been informed of this and will contact you by email to &lt;' . $user_dets['email'] 
    		. '&gt;.';
		trigger_error(db_error(), E_USER_WARNING);
	} else {
		$user_info = 'Success! Your subscription to ' . SERVICE_NAME . ' ' . $user_dets['frequency'] 
		. ' updates has been confirmed.';
	}

	// email administrator
	$email_body = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><p>&lt;' . $user_email 
		. '&gt; has been subscribed to ' . SERVICE_NAME . '.</p></body></html>';
	$mailcheck = sendMultiPartMail(
		ADMIN_ERROR_EMAIL, // recipient
		SERVICE_NAME . ' new subscription', // subject
		$email_body, // HTML body
		strip_tags($email_body) // plain-text body
		);
	if ($mailcheck !== TRUE) {
		trigger_error('Could not send unsubscribe notification email to ' . ADMIN_ERROR_EMAIL, E_USER_WARNING);
	}

}

// record the person's details on the database and send them a confirmation email
if ($step == 2 AND count($user_errors) == 0) {

	// if the person has previously subscribed and unsubscribed, or previously subscribed and then not confirmed
	// before the token expired, update the previous DB record. Otherwise, insert a new record
	$new_token = uniqid();
	$new_token_date = date("Y-m-d H:i:s");
	$sub_enter_query = 'INSERT INTO `users` (`email`, `frequency`, `status`, `token`, `token_sent`, `ip`) ' 
	    . 'VALUES (\'' . db_quote($user_email) . '\', \'' . db_quote($user_freq) . '\', 1, \'' . db_quote($new_token) 
	    . '\', \'' . $new_token_date  . '\', \'' . db_quote($user_ip) . '\') ON DUPLICATE KEY UPDATE '
	    . '`frequency` = \'' . db_quote($user_freq) . '\', `status` = 1, `token` = \'' . db_quote($new_token) 
	    . '\', `token_sent` = \'' . $new_token_date . '\', `ip` = \'' . db_quote($user_ip) . '\'';
	print '<!-- ' . $sub_enter_query . ' -->' . PHP_EOL;
	$sub_enter = db_query($sub_enter_query);
	if ($sub_enter === FALSE) {
		$user_errors[] = 'Your subscription could not be processed because of a database error. The system ' 
    		. 'administrator has been informed of this and will contact you by email to &lt;' . $user_email 
    		. '&gt;.';
		trigger_error(db_error(), E_USER_WARNING);
	} else {
		$output_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body, p, h1, h2 {font-family: ' 
			. '"Helvetica", "Arial", sans-serif;} h1 {font-weight: normal; font-size: 2em; margin-bottom: 2em;} h2 '
			. '{margin-top: 2em; font-size: 1.2em; font-weight: normal; border-bottom: 1px solid #999;} p {line-height:'
			. ' 1.3em;}</style>';
		$output_html .= '</head><body><h1>Confirm your subscription to ' . SERVICE_NAME . '</h1>';
		$output_text = strtoupper('Confirm your subscription to ' . SERVICE_NAME) . '\r\n\r\n';
		$token_url = SERVICE_URL . 'subscribe/?email=' . urlencode($user_email) . '&token=' . $new_token;
		$main_message = '<p>To help stop unwanted email, we will not start your subscription to ' . SERVICE_NAME . ' ' 
			. $user_freq . ' updates until you confirm your subscription by clicking on the link <a href="' . $token_url 
			. '">' . $token_url . '</a> or pasting the link into your web brower. You will only be asked to do this '
			. 'once. This link will expire after three days.</p> <p>If you did <em>not</em> ask to subscribe to this '
			. 'list, you do not need to take any further action.</p>';
		$output_text .= strip_tags($main_message);
		$output_html .= $main_message;
		$mailcheck = sendMultiPartMail($user_email, 'Confirm your subscription to ' . SERVICE_NAME, $output_html, 
			$output_text);
		if ($mailcheck !== TRUE) {
			$user_errors[] = 'Due to an error, we could not send you the email needed to confirm your email address.';
			trigger_error('Error sending confirmation email', E_USER_WARNING);
		} else {
			$user_info = 'One more step! We have sent an email to &lt;' . $user_email . '&gt; to confirm your '
				. 'subscription. You will only start receiving ' . SERVICE_NAME . ' email alerts after you have '
				. 'clicked on the link in that confirmation email.';
		}
	}

}

if (count($user_errors)>0) {

	print '    <div class="error">';
	print '        <p>Sorry, there were problems with your request:</p>' . "\n";
	print '        <ul class="error">' . "\n";
	foreach ($user_errors as $user_error) {
		print '            <li>' . $user_error . '</li>' . "\n";
	}
	print '        </ul>' . "\n";
	print '    </div>' . "\n";

} else {

	print '    <p class="message">' . $user_info . '</p>' . "\n";

}

if ($step == 1) {

if (count($user_errors)>0) {
	print '    <p class="error">Please enter your details again.</p>' . "\n";
}

?>

	<form action="./" method="POST">
	
		<p><input type="email" name="email" placeholder="Your email address" required style="width: 20em; margin-right: 1em;"><input type="radio" id="freqdaily" 
		name="frequency" value="daily"> <label for="freqdaily" style="margin-right: 1em;">daily</label> <input type="radio" name="frequency" 
		value="weekly" checked="checked"> <label for="freqweekly" style="margin-right: 1em;">weekly</label> <button type="submit">Subscribe
		</button></p>
	
	</form>

<?php

}

?>
    </body>
</html>