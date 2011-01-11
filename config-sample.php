<?php
/* Full-Text RSS options */

// Enable service
// ----------------------
// Set this to false if you want to disable the service.
// If set to false, no feed is produced and users will 
// be told that the service is disabled.
$options->enabled = true;

// Restrict service
// ----------------------
// Set this to true if you'd like certain features
// to be available only to key holders.
// Affected features:
// * Link handling (disabled for non-key holders if set to true)
// * Cache time (20 minutes for non-key holders if set to true)
$options->restrict = false;

// Default entries (without API key)
// ----------------------
// The number of feed items to process when no API key is supplied.
$options->default_entries = 5;

// Max entries (without API key)
// ----------------------
// The maximum number of feed items to process when no API key is supplied.
$options->max_entries = 10;

// Rewrite relative URLs
// ----------------------
// With this enabled relative URLs found in the extracted content
// block are automatically rewritten as absolute URLs.
// Set to false if you want to preserve relative URLs appearing in 
// the extracted content block.
$options->rewrite_relative_urls = true;

// Enable caching
// ----------------------
// Enable this if you'd like to cache results
// for 10 minutes. Initially it's best
// to keep this disabled to make sure everything works
// as expected.
$options->caching = false;

// Cache directory
// ----------------------
// Only used if caching is true
$options->cache_dir = dirname(__FILE__).'/cache';

// Message to prepend (without API key)
// ----------------------
// HTML to insert at the beginning of each feed item when no API key is supplied.
$options->message_to_prepend = '';

// Message to append (without API key)
// ----------------------
// HTML to insert at the end of each feed item when no API key is supplied.
$options->message_to_append = '';

// URLs to block
// ----------------------
// List of URLs (or parts of a URL) which the service should not accept
$options->blocked_urls = array();

// Error message when content extraction fails (without API key)
// ----------------------
$options->error_message = '[unable to retrieve full-text content]';

/////////////////////////////////////////////////
/// ADVANCED OPTIONS ////////////////////////////
/////////////////////////////////////////////////

// API keys
// ----------------------
// NOTE: You do not need an API key from fivefilters.org to run your own 
// copy of the code. This is here if you'd like to offer others an API key 
// to access _your_ copy.
// Keys let you group users - those with a key and those without - and
// restrict access to the service to those without a key.
// If you want everyone to access the service in the same way, you can
// leave the array below empty and ignore the API key options further down.
// The options further down in this file will allow you to specify
// how the service should behave in each mode.
$options->api_keys = array();

// Default entries (with API key)
// ----------------------
// The number of feed items to process when a valid API key is supplied.
$options->default_entries_with_key = 5;

// Max entries (with API key)
// ----------------------
// The maximum number of feed items to process when a valid API key is supplied.
$options->max_entries_with_key = 10;

// Message to prepend (with API key)
// ----------------------
// HTML to insert at the beginning of each feed item when a valid API key is supplied.
$options->message_to_prepend_with_key = '';

// Message to append (with API key)
// ----------------------
// HTML to insert at the end of each feed item when a valid API key is supplied.
$options->message_to_append_with_key = '';

// Error message when content extraction fails (with API key)
// ----------------------
$options->error_message_with_key = '[unable to retrieve full-text content]';

// Alternative Full-Text RSS service URL
// ----------------------
// This option is to offer very simple load distribution for the service.
// If you've set up another instance of the Full-Text RSS service on a different
// server, you can enter its full URL here. 
// E.g. 'http://my-other-server.org/full-text-rss/makefulltextfeed.php'
// If you specify a URL here, 50% of the requests to makefulltextfeed.php on
// this server will be redirected to the URL specified here.
$options->alternative_url = '';

// Cache directory level
// ----------------------
// Spread cache files over different directories (only used if caching is enabled).
// Used to prevent large number of files in one directory.
// This corresponds to Zend_Cache's hashed_directory_level
// see http://framework.zend.com/manual/en/zend.cache.backends.html
// It's best not to change this if you're unsure.
$options->cache_directory_level = 0;

?>