<?php

/**

This script increments a click counter in the database and then redirects the user to the journal article

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "redirect");

// require initialisation script
require_once('../../ju-backoffice/initialise.php');

// get input
$url_path = explode('/',filter_var($_SERVER['REQUEST_URI'],FILTER_SANITIZE_URL));
$id = preg_replace("/[^A-Za-z0-9]/",'',$url_path[2]); // strip out non-alpha-numeric characters

log_event('Requested article was ' . $id . ' (base 36), which is ' . base_convert($id,36,10) . ' (base 10)');

// get article details from database
$result = db_query('SELECT link FROM `articles` WHERE id = ' . base_convert($id,36,10) . ' LIMIT 1');
if ($result === 'false') {
    trigger_error(db_error() ,E_USER_ERROR);
}

// if no results, send 404 error
if ($result->num_rows<1) {

	log_event('Requested article ID returned zero rows from DB');

	header("HTTP/1.0 404 Not Found",TRUE,404);

?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title><?= SERVICE_NAME ?> article not found</title>
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
    
    <h2>Article not found</h2>

	<p>Sorry, there was a problem with the link you clicked on: this link was not found.</p>

    </body>
</html>

<?php

} else {

	// extract link
	$thisres = $result->fetch_assoc();
	
	// increment click counter
	$result = db_query('UPDATE `articles` SET clicks = clicks + 1 WHERE id = ' . base_convert($id,36,10));
	if ($result === 'false') {
		trigger_error(db_error() ,E_USER_ERROR);
	}
	
	log_event('Article ID ' . $id . ' corresponds to link ' . $thisres['link'] . ' – counter incremented, redirecting now');

	// redirect user
	header("Location: " . str_replace('&amp;','&',$thisres['link']),TRUE,302);

}

?>