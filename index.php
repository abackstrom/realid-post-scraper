<?php require_once 'init.php'; ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Real ID Thread Statistics</title>
	<link rel="stylesheet" type="text/css" href="realid.css">
</head>
<body>

<h1>Real ID Thread Statistics</h1>

<p>Data relating to <a href="http://forums.worldofwarcraft.com/thread.html?topicId=25712374700">the Real ID thread</a> on the World of Warcraft community forums.</p>

<h2>Pages in Thread</h2>
<ul>
	<li>Pages with the least posts:
		<?php $least = pages_by_count(5); ?>
		<ol>
			<?php foreach($least as $page): ?>
				<li><a href="<?php printf($base, $page->page); ?>"><?php echo $page->page; ?></a> (<?php echo $page->count; ?> posts)</li>
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

<p class="footnotes">* The script is currently rescanning old pages for newly-deleted posts: <?php echo rescan_progress(); ?> pages remaining. True value is &gt;10%.<br>
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
	<li><a href="report-userposts.php">All characters, with post count</a></li>
</ul>

<h3>Mean Posts/Character over Time</h3>
<p>This graph is generated every five minutes with a datapoint every 500 posts.</p>
<div id="mean-posts" class="graph"></div>
<?php $mpot = mean_posts_over_time( 500 ); ?>
<script type="text/javascript">
var mpot = [<?php echo json_encode($mpot); ?>];
</script>

<h2>Source Code</h2>

<ul>
	<li><a href="index.php?src">index.php</a> &mdash; this page</li>
	<li><a href="report-userposts.php?src">report-userposts.php</a> &mdash; characters and their post count</li>
	<li><a href="init.php?src">init.php</a> &mdash; initialization script</li>
	<li><a href="functions.php?src">functions.php</a> &mdash; database queries and stats functions</li>
	<li><a href="realid.php?src">realid.php</a> &mdash; the thread scraper</li>
</ul>

<h2>Feedback</h2>

<p>Reach me via email at <a href="mailto:adam@sixohthree.com">adam@sixohthree.com</a>.</p>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.js"></script>
<script type="text/javascript" src="realid.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
</body>
</html>