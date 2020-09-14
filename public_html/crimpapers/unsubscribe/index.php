<?php

/**

This script constructs the daily email alert and sends it, as well as writing a copy of the HTML email to the web
server as a reference

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "unsubscribe");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');












// redirect to the service home page rather than run the code below, since
// subscription is now managed outside this script
header("Location: " . SERVICE_URL, TRUE, 301);
die();











// get and sanitise any input vars
// also set flag to determine whether necessary information is present
if (filter_has_var(INPUT_GET, 'email') AND filter_has_var(INPUT_GET, 'token')) {
	$user_email = trim(filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL));
	$user_token = trim(filter_input(INPUT_GET, 'token', FILTER_CALLBACK, array('options' => 'filterAlpha')));
	$has_info = TRUE;
} else {
	log_event('User did not provide email or token, so process cannot be continued');
	trigger_error('User tried to unsubscribe from email updates but this request could not be processed. The ' 
	. 'information included in the request (JSON encoded) was \'' . json_encode($_GET) . '\'', E_USER_WARNING);
	$has_info = FALSE;
}

// unsubscribe user from DB
if ($has_info == TRUE) {

	$result = db_query('UPDATE `users` SET `status` = 3 WHERE `email` = \'' . db_quote($user_email) 
		. '\' AND `token` = \'' . db_quote($user_token) . '\'');
	if ($result === FALSE) {
		trigger_error('The user ' . $user_email . ' tried to unsubscribe but a database error occurred:' . PHP_EOL 
			. PHP_EOL . db_error(), E_USER_WARNING);
		$has_info = FALSE;
	}
	log_event(db_info());
	$result_info = sscanf(trim(db_info()), "Rows matched: %d Changed: %d Warnings: %d");
	if ($result_info[1] < 1) {
		trigger_error('The MySQL query to unsubscribe the address ' . $user_email .  ' from email updates was run '
		. 'successfully, but no database rows were matched.', E_USER_WARNING);
		$has_info = FALSE;
	}

	if ($has_info == TRUE) {
		// email user to confirm that they have been unsubscribed
		$email_body = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><p>You have been ' 
			. 'successfully unsubscribed from ' . SERVICE_NAME . ' email updates. If you did not intend to unsubscribe, '
			. 'or you want to subscribe again, you can subscribe at <a href="' . SERVICE_URL . 'subscribe/">' . SERVICE_URL 
			. 'subscribe/</a></p></body></html>';

		$mailcheck = sendMultiPartMail(
			$user_email, // recipient
			'Unsubscribed from ' . SERVICE_NAME . ' email updates', // subject
			$email_body, // HTML body
			strip_tags($email_body) // plain-text body
			);
		if ($mailcheck !== TRUE) {
			trigger_error('Could not send unsubscribe confirmation email to ' . $user_email, E_USER_WARNING);
			$email_error = TRUE;
		}

		// email administrator
		$email_body = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><p>&lt;' . $user_email 
			. '&gt; has been unsubscribed from ' . SERVICE_NAME . '.</p></body></html>';
		$mailcheck = sendMultiPartMail(
			ADMIN_ERROR_EMAIL, // recipient
			SERVICE_NAME . ' unsubscription', // subject
			$email_body, // HTML body
			strip_tags($email_body) // plain-text body
			);
		if ($mailcheck !== TRUE) {
			trigger_error('Could not send unsubscribe notification email to ' . ADMIN_ERROR_EMAIL, E_USER_WARNING);
		}

	}

}

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Unsubscribe from <?= SERVICE_NAME ?> email updates</title>
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
    
    <h2>Unsubscribe from email updates</h2>

<?php

if ($has_info == TRUE) {

?>

	<p>You have been successfully unsubscribed from <?= SERVICE_NAME ?> email updates. You can <a 
	href="<?= SERVICE_URL ?>subscribe/" title="subscribe to <?= SERVICE_NAME ?> email alerts">subscribe again</a> 
	at any time.</p>

<?php

	if ($email_error == TRUE) {

?>

	<p>The system attempted to send you an email confirming that you have been unsubscribed, but the email could not
	be sent. Nevertheless, you have been unsubscribed from email alerts.</p>

<?php

	}

} else {

?>

	<p>Due to a server error, your request to unsubscribe from <?= SERVICE_NAME ?> email updates could not be
	processed at this time. The service administrator has been informed of this problem and will attempt to 
	unsubscribe you manually.</p>

<?php

}

?>

    </body>
</html>