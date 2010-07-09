<?php

require_once 'init.php';

if( $_SERVER['REMOTE_ADDR'] != SCRAPER_BOSS ) {
	die('Sorry, you are not allowed to start the scraper.');
}

date_default_timezone_set('UTC');

$sth = $db->query( "SELECT MAX(`page`)+1 `max_page` FROM `realid_posts` WHERE $region_sql" );
$page_no = array_pop($sth->fetch());
if( $page_no == 0 ) {
	$page_no = 1;
}

global $max_page;

$result = scrape_page( $page_no );
$wait_multiplier = $result ? 1 : 10;

function scrape_random_page() {
	global $db, $region_sql;
	$sth = $db->query("SELECT `page` FROM realid_posts WHERE $region_sql ORDER BY rescanned ASC, page DESC LIMIT 1", PDO::FETCH_OBJ);
	$row = $sth->fetch();
	$page = $row->page;

	return scrape_page( $page );
}

function scrape_page( $page_no ) {
	global $base, $db, $sth, $max_page, $region;

	$url = sprintf($base, $page_no);

	$ch = curl_init( $url );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$page = curl_exec( $ch );

	$doc = phpQuery::newDocumentHTML($page);
	phpQuery::selectDocument($doc);

	get_max_page();

	printf( "Fetching page %d of %d... ", $page_no, $max_page );
	flush();

	if( $page_no == $max_page ) {
		echo "not yet full, will rescan old page.<br>";
		scrape_random_page();
		return false;
	}

	echo '<br>';

	$sth = $db->prepare("
		INSERT INTO `realid_posts`
			(`id`, `page`, `character`, `realm`, `scanned`, `rescanned`, `region`, `level`, `post_date_gmt`) VALUES
			(:post_id, :page_no, :character, :realm, NOW(), NOW(), :region, :level, :post_date)
		ON DUPLICATE KEY UPDATE `page` = :page_no, `character` = :character, `realm` = :realm, `deleted` = 0, `rescanned` = NOW(), `level` = :level, `post_date_gmt` = :post_date
	");

	$expected_post_no = ($page_no - 1) * 20;
	$expected_post_range = range($expected_post_no, $expected_post_no + 19);

	$found_posts = array();

	echo '<ol>';
	foreach( pq('.postdisplay') as $post ) {
		$character = pq($post)->find('.chardata a')->text();
		
		if( ! $character ) {
			continue;
		}

		$guild_url = pq($post)->find('.icon-guild a')->attr('href');
		$realm = pq($post)->find('.icon-realm b')->text();
		$post_id = (int)pq($post)->find('#postid11 b, #postid21 b')->text();
		$level = (int)pq($post)->find('.iconPosition')->text();
		$post_date = pq($post)->find('#postid11 small, #postid21 small')->text();

		$post_date = strtotime($post_date);
		$post_date = date('Y-m-d H:i:s', $post_date);

		$found_posts[] = $post_id;

		$args = compact('character', 'post_id', 'realm', 'page_no', 'region', 'level', 'post_date');

		printf('<li>#%d &mdash; %s of %s (', $post_id, $character, $realm);

		echo $sth->execute($args) ? 'success' : 'failure', ')</li>';
		flush();
	}
	echo '</ol>';
	flush();

	$missing = array_diff( $expected_post_range, $found_posts );
	backfill_deleted_posts( $page_no, $missing );

	return true;
}

function backfill_deleted_posts( $page, $missing ) {
	global $db, $region;

	$sth = $db->prepare("
		INSERT INTO realid_posts (id, deleted, page, scanned, rescanned, region) VALUES (:post_id, 1, :page, NOW(), NOW(), :region)
		ON DUPLICATE KEY UPDATE deleted = 1, rescanned = NOW()
	");

	foreach( $missing as $post_id ) {
		if( $post_id == 0 ) {
			continue;
		}

		$args = compact('post_id', 'page', 'region');
		$sth->execute($args);
	}
}

function get_max_page() {
	global $max_page;
	$max_page = pq('.rpage-thread table td:eq(1) a:last')->text();
}

?>
<script>
setTimeout(do_reload, <?php echo BETWEEN_PAGE_TIMEOUT * 1000 * $wait_multiplier; ?>);
function do_reload() {
	history.go(0);
}
</script>
