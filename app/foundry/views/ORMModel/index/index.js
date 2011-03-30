$(document).ready(function() {
	
	// Enable the 'regenerate class files' button
	$('#btnRegenerateClassFiles').click(function() {
		$('#btnRegenerateClassFiles')
			.attr('disabled','disabled')
			.val('working...');
		$('#imgRegenerateWorking').show();
		$.get(_context.urls.url_base + '/models/orm/generateClassFiles',
			function(resp) {
				$('#imgRegenerateWorking').hide();
				$('#btnRegenerateClassFiles')
					.removeAttr('disabled')
					.val('(Re)generate Class Files');
				$('#spnRegenerateClassFilesResult').html(resp);
			}
		);
	});
	
	// Enable the 'create table' links
	$('.createTable').click(function(e) {
		e.preventDefault();
		$td = $(this).parent();
		$td.children('img.working').show();
		$.get(_context.urls.url_base + '/models/orm/createTable/'
				+ $(this).attr('title') + '.json',{},function(data) {
			if (data) {
				$td.html(data.message);
			}
		});
	});
});
