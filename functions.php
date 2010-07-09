<?php

require_once 'init.php';

class cache {
	function get( $key ) {
		global $mc;

		if( isset($this->$key) ) {
			return $this->$key;
		}

		return $this->$key = $mc->get($key);
	}

	function set( $key, $value, $ttl = 60 ) {
		global $mc;

		if( $mc->set( $key, $value, false, $ttl ) ) {
			$this->$key = $value;
			return true;
		}

		return false;
	}

	function add( $key, $value, $ttl = 60 ) {
		global $mc;

		if( $mc->add( $key, $value, false, $ttl ) ) {
			$this->$key = $value;
			return true;
		}

		return false;
	}

	function key() {
		global $region;

		$append = implode(":", func_get_args());

		return "realid:$region:$append";
	}

	function delete($key) {
		global $mc;
		return $mc->delete($key);
	}
}

function unique_posters() {
	global $cache, $region, $region_sql;

	$key = "realid:$region:unique_posters";

	if( ! $cache->get($key) ) {
		global $db;
		$sth = $db->query("SELECT `character`, `realm` FROM `realid_posts` WHERE `deleted` = 0 AND $region_sql GROUP BY `character`, `realm`");
		$cache->add( $key, $sth->rowCount() );
	}

	return $cache->get($key);
}

function repeat_posters() {
	global $cache, $region, $region_sql;

	$key = $cache->key('repeat_posters');

	if( ! $cache->get($key) ) {
		global $db;
		$sth = $db->query("SELECT `character`, `realm` FROM `realid_posts` WHERE `deleted` = 0 AND $region_sql GROUP BY `character`, `realm` HAVING COUNT(*) > 1");
		$cache->add( $key, $sth->rowCount() );
	}

	return $cache->get($key);
}

function highest_post_number() {
	global $cache, $db, $region_sql;

	$key = $cache->key('highestpost');

	if( ! $cache->get($key) ) {
		$sth = $db->query("SELECT MAX(`id`) `highest` FROM `realid_posts` WHERE $region_sql");
		$row = $sth->fetch(PDO::FETCH_OBJ);
		$cache->add( $key, $row->highest );
	}

	return $cache->get($key);
}

function post_count() {
	global $region_sql;

	static $post_count = null;

	if( $post_count === null ) {
		global $db;
		$sth = $db->query("SELECT COUNT(*) `count` FROM `realid_posts` WHERE `deleted` = 0 AND $region_sql");
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

function count2sql( $count = 5 ) {
	$count = (int)$count;

	if( $count ) {
		$count = "LIMIT $count";
	} else {
		$count = '';
	}

	return $count;
}

function posters_by_post_count( $count = 5 ) {
	global $cache, $db, $region_sql;
	
	$key = $cache->key('posters_by_post_count', $count);
	$count = count2sql($count);

	if( ! $cache->get($key) ) {
		$sth = $db->query("SELECT `character`, `realm`, COUNT(*) `count` FROM realid_posts WHERE `deleted` = 0 AND $region_sql GROUP BY `character`, `realm` ORDER BY COUNT(*) DESC $count", PDO::FETCH_OBJ);
		$cache->set( $key, $sth->fetchAll() );
	}

	return $cache->get($key);
}

function all_posters_by_count() {
	global $cache, $db, $region_sql;

	$key = $cache->key('postersbycount');

	if( ! $cache->get($key) ) {
		$sth = $db->query("SELECT `character`, `realm`, COUNT(*) `count` FROM realid_posts WHERE `deleted` = 0 AND $region_sql GROUP BY `character`, `realm` ORDER BY `character`, `realm`", PDO::FETCH_OBJ);
		$cache->set($key, $sth->fetchAll());
	}

	return $cache->get($key);
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
	global $cache, $db, $region_sql;

	$key = $cache->key('pagesbycount', $count);
	$count = count2sql($count);

	if( ! $cache->get($key) ) {
		$sth = $db->query("SELECT `page`, COUNT(*) `count` FROM realid_posts WHERE `deleted` = 0 AND $region_sql GROUP BY page ORDER BY COUNT(*) $count", PDO::FETCH_OBJ);
		$cache->set($key, $sth->fetchAll());
	}

	return $cache->get($key);
}

function mean_posts_over_time( $sample_every = 10 ) {
	global $db, $mc, $region, $region_sql;

	$sample_every = floor($sample_every);

	$walking_mean = $mc->get( "realid:$region:mpot:$sample_every" );

	if( $walking_mean ) {
		return $walking_mean;
	}

	// try to reserve the spot in memcached for maximum 30 seconds
	if( ! $mc->add( "realid:$region:mpot:$sample_every", 'generating', 30 ) ) {
		return $mc->get( "realid:$region:mpot:$sample_every" );
	}

	ignore_user_abort( true );

	$sth = $db->query("SELECT * FROM realid_posts WHERE `deleted` = 0 AND $region_sql ORDER BY id", PDO::FETCH_OBJ);

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

	$mc->set( "realid:$region:mpot:$sample_every", $walking_mean, false, 300 );

	return $walking_mean;
}

function new_posts_vs_new_characters( $sample_every = 10 ) {
	global $db, $mc, $region, $region_sql;

	$sample_every = floor($sample_every);

	$npnc = $mc->get( "realid:$region:npnc:$sample_every" );

	if( $npnc ) {
		return $npnc;
	}

	// try to reserve the spot in memcached for maximum 30 seconds
	if( ! $mc->add( "realid:$region:npnc:$sample_every", array('generating', 'generating'), 30 ) ) {
		return $mc->get( "realid:$region:npnc:$sample_every" );
	}

	ignore_user_abort( true );

	$sth = $db->query("SELECT * FROM realid_posts WHERE `deleted` = 0 AND $region_sql ORDER BY id", PDO::FETCH_OBJ);

	$seen_characters = array();

	$np = array();
	$nc = array();

	$i = 0;
	foreach( $sth as $post ) {
		$key = $post->character . '-' . $post->realm;

		$seen_characters[$key] = 1;

		$post->id = (int)$post->id;

		if( $i % $sample_every == 0) {
			$np[] = array($post->id, $post->id);
			$nc[] = array($post->id, count($seen_characters));
		}

		$i += 1;
	}

	$mc->set( "realid:$region:npnc:$sample_every", array($np, $nc), false, 300 );

	return array($np, $nc);
}

function best_represented_guilds_by_posts( $count = 5 ) {
	global $cache, $db, $region_sql;

	$key = $cache->key('guildbyposts', $count);
	$count = count2sql($count);

	if( ! $cache->get($key) ) {
		$sth = $db->query("
			SELECT `guild`, `realm`, COUNT(*) `count`, COUNT(DISTINCT `character`) `character_count`
			FROM `realid_posts` `t1`
			WHERE `guild` != '' AND `realm` != '' AND $region_sql
			GROUP BY `guild`, `realm`, `region`
			ORDER BY COUNT(*) DESC, COUNT(DISTINCT `character`) DESC $count
		", PDO::FETCH_OBJ);
		$cache->add($key, $sth->fetchAll());
	}

	return $cache->get($key);
}

function best_represented_guilds_by_characters( $count = 5 ) {
	global $cache, $db, $region_sql;

	$key = $cache->key('guildbychars', $count);
	$count = count2sql($count);

	if( ! $cache->get($key) ) {
		$sth = $db->query("
			SELECT `guild`, `realm`, COUNT(*) `count`, COUNT(DISTINCT `character`) `character_count`
			FROM `realid_posts` `t1`
			WHERE `guild` != '' AND `realm` != '' AND $region_sql
			GROUP BY `guild`, `realm`, `region`
			ORDER BY COUNT(DISTINCT `character`) DESC, COUNT(*) DESC $count
		", PDO::FETCH_OBJ);
		$cache->add($key, $sth->fetchAll());
	}
	return $cache->get($key);
}

function posts_by_hour() {
	global $cache, $db, $region_sql;

	$key = $cache->key( 'postsbyhour' );
	$cache->delete($key);

	if( ! $cache->get($key) ) {
		$sth = $db->query("
			SELECT UNIX_TIMESTAMP(CONCAT(DATE(post_date_gmt), ' ', HOUR(post_date_gmt), ':00:00')) `hour`, COUNT(*) `count`
			FROM `realid_posts`
			WHERE post_date_gmt IS NOT NULL AND $region_sql
			GROUP BY DATE(post_date_gmt), HOUR(post_date_gmt)
		", PDO::FETCH_OBJ);

		$posts = $sth->fetchAll();

		$posts = array_map( function($a) { return array( (int)$a->hour*1000, (int)$a->count ); }, $posts );

		$all_series = array();
		$current_series = array();
		$previous_block = null;

		foreach($posts as $block) {
			$this_block = $block[0];

			if( $previous_block == null || $this_block - $previous_block == 3600*1000 ) {
				// normal.
				$current_series[] = $block;
			} else {
				$all_series[] = $current_series;
				$current_series = array();
			}

			$previous_block = $this_block;
		}

		// make sure we get any leftovers
		if( count($current_series) ) {
			$all_series[] = $current_series;
		}

		$cache->set( $key, $all_series, 60 );
	}

	return $cache->get($key);
}
