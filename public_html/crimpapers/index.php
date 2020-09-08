<?php

/**

This script generates a page listing the journals included in the service.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "serviceinfo");

// require initialisation script
require_once('../../ju-backoffice/initialise.php');

// get number of journals covered by the service
$numjournals = db_query('SELECT * FROM `journals` WHERE `active` = 1');
if ($numjournals === 'false') {
    trigger_error(db_error() ,E_USER_WARNING);
    $journalcount = '';
} else {
	$journalcount = ' It currently provides updates on new articles from ' . $numjournals->num_rows . ' journals.';
}

// get number of articles added to the database in the past eight weeks
$numarticles = db_query('SELECT * FROM `journals`, `articles` WHERE `articles`.`date` >= "' 
    . date('Y-m-d',date('U')-(60*60*24*56)) . '" AND `articles`.`date` < "' . date('Y-m-d') 
    . '" AND `articles`.`excluded` = 0 AND `articles`.`journal`=`journals`.`id` AND `journals`.`active` = 1'
    . ' ORDER BY `journals`.`impact` DESC, `articles`.`date` ASC');
if ($numarticles === 'false') {
    trigger_error(db_error() ,E_USER_WARNING);
    $articlecount = '';
} else {
	$articlecount = $numarticles->num_rows;
	if ($articlecount/(28*2) > 50) {
		$articlecount = ' About ' . number_format(round($articlecount/(28*2))) . ' new articles are published each day.';
	} else {
		$articlecount = ' About ' . number_format(round($articlecount/(4*2))) . ' new articles are published each week.';
	}
}

?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title><?= SERVICE_NAME ?></title>
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
    
	<p><?= SERVICE_DESCRIPTION ?>.<?= $journalcount ?><?= $articlecount ?></p>

	<p>You can subscribe to this free service in several ways.</p>

<?php

if (OUTPUT_TWITTER === TRUE) {

?>
	<h2>Twitter</h2>
	
	<p>Follow <a href="https://twitter.com/<?= TWITTER_HANDLE ?>" title="@<?= TWITTER_HANDLE ?> Twitter feed">
	@<?= TWITTER_HANDLE ?></a> on Twitter to get a tweet every time a new paper is added to a journal website.</p>
	
<?php

}

if (OUTPUT_DAILY_EMAIL === TRUE) {

?>
	<h2>Daily email alerts</h2>
	
	<p>Get a once-daily email listing all the articles added to the various journal websites in the previous 24 hours.
	You can also read <a href="<?= SERVICE_URL ?>today/" title="most-recent daily email">the most-recent email</a> online.
	Your email address won’t be shared with anyone else or used for any other purpose.</p>
        
        <p>To subscribe to the daily mailing list, send a blank email to 
        <a href="mailto:<?= MAILING_LIST_DAILY_SUBSCRIBE ?>"><?= MAILING_LIST_DAILY_SUBSCRIBE ?></a> – you will receive an 
        automated reply asking you to click a link to confirm your subscription. You can unsubscribe at any time by following 
        the instructions at the end of every email alert you receive.</p>

<!--        
	<form action="./subscribe/" method="POST">
		<p>Subscribe to daily updates: <input type="email" name="email" placeholder="Your email address" required 
		style="width: 20em; margin-right: 1em;"><input type="hidden" id="freqdaily" name="frequency" value="daily">
		<button type="submit">Subscribe</button></p>
	</form>
-->

<?php

}

if (OUTPUT_WEEKLY_EMAIL === TRUE) {

?>
	<h2>Weekly email alerts</h2>
	
	<p>Get a once-weekly email on Mondays listing all the articles added in the previous seven days.
	You can also read <a href="<?= SERVICE_URL ?>thisweek/" title="most-recent weekly email">the most-recent email</a> online.
	Your email address won’t be shared with anyone else or used for any other purpose.</p>
	
	<form action="./subscribe/" method="POST">
		<p>Subscribe to weekly updates: <input type="email" name="email" placeholder="Your email address" required 
		style="width: 20em; margin-right: 1em;"><input type="hidden" id="freqweekly" name="frequency" value="weekly">
		<button type="submit">Subscribe</button></p>
	</form>

<?php

}

if (OUTPUT_RSS === TRUE) {

?>
	<h2>RSS feed</h2>
	
	<p>Subscribe to <a href="<?= SERVICE_URL ?>rss/" title="<?= SERVICE_NAME ?> RSS feed">the <?= SERVICE_NAME ?> 
	RSS feed</a> to see new articles in your RSS reader of choice.</p>
	
<?php

}

?>
	<h2>Some technical notes</h2>
	
	<p>The data used to power this service come from the <a href="http://www.whatisrss.com/" title="What is RSS?">RSS 
	feeds</a> provided by each of the journal publishers. Some publishers provide better feeds than others and even 
	within a publisher some journals give more information than others. Although RSS is a widely used standard, each 
	publisher uses it in a slightly different way, so I can only reliably extract the article title and publication date. 
	Not all journals give the authors of papers, but they&#8217;re included if possible.</p>

	<p>Publication dates are problematic, because some journals add a paper to their RSS feeds when it is first 
	published online, while others only add it when the article is published in the print edition of the journal. This 
	service will let you know about an article as soon as it is added to a journal RSS feed, and make it clear if the 
	article has published previously.</p>

	</body>
</html>