<?php

/**

This script retrieves the oldest untweeted article and tweets it.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
if (!defined('THIS_FILE_NAME')) define("THIS_FILE_NAME", "sendtweet");

// require initialisation script
require_once('initialise.php');

// die if this type of output is not enabled
if (FALSE === OUTPUT_TWITTER) 
	trigger_error('Terminating script because this type of output is not enabled', E_USER_ERROR);

// set time if it has not been set by any script that required this one
if(!isset($timenow)) $timenow = time();



//
//
//
// TURN OFF TWEETS TEMPORARILY WHILE INVESTIGATING A PROBLEM
//
//
//
// die();
//
//
//
//
//
//



// get any pending messages
$new_messages = db_query('SELECT * FROM `messages` WHERE `twitter` = 1 ORDER BY `timestamp` ASC LIMIT 1');
if ($new_messages === FALSE) {
	trigger_error(db_error(), E_USER_WARNING);
}

// decide what to tweet
if (filter_has_var(INPUT_GET, 'decision') 
	AND in_array($get_decision = filter_input(INPUT_GET, 'decision', FILTER_CALLBACK, array('options' => 'filterAlpha')), 
		array('weeklyupdate', 'weeklypopular', 'dailyupdate', 'dailypopular', 'message', 'article'))) {
	$decision = $get_decision;
} elseif (gmdate('D', $timenow) == 'Mon' AND intval(gmdate('Hi', $timenow)) >= intval('0930')
	AND intval(gmdate('Hi', $timenow)) < intval('0935') 
	AND filemtime(PATH_FRONTOFFICE . 'thisweek/index.html') > strtotime('-12 hours')) {
	$decision = 'weeklyupdate';
} elseif (gmdate('D', $timenow) == 'Mon' AND intval(gmdate('Hi', $timenow)) >= intval('1630')
	AND intval(gmdate('Hi', $timenow)) < intval('1635')) {
	$decision = 'weeklypopular';
} elseif (intval(gmdate('Hi', $timenow)) >= intval('1230') AND intval(gmdate('Hi', $timenow)) < intval('1235') 
	AND filemtime(PATH_FRONTOFFICE . 'today/index.html') > strtotime('-12 hours')) {
	$decision = 'dailyupdate';
} elseif (intval(gmdate('Hi', $timenow)) >= intval('1600') AND intval(gmdate('Hi', $timenow)) < intval('1605')) {
	$decision = 'dailypopular';
} elseif ($new_messages->num_rows > 0) {
	$decision = 'message';
} else {
	$decision = 'article';
}
log_event('Selected action is ' . $decision);

// build tweet
switch ($decision) {
	case 'weeklyupdate':
	
		$tweettext = 'The ' . SERVICE_NAME . ' weekly update is now available at ' . SERVICE_URL . 'thisweek/';
	
		break;
	case 'weeklypopular':

		// get list of most-popular articles
		$query = 'SELECT `articles`.`journal`, `articles`.`title`, `journals`.`name`, '
			. '`journals`.`abbreviation` FROM `articles`, `journals` WHERE `articles`.`journal` = `journals`.`id` '
			. 'AND `journals`.`active` = 1 AND `articles`.`timestamp` >= \'' 
			. gmdate('Y-m-d 00:00:00', time()-(60*60*24*7)) . '\' AND `timestamp` < \'' 
			. date('Y-m-d H:i:s', strtotime('midnight today UTC')) .'\' ORDER BY `articles`.`clicks` DESC LIMIT 10';
		log_event($query);
		$result = db_query($query);
		if ($result === FALSE) {
			trigger_error(db_error(), E_USER_ERROR);
		}
		if ($result->num_rows < 2) {
			trigger_error('Fewer than 2 entries retrieved from DB, so concept of \'most popular\' article not ' 
			    . 'meaingful', E_USER_ERROR);
		}
		
		$rc = $result->fetch_assoc();

		// check if journal has an abbreviated title
		if (mb_strlen($rc['abbreviation'])>0) {
			$jtitle = $rc['abbreviation'];
		} else {
			$jtitle = $rc['name'];
		}
		
		// form static text for tweet
		$statictext = 'Top @' . SERVICE_NAME . ' article last week: ';

		// calculate how many characters of the title can be presented
		// tweet length - static text - link - ellipsis - length of abbreviated journal name 
		// - 1 because some tweets are coming out over-length (2015-10-20)
		$remaining_chars = 280 - (mb_strlen($statictext) + 7) - 22 - 2 - mb_strlen($jtitle) - 1; 
		log_event($remaining_chars . ' characters available in tweet for article title');

		// abbreviate title if necessary
		log_event('Initial article title is "' . $rc['title'] . '"');
		$finaltitle = trimTitle($rc['title'], $remaining_chars);
		log_event('Trimmed article title is "' . $finaltitle . '"');
	
		$tweettext = $statictext . '“' . $finaltitle . '” in ' . $jtitle . ' ' . SERVICE_URL . 'top/';
	
		break;
	case 'dailyupdate':
	
		$tweettext = 'The ' . SERVICE_NAME . ' daily update is now available at ' . SERVICE_URL . 'today/';
	
		break;
	case 'dailypopular':
	
		// get list of most-popular articles
		$query = 'SELECT `articles`.`journal`, `articles`.`title`, `journals`.`name`, `journals`.`abbreviation`, '
			.'`articles`.`clicks` FROM `articles`, `journals` WHERE `articles`.`journal` = `journals`.`id` AND '
			.'`journals`.`active` = 1 AND `articles`.`excluded` = 0 AND `articles`.`notweet` = 0 AND '
			.'`timestamp` >= \''. date('Y-m-d H:i:s', strtotime('midnight yesterday UTC')) .'\' AND '
			.'`timestamp` < \''. date('Y-m-d H:i:s', strtotime('midnight today UTC')) .'\' ORDER BY `articles`.`clicks` '
			.'DESC LIMIT 10';
		log_event($query);
		$result = db_query($query);
// 		'SELECT `articles`.`journal`, `articles`.`title`, `journals`.`name`, '
// 			. '`journals`.`abbreviation` FROM `articles`, `journals` WHERE `articles`.`journal` = `journals`.`id` '
// 			. 'AND `journals`.`active` = 1 AND `timestamp` >= UNIX_TIMESTAMP(\'' 
// 			. gmdate('Y-m-d 00:00:00', date('U')-(60*60*24)) . '\') ORDER BY `articles`.`clicks` DESC LIMIT 10');
		if ($result === FALSE) {
			trigger_error(db_error(), E_USER_ERROR);
		}
		if ($result->num_rows < 2) {
			trigger_error('Fewer than 2 entries retrieved from DB, so concept of \'most popular\' article not ' 
			    . 'meainngful', E_USER_ERROR);
		}
		
		$rc = $result->fetch_assoc();

		// check if journal has an abbreviated title
		if (mb_strlen($rc['abbreviation'])>0) {
			$jtitle = $rc['abbreviation'];
		} else {
			$jtitle = $rc['name'];
		}
		
		// form static text for tweet
		$statictext = 'Top @' . SERVICE_NAME . ' article yesterday: ';

		// calculate how many characters of the title can be presented
		// tweet length - static text - link - ellipsis - length of abbreviated journal name 
		// - 1 because some tweets are coming out over-length (2015-10-20)
		$remaining_chars = 280 - (mb_strlen($statictext) + 7) - 22 - 2 - mb_strlen($jtitle) - 1; 
		log_event($remaining_chars . ' characters available in tweet for article title');

		// abbreviate title if necessary
		log_event('Initial article title is "' . $rc['title'] . '"');
		$finaltitle = trimTitle($rc['title'], $remaining_chars);
		log_event('Trimmed article title is "' . $finaltitle . '"');
	
		$tweettext = $statictext . '“' . $finaltitle . '” in ' . $jtitle . ' ' . SERVICE_URL . 'top/';
	
		break;
	case 'message':
	
		$message = $new_messages->fetch_assoc();
		$finaltitle = strtok(wordwrap($message['text'], 280 - 30, " …\n"), "\n");
		$tweettext = $finaltitle . ' ' . SERVICE_URL . 'announcements/#message-' . $message['id'];
	
		break;
	case 'article':
	default:

		// get oldest untweeted article
		$result = db_query('SELECT * FROM `journals`, `articles` WHERE `articles`.`tweeted` = 0 '
			. 'AND `articles`.`excluded` = 0 AND `articles`.`notweet` = 0 AND `articles`.`journal`=`journals`.`id`'
			. 'AND `journals`.`active` = 1  ORDER BY `articles`.`timestamp` ASC LIMIT 1');
		if ($result === 'false') {
			trigger_error(db_error() ,E_USER_ERROR);
		}

		// don't send tweet if there are no untweeted articles
		if ($result->num_rows<1) {
			log_event('No untweeted articles to tweet');
			return;
		}

		log_event('Retrieved untweeted article from database');

		$rc = $result->fetch_assoc();

		// check if journal has an abbreviated title
		if (mb_strlen($rc['abbreviation'])>0) {
			$jtitle = $rc['abbreviation'];
		} else {
			$jtitle = $rc['name'];
		}

		// calculate how many characters of the title can be presented
		// tweet length - static text - link - ellipsis - length of abbreviated journal name 
		// - 1 because some tweets are coming out over-length (2015-10-20)
		$remaining_chars = 280 - 7 - 22 - 2 - mb_strlen($jtitle) - 1; 
	
		// abbreviate title if necessary
		$finaltitle = trimTitle($rc['title'], $remaining_chars);
	
		$tweettext = '“' . $finaltitle . '” in ' . $jtitle . ' ' . REDIRECT_URL_BASE . base_convert($rc['id'],10,36);

		break;
}

// connect to Twitter
require_once(PATH_TO_OAUTHDAMNIT);
$twitter = new OAuthDamnit(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_ACCESS_TOKEN, 
    TWITTER_ACCESS_TOKEN_SECRET);
if (!$twitter) trigger_error('Could not create OAuth object',E_USER_ERROR);
log_event('Connected to Twitter API');

// send tweet
print $tweettext;
log_event($tweettext);

$response = $twitter->post('https://api.twitter.com/1.1/statuses/update.json',array('status'=>$tweettext));
$tweets = json_decode($response);
if (is_array($tweets->{'errors'})) {

	foreach ($tweets->{'errors'} as $flag => $error) $tw_req_errors[] = $error->{'code'}.': '.$error->{'message'};
	trigger_error('the Twitter API generated one or more errors:'. PHP_EOL 
		. "\t".implode(PHP_EOL . "\t",$tw_req_errors), E_USER_ERROR);

} else {

	switch ($decision) {
	
		case 'message':
			$updateresult = db_query('UPDATE `messages` SET `twitter` = 2 WHERE `id` = ' . $message['id']);
			if ($updateresult === 'false') {
				trigger_error(db_error() ,E_USER_ERROR);
			}
			log_event('Tweeted message. Tweet text was << ' . $tweettext . ' >>');
			break;
		case 'weeklyupdate':
		case 'weeklypopular':
		case 'dailyupdate':
		case 'dailypopular':
			break;
		case 'article':
		default:
			$updateresult = db_query('UPDATE `articles` SET `tweeted` = 1 WHERE `id` = ' . $rc['id']);
			if ($updateresult === 'false') {
				trigger_error(db_error() ,E_USER_ERROR);
			}
			log_event('Tweeted article. Tweet text was << ' . $tweettext . ' >>');
			break;
	
	}

}

?>