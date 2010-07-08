<?php

require_once 'init.php';

function unique_posters() {
	static $unique_posters = null;

	if( $unique_posters === null ) {
		global $db;
		$sth = $db->query("SELECT `character`, `realm` FROM `realid_posts` WHERE `deleted` = 0 GROUP BY `character`, `realm`");
		$unique_posters = $sth->rowCount();
	}

	return $unique_posters;
}

function repeat_posters() {
	static $repeat_posters = null;

	if( $repeat_posters === null ) {
		global $db;
		$sth = $db->query("SELECT `character`, `realm` FROM `realid_posts` WHERE `deleted` = 0 GROUP BY `character`, `realm` HAVING COUNT(*) > 1");
		$repeat_posters = $sth->rowCount();
	}

	return $repeat_posters;
}

function highest_post_number() {
	static $highest_post_number = null;

	if( $highest_post_number === null ) {
		global $db;
		$sth = $db->query("SELECT MAX(`id`) `highest` FROM `realid_posts`");
		$row = $sth->fetch(PDO::FETCH_OBJ);
		$highest_post_number = $row->highest;
	}

	return $highest_post_number;
}

function post_count() {
	static $post_count = null;

	if( $post_count === null ) {
		global $db;
		$sth = $db->query("SELECT COUNT(*) `count` FROM `realid_posts` WHERE `deleted` = 0");
		$row = $sth->fetch(PDO::FETCH_OBJ);
		$post_count = $row->count;
	}

	return $post_count;
}

function deleted_posts() {
	return highest_post_number() - post_count();
}

function deleted_posts_percent() {
	return sprintf("%.2f", deleted_posts() / highest_post_number() * 100);
}

function posts_per_user() {
	return post_count() / unique_posters();
}

function repeat_posts_per_user() {
	return post_count() / repeat_posters();
}

function posters_by_post_count( $count = 5 ) {
	global $db;
	
	$count = (int)$count;

	if( $count ) {
		$count = "LIMIT $count";
	} else {
		$count = '';
	}

	$sth = $db->query("SELECT `character`, `realm`, COUNT(*) `count` FROM realid_posts WHERE `deleted` = 0 GROUP BY `character`, `realm` ORDER BY COUNT(*) DESC $count", PDO::FETCH_OBJ);

	return $sth->fetchAll();
}

function all_posters_by_count() {
	global $db;

	$sth = $db->query("SELECT `character`, `realm`, COUNT(*) `count` FROM realid_posts WHERE `deleted` = 0 GROUP BY `character`, `realm` ORDER BY `character`, `realm`", PDO::FETCH_OBJ);
	return $sth->fetchAll();
}

function median_posts() {
	$rows = all_posters_by_count();
	$count = count($rows);

	$midpoint = floor( ($count - 1) / 2 );

	if( $count % 2 == 0 ) {
		$v1 = $rows[$midpoint]->count;
		$v2 = $rows[$midpoint+1]->count;
		return (($v1 + $v2) / 2);
	} else {
		return $rows[$midpoint]->count;
	}
}

function pages_by_count( $count = 5 ) {
	global $db;

	$count = (int)$count;

	if( $count ) {
		$count = "LIMIT $count";
	} else {
		$count = '';
	}

	$sth = $db->query("SELECT page, COUNT(*) `count` FROM realid_posts GROUP BY page ORDER BY COUNT(*) $count", PDO::FETCH_OBJ);

	return $sth->fetchAll();
}

function mean_posts_over_time( $sample_every = 10 ) {
	global $db, $mc;

	$walking_mean = $mc->get( 'realid:mpot' );

	if( $walking_mean ) {
		return $walking_mean;
	}

	// try to reserve the spot in memcached for maximum 30 seconds
	if( ! $mc->add( 'realid:mpot', 'generating', 30 ) ) {
		return $mc->get( 'realid:mpot' );
	}

	ignore_user_abort( true );

	$sth = $db->query("SELECT * FROM realid_posts WHERE `deleted` = 0 ORDER BY id", PDO::FETCH_OBJ);

	$posts = array();
	$walking_mean = array();

	$i = 0;
	foreach( $sth as $post ) {
		$key = $post->character . '-' . $post->realm;

		if( isset($posts[$key]) ) {
			$posts[$key] += 1;
		} else {
			$posts[$key] = 1;
		}

		$mean = array_sum($posts) / count($posts);
		
		if( $i % $sample_every == 0) {
			$walking_mean[] = array((int)$post->id, $mean);
		}

		$i++;
	}

	$mc->set( 'realid:mpot', $walking_mean, false, 300 );

	return $walking_mean;
}

function rescan_progress() {
	global $db;

	$sth = $db->query("SELECT COUNT(*) `count` FROM `realid_posts` WHERE rescanned = 0 GROUP BY page", PDO::FETCH_OBJ);
	$count = $sth->rowCount();

	return $count;
}
