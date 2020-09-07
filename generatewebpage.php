<?php

/**

This script retrieves the oldest untweeted article and tweets it.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
if (!defined('THIS_FILE_NAME')) define("THIS_FILE_NAME", "generateweb");

// require initialisation script
require_once('initialise.php');

// die if this type of output is not enabled
if (FALSE === OUTPUT_WEB) 
	trigger_error('Terminating script because this type of output is not enabled', E_USER_ERROR);




function generateArchives ($archive_date = FALSE) {

if (!$archive_date) $archive_date = strtotime('yesterday UTC');

$archive_date_string = date('j F Y', $archive_date);
$index_date_string   = date('F Y', $archive_date);
log_event('Attempting to create web pages for ' . $archive_date_string);

// copy some constants to variables so that they can be used inside heredoc statements
$service_name = SERVICE_NAME;
$service_url  = SERVICE_URL;
$service_css  = SERVICE_CSS;



/**

Write daily list

**/

// write start of HTML file
$file_string = <<< EOT

<!doctype html>
<html class="no-js" lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>${service_name} articles published on ${archive_date_string}</title>
        <meta name="description" content="List of articles published on ${service_name} on ${archive_date_string}.">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="${service_url}/normalize.css">
        <link rel="stylesheet" href="${service_css}">
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please 
            <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

    <h1>${service_name}</h1>
    
    <h2>Articles published on ${archive_date_string}</h2>

EOT;

// get list of articles from DB
$result = db_query('SELECT * FROM `journals`, `articles` WHERE `articles`.`timestamp` >= "' 
    . date('Y-m-d',$archive_date) . ' 00:00:00" AND `articles`.`timestamp` <= "' . date('Y-m-d',$archive_date) 
    . ' 23:59:59" AND `articles`.`excluded` = 0 AND `articles`.`journal`=`journals`.`id` AND `journals`.`active` = 1'
    . ' ORDER BY `journals`.`name` ASC, `articles`.`date` ASC');
if ($result === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
}
log_event('Retrieved ' . $result->num_rows . ' articles from database for daily list');

// write HTML for articles
if ($result->num_rows < 1) {

	$file_string .= <<< EOT

	<p>No articles were published on ${archive_date_string}.</p>

EOT;
	log_event('No articles published on this date');

} else {

	// feed article information into array
	$articles = array();
	while ($article = $result->fetch_assoc()) {
		$articles[$article['journal']][] = $article;
	}

	// write introductory text
	$file_string .= '    <p>On ' . date('j F Y', $archive_date) . ', ' . number_format($result->num_rows) 
		. ' new articles were published in ' . number_format(count($articles)) . ' journals.</p>' . PHP_EOL;

	// process articles
	foreach ($articles as $journal) {

		// add journal name
		$file_string .= PHP_EOL . '    <h3><a href="' . $journal[0]['homepage'] . '">' . $journal[0]['name'] 
			. '</a></h3>' . PHP_EOL;
		log_event('Adding articles from ' . $journal[0]['name'] . ' to the daily web page');
	
		foreach ($journal as $article) {
	
			// convert URL to redirect
			$article_url = REDIRECT_URL_BASE . base_convert($article['id'],10,36);

			$file_string .= '    <p><a href="' . $article_url . '" title="full text of ' . $article['title'] 
				. '" class="title">' . $article['title'] . '</a>';
			if ($article['author']!='') {
				$file_string .= ' <span class="author">by ' . $article['author'] . '</span>';
			}
			$file_string .= '</p>' . PHP_EOL;
			if (strtotime($article['date']) < strtotime('2000-01-01')) {
				$file_string .= '    <p class="note">Note: the publisher did not provide a publication date for this '
					. 'article. It was added to the journal RSS feed on ' . gmdate('j F Y',strtotime($article['timestamp'])) 
					. ', which is why it is shown in the list of articles for that date. The article may be a new '
					. 'article or it may be older.</p>' . PHP_EOL;
			} elseif (strtotime($article['date'])<(time() - (60 * 60 * 24 * OLD_WARNING_DAYS))) {
				$file_string .= '    <p class="note">Note: although this article was published on ' 
					. gmdate('j F Y',strtotime($article['date'])) . ', the publisher only added it to the journal RSS feed' 
					. ' on ' . gmdate('j F Y',strtotime($article['timestamp'])) . ', which is why it is shown in the list' 
					. ' of articles for that date.</p>' . PHP_EOL;
			}
		
			log_event('  Adding article \'' . $article['title'] . '\' to the daily web page');

		}
	}

}

// finalise HTML
$file_string .= PHP_EOL . '    <footer>' . PHP_EOL . '        <ul>' . PHP_EOL;
$file_string .= '            <li><a href="' . SERVICE_URL . strtolower(date('Y/M/', $archive_date)) 
	. '">Archive for ' . date('F Y', $archive_date) . '</a></li>' . PHP_EOL;
$file_string .= '            <li><a href="' . SERVICE_URL . strtolower(date('Y/', $archive_date)) 
	. '">Archive index for ' . date('Y', $archive_date) . '</a></li>' . PHP_EOL;
$file_string .= '            <li><a href="' . SERVICE_URL . '">Home</a></li>' . PHP_EOL;
$file_string .= '        <ul>' . PHP_EOL . '    </footer>' . PHP_EOL . PHP_EOL . '    </body>' . PHP_EOL . '</html>' 
	. PHP_EOL;
$file_string .= '<!-- File generated at ' . date('r') . ' -->' . PHP_EOL;



/**

Write monthly list

**/

// $index_string .= '    <ul class="daysinmonth">' . PHP_EOL;
// foreach (range(1, date('t', $archive_date)) as $day) {
// 	$index_string = '        <li><a href="' . SERVICE_URL . strtolower(date('Y/m/', $archive_date)) . $day . '">' . 
// 		$day . '</a></li>';
// }
// $index_string .= '    </ul>' . PHP_EOL . PHP_EOL . '    </body>' . PHP_EOL . '</html>' . PHP_EOL;

// write HTML for monthly index
$index_string = <<< EOT

<!doctype html>
<html class="no-js" lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>${service_name} articles published during ${index_date_string}</title>
        <meta name="description" content="List of articles published on ${service_name} during ${index_date_string}.">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="${service_url}/normalize.css">
        <link rel="stylesheet" href="${service_css}">
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please 
            <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

    <h1>${service_name}</h1>
    
    <h2>Articles published during ${index_date_string}</h2>

EOT;

// copy this HTML header for re-use in the annual index
$index_string_header = $index_string;

// get list of articles from DB for monthly archive
$result = db_query('SELECT * FROM `journals`, `articles` WHERE `articles`.`timestamp` >= "' 
    . date('Y-m',$archive_date) . '-01 00:00:00" AND `articles`.`timestamp` <= "' . date('Y-m',$archive_date) 
    . '-' . date('t', $archive_date) . ' 23:59:59" AND `articles`.`timestamp` < "' . date('Y-m-d') . ' 00:00:00" AND '
    . '`articles`.`excluded` = 0 AND `articles`.`journal` = `journals`.`id` AND `journals`.`active` = 1 ORDER BY '
    .'`journals`.`name` ASC, `articles`.`date` ASC');
if ($result === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
}
log_event('Retrieved ' . $result->num_rows . ' articles from database for monthly list');

// write HTML for articles
if ($result->num_rows < 1) {

	$index_string .= <<< EOT

	<p>No articles were published during ${index_date_string}.</p>

EOT;
	log_event('No articles published on this date');

} else {

	// feed article information into array
	$articles = array();
	while ($article = $result->fetch_assoc()) {
		$articles[$article['journal']][] = $article;
	}
	
	// write introductory text
	if (date('Y-m') == date('Y-m', $archive_date)) {
		$index_string .= '    <p>So far in ' . date('F Y', $archive_date) . ', ' . number_format($result->num_rows) 
			. ' new articles have been published in ' . number_format(count($articles)) . ' journals.</p>' . PHP_EOL;
	} else {
		$index_string .= '    <p>In ' . date('F Y', $archive_date) . ', ' . number_format($result->num_rows) 
			. ' new articles were published in ' . number_format(count($articles)) . ' journals.</p>' . PHP_EOL;
	}

	// create table of contents
	$index_string .= '    <nav>' . PHP_EOL . '        <h3>Contents</h3>' . PHP_EOL . '        <ul>' . PHP_EOL;
	foreach ($articles as $journal) {
		$index_string .= '            <li><a href="#' 
			. str_replace(' ', '_', strtolower(filterAlpha($journal[0]['abbreviation']))) . '">' 
			. $journal[0]['name'] . '</a></li>' . PHP_EOL;
	}
	$index_string .= '        </ul>' . PHP_EOL . '    </nav>' . PHP_EOL;

	// process articles
	foreach ($articles as $journal) {

		// add journal name
		$index_string .= PHP_EOL . '    <h3 id="' . str_replace(' ', '_', 
			strtolower(filterAlpha($journal[0]['abbreviation']))) . '"><a href="' . $journal[0]['homepage'] . '">' 
			. $journal[0]['name'] . '</a></h3>' . PHP_EOL;
		log_event('Adding articles from ' . $journal[0]['name'] . ' to the monthly web page');
	
		foreach ($journal as $article) {
	
			// convert URL to redirect
			$article_url = REDIRECT_URL_BASE . base_convert($article['id'],10,36);

			$index_string .= '    <p><a href="' . $article_url . '" title="full text of ' . $article['title'] 
				. '" class="title">' . $article['title'] . '</a>';
			if ($article['author']!='') {
				$index_string .= ' <span class="author">by ' . $article['author'] . '</span>';
			}
			$index_string .= '</p>' . PHP_EOL;
			if (strtotime($article['date']) < strtotime('2000-01-01')) {
				$index_string .= '    <p class="note">Note: the publisher did not provide a publication date for this '
					. 'article. It was added to the journal RSS feed on ' . gmdate('j F Y',strtotime($article['timestamp'])) 
					. ', which is why it is shown in the list of articles for that date. The article may be a new '
					. 'article or it may be older.</p>' . PHP_EOL;
			} elseif (strtotime($article['date'])<(time() - (60 * 60 * 24 * OLD_WARNING_DAYS))) {
				$index_string .= '    <p class="note">Note: although this article was published on ' 
					. gmdate('j F Y',strtotime($article['date'])) . ', the publisher only added it to the journal RSS feed' 
					. ' on ' . gmdate('j F Y',strtotime($article['timestamp'])) . ', which is why it is shown in the list' 
					. ' of articles for that date.</p>' . PHP_EOL;
			}
		
			log_event('  Adding article \'' . $article['title'] . '\' to the monthly web page');

		}
	}

}

// finalise HTML
$index_string .= PHP_EOL . '    <footer>' . PHP_EOL . '        <ul>' . PHP_EOL;
$index_string .= '            <li><a href="' . SERVICE_URL . strtolower(date('Y/', $archive_date)) 
	. '">Archive index for ' . date('Y', $archive_date) . '</a></li>' . PHP_EOL;
$index_string .= '            <li><a href="' . SERVICE_URL . '">Home</a></li>' . PHP_EOL;
$index_string .= '        <ul>' . PHP_EOL . '    </footer>' . PHP_EOL . PHP_EOL . '    </body>' . PHP_EOL . '</html>' 
	. PHP_EOL;
$index_string .= '<!-- File generated at ' . date('r') . ' -->' . PHP_EOL;



/**

Write annual list

**/

$year_string = str_replace($index_date_string, date('Y', $archive_date), $index_string_header);
$year_string .= '    <p>Click on the links in this list to see what articles were published on each date.</p>' . PHP_EOL;

// write HTML for annual index
for ($i = 1; $i <= date('n', $archive_date); $i++) {
	$startofmonth = mktime(0, 0, 0, $i, 1, date('Y', $archive_date));
	$year_string .= PHP_EOL . '    <h3><a href="' . SERVICE_URL . strtolower(date('Y/M/')) . '">' 
		. date('F', $startofmonth) . '</a></h3>' . PHP_EOL;
	$year_string .= '    <ul class="daysinmonth">' . PHP_EOL;
	foreach (range(1, date('t', $startofmonth)) as $day) {
		if (strtotime(date('Y-m-') . sprintf("%02d", $day)) <= $archive_date) {
			$year_string .= '        <li><a href="' . SERVICE_URL . strtolower(date('Y/M/', $startofmonth)) 
				. sprintf("%02d", $day) . '">' . $day . '</a></li>' . PHP_EOL;
		}
	}
	$year_string .= '    </ul>' . PHP_EOL;
}
$year_string .= PHP_EOL . '    </body>' . PHP_EOL . '</html>' . PHP_EOL;
$year_string .= '<!-- File generated at ' . date('r') . ' -->' . PHP_EOL;



/**

Write files to server

**/

// create directories if necessary
$dirstocheck = array(PATH_FRONTOFFICE . date('Y', $archive_date), 
	PATH_FRONTOFFICE . strtolower(date('Y/M', $archive_date)),
	PATH_FRONTOFFICE . strtolower(date('Y/M/d', $archive_date)));
foreach ($dirstocheck as $dirtocheck) {
	clearstatcache(); // make sure none of the file checking functions uses cached values
	if (file_exists($dirtocheck)) {
		$current_permissions = substr(sprintf('%o', fileperms($dirtocheck)), -4);
		if (is_writable($dirtocheck) AND $current_permissions == '0755') {
			log_event('Directory ' . $dirtocheck . ' already exists and is writeable (' . $current_permissions . ')');
		} else {
			log_event('Directory ' . $dirtocheck . ' has permission ' . $current_permissions);
			if (chmod($dirtocheck, 0755)) { // note the leading zero because this is an octal number
				log_event('Existing directory ' . $dirtocheck . ' permissions changed from ' . $current_permissions 
					. ' to 0755');
			} else {
				trigger_error('Directory ' . $dirtocheck . ' could not be made writeable', E_USER_ERROR);
			}
		}
	} else {
		if (mkdir($dirtocheck, 0755)) { // note the leading zero because this is an octal number
			log_event('Created directory ' . $dirtocheck);
		} else {
			trigger_error('Could not create directory ' . $dirtocheck, E_USER_ERROR);
		}
	}
}

// write files
$filestowrite = array(
	'daily'   => PATH_FRONTOFFICE . strtolower(date('Y/M/d', $archive_date)) .  '/index.html',
	'monthly' => PATH_FRONTOFFICE . strtolower(date('Y/M', $archive_date)) .  '/index.html',
	'yearly'  => PATH_FRONTOFFICE . strtolower(date('Y', $archive_date)) .  '/index.html'
);
$stringstowrite = array(
	'daily'   => $file_string,
	'monthly' => $index_string,
	'yearly'  => $year_string
);
foreach ($filestowrite as $writefile => $pathtowrite) {
	if (file_put_contents($pathtowrite, $stringstowrite[$writefile])) {
		log_event(ucfirst($writefile) . ' file written to ' . $pathtowrite);
	} else {
		trigger_error('Could not write ' . $writefile . ' file to ' . $pathtowrite, E_USER_ERROR);
	}
}

} // end of generateArchives function



/**

Call function for yesterday and today

Note: yesterday must be called first, otherwise yesterday's values will over-write todays

**/

// find date of archive
if (!$archive_date = strtotime(trim(filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING, 
	FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH)))) {
	$archive_date = strtotime('yesterday UTC');
}
generateArchives($archive_date - (60 * 60 * 24));
generateArchives($archive_date);

?>