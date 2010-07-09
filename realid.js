$(function(){
	if( mpot[0] == "generating" ) {
		$('#mean-posts').replaceWith('<p style="color:red;">Graph is being regenerated, please reload in a few moments.</p>');
	} else {
		$.plot( $('#mean-posts'), mpot );
	}

	if( npnc[0][0] == "generating" ) {
		$('#npnc-posts').replaceWith('<p style="color:red;">Graph is being regenerated, please reload in a few moments.</p>');
	} else {
		$.plot(
			$('#npnc-posts'),
			[
				{ label: 'posts', data: npnc[0] },
				{ label: 'characters', data: npnc[1] }
			],
			{
				yaxis: { min: 0, max: highest_post_number },
				legend: { position: 'nw' }
			}
		);
	}
});
