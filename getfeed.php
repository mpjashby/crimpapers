<?php

/**

This script gets RSS feeds from journal websites, checks to see if each item in each feed is already present in the
database and adds it to the database if not.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
if (!defined('THIS_FILE_NAME')) define("THIS_FILE_NAME", "getfeed");

// require initialisation script
require_once('initialise.php');

// get feed URLs
$feeds = db_query('SELECT `id`, `name`, `url`, `forcefeed`, `updated`, `strikes`, `sinbin` from `journals` WHERE '
	.'`active` = 1 ORDER BY `name` ASC');
if ($feeds === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
}
log_event('Retrieved ' . $feeds->num_rows . ' feeds from database');

// initialise counter for SimplePie errors
$error_count = 0;

// get feed contents, check against DB and add if not present
while ($feed_dets = $feeds->fetch_assoc()) {

	// send warning email if a feed has not been successfully updated for greater than the threshold number of hours
	// NO_FEED_UPDATE_WARNING
// 	if ((time() - strtotime($feed_dets['updated']))/(60*60) > NO_FEED_UPDATE_WARNING) {
// 		trigger_error('The feed for \'' . $feed_dets['name'] . '\' has not been updated for more than' . 
// 			NO_FEED_UPDATE_WARNING . ' hours', E_USER_WARNING);
// 	}

	// if necessary, take journal out of the sin-bin
	if (strtotime($feed_dets['sinbin']) > time()) {
		log_event($feed_dets['name'] . ' is sin-binned until ' . $feed_dets['sinbin']);
		continue;
	} elseif ($feed_dets['strikes'] > 0 AND strtotime($feed_dets['sinbin']) < time()) {
		$outofbin = db_query('UPDATE `journals` SET `strikes` = 0 WHERE `id` = ' . $feed_dets['id']);
		if ($outofbin === FALSE) {
			trigger_error(db_error() ,E_USER_ERROR);
		}
		log_event('Took ' . $feed_dets['name'] . ' out of sin bin');
	}

	// get feed
	if (FALSE === $feed_raw = file_get_contents($feed_dets['url'])) {
	
	    $error = "error in retrieving URL from " . $feed_dets['url'];
	    
		// increase strikes (or put into bin if stikes has reached MAX_STRIKES)
		if ($feed_dets['strikes'] == MAX_STRIKES - 1) {
			$err_query = 'UPDATE `journals` SET `sinbin` = \'' . date('Y-m-d H:i:s', time() + (SINBIN_HOURS * 60 * 60)) 
			. '\', `reason` = \'' . db_quote($error) . '\' WHERE `id` = ' . $feed_dets['id'];
			log_event('Put ' . $feed_dets['name'] . ' into sin bin for ' . SINBIN_HOURS. ' hours. Error: ' . $error);
		} else {
			$err_query = 'UPDATE `journals` SET `strikes` = ' . ($feed_dets['strikes'] + 1) . ' WHERE `id` = ' 
			  . $feed_dets['id'];
			log_event('Increased number of strikes for ' . $feed_dets['name'] . ' to ' . ($feed_dets['strikes'] + 1));
		}
		$intobin = db_query($err_query);
		if ($intobin === FALSE) {
			trigger_error(db_error() ,E_USER_ERROR);
		}

		// kill script when MAX_ERRORS number of errors is reached, otherwise issue a warning
		if (!strstr($error,'cURL error 28')) {
			trigger_error($error . PHP_EOL . PHP_EOL . '. The journal that generated the error was ' 
			. $feed_dets['name'], ($error_count>= MAX_ERRORS ? E_USER_ERROR : E_USER_WARNING));
			$error_count++;
		} else {
			log_event('cURL timeout for ' . $feed_dets['name'] . '. ' . PHP_EOL . PHP_EOL . $error);
		}

		// if there has been an error processing this feed, move on to next feed
 		continue;

	}
	
	// replace unknown root element in some Wiley RSS feeds
	$feed_raw = str_replace(
	    array('<RDF ', '</RDF>'), 
	    array('<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" ', '</rdf:RDF>'), 
	    $feed_raw
	);
	
	// read feed
	$feed = new SimplePie();
// 	$feed->set_feed_url($feed_dets['url']);
    $feed->set_raw_data($feed_raw);
// 	$feed->enable_cache(false);
// 	if ($feed_dets['forcefeed'] == 1) $feed->force_feed(true);
	$feed->init();

	// check for errors
 	if ($error = $feed->error()) {
 	
 	    // save data 
 	    log_event("Saving copy of " . $feed->get_type() . " file from " . $feed_dets['url']);
 	    file_put_contents(PATH_BACKOFFICE . "cache/rss_feed_journal_" . $feed_dets['id'] . "_" . gmdate("c") . ".txt", $feed_raw);

		// increase strikes (or put into bin if stikes has reached MAX_STRIKES)
		if ($feed_dets['strikes'] == MAX_STRIKES - 1) {
			$err_query = 'UPDATE `journals` SET `sinbin` = \'' . date('Y-m-d H:i:s', time() + (SINBIN_HOURS * 60 * 60)) 
			. '\', `reason` = \'' . db_quote($error) . '\' WHERE `id` = ' . $feed_dets['id'];
			log_event('Put ' . $feed_dets['name'] . ' into sin bin for ' . SINBIN_HOURS. ' hours. Error: ' . $error);
		} else {
			$err_query = 'UPDATE `journals` SET `strikes` = ' . ($feed_dets['strikes'] + 1) . ' WHERE `id` = ' 
			  . $feed_dets['id'];
			log_event('Increased number of strikes for ' . $feed_dets['name'] . ' to ' . ($feed_dets['strikes'] + 1));
		}
		$intobin = db_query($err_query);
		if ($intobin === FALSE) {
			trigger_error(db_error() ,E_USER_ERROR);
		}

		// kill script when MAX_ERRORS number of errors is reached, otherwise issue a warning
		if (!strstr($error,'cURL error 28')) {
			trigger_error($error . PHP_EOL . PHP_EOL . '. The journal that generated the error was ' 
			. $feed_dets['name'], ($error_count>= MAX_ERRORS ? E_USER_ERROR : E_USER_WARNING));
			$error_count++;
		} else {
			log_event('cURL timeout for ' . $feed_dets['name'] . '. ' . PHP_EOL . PHP_EOL . $error);
		}

		// if there has been an error processing this feed, move on to next feed
 		continue;

 	}
 	
 	// check if this is the first time that this feed has been updated
 	// if it is, all the articles processed on this run will have the `excluded` flag in the DB marked to TRUE, to
 	// prevent them from being tweeted etc. even if they are very old
 	$excludeall = (strtotime($feed_dets['updated']) < mktime(0,0,0,1,1,1980) ? TRUE : FALSE);
 	
 	// assuming no errors (in which case the loop would have skipped already), load entries into database
 	$entries = $feed->get_item_quantity();
 	$entries_added = 0;
 	for ($j = 0; $j < $entries; $j++) {
 	
 		// get item
 		$item = $feed->get_item($j);

		// skip to next item if this one isn't valid
		if (!$item) {
			continue;
		}
 		
		// extract fields from item
		if (!$this_title = titleCase(trim($item->get_title()))) $this_title = '';
		if (!$this_desc = trim($item->get_description())) $this_desc = '';
		if (!$this_link = trim($item->get_permalink())) $this_link = '';
		if (!$this_date = $item->get_date('Y-m-d H:i:s')) $this_date = '';
		
		// mark article as excluded if it is over a certain age
// 		if ($excludedarticles == 0) {
		  if ($excludeall == TRUE OR $this_date == '' OR strtotime($this_date) < time() - (60*60*24*90)) {
		    $excludearticles = 1;
		  } else {
		    $excludearticles = 0;
		  }
// 			$excludedarticles = (strtotime($this_date) < time() - (60*60*24*90) ? 1 : 0);
// 		}
	
		// extract authors from item
		$author_objs = $item->get_authors();
		if (count($author_objs)>0) {
			$authors = array();
			foreach ($author_objs as $author) {
				$authors[] = titleCase($author->get_name());
			}
			$this_author = trim(implode(', ',$authors));
		} else {
			$this_author = '';
		}
		
		// remove asterisk from end of article name
		// Criminology (and possibly some other Wiley journals) always/often have an asterisk at the end of the title, 
		// which on their website refers users to the corresponding author's address, but which makes no sense in 
		// journal alerts (in which the full journal web page isn't shown)
		if (substr(trim($this_title),-1)=='*') {
			$this_title = substr(trim($this_title),0,-1);
		}
		
		// decide, based on the title, if the article should not be tweeted because it is likely to consist solely of
		// journal information
		$notweet_titles = array('issue information', 'table of contents', 'editorial board');
		$notweet = (in_array(trim(strtolower($this_title)), $notweet_titles) ? 1 : 0);
	
		// check if entry already exists in DB
		$existingentry = db_query('SELECT * FROM `articles` WHERE `link` = "' . db_quote($this_link) 
			. '" OR (`title` = "' . db_quote($this_title) . '" AND `journal` = ' . $feed_dets['id'] . ')');
		if ($existingentry === FALSE) {
			trigger_error(db_error() ,E_USER_ERROR);
		}

		// if entry doesn't already exist, insert it into the DB
		if ($existingentry->num_rows<1) {
			$insertentry = db_query('INSERT INTO `articles` SET `journal`="' . $feed_dets['id']
			     . '", `title`="' . db_quote($this_title) 
			     . '", `description`="' . db_quote($this_desc) 
			     . '", `link`="' . db_quote($this_link) 
			     . '", `date`="' . db_quote($this_date) 
			     . '", `author`="' . db_quote($this_author)
			     . '", `excluded`=' . $excludearticles
			     . ',  `notweet`=' . $notweet
				);
			if ($insertentry === FALSE) {
				trigger_error(db_error(), E_USER_ERROR);
			}
			$entries_added++;
		}

 	}
 	
 	if ($entries_added > 0) {
	 	log_event('Added ' . $entries_added . ' articles to database from ' . $feed_dets['name']);
	 	if ($entries_added > 20) {
	 		trigger_error('Added ' . $entries_added . ' articles to database from ' . $feed_dets['name'] 
	 			. '. The large number of articles suggests some may be duplicates or old articles.', E_USER_WARNING);
	 	}
	} else {
	 	log_event('No new articles from ' . $feed_dets['name']);
	}

	// record that feed for this journal has been successfully processed
	$updateresult = db_query('UPDATE journals SET `updated` = NOW() WHERE id = ' . $feed_dets['id']);
	if ($updateresult === false) {
		trigger_error(db_error(), E_USER_ERROR);
	}

}

?>