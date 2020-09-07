<?php

/**

This script gets RSS feeds from journal websites, checks to see if each item in each feed is already present in the
database and adds it to the database if not.

All configurable variables are in the file config.php

**/

// define name of file for logging purposes
define("THIS_FILE_NAME", "RSS");

// require initialisation script
require_once('../../../ju-backoffice/initialise.php');

// die if this type of output is not enabled
if (FALSE === OUTPUT_RSS) {
	header("HTTP/1.0 404 Not Found");
	trigger_error('Terminating script because this type of output is not enabled');
}

// send header so servers can identify RSS
header('Content-type: application/rss+xml;charset=UTF-8');

print('<?xml version="1.0" ?>');

// get number of journals covered by the service
$numjournals = db_query('SELECT * FROM `journals` WHERE `active` = 1');
if ($numjournals === 'false') {
    trigger_error(db_error() ,E_USER_ERROR);
}

// get list of articles
$result = db_query('SELECT * FROM `journals`, `articles` WHERE `articles`.`excluded` = 0 AND `journals`.`active` = 1 '
	. 'AND `articles`.`journal` = `journals`.`id` ORDER BY `articles`.`timestamp` DESC LIMIT 100');
if ($result === FALSE) {
    trigger_error(db_error() ,E_USER_ERROR);
} elseif ($result->num_rows<1) {
    trigger_error('Query for retrieving articles processed successfuly by database, but no articles returned' ,
        E_USER_ERROR);
}

?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
	<title>Latest articles from <?= SERVICE_NAME ?></title>
	<link><?= SERVICE_URL ?></link>
	<description>
		<?= SERVICE_DESCRIPTION ?>. 
		This service checks for new articles on the websites of <?= $numjournals->num_rows ?> journals. 
		<?= SERVICE_DISCLAIMER ?>.
	</description>
	<pubDate><?=gmdate('r')?></pubDate>
	<ttl><?= RSS_CACHE_MINS ?></ttl>
	<docs>http://blogs.law.harvard.edu/tech/rss</docs>
	<generator><?= SERVICE_NAME ?> (Journal Updates)</generator>
	<language>en</language>
	<lastBuildDate><?= gmdate('r') ?></lastBuildDate>
	<atom:link href="<?= SERVICE_URL ?>rss/" rel="self" type="application/rss+xml" />
<?php

while ($item = $result->fetch_assoc()) {

?>
	<item>
		<title><?=html_entity_decode($item['title'], ENT_QUOTES | ENT_HTML5, "UTF-8")?></title>
		<link><?= REDIRECT_URL_BASE . base_convert($item['id'],10,36)?></link>
		<description><![CDATA[
			<p>“<a href="<?= REDIRECT_URL_BASE . base_convert($item['id'],10,36)?>"><?= 
			html_entity_decode($item['title'], ENT_QUOTES | ENT_HTML5, "UTF-8")?></a>”<?php

	if ($item['author']!='') {
		print ' – by ' . $item['author'];
	}

?>.</p>
<?php

	if (strtotime($item['date']) < strtotime('2000-01-01')) {

?>
			<p>Published in <a href="<?=str_replace('&', '&amp;', $item['homepage'])?>"><?=$item['name']?></a>.</p>
			<p><small><strong>Note</strong>: the publisher did not provide a publication date for this article.
			It was added to the journal RSS feed on <?=gmdate('j F Y',strtotime($item['timestamp']))?>, which is why
			you are receiving this alert now. The article may be a new article or it may be older.</small></p>
<?php

	} else {

		?>
			<p>Published in <a href="<?=str_replace('&', '&amp;', $item['homepage'])?>"><?=$item['name']?></a> on 
			<?=gmdate('l j F Y',strtotime($item['date']))?>.</p><?php

		if (strtotime($item['date'])<(time() - (60 * 60 * 24 * OLD_WARNING_DAYS))) {

?>
			<p><small><strong>Note</strong>: although this article was published on 
			<?=gmdate('j F Y',strtotime($item['date']))?>, the publisher only added it to the journal RSS feed on 
			<?=gmdate('j F Y',strtotime($item['timestamp']))?>, which is why you are receiving this alert now.
			</small></p><?php

		}

	}

?>

		]]></description>
		<source url="<?=str_replace('&', '&amp;', $item['url'])?>"><?=$item['name']?></source>
		<pubDate><?=gmdate('r',strtotime($item['timestamp']))?></pubDate>
		<guid><?= REDIRECT_URL_BASE . base_convert($item['id'],10,36)?></guid>
	</item> 
<?php

}

?>
</channel>
</rss>