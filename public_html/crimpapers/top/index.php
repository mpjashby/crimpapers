<?php

/**

This script generates a page listing the articles that have generated the most clicks.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "toparticles");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');

?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Most popular articles found using <?= SERVICE_NAME ?></title>
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
    
    <h2>Most popular articles found using <?= SERVICE_NAME ?></h2>

	<section id="thisweek">

		<h2>In the past <?= TOP_CHART_DAYS ?> days</h2>

<?php

// get number of published articles
$result = db_query('SELECT COUNT(*) as `article_count` FROM `articles`, `journals` WHERE `articles`.`journal` = ' 
    . '`journals`.`id` AND `articles`.`timestamp` >= DATE_SUB(NOW(),INTERVAL ' . TOP_CHART_DAYS . ' DAY) AND ' 
    . '`journals`.`active` = 1 AND `articles`.`excluded` = 0');
if ($result === 'false') {
	print '<p>Sorry, this list of most-popular articles could not be retrieved. The person responsible for '
        . 'running the service has been notified of this failure and will work to rectify the problem.</p>';
    trigger_error(db_error() ,E_USER_ERROR);
} else {
	$num_articles = $result->fetch_assoc();
	$num_articles = $num_articles['article_count']; // this is the only element we need from the array, so we can re-use the
		// variable name

	// get list of most-popular articles
	$result = db_query('SELECT `articles`.`id`, `articles`.`journal`, `articles`.`title`, `articles`.`date`, ' 
	    . '`articles`.`clicks`, `journals`.`name` FROM `articles`, `journals` WHERE `articles`.`journal` = ' 
	    . '`journals`.`id` AND `journals`.`active` = 1 AND `timestamp` >= DATE_SUB(NOW(),INTERVAL ' . TOP_CHART_DAYS 
	    . ' DAY) ORDER BY `clicks` DESC LIMIT ' . TOP_CHART_NUM);
	if ($result === 'false') {
		print '<p>Sorry, this list of most-popular articles could not be retrieved. The person responsible for '
			. 'running the service has been notified of this failure and will work to rectify the problem.</p>';
		trigger_error(db_error() ,E_USER_ERROR);
	} else {

// print list of articles
?>
	    <p>A total of <?=number_format($num_articles)?> articles have been promoted on <?= SERVICE_NAME ?> in the past 
	    <?= TOP_CHART_DAYS ?> days. These are the most-popular, calculated based on how many subscribers have clicked 
	    through from the journal alert to the article abstract.</p>

	    <ol>
<?php
		while ($article = $result->fetch_assoc()) {
			print '         <li class="toparticle"><a href="' . REDIRECT_URL_BASE . base_convert($article['id'],10,36) 
			    . '" title="' . $article['title'] . '">' . $article['title'] . '</a> published in ' . $article['name'] 
			    . ' on ' . date('l j F',strtotime($article['date'])) . ' (' . $article['clicks'] . ' clicks)</li>' 
			    . PHP_EOL;
		}

?>
    	</ol>

<?php

	}

}

?>
	</section>

    <section id="alltime">
    
    	<h2>Since this service began operating</h2>
    	
<?php

// get number of published articles
$result = db_query('SELECT COUNT(*) as `article_count` FROM `articles`, `journals` WHERE `articles`.`journal`' 
    . ' = `journals`.`id` AND `journals`.`active` = 1 AND `articles`.`excluded` = 0');
if ($result === 'false') {

	print '<p>Sorry, the list of most-popular articles could not be retrieved. The person responsible for '
        . 'running the service has been notified of this failure and will work to rectify the problem.</p>';
    trigger_error(db_error() ,E_USER_ERROR);

} else {

	$num_articles = $result->fetch_assoc();
	$num_articles = $num_articles['article_count']; // we only need one element from the array, so we can re-use the
		// variable name

	// get earliest timestamp in the database
	$result = db_query('SELECT `timestamp` FROM `articles` ORDER BY `timestamp` ASC LIMIT 1');
	if ($result === 'false') {
		print '<p>Sorry, this list of most-popular articles could not be retrieved. The person responsible for '
			. 'running the service has been notified of this failure and will work to rectify the problem.</p>';
		trigger_error(db_error() ,E_USER_WARNING);
	} else {
		
		$earliest_date = $result->fetch_assoc();
		$earliest_date = date("F Y", strtotime($earliest_date['timestamp'])) . '<!-- ' . $earliest_date['timestamp'] . ' -->'; // again, we can re-use the variable name

		// get list of articles
		$result = db_query('SELECT `articles`.`id`, `articles`.`journal`, `articles`.`title`, `articles`.`date`, ' 
		    . '`articles`.`clicks`, `journals`.`name` FROM `articles`, `journals` WHERE `articles`.`journal` = ' 
		    . '`journals`.`id` ORDER BY `clicks` DESC LIMIT ' . TOP_CHART_NUM);
		if ($result === 'false') {
			print '<p>Sorry, this list of most-popular articles could not be retrieved. The person responsible for '
				. 'running the service has been notified of this failure and will work to rectify the problem.</p>';
			trigger_error(db_error() ,E_USER_WARNING);
		} else {

?>

    	<p>A total of <?=number_format($num_articles)?> articles have been promoted on <?= SERVICE_NAME ?> since this 
    	service began operating in <?= $earliest_date ?>. These are the most-popular, calculated based on how many 
    	subscribers have clicked through from the journal alert to the article abstract.</p>
    
    	<ol>
<?php
            while ($article = $result->fetch_assoc()) {
				print '         <li class="toparticle"><a href="' . REDIRECT_URL_BASE . base_convert($article['id'],10,36) 
					. '" title="' . $article['title'] . '">' . $article['title'] . '</a> published in ' . $article['name'] 
					. ' on ' . date('l j F',strtotime($article['date'])) . ' (' . $article['clicks'] . ' clicks)</li>' 
					. PHP_EOL;
			}

?>
    	</ol>

<?php

		}

	}

}

?>
    </section>

    </body>
</html>
