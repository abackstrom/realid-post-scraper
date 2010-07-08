<?php

if( isset($_GET['src']) ) highlight_file($_SERVER['SCRIPT_FILENAME']) and die();

require_once 'phpQuery.php';
require_once 'functions.php';
require_once 'config.php';

$base = "http://forums.worldofwarcraft.com/thread.html?topicId=25712374700&sid=1&pageNo=%d";
$page_no = 1831;

define('BETWEEN_PAGE_TIMEOUT', 7); // in seconds

global $db;
$db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

global $mc;
$mc = new Memcache;
$mc->addServer('localhost', 11211);
