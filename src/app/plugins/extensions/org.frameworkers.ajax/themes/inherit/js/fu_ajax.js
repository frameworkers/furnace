/*
 * This is the Furnace ajax framework. Include this 
 * file in any pages that make use of the framework.
 * 
 * You can include this file from any controller like so:
 * 
 * $this->extensionAddJavascript('org.frameworkers.ajax','fu_ajax.js');
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