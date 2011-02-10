$(document).ready(function() {
	
	// Enable the 'regenerate class files' button
	$('#btnRegenerateClassFiles').click(function() {
		$('#btnRegenerateClassFiles')
			.attr('disabled','disabled')
			.val('working...');
		$('#imgRegenerateWorking').show();
		$.get('/furnace-www/foundry/models/orm/generateClassFiles',
			function(resp) {
				$('#imgRegenerateWorking').hide();
				$('#btnRegenerateClassFiles')
					.removeAttr('disabled')
					.val('(Re)generate Class Files');
				$('#spnRegenerateClassFilesResult').html(resp);
			}
		);
	});
	
	// Enable the 'compare model to database' button
	$('#btnDbCompare').click(function() {
		$('#btnDbCompare')
			.attr('disabled','disabled')
			.val('working...');
		$('#imgDbCompareWorking').show();
		$.get('/furnace-www/foundry/models/orm/compareModelToDatabase',
				function(resp) {
					$('#imgDbCompareWorking').hide();
					$('#btnDbCompare')
						.removeAttr('disabled')
						.val('Compare Model To Database');
					$('#divDbCompareResult').html(resp);
				}
			);
	});
	
	
});
