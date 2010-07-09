<?php require_once 'init.php'; ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Real ID Thread Statistics (<?php echo $region; ?>)</title>
	<link rel="stylesheet" type="text/css" href="realid.css">
</head>
<body>

<h1>Real ID Thread Statistics (<?php echo $region; ?>)</h1>

<?php $the_regions = array_map( function($s) { return sprintf('<a href="?region=%s">%s</a>', $s, $s); }, $valid_regions ); ?>

<p>Data relating to <a href="<?php echo htmlentities(sprintf($base, 1)); ?>">the Real ID thread</a> on the World
of Warcraft community forums. (See also: <?php echo implode(", ", $the_regions); ?>)

<p><strong style="color:red">UPDATE:</strong> The original post on the North American forums is now locked, as Blizzard <a href="http://forums.worldofwarcraft.com/thread.html?topicId=25968987278&sid=1">will no longer require</a> posting under your real name.</p>

<h2>Pages in Thread</h2>
<ul>
	<li>Pages with the least posts:
		<?php $least = pages_by_count(5); ?>
		<ol>
			<?php foreach($least as $page): ?>
				<li><a href="<?php echo htmlentities(sprintf($base, $page->page)); ?>"><?php echo $page->page; ?></a> (<?php echo $page->count; ?> posts)</li>
			<?php endforeach; ?>
		</ol>
	</li>
</ul>

<h2>Posts in Thread</h2>
<ul>
	<li>Highest post number scanned: <?php echo number_format(highest_post_number()); ?></li>
	<li>Total posts: <?php echo number_format(post_count()); ?></li>
	<li>Deleted posts: <?php echo number_format(deleted_posts()); ?> (<?php echo deleted_posts_percent(); ?>%)<span class="footnotes">*&dagger;</span></li>
</ul>

<script type="text/javascript">
var highest_post_number = <?php echo highest_post_number(); ?>;
</script>

<p class="footnotes">* Value updated continuously as pages are rescanned for deleted posts. May not be 100% accurate.<br>
&dagger; Includes moderated posts (trolling, CAPSLOCK, spam, etc.) and self-deleted posts.</p>

<h2>Characters in Thread</h2>
<ul>
	<li>Unique characters: <?php echo number_format(unique_posters()); ?></li>
	<li>Repeat posters: <?php echo number_format(repeat_posters()); ?> (<?php echo round(repeat_posters() / unique_posters() * 100, 2); ?>%)</li>
	<li>Mean posts/character: <?php echo round(posts_per_user(), 2); ?></li>
	<li>Mean posts/character (repeat posters): <?php echo round(repeat_posts_per_user(), 2); ?></li>
	<li>Median posts/character: <?php echo round(median_posts(), 2); ?></li>
	<li>Top 5 characters:
		<?php $top5 = posters_by_post_count(5); ?>
		<ol>
			<?php foreach($top5 as $poster): ?>
			<li><?php echo $poster->character; ?> of <?php echo $poster->realm; ?> (<?php echo $poster->count; ?> posts)</li>
			<?php endforeach; ?>
		</ol>
	</li>
	<li><a href="report-userposts.php?region=<?php echo $region; ?>">All characters, with post count</a></li>
	<li>Top 5 Guilds by Posts:
		<?php $guilds = best_represented_guilds_by_posts(5); ?>
		<ol>
			<?php foreach($guilds as $guild): ?>
			<li>&lt;<?php echo $guild->guild; ?>&gt; of <?php echo $guild->realm; ?> (<?php echo $guild->count; ?> posts, <?php echo $guild->character_count; ?> character<?php echo $guild->character_count == 1 ? '' : 's'; ?>)</li>
			<?php endforeach; ?>
		</ol>
	</li>
	<li>Top 5 Guilds by Characters:
		<?php $guilds = best_represented_guilds_by_characters(5); ?>
		<ol>
			<?php foreach($guilds as $guild): ?>
			<li>&lt;<?php echo $guild->guild; ?>&gt; of <?php echo $guild->realm; ?> (<?php echo $guild->character_count; ?> character<?php echo $guild->character_count == 1 ? '' : 's'; ?>, <?php echo $guild->count; ?> posts)</li>
			<?php endforeach; ?>
		</ol>
	</li>
</ul>

<p>The following graphs are generated every five minutes with a datapoint every <?php echo floor(highest_post_number() / 100); ?> posts (highest post / 100).</p>

<h3>Mean Posts/Character over Time</h3>
<div id="mean-posts" class="graph"></div>
<?php $mpot = mean_posts_over_time( highest_post_number() / 100 ); ?>
<script type="text/javascript">
var mpot = [<?php echo json_encode($mpot); ?>];
</script>

<h3>New Posts vs. New Characters</h3>
<p>Showing post count increase compared to total number of unique characters in the thread.</p>
<div id="npnc-posts" class="graph"></div>
<?php list($np, $nc) = new_posts_vs_new_characters( highest_post_number() / 100 ); ?>
<script type="text/javascript">
var npnc = [<?php echo json_encode($np); ?>, <?php echo json_encode($nc); ?>];
</script>

<h3>New Posts by Hour</h3>
<div id="pbh-posts" class="graph"></div>
<script type="text/javascript">
var pbh = <?php echo json_encode(posts_by_hour()); ?>;
</script>

<h2>Source Code</h2>

<p>Now on github: <a href="http://github.com/abackstrom/realid-post-scraper">http://github.com/abackstrom/realid-post-scraper</a></p>

<h2>Feedback</h2>

<p>Reach me via email at <a href="mailto:adam@sixohthree.com">adam@sixohthree.com</a>.</p>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.js"></script>
<script type="text/javascript" src="realid.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
</body>
</html>
