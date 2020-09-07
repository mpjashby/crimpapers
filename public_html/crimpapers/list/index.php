<?php

/**

This script generates a page listing the journals included in the service.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "articlelist");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');

?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Journals included in <?= SERVICE_NAME ?></title>
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
    
    <h2>Journals included in <?= SERVICE_NAME ?></h2>

<?php

$result = db_query('SELECT * FROM `journals`, `publishers` WHERE `active` = 1 AND `journals`.`publisher` = `publishers`.`id` ORDER BY `name`');
if ($result === 'false') {
	print '<p>Sorry, the list of journals included in the service could not be retrieved. The person responsible for '
        . 'running the service has been notified of this failure and will work to rectify the problem.</p>';
    trigger_error(db_error() ,E_USER_ERROR);
}

?>
	<p>The following <?=$result->num_rows?> journals are included in the <a href="<?= SERVICE_URL ?>"><?= SERVICE_NAME ?></a> service. 
	The number after each title is the <a href="https://scholar.google.com/intl/en/scholar/metrics.html#metrics" 
	title="details of the Google h5-index">Google h5-index</a> for that journal.</p>

	<ul class="journallist">
<?php

$noimpactnote = FALSE;
while ($journal = $result->fetch_assoc()) {
	$thisjournal = '        <li><a href="' . htmlspecialchars($journal['homepage']) . '" title="' . $journal['name'] 
	    . ' homepage">' . $journal['name'] . '</a> – ' . $journal['publisher_name'];
	if ($journal['impact'] > 0) {
		$thisjournal .= ' (' . $journal['impact'] . ')';
	} else {
		$thisjournal .= '*';
		$noimpactnote = TRUE;
	}
	$thisjournal .= '</li>' . "\n";
	print $thisjournal;
}

?>
	</ul>

<?php

if ($noimpactnote == TRUE) {

	print '<p>* This journal does not have a <a href="https://scholar.google.com/intl/en/scholar/metrics.html#metrics" 
	title="details of the Google h5-index">Google h5-index</a>. This may be because the journal does not meet 
	<a href="https://scholar.google.com/intl/en/scholar/metrics.html#coverage" 
	title="inclusion criteria for Google Scholar Metrics">Google\'s inclusion criteria</a>, for example because it has 
	not published enough articles. This is likely to be true for new journals and for annual-review journals.</p>';

}

?>

    </body>
</html>
