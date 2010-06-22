/*
 * This is the Furnace ajax framework. Include this 
 * file in any pages that make use of the framework.
 * 
 * You can include this file from any controller like so:
 * 
 * $this->extensionAddJavascript('org.frameworkers','Ajax','fu_ajax.js');
 */

function ajax_updateObject(obj,att,val,fn) {
	
	params = {'_obj': obj, '_att': att, '_val': val };
	$.post('/_ajax/update/object/',
			params,
			function(data,textState) {
				if (data.status) {
					if (fn) fn(data);
				} else {
					alert(data);
					alert(textState);
					alert('Update failed unexpectedly');
				}
			},'json');
}

function fu_handleAjaxResult(target,data) {
	if(data.status == 'success') {
		target.text(data.payload.newValue);
		$icon = $('<img class="inline-icon" src="/assets/themes/' + fu_current_theme + '/images/inline-success.png"/>')
			.delay(400).fadeOut('slow');
		target.parent().append($icon);
	} else {
		$icon = $('<img class="inline-icon" src="/assets/themes/' + fu_current_theme + '/images/inline-error.png"/>')
			.delay(400).fadeOut('slow');
		target.parent().append($icon);
		target.addClass('error').text(data.message);
	}
}

/**
 * Support Masked EIP Input Items
 * Depends on jEditable http://www.appelsiini.net/projects/jeditable
 * Depends on Masked Input: http://digitalbush.com/projects/masked-input-plugin
 */
$.editable.addInputType('masked', {
	element : function(settings, original) {
		/* Create an input. Mask it using masked input plugin. Settings */
		/* for mask can be passed with Jeditable settings hash. */
		var input = $('<input />').mask(settings.mask);
		$(this).append(input);
		return(input);
	}
}); 

$(document).ready(function() {
	
	//
	// Editable Text Boxes, Text Areas, and Drop Down Lists
	// Depends on jEditable http://www.appelsiini.net/projects/jeditable
	//
	$('.editable').each(function(index) {
		
		//Determine the type
		var eip_type = 'text';
		if ($(this).hasClass('area'))   { eip_type = 'textarea'; }
		if ($(this).hasClass('select')) { eip_type = 'select';   }
		
		// Connect editable elements to the Furnace Ajax EIP framework
		$(this).editable(($(this).attr('action') == '') ? '/_ajax/update/object/' : $(this).attr('action'), {
			type       : eip_type,
			onblur     : 'ignore',
			width      : 'auto',
			height     : 'none',
			name       : '_val',
			submitdata : { _obj : $(this).attr('context'), _att : $(this).attr('_attr')},
			data       : (('[]' == $(this).attr('data')) ? false : $(this).attr('data')),
			submit     : 'OK',
			cancel     : 'Cancel',
			indicator  : '<img src="/assets/themes/' + fu_current_theme + '/images/inline-indicator.gif"/>',
			tooltip    : 'Click to edit...',
			callback   : (($(this).attr('callback') == '')
				? function(value,settings) {
					data = jQuery.parseJSON(value);
					fu_handleAjaxResult($(this),data);
				  }
				: eval($(this).attr('callback')))
		});	
		
		// Highlight editable elements on mouse-over
		$(this).parent()
			.mouseenter(function() { $(this).addClass("highlighted"); } )
			.mouseleave(function() { $(this).removeClass("highlighted"); } );
	});
	
	//
	// Editable Text Boxes representing Dates
	// Depends on jEditable http://www.appelsiini.net/projects/jeditable
	// Depends on Masked Input: http://digitalbush.com/projects/masked-input-plugin
	//
	$('.editable-dateonly').each(function(index) {
		$(this).editable(($(this).attr('action') == '') ? '/_ajax/update/object/' : $(this).attr('action'), {
			type       : 'masked',
			mask       : '9999-99-99',
			onblur     : 'ignore',
			name       : '_val',
			submitdata : { _obj : $(this).attr('context'), _att : $(this).attr('_attr')},
			submit     : 'OK',
			cancel     : 'Cancel',
			indicator  : '<img src="/assets/themes/' + fu_current_theme + '/images/inline-indicator.gif"/>',
			tooltip    : 'Click to edit...',
			callback   : (($(this).attr('callback') == '')
				? function(value,settings) {
					data = jQuery.parseJSON(value);
					fu_handleAjaxResult($(this),data);
				  }
				: eval($(this).attr('callback')))
		});
		
		// Highlight editable elements on mouse-over
		$(this).parent()
			.mouseenter(function() { $(this).addClass("highlighted"); } )
			.mouseleave(function() { $(this).removeClass("highlighted"); } );
		
	});
});