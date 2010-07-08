$(function(){
	if( mpot[0] == "generating" ) {
		$('#mean-posts').replaceWith('<p style="color:red;">Graph is being regenerated, please reload in a few moments.</p>');
	} else {
		$.plot( $('#mean-posts'), mpot );
	}
});
