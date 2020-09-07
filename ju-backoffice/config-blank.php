<?php

/**

This file is a template for the configuration variables. Some variables are given default values, while others are left
blank for the user to include their own values.

This file should be in the backoffice folder.

**/

// paths
define("PATH_BASE", "../");
define("PATH_BACKOFFICE", PATH_BASE . "ju-backoffice/");
define("PATH_FRONTOFFICE", PATH_BASE . "ju-frontoffice/");
define("PATH_REDIRECT", PATH_BASE . "ju-redirect/");
define("PATH_LOG", PATH_BACKOFFICE . 'log/');

// paths to external libraries
define("PATH_TO_SIMPLEPIE", PATH_BACKOFFICE . 'lib/simplepie.php');
define("PATH_TO_OAUTHDAMNIT", PATH_BACKOFFICE . 'lib/oauthdamnit.php');

// DB
define("DB_HOST", "");
define("DB_USER", "");
define("DB_PASSWORD", "");
define("DB_DBNAME", "");

// which output methods should be enabled?
define("OUTPUT_DAILY_EMAIL",  TRUE);  // daily email alert
define("OUTPUT_WEEKLY_EMAIL", TRUE);  // weekly email alert
define("OUTPUT_RSS",          TRUE);  // RSS feed
define("OUTPUT_TWITTER",      TRUE);  // Twitter feed
define("OUTPUT_FACEBOOK",     FALSE); // Facebook feed – not yet implemented

// Twitter
define("TWITTER_HANDLE", ""); // Twitter @ handle for the service's Twitter feed
define("TWITTER_CONSUMER_KEY", "");
define("TWITTER_CONSUMER_SECRET", "");
define("TWITTER_ACCESS_TOKEN", "");
define("TWITTER_ACCESS_TOKEN_SECRET", "");

// mailing list
define("SMTP_HOST", ); // host for sending email via SMTP
define("SMTP_PORT", ); // port for sending email via SMTP
define("SMTP_USER", ""); // user name for SMTP account
define("SMTP_PASSWORD", ""); // password for SMTP account
define("MAILING_LIST_DAILY", ""); // email address for daily mailing list
define("MAILING_LIST_WEEKLY", ""); // email address for weekly mailing list
define("FROM_ADDRESS", ""); // email address that email alerts will appear to be from

// RSS output
define("RSS_CACHE_MINS", 60); // number of minutes for which output RSS feed can be cached by third-party feed readers
define("OLD_WARNING_DAYS", 7); // maximum number of days allowed between date on which article is published and date on
                               // which it was added to RSS feed – after this number of days, a warning will be added to 
                               // the feed item
define("RSS_MAX_ARTICLES", 100); // maximum number of articles to include in RSS feed

// service admin
define("SERVICE_NAME", "Journal Updates"); // public-facing name of the service
define("SERVICE_URL", ""); // public-facing URL for the service
define("SERVICE_CSS", SERVICE_URL . "ju_style.css"); // CSS file for public-facing HTML pages. No special CSS classes,
    // etc. are needed, so this can just be replaced with the main CSS file for your website
define("REDIRECT_URL_BASE", ""); // base URL for the redirect script
define("SERVICE_DESCRIPTION", ""); // public-facing description for the service
define("SERVICE_DISCLAIMER", "Information is taken directly from the journal websites and no liability is accepted for "
    . "any inaccuracies or delays in publishing information"); // public-facing disclaimer text
define("ADMIN_ERROR_EMAIL", ""); // email address to send error reports to
define("MAX_ERRORS", 3); // maximum number of SimplePie errors before the script terminates
define("MAX_STRIKES", 3); // maximum number of strikes before a journal goes into the sin bin
define("SINBIN_HOURS", 24); // number of hours that a journal goes into the sin bin for
define("TOP_CHART_NUM", 10); // number of articles to include in the top-article charts
define("TOP_CHART_DAYS", 7); // number of days to include in the recent top-article chart
define("SERVICE_TIMEZONE", 'Europe/London'); // the timezone that the service is operating in

?>