<?php

/**

This script retrieves the oldest untweeted article and tweets it.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "listmessages");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title><?= SERVICE_NAME ?> system announcements</title>
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
    
    <h2>System announcements</h2>

<?php

$result = db_query('SELECT * FROM `messages` ORDER BY `timestamp` DESC');
if ($result === FALSE) {
	print '    <p>Sorry, the list of system announcements could not be retrieved.</p>';
	trigger_error(db_error(), E_USER_WARNING);
} elseif ($result->num_rows < 1) {
	print '    <p>There are no system announcements at present.</p>';
} else {

	while ($message = $result->fetch_assoc()) {
	
		$messagetime = strtotime($message['timestamp']);
		if ($messagetime > strtotime('-24 hours')) {
			$messagedate = date('j M  \a\t H:i (T)', $messagetime);
		} elseif (date('Y', $messagetime) == date('Y')) {
			$messagedate = date('j M', $messagetime);
		} else {
			$messagedate = date('j M Y', $messagetime);
		}
	
?>
	<section id="message-<?= $message['id'] ?>"<?= (!$firstmessage ? ' class="firstmessage"' : '') ?>>
		<p class="message"><?= $message['text'] ?></p>
		<p class="messagedate"> – <time datetime="<?= date('c', $messagetime) ?>"><?= $messagedate ?></time></p>
	</section>

<?php
	
		$firstmessage = TRUE;
	}

}

?>

	</body>
</html>
