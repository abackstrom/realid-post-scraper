<?php require_once 'init.php'; ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Real ID Thread Statistics</title>
	<link rel="stylesheet" type="text/css" href="realid.css">
</head>
<body>

<h2>Character Post Counts</h2>
<table border="1" cellpadding="2" cellspacing="2">
	<thead>
		<tr>
			<th>Character</th>
			<th>Realm</th>
			<th>Posts</th>
		</tr>
	</thead>
	<tbody>
		<?php $posters = all_posters_by_count(); ?>
		<?php foreach($posters as $poster): ?>
			<tr>
				<td><?php echo $poster->character; ?></td>
				<td><?php echo $poster->realm; ?></td>
				<td><?php echo $poster->count; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="flot/jquery.flot.js"></script>
<script type="text/javascript" src="realid.js"></script>
<!--[if IE]><script language="javascript" type="text/javascript" src="flot/excanvas.min.js"></script><![endif]-->
</body>
</html>
