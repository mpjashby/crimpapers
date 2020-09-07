<?php

/**

This file includes all the functions needed to run the journal updates service.

**/



/**

This function logs events during the life of the script

**/
function log_event($event) {

    $log_name = PATH_LOG . 'log_' . THIS_FILE_NAME . '_' . date("Y_m_d") . '.txt';

    file_put_contents($log_name, PHP_EOL . $event, FILE_APPEND);
    
    return $log_name;

}



/**

This function handles errors. All errors are logged using log_event, while all errors that are not notices are emailed
to the admin email address.

**/
function handle_error($errno, $errstr, $errfile, $errline) {

    // reference array for names of error levels
    $errlvls = array(
        1=>'error',
        2=>'warning',
        4=>'parse error',
        8=>'notice',
        16=>'core error',
        32=>'core warning',
        256=>'error',
        512=>'warning',
        1024=>'notice',
        2048=>'strict standards notice',
        4096=>'catchable error'
        );

    // create human-readable error string
    $error_string = ucfirst($errlvls[$errno]) . ' ' . $errstr . ' occurred on line ' . $errline;

    $log_name = log_event($error_string);

	// email error if it is not a notice
	if (!in_array($errno,array(8,1024,2048)) AND THIS_FILE_NAME != "getfeed") {
        $error_email_string = strtoupper(SERVICE_NAME) . ' SYSTEM MESSAGE' . PHP_EOL . PHP_EOL . $error_string . '.'
            . PHP_EOL . PHP_EOL . 'This error occurred in the file ' . $errfile . ' on ' . date('l j F Y \a\t H:i') . '. '
            . PHP_EOL . PHP_EOL . 'A log for the process that generated this error is available at '
            . $log_name;
		error_log($error_email_string, 1, ADMIN_ERROR_EMAIL);
	}

	// die if error was an error, rather than a warning or notice
	if (in_array($errno,array(1,4,16,256))) die();
	
	// apparently necessary to stop PHP triggering default error handler
	return true;

}
$old_error_handler = set_error_handler("handle_error");



/**

This function connects to a MySQL database

Source: https://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17

**/
function db_connect() {

    // Define connection as a static variable, to avoid connecting more than once 
    static $con;

    // Try and connect to the database, if a connection has not been established yet
    if(!isset($con)) {
        $con = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_DBNAME);
    }

    // If connection was not successful, handle the error
    if($con === false) {
        // Handle error - notify administrator, log to a file, show an error screen, etc.
        trigger_error(mysqli_connect_error(), E_USER_ERROR);
    }
    return $con;

}



/**

This function queries the database. This is extracted into a function so that the script could eventually be made
independent of a particular DB.

Source: https://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17

**/
function db_query($query) {

    // Connect to the database
    $connection = db_connect();

    // Query the database
    $result = mysqli_query($connection,$query);

    return $result;
}




/**

This function retrieves the most-recent database error.

Source: https://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17

**/
function db_error() {

    $connection = db_connect();

    return mysqli_error($connection);

}



/**

This function makes user input safe for insertion into the DB

Source: https://www.binpress.com/tutorial/using-php-with-mysql-the-right-way/17

**/
function db_quote($value) {
    $connection = db_connect();
    return mysqli_real_escape_string($connection,$value);
}



/**

This function gets information about the last DB operation

**/
function db_info() {

	$connection = db_connect();
	
	return $connection->info;

}



/**

This function tries (imperfectly) to identify characters in article titles that should be upper case. This is needed
because some publishers put the article title in all-caps in their RSS feeds.

**/
function titleCase ($string) {
    $word_splitters = array(' ', '-', "O'", "L'", "D'", 'St.', 'Mc', '"', '“', '”');
    $lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'on', 'to', 'in', 'and', "l'", "d'");
    $uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX', 'UK', 'USA', 'HM', 'EU');
 
    $string = strtolower($string);
    foreach ($word_splitters as $delimiter)
    { 
        $words = explode($delimiter, $string); 
        $newwords = array(); 
        foreach ($words as $word)
        { 
            if (in_array(strtoupper($word), $uppercase_exceptions))
                $word = strtoupper($word);
            else
            if (!in_array($word, $lowercase_exceptions))
                $word = ucfirst($word); 
 
            $newwords[] = $word;
        }
 
        if (in_array(strtolower($delimiter), $lowercase_exceptions))
            $delimiter = strtolower($delimiter);
 
        $string = join($delimiter, $newwords); 
    } 
    return $string; 
}



/**

This function sends an email via SMTP using PHPMailer.

**/
function sendMultiPartMail ($recipient, $subject, $htmlBody, $plainBody) {

	// connect to SMTP server
	require_once(PATH_TO_PHPMAILER);
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host = SMTP_HOST;
//  	$mail->SMTPAuth = true;
//  	$mail->Username = SMTP_USER;
//  	$mail->Password = SMTP_PASSWORD;
//  	$mail->SMTPSecure = 'ssl';
//  	$mail->Port = SMTP_PORT;
	$mail->SMTPAuth = false;
	$mail->Port = SMTP_PORT;
	
	// set up email
	$mail->setFrom(FROM_ADDRESS, SERVICE_NAME);
	$mail->addAddress($recipient);
//	$mail->AddBCC('noreply@lesscrime.info');
	$mail->isHTML(true);
	$mail->CharSet = 'UTF-8';
	$mail->Subject = $subject;
	$mail->Body = $htmlBody;
	$mail->AltBody = $plainBody;
	
	// send email
	if(!$mail->send()) {
		return $mail->ErrorInfo;
	} else {
		return true;
	}

}



/**

This function filters out non-alphanumeric characters from a string

Note: this function *should* be unicode safe

Source: http://stackoverflow.com/a/17151182

**/
function filterAlpha ($string) {

	return preg_replace("/[^[:alnum:][:space:]]/u", '', $string);

}



/**

This function trims the length of an article title (or, indeed, any string) to fit within a specified number of
characters.

**/
function trimTitle ($title, $length) {

	// abbreviate title if necessary
	if (mb_strlen($title)>$length) {

		$colonparts = explode(': ',$title);
		$qmarkparts = explode('? ',$title);

		// if title can be split using a colon, and colon is more than half-way through title, use just first part
		if (count($colonparts)>1 AND mb_strlen($colonparts[0])>$length/2 AND mb_strlen($colonparts[0]) < $length) {
			$finaltitle = $colonparts[0];

		// if title can be split using a question mark, do the same
		} elseif (count($qmarkparts)>1 AND mb_strlen($qmarkparts[0])>$length/2 
			AND mb_strlen($qmarkparts[0]) < $length) {
			$finaltitle = $qmarkparts[0] . '?';			

		// otherwise just use as many title words as will fit
		} else {
			$finaltitle = strtok(wordwrap($title, $length, " …\n"), "\n");
		}

	} else {
		$finaltitle = $title;
	}
	
	return $finaltitle;

}

?>