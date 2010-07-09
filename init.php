<?php

if( isset($_GET['src']) ) highlight_file($_SERVER['SCRIPT_FILENAME']) and die();

require_once 'phpQuery.php';
require_once 'functions.php';
require_once 'config.php';

$valid_regions = array('us', 'de', 'uk', 'ru', 'es', 'fr');

global $region, $region_sql;
if( isset($_GET['region']) && in_array($_GET['region'], $valid_regions) ) {
	$region = $_GET['region'];
} else {
	$region = 'us';
}
$region_sql = "`region` = '$region'";

global $base;

$base_urls = array(
	'us' => "http://forums.worldofwarcraft.com/thread.html?topicId=25712374700&sid=1&pageNo=%d",
	'de' => "http://forums.wow-europe.com/thread.html?topicId=13816898570&sid=3&pageNo=%d",
	'uk' => "http://forums.wow-europe.com/thread.html?topicId=13816838128&sid=1&pageNo=%d",
	'ru' => "http://forums.wow-europe.com/thread.html?topicId=13816838131&sid=5&pageNo=%d",
	'es' => "http://forums.wow-europe.com/thread.html?topicId=13816838130&sid=4&pageNo=%d",
	'fr' => "http://forums.wow-europe.com/thread.html?topicId=13816838129&sid=2&pageNo=%d"
);

$base = $base_urls[$region];

define('BETWEEN_PAGE_TIMEOUT', 7); // in seconds

global $db;
$db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

global $mc;
$mc = new Memcache;
$mc->addServer('localhost', 11211);
